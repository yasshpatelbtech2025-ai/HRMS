<?php

namespace App\Services\Payroll;

use App\Models\BonusEntry;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\TdsOverride;
use Carbon\CarbonImmutable;

/**
 * TDS on salary: estimates an employee's tax for the financial year (April
 * to March) from what they have been paid so far, their salary for the
 * months left and any yearly bonus, under their regime (config/tds.php),
 * and spreads the tax not yet deducted over the months left. A monthly
 * amount set by hand (Payroll › TDS) is used instead when there is one.
 */
class IncomeTax
{
    /** The financial year a month falls in, by its starting year. */
    public static function fy(CarbonImmutable $month): int
    {
        return $month->month >= 4 ? $month->year : $month->year - 1;
    }

    /**
     * Tax on a year's taxable income: slabs, the section 87A rebate (with
     * marginal relief just above it in the new regime) and cess.
     */
    public function tax(float $taxable, string $regime): float
    {
        $regime = $regime === 'old' ? 'old' : 'new';
        $tax = 0.0;
        $lower = 0.0;
        foreach (config("tds.slabs.{$regime}") as [$upper, $rate]) {
            if ($taxable > $lower) {
                $tax += (min($taxable, $upper ?? INF) - $lower) * $rate / 100;
            }
            $lower = (float) $upper;
            if ($upper === null) {
                break;
            }
        }

        $rebate = config("tds.rebate.{$regime}");
        if ($taxable <= $rebate['limit']) {
            $tax = max(0, $tax - $rebate['max']);
        } elseif ($regime === 'new') {
            $tax = min($tax, $taxable - $rebate['limit']); // marginal relief
        }

        return round($tax * (1 + config('tds.cess') / 100));
    }

    /**
     * The year's estimate for an employee as of a month.
     *
     * @param  array{gross: float, pt: float, pf: float}|null  $current  the month being paid (payroll); null for the TDS report, which projects from the last payslip
     * @return array<string, mixed>
     */
    public function project(Employee $employee, CarbonImmutable $month, ?array $current = null): array
    {
        $fy = self::fy($month);
        $start = CarbonImmutable::create($fy, 4, 1);
        $end = CarbonImmutable::create($fy + 1, 3, 1);
        $regime = $employee->tax_regime === 'old' ? 'old' : 'new';

        $earlier = Payslip::where('employee_id', $employee->id)->whereDate('month', '>=', $start)
            ->whereDate('month', $current ? '<' : '<=', $current ? $month : $end)->orderBy('month')->get();
        $sum = fn (string $list, array $keys) => $earlier->sum(fn (Payslip $p) => collect($keys)->sum(fn ($k) => $p->amount($list, $k)));
        $paidGross = (float) $earlier->sum('gross_earnings');
        $paidTds = $sum('deductions', ['tds']);

        // Months still to be paid this year (including the current one in payroll).
        $from = $current ? $month : ($earlier->last()?->month ? CarbonImmutable::parse($earlier->last()->month)->addMonthNoOverflow() : $month->max($start));
        $monthsLeft = $from->gt($end) ? 0 : (int) $from->diffInMonths($end) + 1;
        $monthly = $this->monthlyGross($employee);
        $restGross = $current ? $current['gross'] + $monthly * max(0, $monthsLeft - 1) : $monthly * $monthsLeft;
        $bonus = (float) BonusEntry::where('employee_id', $employee->id)->whereHas('sheet', fn ($q) => $q->where('fy', $fy))->sum('amount');

        $gross = round($paidGross + $restGross + $bonus, 2);
        $standard = (float) config("tds.standard_deduction.{$regime}");
        $other = 0.0;
        if ($regime === 'old') {
            $pt = $sum('deductions', ['pt']) + ($current['pt'] ?? 200) * $monthsLeft;
            $pf = $sum('deductions', ['pf', 'vpf']) + ($current['pf'] ?? $earlier->last()?->amount('deductions', 'pf') ?? 0) * $monthsLeft;
            $other = $pt + min($pf, config('tds.section_80c'));
        }
        $taxable = max(0, round($gross - $standard - $other));
        $annualTax = $this->tax($taxable, $regime);
        $balance = max(0, $annualTax - $paidTds);
        $estimate = $monthsLeft > 0 ? round($balance / $monthsLeft) : 0;
        $override = TdsOverride::where('employee_id', $employee->id)->where('fy', $fy)->first();

        return [
            'fy' => $fy,
            'regime' => $regime,
            'paid_gross' => $paidGross,
            'paid_tds' => $paidTds,
            'months_left' => $monthsLeft,
            'monthly_gross' => $monthly,
            'bonus' => $bonus,
            'gross' => $gross,
            'standard' => $standard,
            'other' => $other,
            'taxable' => $taxable,
            'tax' => $annualTax,
            'balance' => $balance,
            'estimate' => $estimate,
            'override' => $override,
            'monthly_tds' => $override ? (float) $override->monthly_amount : $estimate,
        ];
    }

    /** The employee's full monthly gross (daily-rated: 26 days). */
    private function monthlyGross(Employee $employee): float
    {
        $elements = $employee->salaryElements->keyBy('element');
        $gross = collect(config('employee.gross_salary_elements'))->sum(fn ($e) => (float) ($elements[$e]->monthly ?? 0));

        return $employee->pay_frequency === 'monthly' || $gross > 0 ? $gross : (float) $employee->daily_rate * 26;
    }
}
