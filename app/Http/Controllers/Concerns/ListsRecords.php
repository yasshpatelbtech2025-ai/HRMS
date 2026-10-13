<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;

/** Sorting and page size for list pages, read from ?sort=&dir=&per_page=. */
trait ListsRecords
{
    protected const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    /**
     * @param  array<string, string|\Closure>  $sortable  query key => column, or closure(Builder, dir)
     */
    protected function sorted(Builder $query, array $sortable, string $default): Builder
    {
        $key = array_key_exists((string) request('sort'), $sortable) ? request('sort') : $default;
        $dir = request('dir') === 'desc' ? 'desc' : 'asc';
        $column = $sortable[$key];

        return $column instanceof \Closure ? $column($query, $dir) : $query->orderBy($column, $dir);
    }

    protected function perPage(): int
    {
        return in_array(request()->integer('per_page'), self::PER_PAGE_OPTIONS, true) ? request()->integer('per_page') : 25;
    }
}
