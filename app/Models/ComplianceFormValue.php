<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fill-in fields and the signed copy of a compliance return for a period.
 */
#[Fillable(['company_id', 'form', 'period', 'data', 'signed_copy_path', 'signed_copy_name'])]
class ComplianceFormValue extends Model
{
    use Auditable, BelongsToCompany;

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function value(int|string $key): ?string
    {
        return $this->data[$key] ?? null;
    }
}
