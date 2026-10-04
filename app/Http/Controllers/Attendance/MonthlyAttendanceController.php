<?php

namespace App\Http\Controllers\Attendance;

use App\Rules\AccessibleEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompOffUse;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\SubDepartment;
use App\Services\Attendance\DayPunches;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Attendance › Monthly attendance: a company's employees by day for a month,
 * where each day can be set present (with in/out times), on leave, on
 * compensatory off, or reset to absent / week-off / holiday.
 */
class MonthlyAttendanceController extends Controller
{
    public function __construct(private MonthlyAttendance $attendance, private DayPunches $punches) {}

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $month = $this->month($request);
        $subDepartments = $company ? SubDepartment::where('company_id', $company->id)->orderBy('name')->get() : collect();
        $allEmployees = $company ? $this->employedIn($company, $month)->get() : collect();

        $employees = $allEmployees
            ->when($request->filled('sub_department'), fn ($c) => $c->where('sub_department_id', (int) $request->query('sub_department')))
            ->when($request->filled('employee'), fn ($c) => $c->where('uuid', $request->query('employee')))
            ->values();

        $grid = $this->attendance->forMonth($employees, $month);
        $compOff = $employees->mapWithKeys(fn (Employee $e) => [$e->id => in_array('A', array_column($grid[$e->id]['days'], 'status'), true)
            ? $this->attendance->compOffBalance($e, $month->year) : 0]);

        return view('attendance.monthly', [
            'companies' => $companies,
            'company' => $company,
            'month' => $month,
            'subDepartments' => $subDepartments,
            'allEmployees' => $allEmployees,
            'employees' => $employees,
            'grid' => $grid,
            'compOff' => $compOff,
            'leaveTypes' => $company ? $company->leaveTypes()->orderBy('name')->get() : collect(),
        ]);
    }

    /**
     * "Enter In-Time & Out-Time": replace the day's punches with these times.
     */
    public function setTime(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'in_time' => ['required', 'date', 'before_or_equal:now'],
            'out_time' => ['required', 'date', 'after:in_time', 'before_or_equal:now'],
        ], [], ['in_time' => 'in-time', 'out_time' => 'out-time']);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $in = CarbonImmutable::parse($data['in_time'])->startOfMinute();
        $out = CarbonImmutable::parse($data['out_time'])->startOfMinute();

        $this->punches->replace($employee, $data['date'], $in, $out);

        return back()->with('status', __(':name marked present on :date.', ['name' => $employee->full_name, 'date' => $data['date']]));
    }

    /**
     * "Enter Leave": record an approved leave from the grid.
     */
    public function setLeave(Request $request): RedirectResponse
    {
        $employee = Employee::where('uuid', $request->input('employee'))->firstOrFail();

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('company_id', $employee->company_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'numeric', 'min:0.5', 'max:366'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['leave_type_id' => 'leave type', 'start_date' => 'from date', 'end_date' => 'end date', 'days' => 'total days']);

        if (LeaveApplication::where('employee_id', $employee->id)->approvedBetween($data['start_date'], $data['end_date'])->exists()) {
            throw ValidationException::withMessages(['start_date' => __(':name already has approved leave in these dates.', ['name' => $employee->full_name])]);
        }

        LeaveApplication::create($data + [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'status' => LeaveApplication::APPROVED,
            'approved_start_date' => $data['start_date'],
            'approved_end_date' => $data['end_date'],
            'approved_days' => $data['days'],
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', __('Leave entered for :name.', ['name' => $employee->full_name]));
    }

    public function useCompOff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $employee = Employee::where('uuid', $data['employee'])->with('shift')->firstOrFail();

        if ($this->attendance->compOffBalance($employee, CarbonImmutable::parse($data['date'])->year) < 1) {
            return back()->with('error', __(':name has no compensatory off left.', ['name' => $employee->full_name]));
        }

        DB::transaction(function () use ($employee, $data) {
            $this->punches->clear($employee, $data['date']);
            CompOffUse::firstOrCreate(['employee_id' => $employee->id, 'work_date' => $data['date']]);
        });

        return back()->with('status', __('COFF added for :name on :date.', ['name' => $employee->full_name, 'date' => $data['date']]));
    }

    /**
     * Clear a day's punches, leave and COFF so it falls back to absent,
     * week-off or holiday.
     */
    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')],
            'date' => ['required', 'date'],
        ]);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();

        $leave = LeaveApplication::where('employee_id', $employee->id)->approvedBetween($data['date'], $data['date'])->first();
        if ($leave && ! $leave->approved_start_date->equalTo($leave->approved_end_date)) {
            return back()->with('error', __('This day is part of a :days-day leave. Change it in Attendance › Leave.', ['days' => rtrim(rtrim($leave->approved_days, '0'), '.')]));
        }

        DB::transaction(function () use ($employee, $data, $leave) {
            $this->punches->clear($employee, $data['date']);
            $leave?->delete();
            CompOffUse::where('employee_id', $employee->id)->whereDate('work_date', $data['date'])->delete();
        });

        return back()->with('status', __('Attendance for :name on :date cleared.', ['name' => $employee->full_name, 'date' => $data['date']]));
    }

    /**
     * Employees of the company who were employed at some point in the month.
     */
    private function employedIn(Company $company, CarbonImmutable $month): Builder
    {
        return Employee::where('company_id', $company->id)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $month->endOfMonth()))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>=', $month->startOfMonth()))
            ->with(['shift', 'position', 'subDepartment'])
            ->orderBy('employee_code');
    }

    private function month(Request $request): CarbonImmutable
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        return ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12)
            ? CarbonImmutable::create($year, $month, 1)
            : CarbonImmutable::today()->startOfMonth();
    }
}
