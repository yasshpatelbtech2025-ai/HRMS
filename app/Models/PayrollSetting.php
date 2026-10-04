<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company's payroll settings (Configs › Payroll).
 */
#[Fillable([
    'company_id', 'probation_period_months', 'notice_period_days', 'salary_structure', 'bonus_frequency',
    'tax_deduction', 'lwf_paid_by_company', 'pf_effect_on', 'esi_effect_on',
])]
class PayrollSetting extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = [
        'salary_structure' => 'old',
        'bonus_frequency' => 'yearly',
        'tax_deduction' => false,
        'lwf_paid_by_company' => false,
        'pf_effect_on' => 'basic',
        'esi_effect_on' => 'gross',
    ];

    protected function casts(): array
    {
        return [
            'probation_period_months' => 'integer',
            'notice_period_days' => 'integer',
            'tax_deduction' => 'boolean',
            'lwf_paid_by_company' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
