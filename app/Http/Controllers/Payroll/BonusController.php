<?php

namespace App\Http\Controllers\Payroll;

use App\Rules\AccessibleCompany;
use App\Http\Controllers\Controller;
use App\Models\BonusSheet;
use App\Models\Company;
use App\Models\PayrollSetting;
use App\Services\Payroll\BonusSheets;
use App\Services\Payroll\IncomeTax;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Bonus: generate a company's yearly bonus sheet for an
 * accounting year, adjust amounts, approve it with the date paid.
 */
class BonusController extends Controller
{
    public function __construct(private BonusSheets $bonus) {}

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        // By default the last completed accounting year.
        $fy = $request->integer('fy') ?: IncomeTax::fy(CarbonImmutable::today()) - 1;
        $sheet = $company ? BonusSheet::where('company_id', $company->id)->where('fy', $fy)
            ->with(['entries' => fn ($q) => $q->with('employee.position')->orderBy('employee_id'), 'approver'])->first() : null;

        return view('payroll.bonus', [
            'companies' => $companies,
            'company' => $company,
            'fy' => $fy,
            'sheet' => $sheet,
            'frequency' => $company ? (PayrollSetting::where('company_id', $company->id)->value('bonus_frequency') ?? 'yearly') : 'yearly',
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate(['company' => ['required', 'uuid', new AccessibleCompany('uuid'), 'exists:companies,uuid'], 'fy' => ['required', 'integer', 'between:2000,2100']]);
        $company = Company::where('uuid', $data['company'])->firstOrFail();
        $sheet = $this->bonus->generate($company, (int) $data['fy'], $request->user());
        $count = $sheet->entries()->count();

        return back()->with('status', $count
            ? trans_choice('Bonus worked out for :count employee.|Bonus worked out for :count employees.', $count)
            : __('No salary was generated in this accounting year, so there is no bonus to work out.'));
    }

    public function update(Request $request, BonusSheet $sheet): RedirectResponse
    {
        $data = $request->validate(['amounts' => ['required', 'array'], 'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:9999999']], [], ['amounts.*' => 'bonus amount']);
        $this->bonus->updateAmounts($sheet->load('entries'), $data['amounts']);

        return back()->with('status', __('Bonus amounts saved.'));
    }

    public function approve(Request $request, BonusSheet $sheet): RedirectResponse
    {
        $data = $request->validate(['paid_on' => ['nullable', 'date']], [], ['paid_on' => 'date paid']);
        $this->bonus->approve($sheet, $request->user(), $data['paid_on'] ?? null);

        return back()->with('status', __('Bonus sheet approved.'));
    }

    public function unapprove(BonusSheet $sheet): RedirectResponse
    {
        $this->bonus->unapprove($sheet);

        return back()->with('status', __('Bonus sheet unapproved; amounts can be changed again.'));
    }

    public function destroy(BonusSheet $sheet): RedirectResponse
    {
        abort_if($sheet->isApproved(), 422);
        $sheet->delete();

        return back()->with('status', __('Bonus sheet deleted.'));
    }

    public function export(BonusSheet $sheet, string $format): StreamedResponse
    {
        $sheet->load(['company', 'entries.employee.position']);

        return TableExport::download($format, 'bonus-'.$sheet->fy.'-'.($sheet->fy + 1),
            ['Card No', 'Employee Name', 'Designation', 'Days worked', 'Salary (Basic + DA)', 'Bonus salary', 'Bonus %', 'Eligible', 'Worked out', 'Bonus paid'],
            $sheet->entries->map(fn ($e) => [
                $e->employee->employee_code, $e->employee->full_name, $e->employee->position?->name, $e->days_worked, $e->salary, $e->bonus_salary,
                $e->rate, $e->eligible ? 'Yes' : 'No — '.$e->reason, $e->calculated, $e->amount,
            ]),
            "Bonus {$sheet->fy}-".($sheet->fy + 1)." — {$sheet->company->establishment_name}",
        );
    }
}
