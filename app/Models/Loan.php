<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\Trashable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A loan to an employee, repaid in monthly installments from salary.
 */
#[Fillable([
    'company_id', 'employee_id', 'permitted_by_id', 'loan_no', 'details', 'amount', 'interest_rate', 'installment_period',
    'repayment_amount', 'installment', 'installments_cleared', 'approved_date', 'repayment_start', 'attachment_path', 'is_active',
])]
class Loan extends Model
{
    use BelongsToCompany, HasUuids, Trashable;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'interest_rate' => 'decimal:2', 'repayment_amount' => 'decimal:2', 'installment' => 'decimal:2',
            'installment_period' => 'integer', 'installments_cleared' => 'integer',
            'approved_date' => 'date', 'repayment_start' => 'date', 'is_active' => 'boolean',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function permittedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'permitted_by_id')->withTrashed();
    }

    /**
     * Total repayable (principal plus flat interest) and the monthly
     * installment, as the old system worked them out.
     *
     * @return array{repayment: int, installment: int}
     */
    public static function schedule(float $amount, float $interestRate, int $period): array
    {
        $repayment = (int) round($amount + $amount * $interestRate / 100);

        return ['repayment' => $repayment, 'installment' => $period > 0 ? (int) round($repayment / $period) : $repayment];
    }

    public function isRepaid(): bool
    {
        return $this->installments_cleared >= $this->installment_period;
    }

    /** The installment due in a month, or 0 if none is due. */
    public function installmentFor(CarbonImmutable $month): float
    {
        $due = $this->is_active && ! $this->isRepaid() && $month->gte($this->repayment_start->startOfMonth());

        return $due ? (float) min($this->installment, $this->repayment_amount - $this->installment * $this->installments_cleared) : 0.0;
    }
}
