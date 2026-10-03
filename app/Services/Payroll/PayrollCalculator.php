<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\SalaryAdjustment;
use App\Models\SalaryAdvance;
use Carbon\CarbonImmutable;

/**
 * Works out one employee's salary for a month from their salary structure,
 * the month's attendance and the company's payroll settings.
 *
 * Paid days
 *   P + ½·HD + COFF + paid leave, plus week-offs and holidays. Daily-rate
 *   employees are paid week-offs and holidays only when their shift says so.
 *   LOP, unpaid leave and days outside employment are not paid.
 *
 * Earnings (monthly salary), by the employee's "Monthly salary calculation type":
 *   Calendar month       element × paid days ÷ days in month
 *   Every month 30 days  element × (30 − unpaid days) ÷ 30
 *   Exclude weekly offs  element × (paid days − week-offs) ÷ (days in month − week-offs)
 * Daily-rate employees' elements are per day (× days paid); hourly-rate
 * employees get rate × hours worked. Performance and joining bonus are paid
 * in full; Bonus % is a share of earned basic when bonus is paid monthly.
 * Overtime: hours × (basic or gross per hour, per the shift) × 1 or 2;
 * public-holiday overtime uses the shift's holiday overtime rule.
 *
 * Deductions (the old system's thresholds by organisation size)
 *   PF   12% of earned basic or gross (Configs › Payroll), for organisations
 *        over 20 people, when the full monthly amount is ₹15,000 or less
 *   ESI  0.75% of earned gross + overtime, for organisations over 10 people,
 *        when the full monthly gross is ₹21,000 or less (employer 3.25%)
 *   PT   ₹200 when earned gross is ₹12,000 or more, organisations over 20
 *   LWF  ₹6 employee / ₹12 employer in June and December, organisations
 *        over 10 (all ₹18 by the employer when it pays the employee's share)
 *   VPF % of earned basic, notice period recovery, salary advances due,
 *   loan installments and salary adjustments.
 *   TDS when the company deducts tax (Configs › Payroll): see IncomeTax.
 */
class PayrollCalculator
{
    /** Gross elements paid in full rather than pro-rated. */
    private const FIXED_ELEMENTS = ['performance_bonus', 'joining_bonus'];

    public function __construct(private IncomeTax $incomeTax) {}

