<?php

namespace App\Http\Controllers\Attendance;

use App\Support\Activity;
use App\Rules\AccessibleEmployee;
use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Attendance\PunchProcessor;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance › Attendance raw data: one employee's punches for a month,
 * with adding, editing, deleting, locking and re-processing them.
 */
class RawDataController extends Controller
{
    use ListsRecords;

    public function __construct(private PunchProcessor $processor) {}

    public function index(Request $request): View
    {
        $employees = Employee::current()->with(['company', 'shift'])->orderBy('company_id')->orderBy('employee_code')->get();
        $employee = $employees->firstWhere('uuid', $request->query('employee'));
        $month = $this->month($request);

        return view('attendance.raw-data', [
            'employees' => $employees,
            'employee' => $employee,
            'month' => $month,
            'punches' => $employee
                ? $this->punches($employee, $month)->paginate($perPage = $this->perPage($request, allowAll: true))->withQueryString()
                : null,
            'perPage' => $perPage ?? 10,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'time' => ['required', 'date', 'before_or_equal:now'],
            'type' => ['required', Rule::in([Attendance::IN, Attendance::OUT])],
        ]);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $time = CarbonImmutable::parse($data['time'])->startOfMinute();

        if ($employee->attendances()->where('punched_at', $time)->where('type', $data['type'])->exists()) {
            throw ValidationException::withMessages(['time' => __('This :type punch already exists.', ['type' => $data['type']])]);
        }

        $employee->attendances()->create([
            'company_id' => $employee->company_id, 'punched_at' => $time, 'work_date' => $time->toDateString(),
            'type' => $data['type'], 'source' => 'manual', 'shift_id' => $employee->shift_id,
        ]);
        $this->processor->process($employee, $time, $time);

        return back()->with('status', __('Attendance data added for :name.', ['name' => $employee->full_name]));
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->ensureUnlocked($attendance);

        $data = $request->validate([
            'time' => ['required', 'date', 'before_or_equal:now'],
            'type' => ['required', Rule::in([Attendance::IN, Attendance::OUT])],
        ]);

        $before = $attendance->punched_at;
        $attendance->update(['punched_at' => CarbonImmutable::parse($data['time'])->startOfMinute(), 'type' => $data['type']]);
        $this->processor->process($attendance->employee, min($before, $attendance->punched_at), max($before, $attendance->punched_at));

        return back()->with('status', __('Attendance data updated.'));
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->ensureUnlocked($attendance);

        $attendance->delete();
        $this->processor->process($attendance->employee, $attendance->punched_at, $attendance->punched_at);

        return back()->with('status', __('Attendance data deleted.'));
    }

    /**
     * Lock or unlock a day's punches so corrections and edits leave them alone.
     */
    public function toggleLock(Attendance $attendance): RedirectResponse
    {
        $locked = ! $attendance->is_locked;

        Attendance::where('employee_id', $attendance->employee_id)->whereDate('work_date', $attendance->work_date)->update(['is_locked' => $locked]);
        Activity::record('updated', $attendance, $attendance->auditLabel(), ['old' => ['is_locked' => ! $locked], 'new' => ['is_locked' => $locked, 'work_date' => $attendance->work_date->toDateString()]]);

        return back()->with('status', $locked
            ? __('Attendance for :date locked.', ['date' => $attendance->work_date->format('d M Y')])
            : __('Attendance for :date unlocked.', ['date' => $attendance->work_date->format('d M Y')]));
    }

    /**
     * "Attendance data correction": re-work days, shifts and missing flags
     * for a date range, e.g. after changing the employee's shift.
     */
    public function correct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date', function ($attribute, $value, $fail) use ($request) {
                if (CarbonImmutable::parse($request->input('from_date'))->diffInDays($value) > 92) {
                    $fail(__('Correct at most three months at a time.'));
                }
            }],
        ], [], ['from_date' => 'from date', 'to_date' => 'to date']);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $this->processor->process($employee, CarbonImmutable::parse($data['from_date']), CarbonImmutable::parse($data['to_date']));

        return back()->with('status', __('Attendance data corrected for :name from :from to :to.', [
            'name' => $employee->full_name, 'from' => $data['from_date'], 'to' => $data['to_date'],
        ]));
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        $employee = Employee::where('uuid', $request->query('employee'))->firstOrFail();

        return TableExport::download($format, 'attendance-raw-data-'.$employee->employee_code,
            ['Sl', 'Device', 'Time', 'Action', 'Type', 'Is night shift', 'Is missing', 'Original shift', 'Actual shift', 'Locked'],
            $this->punches($employee, $this->month($request))->get()->map(fn (Attendance $p, int $i) => [
                $i + 1, $p->device ?? '--', $p->punched_at->format('Y-m-d H:i'), strtoupper($p->source), $p->isIn() ? 'In Time' : 'Out Time',
                (int) $p->is_night_shift, (int) $p->is_missing, $p->shift?->name, $p->shift_code, $p->is_locked ? 'Yes' : 'No',
            ]),
        );
    }

    private function punches(Employee $employee, CarbonImmutable $month): Builder
    {
        return $employee->attendances()->getQuery()->with('shift')
            ->whereDate('work_date', '>=', $month)->whereDate('work_date', '<=', $month->endOfMonth())
            ->orderByDesc('punched_at')->orderByDesc('id');
    }

    private function month(Request $request): CarbonImmutable
    {
        $month = $request->query('month');

        return is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month)
            : CarbonImmutable::today()->startOfMonth();
    }

    private function ensureUnlocked(Attendance $attendance): void
    {
        if ($attendance->is_locked) {
            throw ValidationException::withMessages(['time' => __('This attendance is locked. Unlock it first.')]);
        }
    }
}
