<?php

namespace App\Models\Concerns;

use App\Support\RecentlyDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Deleted records go to Configs › Recently deleted and can be restored for
 * RecentlyDeleted::RETENTION_DAYS; after that `model:prune` (scheduled daily) removes them
 * for good. A model can tidy up stored files in `pruning()`.
 */
trait Trashable
{
    use Prunable, SoftDeletes;

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<', now()->subDays(RecentlyDeleted::RETENTION_DAYS));
    }
}
