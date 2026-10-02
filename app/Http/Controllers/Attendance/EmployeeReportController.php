<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employee wise attendance report: one employee's days for a month with
 * in/out times, hours, overtime and status. Opened from the monthly grid.
 */
class EmployeeReportController extends Controller
{
    public function index(Request $request, MonthlyAttendance $attendance): View
    {
        $employees = Employee::with('company')->orderBy('company_id')->orderBy('employee_code')->get();
        $employee = $employees->firstWhere('uuid', $request->query('employee'));
        $month = is_string($request->query('month')) && preg_match('/^\d{4}-\d{2}$/', $request->query('month'))
            ? CarbonImmutable::createFromFormat('!Y-m', $request->query('month'))
            : CarbonImmutable::today()->startOfMonth();

        $employee?->load(['shift', 'position', 'subDepartment', 'department']);

        return view('attendance.employee-report', [
            'employees' => $employees,
            'employee' => $employee,
            'month' => $month,
            'report' => $employee ? $attendance->forMonth(collect([$employee]), $month)[$employee->id] : null,
        ]);
    }
}
