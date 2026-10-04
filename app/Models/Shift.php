<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of a company's configured shifts (Configs › Attendance): its type and
 * slot, the timing of each part (e.g. FS/SS/NS for a rotational shift) and
 * the pay rules applied to employees on it.
 */
#[Fillable([
    'company_id', 'name', 'type', 'slot', 'timings', 'half_day_rule', 'wo_ph_work_pay', 'pay_daily_rate_on_wo_ph',
    'ot_effect_on', 'ot_rate', 'ph_ot_effect_on', 'ph_ot_rate', 'ot_after_hours', 'late_penalty_after_minutes',
    'pf_effect_on', 'esi_effect_on',
])]
class Shift extends Model
{
    use Auditable, BelongsToCompany, HasFactory;

    /** Rule fields and their defaults for a new shift. */
    public const RULE_DEFAULTS = [
        'half_day_rule' => 'slab',
        'wo_ph_work_pay' => 'coff',
        'pay_daily_rate_on_wo_ph' => false,
        'ot_effect_on' => 'basic',
        'ot_rate' => 'regular',
        'ph_ot_effect_on' => 'basic',
        'ph_ot_rate' => 'regular',
        'ot_after_hours' => null,
        'late_penalty_after_minutes' => null,
        'pf_effect_on' => 'basic',
        'esi_effect_on' => 'gross',
    ];

    protected function casts(): array
    {
        return [
            'timings' => 'array',
            'pay_daily_rate_on_wo_ph' => 'boolean',
            'ot_after_hours' => 'decimal:2',
            'late_penalty_after_minutes' => 'integer',
            'slot' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Part codes this shift runs, e.g. ['FS', 'SS', 'NS'].
     *
     * @return list<string>
     */
    public function partCodes(): array
    {
        return array_keys(config("attendance.shift_types.{$this->type}.parts", []));
    }

    /**
     * "Rotational Shift (FS 06:00–14:00, SS 14:00–23:00, NS 23:00–06:00)"
     */
    protected function summary(): Attribute
    {
        return Attribute::get(function () {
            $parts = collect($this->timings ?? [])
                ->filter(fn ($t) => filled($t['start'] ?? null))
                ->map(fn ($t, $code) => (count($this->timings) > 1 ? "{$code} " : '').substr($t['start'], 0, 5).'–'.substr($t['end'] ?? '', 0, 5));

            return $parts->isEmpty() ? $this->name : "{$this->name} ({$parts->implode(', ')})";
        });
    }
}
