<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money paid to an employee ahead of salary, recovered from a month's salary.
 */
#[Fillable(['company_id', 'employee_id', 'amount', 'released_amount', 'salary_month', 'advance_date', 'attachment_path', 'is_active'])]
class SalaryAdvance extends Model
{
    use HasUuids, Trashable;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'released_amount' => 'decimal:2', 'salary_month' => 'date', 'advance_date' => 'date', 'is_active' => 'boolean'];
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
