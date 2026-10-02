<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's row on a monthly shift roster: their weekly off and the
 * shift code (or WO) for each day.
 */
#[Fillable(['shift_roster_id', 'employee_id', 'weekly_off', 'is_manual', 'days'])]
class ShiftRosterEntry extends Model
{
    protected function casts(): array
    {
        return ['is_manual' => 'boolean', 'days' => 'array'];
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(ShiftRoster::class, 'shift_roster_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
