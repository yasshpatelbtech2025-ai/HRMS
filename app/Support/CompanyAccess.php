<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Which companies the signed-in user may see and change: all for admins
 * (and with nobody signed in - console commands, the scheduler), only their
 * assigned ones for users.
 */
class CompanyAccess
{
    /**
     * Signed-in user => company ids. Keyed by the User object (not its id), so
     * the answer lives only as long as that login does and never carries over
     * to another request or worker job that reuses an id.
     *
     * @var \WeakMap<User, list<int>>|null
     */
    private static ?\WeakMap $cache = null;

    /**
     * The company ids the current user is limited to, or null when not limited.
     *
     * @return list<int>|null
     */
    public static function restrictedTo(): ?array
    {
        // hasUser(): never resolve the user from here (it would re-enter the user query).
        if (! Auth::hasUser()) {
            return null;
        }
        $user = Auth::user();
        if (! $user instanceof User || $user->isAdmin()) {
            return null;
        }

        self::$cache ??= new \WeakMap;

        return self::$cache[$user] ??= DB::table('company_user')->where('user_id', $user->id)->pluck('company_id')->map(fn ($id) => (int) $id)->all();
    }

    public static function allows(?int $companyId): bool
    {
        $ids = self::restrictedTo();

        return $ids === null || ($companyId !== null && in_array($companyId, $ids, true));
    }

    /**
     * @throws AuthorizationException when the user may not change records of this company
     */
    public static function assertWritable(?int $companyId): void
    {
        if (! self::allows($companyId)) {
            throw new AuthorizationException(__('You do not have access to this company.'));
        }
    }

    /**
     * Whether the signed-in user may open a page (route name), by
     * config('access.admin_only'); for hiding tabs and links.
     */
    public static function canOpen(string $routeName): bool
    {
        $user = Auth::user();

        return ! $user || $user->isAdmin() || ! Str::is(config('access.admin_only'), $routeName);
    }

    /** After assignments change. */
    public static function forget(): void
    {
        self::$cache = null;
    }
}
