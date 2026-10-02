<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates or updates an employee from validated Add / Edit employee form
 * data, including addresses, qualifications, experience, family, salary
 * elements and uploaded files, in one transaction.
 */
class SaveEmployee
{
    /** Form file fields => employee column holding the stored path. */
    public const FILES = [
        'photo' => 'photo_path',
        'aadhar_attachment' => 'aadhar_attachment_path',
        'pan_attachment' => 'pan_attachment_path',
        'bank_passbook' => 'bank_passbook_path',
    ];

    private const ADDRESS_FIELDS = ['address_line_1', 'address_line_2', 'village', 'district', 'taluka', 'state'];

    /**
     * @param  array<string, mixed>  $data  validated form data
     * @param  array<string, UploadedFile|string>  $files  form field => upload, or a data: URL for the photo
     */
    public function __invoke(Employee $employee, array $data, array $files = []): Employee
    {
        $replaced = [];

        DB::transaction(function () use ($employee, $data, $files, &$replaced) {
            $companyId = (int) $data['company_id'];

            // Lock the company row so two employees added at once can't get the same id.
            Company::whereKey($companyId)->lockForUpdate()->first();

            $employee->fill(Arr::except($data, [
                'shift', 'addresses', 'qualifications', 'experiences', 'family', 'salary', 'photo_data', ...array_keys(self::FILES),
            ]));
            $employee->shift_id = $this->shift($companyId, $data['shift'])->id;

            if (! $employee->exists || $employee->isDirty('company_id')) {
                $employee->employee_code = Employee::nextCodeFor($companyId);
            }

            $employee->save();

            foreach ($files as $field => $file) {
                $column = self::FILES[$field];
                $old = $employee->{$column};
                $employee->{$column} = $this->store($employee, $field, $file);

                if ($old) {
                    $replaced[] = $old;
                }
            }
            $employee->save();

            $this->syncAddresses($employee, $data);
            $this->syncKeyed($employee->qualifications(), 'level', $data['qualifications'] ?? []);
            $this->syncKeyed($employee->familyMembers(), 'relation', $data['family'] ?? [], ['is_nominee']);
            $this->syncExperiences($employee, $data['experiences'] ?? []);
            $this->syncSalary($employee, $data['salary'] ?? []);
        });

        // Old files are only removed once the new record is safely saved.
        foreach ($replaced as $path) {
            Storage::disk(config('filesystems.employee_files'))->delete($path);
        }

        return $employee->refresh();
    }

    /**
     * The company's shift with this name, created if it doesn't exist yet.
     */
    private function shift(int $companyId, string $name): Shift
    {
        $name = trim($name);

        return Shift::where('company_id', $companyId)->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first()
            ?? Shift::create(['company_id' => $companyId, 'name' => $name]);
    }

    private function store(Employee $employee, string $field, UploadedFile|string $file): string
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        $directory = "employees/{$employee->uuid}";

        if (is_string($file)) {
            // A photo captured or resized in the browser, sent as a data: URL.
            [$meta, $payload] = explode(',', $file, 2);
            $extension = Str::between($meta, 'image/', ';') === 'png' ? 'png' : 'jpg';
            $path = "{$directory}/{$field}-".Str::random(12).".{$extension}";
            $disk->put($path, base64_decode($payload));

            return $path;
        }

        return $disk->putFileAs($directory, $file, "{$field}-".Str::random(12).'.'.$file->guessExtension());
    }

    private function syncAddresses(Employee $employee, array $data): void
    {
        $addresses = $data['addresses'] ?? [];

        if ($data['permanent_address_is_present'] ?? false) {
            $addresses['permanent'] = $addresses['present'] ?? [];
        }

        $this->syncKeyed($employee->addresses(), 'type', $addresses);
    }

    /**
     * Upsert one row per key (e.g. per address type) and drop rows whose
     * fields were all left empty.
     *
     * @param  list<string>  $flags  boolean fields that don't count as "filled" on their own
     */
    private function syncKeyed($relation, string $keyColumn, array $rows, array $flags = []): void
    {
        foreach ($rows as $key => $fields) {
            $fields = array_map(fn ($value) => $value === '' ? null : $value, (array) $fields);
            $filled = collect(Arr::except($fields, $flags))->filter(fn ($value) => filled($value))->isNotEmpty();

            foreach ($flags as $flag) {
                $fields[$flag] = (bool) ($fields[$flag] ?? false);
            }

            if ($filled) {
                $relation->updateOrCreate([$keyColumn => $key], $fields);
            } else {
                (clone $relation)->where($keyColumn, $key)->delete();
            }
        }
    }

    private function syncExperiences(Employee $employee, array $rows): void
    {
        $employee->experiences()->delete();

        foreach ($rows as $row) {
            if (collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                $employee->experiences()->create($row);
            }
        }
    }

    private function syncSalary(Employee $employee, array $elements): void
    {
        $employee->salaryElements()->delete();

        foreach ($elements as $element => $amounts) {
            $monthly = (float) ($amounts['monthly'] ?? 0);
            // Bonus is a percentage of basic, so it has no yearly amount.
            $yearly = match (true) {
                $element === 'bonus' => 0,
                filled($amounts['yearly'] ?? null) => (float) $amounts['yearly'],
                default => round($monthly * 12, 2),
            };

            if ($monthly > 0 || $yearly > 0) {
                $employee->salaryElements()->create(['element' => $element, 'monthly' => $monthly, 'yearly' => $yearly]);
            }
        }
    }
}
