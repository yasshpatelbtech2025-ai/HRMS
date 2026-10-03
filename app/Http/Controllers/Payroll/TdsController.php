<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Models\TdsOverride;
use App\Services\Payroll\IncomeTax;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › TDS: each employee's income tax estimate for a financial year -
 * paid so far, projected salary and bonus, deductions, taxable income, the
 * year's tax, TDS deducted and the monthly TDS - with a monthly amount set
 * by hand to use instead of the estimate.
 */
class TdsController extends Controller
{
    public function __construct(private IncomeTax $tax) {}

    public function index(Request $request): View
    {
        [$companies, $company, $fy] = $this->filters($request);

        return view('payroll.tds', [
            'companies' => $companies,
            'company' => $company,
            'fy' => $fy,
            'rows' => $company ? $this->rows($company, $fy) : collect(),
            'deducting' => $company && (bool) PayrollSetting::where('company_id', $company->id)->value('tax_deduction'),
        ]);
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        [, $company, $fy] = $this->filters($request);
        abort_unless($company, 404);

        return TableExport::download($format, 'tds-'.$fy.'-'.($fy + 1),
            ['Card No', 'Employee Name', 'PAN', 'Regime', 'Paid so far', 'Projected gross', 'Bonus', 'Deductions', 'Taxable', 'Tax for the year', 'TDS deducted', 'Balance', 'Months left', 'Monthly TDS', 'Set by hand'],
            $this->rows($company, $fy)->map(fn ($r) => [
                $r['employee']->employee_code, $r['employee']->full_name, $r['employee']->pan_card_no, ucfirst($r['regime']),
                $r['paid_gross'], $r['gross'], $r['bonus'], $r['standard'] + $r['other'], $r['taxable'], $r['tax'], $r['paid_tds'], $r['balance'],
                $r['months_left'], $r['monthly_tds'], $r['override'] ? 'Yes' : '',
            ]),
            "TDS {$fy}-".($fy + 1)." — {$company->establishment_name}",
        );
    }

    /**
     * Set (or clear, when blank) the monthly TDS for an employee for a year.
     */
    public function setMonthly(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'fy' => ['required', 'integer', 'between:2000,2100'],
            'monthly_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['monthly_amount' => 'monthly TDS']);

        if (($data['monthly_amount'] ?? null) === null) {
            TdsOverride::where('employee_id', $employee->id)->where('fy', $data['fy'])->delete();

            return back()->with('status', __('Monthly TDS for :name is estimated again.', ['name' => $employee->full_name]));
        }

        TdsOverride::updateOrCreate(['employee_id' => $employee->id, 'fy' => $data['fy']], ['monthly_amount' => $data['monthly_amount'], 'note' => $data['note'] ?? null]);

        return back()->with('status', __('Monthly TDS for :name set to :amount.', ['name' => $employee->full_name, 'amount' => number_format((float) $data['monthly_amount'], 2)]));
    }

    /**
     * @return array{Collection, ?Company, int}
     */
    private function filters(Request $request): array
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $fy = $request->integer('fy') ?: IncomeTax::fy(CarbonImmutable::today());

        return [$companies, $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first(), $fy];
    }

    /**
     * The year's estimate for every current employee and anyone paid in the year.
     */
    private function rows(Company $company, int $fy): Collection
    {
        // As of today within the year; for a past or future year, its last or first month.
        $asOf = CarbonImmutable::today()->startOfMonth()->max(CarbonImmutable::create($fy, 4, 1))->min(CarbonImmutable::create($fy + 1, 3, 1));

        return Employee::where('company_id', $company->id)
            ->where(fn ($q) => $q->current()->orWhereHas('payslips', fn ($p) => $p->whereDate('month', '>=', CarbonImmutable::create($fy, 4, 1))->whereDate('month', '<=', CarbonImmutable::create($fy + 1, 3, 1))))
            ->with('salaryElements')->orderBy('employee_code')->get()
            ->map(fn (Employee $e) => ['employee' => $e] + $this->tax->project($e, $asOf));
    }
}
