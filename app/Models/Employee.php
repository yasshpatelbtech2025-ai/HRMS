<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(EmployeeFactory::class)]
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const ACTIVE = 'active';

    public const SEPARATED = 'separated';

    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    public const MARITAL_STATUSES = ['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'];

    protected $fillable = [
        'company_id', 'employee_code', 'department_id', 'position_id',
        'first_name', 'middle_name', 'last_name', 'father_name', 'gender', 'date_of_birth',
        'marital_status', 'mobile', 'email', 'address',
        'joining_date', 'status', 'separation_date', 'separation_reason', 'gross_salary',
        'aadhaar_number', 'pan_number', 'uan_number', 'esic_number',
        'bank_name', 'bank_account_number', 'ifsc_code',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'separation_date' => 'date',
            'gross_salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            $employee->employee_code = $employee->employee_code ?: static::nextCodeFor($employee->company_id);
        });
    }

    public static function nextCodeFor(int $companyId): string
    {
        $highest = static::withTrashed()->where('company_id', $companyId)->pluck('employee_code')
            ->filter(fn ($code) => ctype_digit((string) $code))->map(fn ($code) => (int) $code)->max() ?? 0;

        return str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function scopeSeparated(Builder $query): Builder
    {
        return $query->where('status', self::SEPARATED);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
