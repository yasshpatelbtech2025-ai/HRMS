<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A department's shift rotation for one month.
 */
#[Fillable(['company_id', 'sub_department_id', 'month', 'parts', 'iteration'])]
class ShiftRoster extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return ['month' => 'date', 'parts' => 'array', 'iteration' => 'integer'];
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ShiftRosterEntry::class);
    }
}
