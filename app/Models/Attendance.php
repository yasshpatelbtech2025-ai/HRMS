<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attendance punch: an in or out time and the day it counts towards.
 */
#[Fillable([
    'company_id', 'employee_id', 'punched_at', 'work_date', 'type', 'source', 'device', 'shift_id', 'shift_code',
    'is_night_shift', 'is_missing', 'is_locked',
])]
class Attendance extends Model
{
    use HasFactory, HasUuids;

    public const IN = 'in';

    public const OUT = 'out';

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'work_date' => 'date',
            'is_night_shift' => 'boolean',
            'is_missing' => 'boolean',
            'is_locked' => 'boolean',
        ];
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function isIn(): bool
    {
        return $this->type === self::IN;
    }
}
