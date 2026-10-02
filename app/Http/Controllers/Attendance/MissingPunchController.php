<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Attendance › Missing punch: days in a month where an employee punched in
 * without punching out, or out without in.
 */
class MissingPunchController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $month = is_string($request->query('month')) && preg_match('/^\d{4}-\d{2}$/', $request->query('month'))
            ? CarbonImmutable::createFromFormat('!Y-m', $request->query('month'))
            : CarbonImmutable::today()->startOfMonth();

        $punches = $company
            ? Attendance::where('company_id', $company->id)->where('is_missing', true)
                ->whereDate('work_date', '>=', $month)->whereDate('work_date', '<=', $month->endOfMonth())
                ->whereHas('employee', fn ($q) => $q->whereNull('deleted_at'))
                ->with(['employee.position', 'shift'])->orderBy('work_date')->orderBy('punched_at')->get()
            : collect();

        return view('attendance.missing-punch', compact('companies', 'company', 'month', 'punches'));
    }
}
