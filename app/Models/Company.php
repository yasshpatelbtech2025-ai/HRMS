<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(CompanyFactory::class)]
class Company extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'establishment_name',
        'parent_company_id',
        'address_line_1',
        'address_line_2',
        'village',
        'taluka',
        'district',
        'state',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Whether this company is $company itself or sits anywhere beneath it
     * in the connected-company chain.
     */
    public function isWithin(Company $company): bool
    {
        for ($current = $this, $seen = []; $current && ! isset($seen[$current->id]); $current = $current->parent) {
            if ($current->is($company)) {
                return true;
            }

            $seen[$current->id] = true;
        }

        return false;
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('name');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Name shown in pickers and lists, e.g. "PI Industry [Srympex]".
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn () => $this->parent ? "{$this->establishment_name} [{$this->parent->establishment_name}]" : $this->establishment_name);
    }
}
