<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyAccessScope;
use App\Support\CompanyAccess;

/**
 * A record owned by a company: users only see their assigned companies'
 * records and cannot save one for another company (CompanyAccess).
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyAccessScope);

        static::saving(function ($model) {
            CompanyAccess::assertWritable($model instanceof Company ? $model->getKey() : $model->company_id);
            // Moving a record to another company needs access to the old one too.
            if (! $model instanceof Company && $model->exists && $model->isDirty('company_id')) {
                CompanyAccess::assertWritable($model->getOriginal('company_id'));
            }
        });
    }
}
