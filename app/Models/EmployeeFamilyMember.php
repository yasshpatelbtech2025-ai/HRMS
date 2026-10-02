<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['relation', 'full_name', 'date_of_birth', 'aadhar_card_no', 'is_nominee'])]
class EmployeeFamilyMember extends Model
{
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'aadhar_card_no' => 'encrypted',
            'is_nominee' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
