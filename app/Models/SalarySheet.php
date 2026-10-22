<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company's generated salary for one month.
 */
#[Fillable(['company_id', 'month', 'generated_by', 'generated_at', 'status', 'approved_by', 'approved_at', 'paid_date', 'payable_days'])]
class SalarySheet extends Model
{
    use Auditable, BelongsToCompany, HasUuids;

    protected function casts(): array
    {
        return ['month' => 'date', 'generated_at' => 'datetime', 'approved_at' => 'datetime', 'paid_date' => 'date', 'payable_days' => 'array'];
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
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
