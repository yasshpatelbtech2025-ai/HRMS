<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payroll rule for a company and month, e.g. a variable bonus of a fixed
 * amount or a percentage of basic or gross salary.
 */
#[Fillable(['company_id', 'type', 'name', 'amount', 'month', 'is_percent', 'effect_on', 'is_active'])]
class PayrollRule extends Model
{
    use HasUuids, Trashable;

    public const TYPES = ['variable_bonus' => 'Variable Bonus'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'month' => 'date', 'is_percent' => 'boolean', 'is_active' => 'boolean'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
}