    /**
     * @param  array{days: array<string, array>, totals: array<string, float>}  $attendance  from MonthlyAttendance
     * @return array{attendance: array, earnings: list<array>, deductions: list<array>, employer: list<array>, gross: float, deduction_total: float, net: float}
     */
    public function calculate(Employee $employee, CarbonImmutable $month, array $attendance, PayrollSetting $setting, int $organisationSize): array
    {
        $shift = $employee->shift;
        $elements = $employee->salaryElements->keyBy('element');
        $monthly = fn (string $e) => (float) ($elements[$e]->monthly ?? 0);
        $daysInMonth = $month->daysInMonth;
        $days = collect($attendance['days']);

        // Attendance counts for the month.
        $count = fn (string $status) => $days->where('status', $status)->count();
        $present = $count('P');
        $halfDays = $count('HD');
        $weekOffs = $count('WO');
        $holidays = $count('PH');
        $compOffs = $count('COFF');
        $paidLeave = $days->where('status', 'LEAVE')->where('leavePaid', true)->count();
        $unpaidLeave = $count('LEAVE') - $paidLeave;
        $lop = $count('A');
        $notEmployed = $days->whereNull('status')->count();
        $weekOffDays = $days->filter(fn ($d) => $d['status'] && $d['weeklyOff'])->count(); // incl. week-offs worked
        $otHours = round($days->sum('ot'), 2);
        $holidayOtHours = round($days->filter(fn ($d) => $d['holiday'] && $d['status'] === 'P')->sum('ot'), 2);
        $hoursWorked = round($days->sum('hours'), 2);
        $paysOffDays = $employee->pay_frequency === 'monthly' || ($shift?->pay_daily_rate_on_wo_ph ?? false);
        $paidDays = $present + $halfDays / 2 + $compOffs + $paidLeave + ($paysOffDays ? $weekOffs + $holidays : 0);
        $unpaidDays = $daysInMonth - $paidDays;

        // Earnings.
        $earnings = [];
        $grossElements = array_diff(array_keys(config('employee.gross_elements')), ['bonus']);

        if ($employee->pay_frequency === 'hourly') {
            $rate = (float) $employee->hourly_rate;
            $earnings[] = $this->line('basic', 'BASIC', $rate * ($hoursWorked + $paidLeave * (float) ($employee->daily_hour ?: 8)));
        } else {
            $factor = match (true) {
                $employee->pay_frequency === 'daily' => null,
                $employee->monthly_salary_calculation === 'thirty_days' => max(0, 30 - $unpaidDays) / 30,
                $employee->monthly_salary_calculation === 'exclude_weekly_offs' => ($daysInMonth - $weekOffDays) > 0
                    ? max(0, $paidDays - $weekOffDays) / ($daysInMonth - $weekOffDays) : 0,
                default => $paidDays / $daysInMonth,
            };

            if ($employee->pay_frequency === 'daily' && ! $elements->has('basic') && $employee->daily_rate) {
                $earnings[] = $this->line('basic', 'BASIC', (float) $employee->daily_rate * $paidDays);
            }

            foreach ($grossElements as $element) {
                $amount = $monthly($element);
                if ($amount <= 0) {
                    continue;
                }
                $earned = match (true) {
                    in_array($element, self::FIXED_ELEMENTS, true) => $amount,
                    $factor === null => $amount * $paidDays, // daily rate per element
                    default => $amount * $factor,
                };
                $earnings[] = $this->line($element, config("employee.gross_elements.{$element}"), $earned);
            }
        }

        $earnedBasic = $this->sum($earnings, ['basic']);

        if ($setting->bonus_frequency === 'monthly' && $monthly('bonus') > 0) {
            $earnings[] = $this->line('bonus', 'BONUS ('.rtrim(rtrim(number_format($monthly('bonus'), 2), '0'), '.').'% of Basic)', $earnedBasic * $monthly('bonus') / 100);
        }

        // Overtime, priced per hour from basic or gross.
        if ($otHours > 0 && $shift) {
            $perHour = fn (string $basis) => $this->hourlyRate($employee, $basis === 'gross'
                ? collect(config('employee.gross_salary_elements'))->sum($monthly)
                : ($monthly('basic') ?: (float) $employee->daily_rate), $daysInMonth);
            $regular = $otHours - $holidayOtHours;
            $otPay = $regular * $perHour($shift->ot_effect_on) * ($shift->ot_rate === 'double' ? 2 : 1)
                + $holidayOtHours * $perHour($shift->ph_ot_effect_on) * ($shift->ph_ot_rate === 'double' ? 2 : 1);
            $earnings[] = $this->line('overtime', "OVERTIME ({$otHours} hrs)", $otPay);
        }

        // Variable bonus rules for the month.
        $earnedGross = $this->sum($earnings, config('employee.gross_salary_elements'));
        foreach (PayrollRule::where('company_id', $employee->company_id)->where('is_active', true)->whereDate('month', $month)->get() as $rule) {
            $amount = $rule->is_percent ? ($rule->effect_on === 'gross' ? $earnedGross : $earnedBasic) * (float) $rule->amount / 100 : (float) $rule->amount;
            $earnings[] = $this->line("rule:{$rule->uuid}", $rule->name, $amount);
        }

        $adjustments = SalaryAdjustment::where('employee_id', $employee->id)->whereDate('salary_month', $month)->get();
        foreach ($adjustments->where('type', 'earning') as $adjustment) {
            $earnings[] = $this->line("adjustment:{$adjustment->uuid}", $adjustment->title, (float) $adjustment->amount);
        }

        $gross = round(array_sum(array_column($earnings, 'amount')), 2);
        $overtime = $this->sum($earnings, ['overtime']);

        // Deductions.
        $deductions = [];
        $employer = [];
        $fullBasic = $employee->pay_frequency === 'monthly' ? $monthly('basic') : ($monthly('basic') ?: (float) $employee->daily_rate) * 26;
        $fullGross = $employee->pay_frequency === 'monthly'
            ? collect(config('employee.gross_salary_elements'))->sum($monthly)
            : $earnedGross;

        $pfOnGross = $setting->pf_effect_on === 'gross';
        $pfBase = $pfOnGross ? $earnedGross : $earnedBasic;
        if ($organisationSize > 20 && ($pfOnGross ? $fullGross : $fullBasic) <= 15000 && $pfBase > 0) {
            $pf = round($pfBase * 0.12);
            $deductions[] = $this->line('pf', 'PF', $pf);
            $eps = min(round($pfBase * 0.0833), 1250);
            $employer[] = $this->line('employer_eps', 'Employer EPS (8.33%)', $eps);
            $employer[] = $this->line('employer_epf', 'Employer EPF (3.67%)', $pf - $eps);
        }

        $esiBase = ($setting->esi_effect_on === 'basic' ? $earnedBasic : $earnedGross) + $overtime;
        if ($organisationSize > 10 && $fullGross <= 21000 && $esiBase > 0) {
            $deductions[] = $this->line('esi', 'Employee State Insurance (ESI)', ceil($esiBase * 0.0075));
            $employer[] = $this->line('employer_esi', 'Employer ESI (3.25%)', ceil($esiBase * 0.0325));
        }

        if ($organisationSize > 20 && $gross >= 12000) {
            $deductions[] = $this->line('pt', 'PROF TAX', 200);
        }

        if ($organisationSize > 10 && in_array($month->month, [6, 12], true)) {
            if ($setting->lwf_paid_by_company) {
                $employer[] = $this->line('employer_lwf', 'Employer LWF (incl. employee share)', 18);
            } else {
                $deductions[] = $this->line('lwf', 'LABOUR WELFARE FUND', 6);
                $employer[] = $this->line('employer_lwf', 'Employer LWF', 12);
            }
        }

        if ($monthly('vpf') > 0) {
            $deductions[] = $this->line('vpf', 'Voluntary Provident Fund (VPF)', $earnedBasic * $monthly('vpf') / 100);
        }
        if ($monthly('notice_period_recovery') > 0) {
            $deductions[] = $this->line('notice_period_recovery', 'NOTICE PERIOD RECOVERY', $monthly('notice_period_recovery'));
        }

        foreach (SalaryAdvance::where('employee_id', $employee->id)->where('is_active', true)->whereDate('salary_month', $month)->get() as $advance) {
            $due = (float) $advance->amount - (float) $advance->released_amount;
            if ($due > 0) {
                $deductions[] = $this->line("advance:{$advance->uuid}", 'SALARY ADVANCE', $due);
            }
        }

        foreach (Loan::where('employee_id', $employee->id)->where('is_active', true)->get() as $loan) {
            $installment = $loan->installmentFor($month);
            if ($installment > 0) {
                $deductions[] = $this->line("loan:{$loan->uuid}", "LOAN {$loan->loan_no} (".($loan->installments_cleared + 1)."/{$loan->installment_period})", $installment);
            }
        }

        foreach ($adjustments->where('type', 'deduction') as $adjustment) {
            $deductions[] = $this->line("adjustment:{$adjustment->uuid}", $adjustment->title, (float) $adjustment->amount);
        }

        // TDS: the year's estimated tax still due, spread over the months left (or the amount set in Payroll › TDS).
        if ($setting->tax_deduction) {
            $tds = $this->incomeTax->project($employee, $month, [
                'gross' => $gross, 'pt' => $this->sum($deductions, ['pt']), 'pf' => $this->sum($deductions, ['pf', 'vpf']),
            ])['monthly_tds'];
            if ($tds > 0) {
                $deductions[] = $this->line('tds', 'TDS (INCOME TAX)', $tds);
            }
        }

        $deductionTotal = round(array_sum(array_column($deductions, 'amount')), 2);

        return [
            'attendance' => [
                'days_in_month' => $daysInMonth, 'paid_days' => $paidDays, 'present' => $present, 'half_days' => $halfDays,
                'lop' => $lop, 'week_offs' => $weekOffs, 'holidays' => $holidays, 'paid_leave' => $paidLeave,
                'unpaid_leave' => $unpaidLeave, 'comp_offs' => $compOffs, 'not_employed' => $notEmployed,
                'hours' => $hoursWorked, 'ot_hours' => $otHours,
            ],
            'earnings' => $earnings,
            'deductions' => $deductions,
            'employer' => $employer,
            'gross' => $gross,
            'deduction_total' => $deductionTotal,
            'net' => round($gross - $deductionTotal, 2),
        ];
    }

    /**
     * Per-hour value of a monthly (or, for daily-rate staff, daily) amount.
     */
    private function hourlyRate(Employee $employee, float $amount, int $daysInMonth): float
    {
        $hours = (float) ($employee->daily_hour ?: 8);

        return match ($employee->pay_frequency) {
            'hourly' => (float) $employee->hourly_rate,
            'daily' => $amount / $hours,
            default => $amount / $daysInMonth / $hours,
        };
    }

    private function line(string $key, string $label, float $amount): array
    {
        return ['key' => $key, 'label' => $label, 'amount' => round($amount, 2)];
    }

    private function sum(array $lines, array $keys): float
    {
        return round(collect($lines)->whereIn('key', $keys)->sum('amount'), 2);
    }
}
