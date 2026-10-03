<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(EmployeeFactory::class)]
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Only ever filled from validated form data; the id, uuid and per-company
     * employee code are assigned by the app.
     */
    protected $guarded = ['id', 'uuid', 'employee_code'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
            'date_of_birth' => 'date',
            'date_of_joining' => 'date',
            'date_of_exit_prev_org' => 'date',
            'separation_date' => 'date',
            'id_card_assigned_on' => 'date',
            'works_on_hazardous_process' => 'boolean',
            'can_punch_in_hybrid_mode' => 'boolean',
            'permanent_address_is_present' => 'boolean',
            'is_disabled' => 'boolean',
            'no_of_kids' => 'integer',
            'hourly_rate' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'daily_hour' => 'decimal:2',
            // Identity and bank numbers are encrypted at rest.
            'aadhar_card_no' => 'encrypted',
            'pan_card_no' => 'encrypted',
            'passport_no' => 'encrypted',
            'driving_license_no' => 'encrypted',
            'account_number' => 'encrypted',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function reportingOfficer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_officer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(EmployeeQualification::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class)->orderBy('id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function salaryElements(): HasMany
    {
        return $this->hasMany(EmployeeSalaryElement::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    public function address(string $type): ?EmployeeAddress
    {
        return $this->addresses->firstWhere('type', $type);
    }

    public function isSeparated(): bool
    {
        return $this->separation_date !== null && $this->separation_date->lte(today());
    }

    /**
     * Employees still on the rolls: no separation date, or one in the future.
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>', today()));
    }

    /**
     * The next per-company employee id, e.g. "0007". Deleted employees keep
     * their ids so numbers are never reused.
     */
    public static function nextCodeFor(int $companyId): string
    {
        $last = static::withTrashed()->where('company_id', $companyId)->pluck('employee_code')
            ->map(fn ($code) => (int) preg_replace('/\D/', '', (string) $code))
            ->max() ?? 0;

        return str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
