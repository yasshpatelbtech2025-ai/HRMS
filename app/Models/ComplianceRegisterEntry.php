<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row typed into a compliance register (Form C, 20 or 21).
 */
#[Fillable(['company_id', 'form', 'month', 'employee_id', 'data'])]
class ComplianceRegisterEntry extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['month' => 'date', 'data' => 'array'];
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
