<?php

namespace App\Support;

use App\Models\Company;

/**
 * The client company picked in the top bar, kept in the session so it carries across
 * pages. Pages already read the company from ?company=<uuid>; UseCurrentCompany fills that
 * in when a page is opened without one. Null means "All companies" (each page's default).
 */
class CurrentCompany
{
    public const SESSION_KEY = 'current_company';

    /** Stable colour per company, so the selected one is recognisable at a glance. */
    public static function hue(int $id): int
    {
        return ($id * 47) % 360;
    }

    public static function uuid(): ?string
    {
        $uuid = session(self::SESSION_KEY);

        return $uuid && self::accessible()->where('uuid', $uuid)->exists() ? $uuid : null;
    }

    public static function get(): ?Company
    {
        $uuid = self::uuid();

        return $uuid ? Company::where('uuid', $uuid)->first() : null;
    }

    public static function set(?string $uuid): void
    {
        $uuid ? session([self::SESSION_KEY => $uuid]) : session()->forget(self::SESSION_KEY);
    }

    /** Companies the signed-in user may see (all for admins). */
    public static function accessible()
    {
        $ids = CompanyAccess::restrictedTo();

        return Company::query()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids));
    }
}
