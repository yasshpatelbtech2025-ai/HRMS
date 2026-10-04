<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Configs › Payroll: a company's payroll settings and its payroll rules
 * ("Create setting" → New rule), e.g. a month's variable bonus.
 */
class PayrollSettingController extends Controller
{
    public const STRUCTURES = ['old' => 'Old Structure', 'new' => 'New Structure'];

    public const BONUS_FREQUENCIES = ['yearly' => 'Yearly', 'monthly' => 'Monthly'];

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();

        return view('configs.payroll', [
            'companies' => $companies,
            'company' => $company,
            'setting' => $company ? PayrollSetting::firstOrNew(['company_id' => $company->id]) : null,
            'rules' => $company ? PayrollRule::where('company_id', $company->id)->orderByDesc('month')->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'probation_period_months' => ['nullable', 'integer', 'min:0', 'max:60'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'salary_structure' => ['required', Rule::in(array_keys(self::STRUCTURES))],
            'bonus_frequency' => ['required', Rule::in(array_keys(self::BONUS_FREQUENCIES))],
            'tax_deduction' => ['required', 'boolean'],
            'lwf_paid_by_company' => ['required', 'boolean'],
            'pf_effect_on' => ['required', Rule::in(array_keys(config('attendance.effect_on')))],
            'esi_effect_on' => ['required', Rule::in(array_keys(config('attendance.effect_on')))],
        ], [], ['probation_period_months' => 'probation period', 'notice_period_days' => 'notice period']);

        PayrollSetting::updateOrCreate(['company_id' => $company->id], $data);

        return redirect()->route('configs.payroll', ['company' => $company->uuid])
            ->with('status', __('Payroll settings for :company saved.', ['company' => $company->establishment_name]));
    }

    public function storeRule(Request $request): RedirectResponse
    {
        PayrollRule::create($this->validatedRule($request));

        return back()->with('status', __('Payroll rule added.'));
    }

    public function updateRule(Request $request, PayrollRule $rule): RedirectResponse
    {
        $rule->update($this->validatedRule($request));

        return back()->with('status', __('Payroll rule updated.'));
    }

    public function destroyRule(PayrollRule $rule): RedirectResponse
    {
        $rule->delete();

        return $this->deleted($rule, __('Payroll rule deleted.'));
    }

    private function validatedRule(Request $request): array
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(PayrollRule::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', $request->boolean('is_percent') ? 'max:100' : 'max:9999999999'],
            'month' => ['required', 'date_format:Y-m'],
            'is_percent' => ['required', 'boolean'],
            'effect_on' => ['required', Rule::in(array_keys(config('attendance.effect_on')))],
            'is_active' => ['required', 'boolean'],
        ], ['amount.max' => __('A percentage cannot be more than 100.')]);

        return ['month' => $data['month'].'-01'] + $data;
    }
}
