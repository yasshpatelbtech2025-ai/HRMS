<?php

namespace App\Services\Payroll;

use App\Models\BonusEntry;
use App\Models\BonusSheet;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Payroll › Bonus: a company's yearly bonus for an accounting year (April
 * to March) under the Payment of Bonus Act, from the year's payslips:
 * eligible employees (basic + DA up to the limit a month, enough days
 * worked) get their Bonus % (between the statutory minimum and maximum) of
 * basic + DA, capped at the calculation ceiling a month (config/bonus.php).
 */
class BonusSheets
{
    public function generate(Company $company, int $fy, User $user): BonusSheet
    {
        return DB::transaction(function () use ($company, $fy, $user) {
            $sheet = BonusSheet::firstOrCreate(['company_id' => $company->id, 'fy' => $fy], ['generated_by' => $user->id]);
            abort_if($sheet->isApproved(), 422, 'An approved bonus sheet cannot be regenerated.');
            $sheet->entries()->delete();

            foreach ($this->calculate($company, $fy) as $row) {
                $sheet->entries()->create($row);
            }
            $sheet->update(['generated_by' => $user->id]);

            return $sheet;
        });
    }

    /**
     * @return list<array<string, mixed>> one row per employee paid in the year
     */
    public function calculate(Company $company, int $fy): array
    {
        $payslips = Payslip::where('company_id', $company->id)
            ->whereDate('month', '>=', CarbonImmutable::create($fy, 4, 1))->whereDate('month', '<=', CarbonImmutable::create($fy + 1, 3, 1))
            ->with('employee.salaryElements')->get()->groupBy('employee_id');
        $ceiling = (float) config('bonus.calculation_ceiling');
        $rows = [];

        foreach ($payslips as $employeeId => $slips) {
            $employee = $slips->first()->employee;
            $wage = fn (Payslip $p) => $p->amount('earnings', 'basic') + $p->amount('earnings', 'da');
            $salary = round($slips->sum($wage), 2);
            $days = $slips->sum(fn (Payslip $p) => ($p->attendance['present'] ?? 0) + ($p->attendance['half_days'] ?? 0) / 2);
            $rate = (float) ($employee?->salaryElements->firstWhere('element', 'bonus')?->monthly ?: config('bonus.minimum_rate'));
            $rate = min(max($rate, config('bonus.minimum_rate')), config('bonus.maximum_rate'));
            $average = $salary / max(1, $slips->count());

            $reason = match (true) {
                $average > config('bonus.eligibility_salary') => __('Salary over :amount a month', ['amount' => number_format(config('bonus.eligibility_salary'))]),
                $days < config('bonus.eligibility_days') => __('Worked fewer than :days days', ['days' => config('bonus.eligibility_days')]),
                default => null,
            };
            $bonusSalary = round($slips->sum(fn (Payslip $p) => min($wage($p), $ceiling)), 2);
            $calculated = $reason ? 0.0 : round($bonusSalary * $rate / 100);

            $rows[] = [
                'employee_id' => $employeeId, 'days_worked' => $days, 'salary' => $salary, 'bonus_salary' => $bonusSalary,
                'rate' => $rate, 'eligible' => $reason === null, 'reason' => $reason, 'calculated' => $calculated, 'amount' => $calculated,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, float|string|null>  $amounts  entry id => amount
     */
    public function updateAmounts(BonusSheet $sheet, array $amounts): void
    {
        abort_if($sheet->isApproved(), 422, 'Unapprove the bonus sheet to change amounts.');
        foreach ($sheet->entries as $entry) {
            if (array_key_exists($entry->id, $amounts)) {
                $entry->update(['amount' => round((float) $amounts[$entry->id], 2)]);
            }
        }
    }

    public function approve(BonusSheet $sheet, User $user, ?string $paidOn): void
    {
        $sheet->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now(), 'paid_on' => $paidOn]);
    }

    public function unapprove(BonusSheet $sheet): void
    {
        $sheet->update(['status' => 'draft', 'approved_by' => null, 'approved_at' => null, 'paid_on' => null]);
    }

    /** An employee's yearly bonus, when the sheet is approved. */
    public static function paidTo(int $employeeId, int $fy): ?BonusEntry
    {
        return BonusEntry::where('employee_id', $employeeId)->whereHas('sheet', fn ($q) => $q->where('fy', $fy)->where('status', 'approved'))->with('sheet')->first();
    }
}
