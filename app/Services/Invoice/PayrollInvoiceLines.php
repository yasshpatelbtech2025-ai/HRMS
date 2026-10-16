<?php

namespace App\Services\Invoice;

use App\Models\Company;
use App\Models\Payslip;
use App\Models\SalarySheet;
use Carbon\CarbonImmutable;

/**
 * Suggested invoice lines from a company's salary sheet for a month, as on
 * the Excel invoice: manpower supply, public holidays, employer PF and ESIC
 * carry the service charge; overtime is added after it.
 *
 *   Manpower supply  earnings (without overtime) for days other than PH
 *   PH               earnings (without overtime) × PH days ÷ paid days
 *   Employer PF      PF wages × 13% (PF wages = employee PF ÷ 12%)
 *   Employer ESIC    the employer ESI (3.25%) on the payslips
 *   OT charges       overtime earnings
 *
 * Amounts are rounded to rupees, like the Excel; the admin can edit them.
 */
class PayrollInvoiceLines
{
    /** @return array{sheet: SalarySheet, lines: list<array{description: string, amount: float}>, extra_lines: list<array{description: string, amount: float}>}|null */
    public static function for(Company $company, CarbonImmutable $month): ?array
    {
        $sheet = SalarySheet::where('company_id', $company->id)->whereDate('month', $month->startOfMonth())->first();
        if (! $sheet) {
            return null;
        }

        $amount = fn (array $lines, string $key) => collect($lines)->where('key', $key)->sum('amount');
        $wages = $holidayPay = $pf = $esi = $overtime = 0.0;

        foreach ($sheet->payslips()->get() as $payslip) {
            /** @var Payslip $payslip */
            $ot = $amount($payslip->earnings, 'overtime');
            $earned = (float) $payslip->gross_earnings - $ot;
            $paidDays = (float) ($payslip->attendance['paid_days'] ?? 0);
            $ph = $paidDays > 0 ? $earned * (float) ($payslip->attendance['holidays'] ?? 0) / $paidDays : 0;

            $wages += $earned - $ph;
            $holidayPay += $ph;
            $pf += $amount($payslip->deductions, 'pf') / 0.12 * config('invoice.pf_rate') / 100;
            $esi += $amount($payslip->employer, 'employer_esi');
            $overtime += $ot;
        }

        $label = $month->format('M y');
        $line = fn (string $description, float $value) => ['description' => $description, 'amount' => round($value)];

        return [
            'sheet' => $sheet,
            'lines' => collect([
                $line("Manpower Supply Charges {$label}", $wages),
                $line("PH for {$label}", $holidayPay),
                $line('Employers PF Contribution @ '.config('invoice.pf_rate').'%', $pf),
                $line('Employers ESIC @ 3.25%', $esi),
            ])->filter(fn ($l) => $l['amount'] > 0)->values()->all(),
            'extra_lines' => collect([$line("Manpower Supply OT Charges {$label}", $overtime)])
                ->filter(fn ($l) => $l['amount'] > 0)->values()->all(),
        ];
    }
}
