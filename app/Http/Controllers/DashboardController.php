<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * Dashboard: today's headcount and attendance across all companies, the
 * same split by department, and requests waiting for a decision.
 */
class DashboardController extends Controller
{
    public function __invoke(MonthlyAttendance $attendance): View
    {
        $today = CarbonImmutable::today();
        $employees = Employee::current()->with(['shift', 'subDepartment', 'department'])->get();
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

        return view('dashboard', [
            'stats' => [
                'total' => $employees->count(),
                'present' => $states->filter(fn ($s) => $s === 'present')->count(),
                'absent' => $states->filter(fn ($s) => $s === 'absent')->count(),
                'leave' => $states->filter(fn ($s) => $s === 'leave')->count(),
            ],
            'departments' => $departments,
            'attendanceRequests' => AttendanceRequest::where('status', 'pending')->with('employee')->latest()->limit(5)->get(),
            'leaveRequests' => LeaveApplication::where('status', LeaveApplication::PENDING)->with(['employee', 'leaveType'])->latest()->limit(5)->get(),
            'today' => $today,
        ]);
    }
}
