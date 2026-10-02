<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance › Leave › Holiday: which holidays from the master list each
 * company observes, by year.
 */
class CompanyHolidayController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $year = $request->integer('year', (int) date('Y'));
        $year = $year >= 2000 && $year <= 2100 ? $year : (int) date('Y');

        return view('attendance.leave.holidays', [
            'companies' => $companies,
            'company' => $company,
            'year' => $year,
            'holidays' => $company ? $company->holidays()->inYear($year)->orderBy('start_date')->get() : collect(),
            'available' => Holiday::inYear($year)
                ->when($company, fn ($q) => $q->whereDoesntHave('companies', fn ($c) => $c->whereKey($company->id)))
                ->orderBy('start_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'holidays' => ['required', 'array', 'min:1'],
            'holidays.*' => ['uuid', Rule::exists('holidays', 'uuid')],
        ], [], ['company_id' => 'company', 'holidays' => 'holiday']);

        $company = Company::findOrFail($data['company_id']);
        $company->holidays()->syncWithoutDetaching(Holiday::whereIn('uuid', $data['holidays'])->pluck('id'));

        return back()->with('status', trans_choice(':count holiday added for :company.|:count holidays added for :company.', count($data['holidays']), [
            'company' => $company->establishment_name,
        ]));
    }

    public function destroy(Company $company, Holiday $holiday): RedirectResponse
    {
        $company->holidays()->detach($holiday->id);

        return back()->with('status', __(':holiday removed for :company.', ['holiday' => $holiday->name, 'company' => $company->establishment_name]));
    }
}
