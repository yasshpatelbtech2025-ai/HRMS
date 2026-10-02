<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'employee_code', 'first_name', 'last_name', 'email', 'phone', 'gender', 'date_of_birth',
    'date_of_joining', 'department', 'designation', 'employment_type', 'status', 'address',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'date_of_joining' => 'date',
            'employment_type' => EmploymentType::class,
            'status' => EmployeeStatus::class,
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Match name, code or email against a free-text search term.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], strtolower(trim($term))).'%';

        $query->where(fn (Builder $q) => $q
            ->whereRaw('lower(first_name) like ?', [$like])
            ->orWhereRaw('lower(last_name) like ?', [$like])
            ->orWhereRaw('lower(employee_code) like ?', [$like])
            ->orWhereRaw('lower(email) like ?', [$like]));
    }

    /**
     * The next sequential code, e.g. EMP0007. Trashed rows count so codes are never reused.
     */
    public static function nextCode(): string
    {
        $last = static::withTrashed()->where('employee_code', 'like', 'EMP%')->max('id') ?? 0;

        return sprintf('EMP%04d', $last + 1);
    }
}
