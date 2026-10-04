<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One upload of the employee Excel import, processed in the background by
 * App\Jobs\ImportEmployees.
 */
class EmployeeImportRun extends Model
{
    use BelongsToCompany, HasUuids;

    protected $table = 'employee_imports';

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return ['errors' => 'array', 'finished_at' => 'datetime', 'imported' => 'integer'];
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

    public function isFinished(): bool
    {
        return in_array($this->status, ['done', 'invalid', 'failed'], true);
    }
}
