<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A leave application. Once approved, the approved dates count as LEAVE on
 * the monthly attendance.
 */
#[Fillable([
    'company_id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'reason', 'hard_copy_path', 'status',
    'approved_start_date', 'approved_end_date', 'approved_days', 'approval_description', 'approved_by', 'decided_at',
])]
class LeaveApplication extends Model
{
    use HasFactory, HasUuids;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_start_date' => 'date',
            'approved_end_date' => 'date',
            'days' => 'decimal:1',
            'approved_days' => 'decimal:1',
            'decided_at' => 'datetime',
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

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved leave overlapping the given dates.
     */
    public function scopeApprovedBetween(Builder $query, $from, $to): void
    {
        $query->where('status', self::APPROVED)
            ->whereDate('approved_start_date', '<=', $to)
            ->whereDate('approved_end_date', '>=', $from);
    }
}
