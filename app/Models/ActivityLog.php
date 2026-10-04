<?php

namespace App\Models;

use App\Models\Scopes\CompanyAccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in Configs › Activity log (admins only), written by
 * App\Support\Activity. Entries are never edited; they are removed after
 * config('access.activity_retention_days').
 */
class ActivityLog extends Model
{
    use MassPrunable;

    protected $table = 'activity_log';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public const EVENTS = [
        'created' => 'Created',
        'updated' => 'Changed',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'purged' => 'Deleted for good',
        'bulk' => 'Bulk action',
        'generated' => 'Generated',
        'exported' => 'Exported',
        'signed-in' => 'Signed in',
        'signed-out' => 'Signed out',
        'sign-in-failed' => 'Failed sign-in',
        'locked-out' => 'Locked out',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed()->withoutGlobalScope(CompanyAccessScope::class);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('access.activity_retention_days')));
    }

    /** "Salary Advance" for App\Models\SalaryAdvance. */
    public function subjectLabel(): ?string
    {
        return $this->subject_type ? \Illuminate\Support\Str::headline(class_basename($this->subject_type)) : null;
    }
}
