<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A day taken off against compensatory-off balance (COFF).
 */
#[Fillable(['employee_id', 'work_date'])]
class CompOffUse extends Model
{
    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }
}
