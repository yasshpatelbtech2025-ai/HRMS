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
