<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Rules\AccessibleCompany;
use App\Services\Attendance\PunchProcessor;
use App\Support\Activity;
use App\Support\SheetImport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * "Import attendance": adds every punch of a filled-in sheet to the
 * employees of one company, then re-works the affected days. Nothing is
 * saved unless every row is valid.
 */
class AttendanceImportController extends Controller
{
    private const HEADINGS = ['Employee Code *', 'Date and Time * (YYYY-MM-DD HH:MM)', 'Type * (IN/OUT)'];

    public function __construct(private PunchProcessor $processor) {}

    public function template(): StreamedResponse
    {
        return SheetImport::template('attendance-template.xlsx', self::HEADINGS, [['EMP0001', now()->subDay()->format('Y-m-d').' 09:00', 'IN'], ['EMP0001', now()->subDay()->format('Y-m-d').' 18:00', 'OUT']]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], ['file.mimes' => __('The file must be an .xlsx or .csv file. Save .xls files as .xlsx first.')]);

        $company = Company::findOrFail($request->integer('company_id'));
        $file = $request->file('file');
        $sheet = SheetImport::read($file->getRealPath(), strtolower($file->getClientOriginalExtension()) === 'csv' ? 'csv' : 'xlsx', self::HEADINGS);

        if ($sheet === []) {
            return back()->withErrors(['file' => __('The file has no attendance rows under the headings.')]);
        }

        $employees = Employee::current()->where('company_id', $company->id)->get()->keyBy(fn ($e) => mb_strtolower($e->employee_code));
        $errors = [];
        $punches = [];

        foreach ($sheet as $number => $row) {
            [$code, $when, $type] = array_values($row);
            $employee = $employees->get(mb_strtolower($code));
            $type = mb_strtolower($type);

            try {
                $time = CarbonImmutable::parse($when)->startOfMinute();
            } catch (Throwable) {
                $time = null;
            }

            if (! $employee) {
                $errors[] = __('Row :row: no employee with code ":code" in :company.', ['row' => $number, 'code' => $code, 'company' => $company->establishment_name]);
            } elseif (! $time || $when === '') {
                $errors[] = __('Row :row: the date and time is not valid. Use YYYY-MM-DD HH:MM.', ['row' => $number]);
            } elseif ($time->isFuture()) {
                $errors[] = __('Row :row: the date and time is in the future.', ['row' => $number]);
            } elseif (! in_array($type, [Attendance::IN, Attendance::OUT], true)) {
                $errors[] = __('Row :row: the type must be IN or OUT.', ['row' => $number]);
            } else {
                $punches[] = compact('employee', 'time', 'type');
            }
        }

        if ($errors) {
            return back()->with('import_errors', $errors);
        }

        $added = 0;

        DB::transaction(function () use ($punches, &$added) {
            $ranges = [];

            foreach ($punches as ['employee' => $employee, 'time' => $time, 'type' => $type]) {
                if ($employee->attendances()->where('punched_at', $time)->where('type', $type)->exists()) {
                    continue;
                }

                $employee->attendances()->create([
                    'company_id' => $employee->company_id, 'punched_at' => $time, 'work_date' => $time->toDateString(),
                    'type' => $type, 'source' => 'import', 'shift_id' => $employee->shift_id,
                ]);
                $added++;
                $ranges[$employee->id] = [$employee, min($ranges[$employee->id][1] ?? $time, $time), max($ranges[$employee->id][2] ?? $time, $time)];
            }

            foreach ($ranges as [$employee, $from, $to]) {
                $this->processor->process($employee, $from, $to);
            }
        });
        Activity::record('bulk', description: __('Imported :count attendance punch(es) from :file', ['count' => $added, 'file' => $file->getClientOriginalName()]));

        return back()->with('status', __(':added punches imported (:skipped already existed and were skipped).', ['added' => $added, 'skipped' => count($punches) - $added]));
    }
}
