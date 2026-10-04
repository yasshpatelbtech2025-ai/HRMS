<?php

namespace App\Models\Scopes;

use App\Models\Company;
use App\Support\CompanyAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits a company-owned model to the companies the signed-in user may see
 * (App\Support\CompanyAccess) - lists, search, exports and route model
 * binding alike, so a guessed URL to another company's record is a 404.
 */
class CompanyAccessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $ids = CompanyAccess::restrictedTo();
        if ($ids === null) {
            return;
        }

        $builder->whereIn($model instanceof Company ? $model->getQualifiedKeyName() : $model->qualifyColumn('company_id'), $ids);
    }
}
