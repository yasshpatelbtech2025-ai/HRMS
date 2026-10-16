<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\ComplianceTask;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Services\Attendance\MonthlyAttendance;
use App\Services\Compliance\ComplianceTasks;
use App\Support\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * Dashboard: what needs attention first (overdue compliance, missing punches,
 * pending requests), then today's headcount and attendance, the same split by
 * department, requests waiting for a decision, and compliance health from the
 * To Do List. Follows the top-bar company; all companies when none is picked.
 */
class DashboardController extends Controller
{
    public function __invoke(MonthlyAttendance $attendance, ComplianceTasks $compliance): View
    {
        $company = CurrentCompany::get();
        $scope = fn ($query) => $query->when($company, fn ($q) => $q->where('company_id', $company->id));

        ($company ? collect([$company]) : Company::all())->each(fn (Company $c) => $compliance->refresh($c));
        $tasks = $scope(ComplianceTask::with(['company', 'employee']))->orderBy('due_on')->get();
        $due = $tasks->filter(fn (ComplianceTask $t) => $t->due_on->lt(today())); // past their due date (due today is not late yet)

        $today = CarbonImmutable::today();
        $employees = $scope(Employee::current())->with(['shift', 'subDepartment', 'department'])->get();
        $days = collect($attendance->forRange($employees, $today, $today))->map(fn ($row) => $row['days'][$today->toDateString()]['status'] ?? null);

        // Today's state per employee: present (incl. half day), leave (incl. comp off), absent, or off (week-off / holiday).
        $state = fn (?string $status) => match ($status) {
            'P', 'HD' => 'present',
            'LEAVE', 'COFF' => 'leave',
            'WO', 'PH' => 'off',
            'A' => 'absent',
            default => null, // not yet joined
        };
        $states = $employees->mapWithKeys(fn (Employee $e) => [$e->id => $state($days[$e->id] ?? null)]);

        $departments = $employees
            ->groupBy(fn (Employee $e) => $e->subDepartment?->name ?? $e->department?->name ?? __('No department'))
            ->map(fn ($group) => collect(['present', 'leave', 'absent', 'off'])->mapWithKeys(fn ($s) => [$s => $group->filter(fn ($e) => $states[$e->id] === $s)->count()])->all())
            ->filter(fn ($counts) => array_sum($counts) > 0)
            ->sortKeys();

        $counts = collect(['overdue', 'week', 'upcoming'])->mapWithKeys(fn ($s) => [$s => $tasks->filter(fn ($t) => $t->status() === $s)->count()])->all();

        return view('dashboard', [
            'company' => $company,
            // "Needs attention": real counts only, each opening the list behind it.
            'attention' => [
                'overdue' => $counts['overdue'],
                'week' => $counts['week'],
                'missing' => $scope(Attendance::where('is_missing', true))
                    ->whereDate('work_date', '>=', $today->startOfMonth())->whereDate('work_date', '<=', $today)
                    ->whereHas('employee', fn ($q) => $q->whereNull('deleted_at'))->count(),
                'attendanceRequests' => $scope(AttendanceRequest::where('status', 'pending'))->count(),
                'leaveRequests' => $scope(LeaveApplication::where('status', LeaveApplication::PENDING))->count(),
            ],
            'stats' => [
                'total' => $employees->count(),
                'present' => $states->filter(fn ($s) => $s === 'present')->count(),
                'absent' => $states->filter(fn ($s) => $s === 'absent')->count(),
                'leave' => $states->filter(fn ($s) => $s === 'leave')->count(),
            ],
            'departments' => $departments,
            'attendanceRequests' => $scope(AttendanceRequest::where('status', 'pending'))->with('employee')->latest()->limit(5)->get(),
            'leaveRequests' => $scope(LeaveApplication::where('status', LeaveApplication::PENDING))->with(['employee', 'leaveType'])->latest()->limit(5)->get(),
            'today' => $today,
            // Health: the share of tasks due so far that are done.
            'compliance' => [
                'health' => $due->isEmpty() ? 100 : (int) round($due->whereNotNull('completed_at')->count() / $due->count() * 100),
                'counts' => $counts,
                'urgent' => $tasks->filter(fn ($t) => in_array($t->status(), ['overdue', 'week'], true))->take(5),
            ],
        ]);
    }
}
