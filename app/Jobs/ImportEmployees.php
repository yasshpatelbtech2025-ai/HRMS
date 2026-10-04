<?php

namespace App\Jobs;

use App\Actions\SaveEmployee;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeImportRun;
use App\Models\User;
use App\Support\Activity;
use App\Support\CompanyAccess;
use App\Support\EmployeeImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Adds every row of an uploaded employee sheet to its company, in the
 * background (a large sheet is many queries per row). Nothing is saved
 * unless every row is valid; the outcome is recorded on the import run.
 */
class ImportEmployees implements ShouldQueue
{
    use Queueable;

    /** A half-imported sheet is never retried blindly. */
    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public EmployeeImportRun $run) {}

    public function handle(SaveEmployee $save): void
    {
        $run = $this->run;
        $run->update(['status' => 'running']);
        $disk = Storage::disk(config('filesystems.employee_files'));
        $local = tempnam(sys_get_temp_dir(), 'import');

        // Act as the person who uploaded it: company access and the activity log apply as usual.
        $previous = Auth::hasUser() ? Auth::user() : null;
        $uploader = $run->user_id ? User::find($run->user_id) : null;
        $uploader ? Auth::setUser($uploader) : null;
        CompanyAccess::forget();

        try {
            file_put_contents($local, $disk->get($run->path));
            $rows = (new EmployeeImport($run->company))->read($local, str_ends_with(strtolower($run->original_name), '.csv') ? 'csv' : 'xlsx');

            if ($rows === []) {
                $run->update(['status' => 'invalid', 'errors' => [__('The file has no employee rows under the headings.')], 'finished_at' => now()]);

                return;
            }

            if ($errors = $this->invalidRows($rows)) {
                $run->update(['status' => 'invalid', 'errors' => $errors, 'finished_at' => now()]);

                return;
            }

            DB::transaction(function () use ($rows, $save) {
                foreach ($rows as $data) {
                    $save(new Employee, $data);
                }
            });
            $run->update(['status' => 'done', 'imported' => count($rows), 'finished_at' => now()]);
            Activity::record('bulk', $run, __('Imported :count employee(s) from :file', ['count' => count($rows), 'file' => $run->original_name]));
        } finally {
            @unlink($local);
            $disk->delete($run->path);
            $previous ? Auth::setUser($previous) : Auth::forgetUser();
            CompanyAccess::forget();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->run->update(['status' => 'failed', 'errors' => [__('The import stopped unexpectedly. Nothing was saved; please upload the file again.')], 'finished_at' => now()]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  by sheet row number
     * @return list<string>
     */
    private function invalidRows(array $rows): array
    {
        $errors = [];
        $emails = [];

        foreach ($rows as $number => $data) {
            $rules = (new EmployeeRequest)->merge($data);
            $validator = Validator::make($data, $rules->rules(), [], $rules->attributes())->after($rules->after());
            $messages = $validator->errors()->all();

            if (filled($data['email']) && in_array(mb_strtolower($data['email']), $emails, true)) {
                $messages[] = __('The email is used on an earlier row of this file.');
            }
            $emails[] = mb_strtolower((string) $data['email']);

            if ($messages) {
                $errors[] = __('Row :row: :errors', ['row' => $number, 'errors' => implode(' ', $messages)]);
            }
        }

        return $errors;
    }
}
