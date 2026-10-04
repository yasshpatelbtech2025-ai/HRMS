<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\DayPunches;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance › Attendance requests: corrections to a day's in/out times,
 * recorded by staff for now (employees will raise them themselves once they
 * have logins) and approved or rejected. Approving one sets the day's punches.
 */
class AttendanceRequestController extends Controller
{
    use ListsRecords;

    public function __construct(private DayPunches $punches) {}

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : null;

        $requests = $company
            ? AttendanceRequest::where('company_id', $company->id)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->with(['employee.position', 'employee.shift'])
                ->tap(fn ($q) => $this->sort($q, $request, ['name' => $this->byEmployeeName('attendance_requests'), 'date' => 'work_date', 'status' => 'status'],
                    fn ($q) => $q->latest('work_date')->latest('id')))
                ->paginate($perPage = $this->perPage($request))->withQueryString()
            : null;

        // The punches currently on each requested day, to show beside the request.
        $current = $requests ? Attendance::whereIn('employee_id', $requests->pluck('employee_id'))
            ->whereDate('work_date', '>=', $requests->min('work_date') ?? today())
            ->whereDate('work_date', '<=', $requests->max('work_date') ?? today())
            ->orderBy('punched_at')->get()
            ->groupBy(fn ($p) => $p->employee_id.'|'.$p->work_date->toDateString()) : collect();

        return view('attendance.requests', [
            'companies' => $companies,
            'company' => $company,
            'status' => $status,
            'requests' => $requests,
            'current' => $current,
            'perPage' => $perPage ?? 10,
            'employees' => $company ? Employee::current()->where('company_id', $company->id)->orderBy('employee_code')->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'requested_in' => ['required', 'date'],
            'requested_out' => ['required', 'date', 'after:requested_in'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['requested_in' => 'requested in time', 'requested_out' => 'requested out time']);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();

        AttendanceRequest::create([
            'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'work_date' => $data['date'],
            'requested_in' => $data['requested_in'], 'requested_out' => $data['requested_out'], 'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', __('Attendance request added for :name.', ['name' => $employee->full_name]));
    }

    public function approve(Request $request, AttendanceRequest $attendanceRequest): RedirectResponse
    {
        $this->punches->replace(
            $attendanceRequest->employee,
            $attendanceRequest->work_date->toDateString(),
            CarbonImmutable::parse($attendanceRequest->requested_in),
            CarbonImmutable::parse($attendanceRequest->requested_out),
            'request',
        );

        $attendanceRequest->update(['status' => 'approved', 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        return back()->with('status', __('Request approved; attendance for :name on :date updated.', [
            'name' => $attendanceRequest->employee->full_name, 'date' => $attendanceRequest->work_date->toDateString(),
        ]));
    }

    public function reject(Request $request, AttendanceRequest $attendanceRequest): RedirectResponse
    {
        $attendanceRequest->update(['status' => 'rejected', 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        return back()->with('status', __('Request rejected.'));
    }

    public function destroy(AttendanceRequest $attendanceRequest): RedirectResponse
    {
        $attendanceRequest->delete();

        return $this->deleted($attendanceRequest, __('Request deleted.'));
    }
}
