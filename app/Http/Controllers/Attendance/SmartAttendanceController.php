<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\SmartAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance › Smart attendance: fill a month's attendance for chosen
 * employees from their shift timings.
 */
class SmartAttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.smart', [
            'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
            'employees' => Employee::current()->orderBy('employee_code')->get(['id', 'uuid', 'company_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'shift_id']),
        ]);
    }

    public function store(Request $request, SmartAttendance $smart): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*' => ['uuid', Rule::exists('employees', 'uuid')->where('company_id', $request->integer('company_id'))->whereNull('deleted_at')],
            'year' => ['required', 'integer', 'between:2000,'.date('Y')],
            'month' => ['required', 'integer', 'between:1,12'],
        ], [], ['company_id' => 'company', 'employees' => 'employee']);

        $employees = Employee::whereIn('uuid', $data['employees'])->with('shift')->get();
        $days = 0;
        $withoutShift = [];

        foreach ($employees as $employee) {
            if (! $employee->shift) {
                $withoutShift[] = $employee->full_name;

                continue;
            }
            $days += $smart->fill($employee, (int) $data['year'], (int) $data['month']);
        }

        $redirect = back()->withInput()->with('status', trans_choice('Attendance filled for :days day across :employees employee(s).|Attendance filled for :days days across :employees employee(s).', $days, [
            'days' => $days, 'employees' => $employees->count() - count($withoutShift),
        ]));

        return $withoutShift
            ? $redirect->with('error', __('Skipped (no shift set): :names', ['names' => implode(', ', $withoutShift)]))
            : $redirect;
    }
}
