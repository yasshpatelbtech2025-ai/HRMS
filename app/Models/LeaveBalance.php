<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Adjustments to an employee's balance of one leave type for a year.
 */
#[Fillable(['employee_id', 'leave_type_id', 'year', 'total', 'used_adjustment'])]
class LeaveBalance extends Model
{
    protected function casts(): array
    {
        return ['total' => 'decimal:1', 'used_adjustment' => 'decimal:1', 'year' => 'integer'];
    }
}
