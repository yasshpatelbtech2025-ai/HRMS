<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to correct a day's in/out times. Approving it replaces that
 * day's punches with the requested times.
 */
#[Fillable(['company_id', 'employee_id', 'work_date', 'requested_in', 'requested_out', 'reason', 'status', 'decided_by', 'decided_at'])]
class AttendanceRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['work_date' => 'date', 'requested_in' => 'datetime', 'requested_out' => 'datetime', 'decided_at' => 'datetime'];
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

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
