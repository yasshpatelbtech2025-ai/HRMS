<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee's bonus on a yearly bonus sheet.
 */
#[Fillable(['bonus_sheet_id', 'employee_id', 'days_worked', 'salary', 'bonus_salary', 'rate', 'eligible', 'reason', 'calculated', 'amount'])]
class BonusEntry extends Model
{
    protected function casts(): array
    {
        return ['days_worked' => 'float', 'salary' => 'float', 'bonus_salary' => 'float', 'rate' => 'float', 'eligible' => 'boolean', 'calculated' => 'float', 'amount' => 'float'];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(BonusSheet::class, 'bonus_sheet_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
