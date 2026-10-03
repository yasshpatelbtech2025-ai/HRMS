<?php

namespace App\Services\Compliance;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceRegisterEntry;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\Loan;
use App\Models\MedicalExamination;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalaryAdvance;
use App\Models\SalarySheet;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Builds the data for each statutory form on Compliance › Forms from the
 * HRMS records: employees, attendance, payslips, advances and loans, plus
 * rows and fields typed into the forms themselves.
 */
class ComplianceForms
{
    public function __construct(private MonthlyAttendance $attendance) {}

    /**
     * @return array<string, mixed> data for the form's view
     */
    public function build(string $view, Company $company, Request $request): array
    {
        $company->loadMissing('parent');
        $base = [
            'company' => $company,
            'contractor' => $company->parent?->establishment_name ?? $company->establishment_name,
            'address' => $this->companyAddress($company),
        ];

        return $base + match ($view) {
            'form-a' => $this->formA($company, $this->month($request)),
            'form-b', 'form-17' => $this->wages($company, $this->month($request)),
            'form-c' => $this->formC($company, $this->month($request)),
            'form-d', 'form-16' => $this->muster($company, $this->month($request)),
            'form-18' => $this->muster($company, $this->month($request)) + $this->wages($company, $this->month($request)),
            'form-14', 'gratuity-form-f', 'pf-form-10c', 'pf-form-19' => $this->employeePicker($company, $request, $view),
            'pf-statement' => $this->providentFund($company, $this->month($request)),
            'form-13' => ['employees' => $this->employees($company)->get()],
            'form-20', 'form-21' => $this->entries($company, $view, $this->month($request)),
            'form-22' => $this->advances($company, $this->month($request)),
            'form-23' => $this->overtime($company, $this->month($request)),
            'form-25' => $this->halfYearly($company, $request),
            'bonus-form-d' => $this->bonus($company, $request),
            'form-15', 'id-card-register' => ['employees' => $this->employees($company)->current()->get()],
            'form-29' => $this->accidents($company, $request),
            'er-1' => $this->quarterly($company, $request),
            'pt-form-5' => $this->professionalTax($company, $this->month($request)),
            'bonus-form-a', 'bonus-form-b' => $this->bonusReturn($company, $request, $view),
            'bonus-form-c' => $this->bonusDetails($company, $request),
            'form-5', 'form-27a', 'form-30', 'form-32' => $this->examinations($company, $request),
            default => abort(404),
        };
    }

