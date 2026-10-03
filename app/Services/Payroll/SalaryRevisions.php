<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\SalaryRevision;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Applies salary revisions: once a revision's effective month has started
 * its revised elements replace the employee's salary structure.
 */
class SalaryRevisions
{
    /**
     * The employee's salary elements as element => [monthly, yearly].
     *
     * @return array<string, array{monthly: float, yearly: float}>
     */
    public function elementsOf(Employee $employee): array
    {
        return $employee->salaryElements()->get()
            ->mapWithKeys(fn ($e) => [$e->element => ['monthly' => (float) $e->monthly, 'yearly' => (float) $e->yearly]])
            ->all();
    }

    /**
     * Yearly gross: the gross elements that make up salary (not bonus % or
     * deductions).
     */
    public function yearlyGross(array $elements): float
    {
        return round(collect(config('employee.gross_salary_elements'))->sum(fn ($e) => (float) ($elements[$e]['yearly'] ?? 0)), 2);
    }

    public function apply(SalaryRevision $revision): void
    {
        DB::transaction(function () use ($revision) {
            $employee = $revision->employee;
            $employee->salaryElements()->delete();

            foreach ($revision->revised_elements as $element => $amounts) {
                if ((float) ($amounts['monthly'] ?? 0) > 0 || (float) ($amounts['yearly'] ?? 0) > 0) {
                    $employee->salaryElements()->create(['element' => $element, 'monthly' => $amounts['monthly'] ?? 0, 'yearly' => $amounts['yearly'] ?? 0]);
                }
            }

            $revision->update(['status' => 'applied']);
        });
    }

    /**
     * Apply every pending revision whose effective month has started.
     */
    public function applyDue(): int
    {
        $due = SalaryRevision::where('status', 'pending')
            ->whereDate('effective_from', '<=', CarbonImmutable::today()->startOfMonth())
            ->orderBy('effective_from')->orderBy('id')->get();

        $due->each(fn (SalaryRevision $r) => $this->apply($r));

        return $due->count();
    }
}
