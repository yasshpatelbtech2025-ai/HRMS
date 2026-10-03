<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A monthly TDS amount set by hand for an employee for a financial year.
 */
#[Fillable(['employee_id', 'fy', 'monthly_amount', 'note'])]
class TdsOverride extends Model
{
    protected function casts(): array
    {
        return ['monthly_amount' => 'decimal:2'];
    }
}
