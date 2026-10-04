<?php

namespace App\Http\Controllers\Concerns;

use App\Support\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared behaviour for the DataTables-style list pages: "Show N entries"
 * and case-insensitive search.
 */
trait ListsRecords
{
    /**
     * The requested page size, or the first option. With $allowAll, "all"
     * shows every row on one page.
     */
    protected function perPage(Request $request, bool $allowAll = false): int
    {
        if ($allowAll && $request->query('per_page') === 'all') {
            return PageSize::ALL;
        }

        return in_array($request->integer('per_page'), PageSize::OPTIONS, true) ? $request->integer('per_page') : PageSize::OPTIONS[0];
    }

    /**
     * Sort by the requested column (?sort=key&direction=asc|desc, from
     * <x-sort-header>) when it is one of $sortable (key => column, or a
     * callback receiving the query and direction); otherwise apply $default.
     * Only listed keys are accepted, so a URL can't sort by any column.
     *
     * @param  array<string, string|callable>  $sortable
     */
    protected function sort(Builder $query, Request $request, array $sortable, callable $default): Builder
    {
        $key = $request->query('sort');
        if (! is_string($key) || ! array_key_exists($key, $sortable)) {
            $default($query);

            return $query;
        }

        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $column = $sortable[$key];
        is_callable($column) ? $column($query, $direction) : $query->orderBy($column, $direction);

        // Ties keep a stable order across pages.
        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /**
     * A sort callback by the related employee's name, for lists of records
     * that belong to an employee.
     */
    protected function byEmployeeName(string $table): callable
    {
        return fn (Builder $query, string $direction) => $query
            ->orderBy(\App\Models\Employee::withTrashed()->select('first_name')->whereColumn('employees.id', "{$table}.employee_id"), $direction)
            ->orderBy(\App\Models\Employee::withTrashed()->select('last_name')->whereColumn('employees.id', "{$table}.employee_id"), $direction);
    }

    /**
     * Constrain $query to rows where any of $columns contains the request's
     * search term, ignoring case. Dotted columns search a relation, e.g.
     * "department.name".
     *
     * @param  list<string>  $columns
     */
    protected function search(Builder $query, Request $request, array $columns): Builder
    {
        if (blank($term = $request->query('search'))) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower(trim($term))).'%';

        return $query->where(function (Builder $q) use ($columns, $like) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $q->orWhereHas($relation, fn (Builder $r) => $r->whereRaw("lower({$field}) like ?", [$like]));
                } else {
                    $q->orWhereRaw("lower({$column}) like ?", [$like]);
                }
            }
        });
    }
}
