<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's salary for a month: attendance used, earnings, deductions,
 * employer contributions and net pay.
 */
#[Fillable([
    'salary_sheet_id', 'company_id', 'employee_id', 'month', 'pay_frequency', 'attendance', 'earnings', 'deductions', 'employer',
    'gross_earnings', 'total_deductions', 'net_pay',
])]
class Payslip extends Model
{
    use Auditable, BelongsToCompany, HasUuids;

    protected function casts(): array
    {
        return [
            'month' => 'date', 'attendance' => 'array', 'earnings' => 'array', 'deductions' => 'array', 'employer' => 'array',
            'gross_earnings' => 'decimal:2', 'total_deductions' => 'decimal:2', 'net_pay' => 'decimal:2',
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

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SalarySheet::class, 'salary_sheet_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /** The amount of one line (earning, deduction or employer) by key. */
    public function amount(string $list, string $key): float
    {
        return (float) (collect($this->{$list})->firstWhere('key', $key)['amount'] ?? 0);
    }
}
