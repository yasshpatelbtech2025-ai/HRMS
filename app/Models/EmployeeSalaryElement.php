<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['element', 'monthly', 'yearly'])]
class EmployeeSalaryElement extends Model
{
    protected function casts(): array
    {
        return ['monthly' => 'decimal:2', 'yearly' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
