<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A compliance task on the To Do List: due on a date, pending until it is
 * completed by hand or automatically once the HRMS shows it is done.
 */
#[Fillable(['company_id', 'type', 'employee_id', 'period', 'title', 'category', 'department', 'due_on', 'priority', 'assigned_to', 'notes', 'completed_at', 'completed_by'])]
class ComplianceTask extends Model
{
    use BelongsToCompany, HasUuids, SoftDeletes;

    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'completed_at' => 'datetime'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** completed, overdue, week (due within 7 days) or upcoming. */
    public function status(): string
    {
        return match (true) {
            $this->completed_at !== null => 'completed',
            $this->due_on->lt(today()) => 'overdue',
            $this->due_on->lte(today()->addDays(7)) => 'week',
            default => 'upcoming',
        };
    }
}
