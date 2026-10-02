<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company's general attendance settings (Configs › Attendance).
 */
#[Fillable(['company_id', 'punch_buffer_minutes'])]
class AttendanceSetting extends Model
{
    protected $attributes = ['punch_buffer_minutes' => 30];

    protected function casts(): array
    {
        return ['punch_buffer_minutes' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
