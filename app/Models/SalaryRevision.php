<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change to an employee's salary structure from an effective month.
 * Pending until that month arrives (or it is applied by hand), then the
 * revised elements become the employee's salary.
 */
#[Fillable([
    'company_id', 'employee_id', 'revised_on', 'effective_from', 'previous_yearly', 'revised_yearly',
    'previous_elements', 'revised_elements', 'remarks', 'status', 'created_by',
])]
class SalaryRevision extends Model
{
    use Auditable, BelongsToCompany, HasUuids;

    protected function casts(): array
    {
        return [
            'revised_on' => 'date', 'effective_from' => 'date', 'previous_yearly' => 'decimal:2', 'revised_yearly' => 'decimal:2',
            'previous_elements' => 'array', 'revised_elements' => 'array',
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

    /** Percentage change in yearly salary. */
    public function percentage(): float
    {
        return (float) $this->previous_yearly > 0 ? round(((float) $this->revised_yearly - (float) $this->previous_yearly) / (float) $this->previous_yearly * 100, 2) : 0.0;
    }
}
