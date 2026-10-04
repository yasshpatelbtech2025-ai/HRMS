<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-off earning (e.g. arrears) or deduction (e.g. canteen) for a month.
 */
#[Fillable(['company_id', 'employee_id', 'title', 'amount', 'salary_month', 'type'])]
class SalaryAdjustment extends Model
{
    use HasUuids, Trashable;

    public const TYPES = ['deduction' => 'Deduction', 'earning' => 'Earning'];

    /** Titles offered before any have been typed, as in the old system. */
    public const TITLES = ['Other Deduction', 'Canteen Allowance', 'Fuel Allowance', 'Late Come Penalty', 'Ticket Allowance', 'ARREARS'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'salary_month' => 'date'];
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
}
