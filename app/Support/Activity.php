<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes the activity log. Models with the Auditable trait log their own
 * changes; controllers and services record what model events can't see
 * (bulk actions, generation runs, exports, sign-ins).
 */
class Activity
{
    private static bool $muted = false;

    /**
     * Run without logging each model change, e.g. while generating many
     * payslips; record one summary entry instead.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function quietly(callable $callback): mixed
    {
        $was = self::$muted;
        self::$muted = true;

        try {
            return $callback();
        } finally {
            self::$muted = $was;
        }
    }

    public static function muted(): bool
    {
        return self::$muted;
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(string $event, ?Model $subject = null, ?string $description = null, ?array $changes = null, ?int $companyId = null, ?int $userId = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId ?? (Auth::hasUser() ? Auth::id() : null),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'company_id' => $companyId ?? ($subject instanceof Company ? $subject->getKey() : $subject?->getAttribute('company_id')),
            'description' => $description !== null ? mb_substr($description, 0, 255) : null,
            'changes' => $changes ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