    public function month(Request $request): CarbonImmutable
    {
        $month = $request->query('month');

        return is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month)
            : CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
    }

    /** Father's or husband's name, from the employee's family details. */
    public static function guardian(Employee $employee): ?string
    {
        $family = $employee->familyMembers->keyBy('relation');

        return $family['father']->full_name ?? $family['spouse']->full_name ?? null;
    }

    public static function address(?EmployeeAddress $address): string
    {
        return $address ? collect([$address->address_line_1, $address->address_line_2, $address->village, $address->taluka, $address->district, $address->state])->filter()->implode(', ') : '';
    }

    /** Where the employee lives: the present address, or the permanent one when they are the same. */
    public static function residence(Employee $employee): string
    {
        return self::address($employee->address('present') ?? $employee->address('permanent'));
    }

    public static function mask(?string $value): string
    {
        return $value ? str_repeat('X', max(0, strlen($value) - 4)).substr($value, -4) : '';
    }

    private function companyAddress(Company $company): string
    {
        return collect([$company->address_line_1, $company->address_line_2, $company->village, $company->taluka, $company->district, $company->state])->filter()->implode(', ');
    }

    private function employees(Company $company)
    {
        return Employee::where('company_id', $company->id)
            ->with(['position', 'subDepartment', 'familyMembers', 'addresses', 'qualifications', 'shift', 'salaryElements'])
            ->orderBy('employee_code');
    }

    /** Employees who worked any part of the month. */
    private function employedIn(Company $company, CarbonImmutable $month): Collection
    {
        return $this->employees($company)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $month->endOfMonth()))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>=', $month))
            ->get();
    }

    private function formA(Company $company, CarbonImmutable $month): array
    {
        return ['month' => $month, 'employees' => $this->employedIn($company, $month)];
    }

    /**
     * Daily status grid and day totals (Form D, 16, 18).
     */
    private function muster(Company $company, CarbonImmutable $month): array
    {
        $employees = $this->employedIn($company, $month);

        return ['month' => $month, 'employees' => $employees, 'grid' => $this->attendance->forMonth($employees, $month)];
    }

    /**
     * Payslip figures for the month (Form B, 17, 18).
     */
    private function wages(Company $company, CarbonImmutable $month): array
    {
        $employees = $this->employedIn($company, $month);
        $sheet = SalarySheet::where('company_id', $company->id)->whereDate('month', $month)->first();
        $payslips = $sheet ? $sheet->payslips()->get()->keyBy('employee_id') : collect();

        $wages = $employees->mapWithKeys(function (Employee $e) use ($payslips) {
            /** @var Payslip|null $p */
            $p = $payslips->get($e->id);
            $statutory = ['pf', 'esi', 'pt', 'lwf', 'vpf'];
            $deductions = collect($p?->deductions ?? []);
            $earnings = collect($p?->earnings ?? []);

            return [$e->id => $p ? [
                'days' => $p->attendance['paid_days'],
                'ot_hours' => $p->attendance['ot_hours'],
                'basic' => (float) ($earnings->firstWhere('key', 'basic')['amount'] ?? 0),
                'da' => (float) ($earnings->firstWhere('key', 'da')['amount'] ?? 0),
                'overtime' => (float) ($earnings->firstWhere('key', 'overtime')['amount'] ?? 0),
                'other' => round($earnings->whereNotIn('key', ['basic', 'da', 'overtime'])->sum('amount'), 2),
                'gross' => (float) $p->gross_earnings,
                'statutory' => round($deductions->whereIn('key', $statutory)->sum('amount'), 2),
                'other_deductions' => round($deductions->whereNotIn('key', $statutory)->sum('amount'), 2),
                'deductions' => (float) $p->total_deductions,
                'net' => (float) $p->net_pay,
            ] : null];
        });

        return ['month' => $month, 'employees' => $employees, 'wages' => $wages, 'sheet' => $sheet];
    }

    /**
     * Form C: advances and loan installments for the month, plus rows typed in.
     */
    private function formC(Company $company, CarbonImmutable $month): array
    {
        $rows = collect();

        foreach (SalaryAdvance::where('company_id', $company->id)->whereDate('salary_month', $month)->with('employee')->get() as $a) {
            $rows->push(['employee' => $a->employee, 'type' => 'Advance', 'particular' => __('Salary advance'), 'date' => $a->advance_date->format('d-m-Y'),
                'amount' => (float) $a->amount, 'show_cause' => '', 'heard_by' => '', 'installments' => 1,
                'first' => $a->salary_month->format('m/Y'), 'last' => $a->salary_month->format('m/Y'),
                'recovered' => (float) $a->released_amount >= (float) $a->amount ? $a->salary_month->endOfMonth()->format('d-m-Y') : '', 'remarks' => '']);
        }

        foreach (Loan::where('company_id', $company->id)->where('is_active', true)->with('employee')->get() as $loan) {
            if ($loan->installmentFor($month) <= 0) {
                continue;
            }
            $last = $loan->repayment_start->addMonths($loan->installment_period - 1);
            $rows->push(['employee' => $loan->employee, 'type' => 'Loans', 'particular' => $loan->loan_no.($loan->details ? ' — '.$loan->details : ''),
                'date' => $loan->approved_date->format('d-m-Y'), 'amount' => (float) $loan->repayment_amount, 'show_cause' => '', 'heard_by' => '',
                'installments' => $loan->installment_period, 'first' => $loan->repayment_start->format('m/Y'), 'last' => $last->format('m/Y'),
                'recovered' => $loan->isRepaid() ? $last->endOfMonth()->format('d-m-Y') : '', 'remarks' => '']);
        }

        return ['month' => $month, 'rows' => $rows] + $this->entries($company, 'form-c', $month);
    }

    /**
     * Rows typed into a register for the month, and employees to pick from.
     */
    private function entries(Company $company, string $form, CarbonImmutable $month): array
    {
        return [
            'month' => $month,
            'entries' => ComplianceRegisterEntry::where('company_id', $company->id)->where('form', $form)->whereDate('month', $month)
                ->with(['employee.position', 'employee.familyMembers'])->orderBy('id')->get(),
            'employeeOptions' => $this->employedIn($company, $month),
        ];
    }

    private function advances(Company $company, CarbonImmutable $month): array
    {
        return [
            'month' => $month,
            'advances' => SalaryAdvance::where('company_id', $company->id)
                ->where(fn ($q) => $q->whereDate('salary_month', $month)
                    ->orWhere(fn ($w) => $w->whereDate('advance_date', '>=', $month)->whereDate('advance_date', '<=', $month->endOfMonth())))
                ->with(['employee.position', 'employee.familyMembers', 'employee.salaryElements'])->orderBy('advance_date')->get(),
        ];
    }

    /**
     * Form 23: overtime worked by day, priced as on the payslip when there is one.
     */
    private function overtime(Company $company, CarbonImmutable $month): array
    {
        $employees = $this->employedIn($company, $month);
        $grid = $this->attendance->forMonth($employees, $month);
        $sheet = SalarySheet::where('company_id', $company->id)->whereDate('month', $month)->first();
        $payslips = $sheet ? $sheet->payslips()->get()->keyBy('employee_id') : collect();
        $rows = [];

        foreach ($employees as $e) {
            $days = collect($grid[$e->id]['days'])->filter(fn ($d) => $d['ot'] > 0);
            if ($days->isEmpty()) {
                continue;
            }

            $hours = round($days->sum('ot'), 2);
            $basic = (float) ($e->salaryElements->firstWhere('element', 'basic')->monthly ?? 0);
            $dailyHours = (float) ($e->daily_hour ?: 8);
            $normal = match ($e->pay_frequency) {
                'hourly' => (float) $e->hourly_rate,
                'daily' => ($basic ?: (float) $e->daily_rate) / $dailyHours,
                default => $basic / $month->daysInMonth / $dailyHours,
            };
            $multiplier = ($e->shift?->ot_rate ?? 'regular') === 'double' ? 2 : 1;
            $earnings = $payslips->has($e->id) ? $payslips[$e->id]->amount('earnings', 'overtime') : round($hours * $normal * $multiplier, 2);

            $rows[] = [
                'employee' => $e,
                'dates' => $days->map(fn ($d) => $d['date']->format('jS').' -- '.rtrim(rtrim(number_format($d['ot'], 2), '0'), '.').' hrs')->implode(', '),
                'hours' => $hours,
                'normal' => round($normal, 2),
                'rate' => round($normal * $multiplier, 2),
                'earnings' => $earnings,
                'paid_on' => $sheet?->paid_date?->format('d-m-Y'),
            ];
        }

        return ['month' => $month, 'rows' => $rows];
    }

    /**
     * Form 25 (Factories Act half-yearly return): days worked, mandays and
     * average daily workers by age group and sex, hazardous-process workers.
     */
    private function halfYearly(Company $company, Request $request): array
    {
        $year = $request->integer('year', (int) date('Y'));
        $half = $request->query('month') === 'december' ? 'december' : 'june';
        $from = CarbonImmutable::create($year, $half === 'june' ? 1 : 7, 1);
        $to = $from->addMonths(5)->endOfMonth()->startOfDay();
        $employees = $this->employees($company)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $to))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>=', $from))
            ->get();

        $data = $this->attendance->forRange($employees, $from, $to->min(CarbonImmutable::today()));
        $mandays = ['adult_men' => 0, 'adult_women' => 0, 'adolescent_men' => 0, 'adolescent_women' => 0, 'boys' => 0, 'girls' => 0];
        $workingDays = [];

        foreach ($employees as $e) {
            $age = $e->date_of_birth ? $e->date_of_birth->diffInYears($to) : 30;
            $female = $e->gender?->value === 'female';
            $group = match (true) {
                $age < 15 => $female ? 'girls' : 'boys',
                $age < 18 => $female ? 'adolescent_women' : 'adolescent_men',
                default => $female ? 'adult_women' : 'adult_men',
            };

            foreach ($data[$e->id]['days'] as $date => $day) {
                if (in_array($day['status'], ['P', 'HD'], true)) {
                    $mandays[$group]++; // half a shift or more counts as full attendance
                    $workingDays[$date] = true;
                }
            }
        }

        $days = count($workingDays);

        return [
            'year' => $year,
            'half' => $half,
            'periodEnd' => $to,
            'workingDays' => $days,
            'mandays' => $mandays,
            'average' => array_map(fn ($n) => $days ? round($n / $days) : 0, $mandays),
            'hazardous' => $employees->where('works_on_hazardous_process', true)->count(),
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'form-25', 'period' => $to->format('Y-m')]),
        ];
    }

    /**
     * Payment of Bonus Act Form D for an accounting year (April to March).
     */
    private function bonus(Company $company, Request $request): array
    {
        [$start, $from, $to] = $this->accountingYear($request);

        $payslips = Payslip::where('company_id', $company->id)->whereDate('month', '>=', $from)->whereDate('month', '<=', $to)->get();
        $bonus = $payslips->sum(fn (Payslip $p) => $p->amount('earnings', 'bonus'));

        return [
            'startYear' => $start,
            'yearEnd' => $to,
            'totalEmployees' => $payslips->pluck('employee_id')->unique()->count(),
            'benefited' => $payslips->filter(fn (Payslip $p) => $p->amount('earnings', 'bonus') > 0)->pluck('employee_id')->unique()->count(),
            'bonusPaid' => round($bonus, 2),
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'bonus-form-d', 'period' => $start.'-'.($start + 1)]),
        ];
    }

    public function year(Request $request): int
    {
        $year = (int) $request->query('year');

        return $year >= 2000 && $year <= 2100 ? $year : (int) date('Y');
    }

    /**
     * PF ACT: the month's contribution statement from the payslips -
     * wages, employee share (EE), pension (EPS) and employer EPF (ER) per
     * employee, and the challan accounts: AC 1 (EE + ER), AC 2 admin charge
     * (0.5% of EPF wages, at least ₹500), AC 10 (EPS) and AC 21 EDLI (0.5% of
     * EDLI wages).
     */
    private function providentFund(Company $company, CarbonImmutable $month): array
    {
        $onGross = PayrollSetting::where('company_id', $company->id)->value('pf_effect_on') === 'gross';
        $payslips = Payslip::where('company_id', $company->id)->whereDate('month', $month)->with('employee')->get()
            ->sortBy(fn (Payslip $p) => $p->employee?->employee_code)->values();

        $rows = $payslips->map(function (Payslip $p) use ($onGross) {
            $ee = $p->amount('deductions', 'pf');
            $wage = $ee > 0 ? round($onGross ? (float) $p->gross_earnings : $p->amount('earnings', 'basic')) : 0;

            return [
                'employee' => $p->employee,
                'gross' => (float) $p->gross_earnings,
                'epf' => $wage,
                'eps' => min($wage, 15000),
                'edli' => min($wage, 15000),
                'ee' => $ee,
                'eps_share' => $p->amount('employer', 'employer_eps'),
                'er' => $p->amount('employer', 'employer_epf'),
                'absent' => $p->attendance['lop'] ?? 0,
            ];
        });

        $sum = fn (string $key) => round($rows->sum($key), 2);
        $ac2 = $sum('epf') > 0 ? max(500, round($sum('epf') * 0.005)) : 0;
        $ac21 = round($sum('edli') * 0.005);
        $totals = [
            'ADMINISTRATION CHARGE (AC 2)' => $ac2,
            'EDLI CHARGE (AC 21)' => $ac21,
            'Total EPF Contribution' => $sum('ee'),
            'Total EPS Contribution (AC 10)' => $sum('eps_share'),
            'Total EPF-EPS Contribution' => $sum('er'),
            'TOTAL PF' => $sum('ee') + $sum('er') + $sum('eps_share') + $ac2 + $ac21,
            'AC 1' => $sum('ee') + $sum('er'),
        ];

        return [
            'month' => $month,
            'rows' => $rows,
            'sums' => collect(['gross', 'epf', 'eps', 'edli', 'ee', 'eps_share', 'er'])->mapWithKeys(fn ($k) => [$k => $sum($k)])->all(),
            'totals' => $totals,
            'generated' => $payslips->isNotEmpty(),
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'pf-statement', 'period' => $month->format('Y-m')]),
            'export' => [
                'headings' => ['Sr No', 'Employee Id', 'Employee Name', 'UAN', 'Gross Salary', 'EPF Wage', 'EPS Wage', 'EDLI Wages', 'EE', 'EPS', 'ER', 'Absent'],
                'rows' => $rows->values()->map(fn ($r, $i) => [$i + 1, $r['employee']?->employee_code, $r['employee']?->full_name, $r['employee']?->uan_code,
                    $r['gross'], $r['epf'], $r['eps'], $r['edli'], $r['ee'], $r['eps_share'], $r['er'], $r['absent']])
                    ->concat(collect($totals)->map(fn ($v, $label) => ['', '', $label, '', $v]))->values()->all(),
            ],
        ];
    }

    /**
     * PT Form 5: employees paid in the month by salary slab (below
     * ₹12,000 / ₹12,000 or more) and the professional tax deducted, from the
     * month's payslips.
     */
    private function professionalTax(Company $company, CarbonImmutable $month): array
    {
        $payslips = Payslip::where('company_id', $company->id)->whereDate('month', $month)->get();
        $slab = fn (bool $upper) => $payslips->filter(fn (Payslip $p) => ((float) $p->gross_earnings >= 12000) === $upper);
        $row = function (bool $upper) use ($slab) {
            $slips = $slab($upper);
            $taxed = $slips->filter(fn (Payslip $p) => $p->amount('deductions', 'pt') > 0);

            return [
                'employees' => $slips->count(),
                'exempt' => $slips->count() - $taxed->count(),
                'taxed' => $taxed->count(),
                'rate' => $upper ? 200 : 0,
                'tax' => round($taxed->sum(fn (Payslip $p) => $p->amount('deductions', 'pt')), 2),
            ];
        };

        return [
            'month' => $month,
            'slabs' => ['Below Rs. 12,000/-' => $row(false), 'Rs. 12,000/- or more' => $row(true)],
            'generated' => $payslips->isNotEmpty(),
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'pt-form-5', 'period' => $month->format('Y-m')]),
        ];
    }

    /**
     * The bonus accounting year (April to March) picked by its starting
     * year; by default the last completed one.
     *
     * @return array{int, CarbonImmutable, CarbonImmutable}
     */
    private function accountingYear(Request $request): array
    {
        $start = $request->integer('year', (int) date('Y') - (date('n') >= 4 ? 1 : 2));

        return [$start, CarbonImmutable::create($start, 4, 1), CarbonImmutable::create($start + 1, 3, 31)];
    }

    /**
     * Bonus Form A (allocable surplus) and B (set-on / set-off): figures
     * typed in for the accounting year.
     */
    private function bonusReturn(Company $company, Request $request, string $view): array
    {
        [$start, , $to] = $this->accountingYear($request);

        return [
            'startYear' => $start,
            'yearEnd' => $to,
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => $view, 'period' => $start.'-'.($start + 1)]),
        ];
    }

    /**
     * Bonus Form C: per employee, days worked, salary (basic + DA) and bonus
     * for the accounting year from the payslips. The bonus is what payroll
     * paid monthly; otherwise the employee's Bonus % (8.33%, the statutory
     * minimum, when none is set) of that salary.
     */
    private function bonusDetails(Company $company, Request $request): array
    {
        [$start, $from, $to] = $this->accountingYear($request);
        $payslips = Payslip::where('company_id', $company->id)->whereDate('month', '>=', $from)->whereDate('month', '<=', $to)->get()->groupBy('employee_id');
        $employees = $this->employees($company)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $to))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>=', $from))
            ->get();

        $rows = $employees->map(function (Employee $e) use ($payslips, $from) {
            $slips = $payslips[$e->id] ?? collect();
            $salary = round($slips->sum(fn (Payslip $p) => $p->amount('earnings', 'basic') + $p->amount('earnings', 'da')), 2);
            $paidMonthly = round($slips->sum(fn (Payslip $p) => $p->amount('earnings', 'bonus')), 2);
            $rate = (float) ($e->salaryElements->firstWhere('element', 'bonus')?->monthly ?: 8.33);

            return [
                'employee' => $e,
                'over15' => $e->date_of_birth ? $e->date_of_birth->diffInYears($from) >= 15 : null,
                'days' => $slips->sum(fn (Payslip $p) => ($p->attendance['present'] ?? 0) + ($p->attendance['half_days'] ?? 0) / 2),
                'salary' => $salary,
                'payable' => $paidMonthly > 0 ? $paidMonthly : round($salary * $rate / 100, 2),
                'rate' => $paidMonthly > 0 ? null : $rate,
            ];
        });

        return [
            'startYear' => $start,
            'yearStart' => $from,
            'yearEnd' => $to,
            'workingDays' => Attendance::where('company_id', $company->id)->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)->distinct()->count('work_date'),
            'rows' => $rows,
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'bonus-form-c', 'period' => $start.'-'.($start + 1)]),
        ];
    }

    /**
     * ER-1: men and women on the rolls on the last day of the previous
     * quarter and of the quarter (or today, while the quarter is running).
     */
    private function quarterly(Company $company, Request $request): array
    {
        $year = $this->year($request);
        $quarter = (int) $request->query('quarter') ?: (int) ceil(date('n') / 3);
        $quarter = min(4, max(1, $quarter));
        $end = CarbonImmutable::create($year, $quarter * 3, 1)->endOfMonth()->startOfDay();
        $previous = $end->subMonthsNoOverflow(3)->endOfMonth()->startOfDay();
        $employees = $this->employees($company)->get();

        $count = function (CarbonImmutable $on) use ($employees): array {
            $on = $on->min(CarbonImmutable::today());
            $rolls = $employees->filter(fn (Employee $e) => (! $e->date_of_joining || $e->date_of_joining->lte($on)) && (! $e->separation_date || $e->separation_date->gt($on)));
            $women = $rolls->filter(fn (Employee $e) => $e->gender?->value === 'female')->count();

            return ['men' => $rolls->count() - $women, 'women' => $women, 'total' => $rolls->count()];
        };

        return [
            'year' => $year,
            'quarter' => $quarter,
            'quarterEnd' => $end,
            'previousEnd' => $previous,
            'running' => $end->isFuture(),
            'counts' => ['previous' => $count($previous), 'current' => $count($end)],
            'values' => ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => 'er-1', 'period' => $end->format('Y-m')]),
        ];
    }

    /**
     * Form 29: accidents typed in for the year.
     */
    private function accidents(Company $company, Request $request): array
    {
        $year = $this->year($request);

        return [
            'year' => $year,
            'month' => CarbonImmutable::create($year, 1, 1),
            'entries' => ComplianceRegisterEntry::where('company_id', $company->id)->where('form', 'form-29')->whereYear('month', $year)
                ->with('employee')->orderBy('month')->orderBy('id')->get(),
            'employeeOptions' => $this->employees($company)->current()->get(),
        ];
    }

    /**
     * Medical examination forms (5, 27-A, 30, 32): employees with their
     * examinations, and the one picked (by default the latest).
     */
    private function examinations(Company $company, Request $request): array
    {
        $examinations = MedicalExamination::where('company_id', $company->id)->orderByDesc('examined_on')->orderByDesc('id')->get()->groupBy('employee_id');
        $employees = $this->employees($company)
            ->where(fn ($q) => $q->current()->orWhereIn('id', $examinations->keys()))
            ->get()->each(fn (Employee $e) => $e->setRelation('examinations', $examinations[$e->id] ?? collect()));

        $employee = $employees->firstWhere('uuid', $request->query('employee'))
            ?? $employees->first(fn (Employee $e) => $e->examinations->isNotEmpty())
            ?? $employees->first();
        $examination = $employee?->examinations->firstWhere('uuid', $request->query('exam')) ?? $employee?->examinations->first();

        return ['employees' => $employees, 'employee' => $employee, 'examination' => $examination];
    }

    /** Age in whole years on a date, from the date of birth. */
    public static function age(Employee $employee, ?\DateTimeInterface $on = null): ?int
    {
        return $employee->date_of_birth ? (int) $employee->date_of_birth->diffInYears($on ?? today()) : null;
    }

    /**
     * Forms for one employee (Form 14, Gratuity Form F).
     */
    private function employeePicker(Company $company, Request $request, string $view): array
    {
        $employees = $this->employees($company)->get();
        $employee = $employees->firstWhere('uuid', $request->query('employee')) ?? $employees->first();

        return [
            'employees' => $employees,
            'employee' => $employee,
            'values' => $employee && $view !== 'form-14'
                ? ComplianceFormValue::firstOrNew(['company_id' => $company->id, 'form' => $view, 'period' => 'employee:'.$employee->id])
                : null,
        ];
    }
}
