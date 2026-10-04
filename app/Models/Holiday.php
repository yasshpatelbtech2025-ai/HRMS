<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A holiday on the master Holiday list. Companies observe the ones chosen
 * for them on the Holiday tab.
 */
#[Fillable(['name', 'start_date', 'end_date'])]
class Holiday extends Model
{
    use Auditable, HasFactory, HasUuids, Trashable;

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    protected function totalDays(): Attribute
    {
        return Attribute::get(fn () => (int) $this->start_date->diffInDays($this->end_date) + 1);
    }

    /**
     * Holidays that fall (at least partly) in the given year.
     */
    public function scopeInYear(Builder $query, int $year): void
    {
        $query->whereDate('start_date', '<=', "{$year}-12-31")->whereDate('end_date', '>=', "{$year}-01-01");
    }
}
