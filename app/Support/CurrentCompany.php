<?php

namespace App\Support;

use App\Models\Company;

/**
 * The client company the user is working on, chosen in the top bar and kept
 * in the session so it carries across pages. Null means "All companies".
 */
class CurrentCompany
{
    public const SESSION_KEY = 'current_company_id';

    private ?Company $company = null;

    private bool $resolved = false;

    public function get(): ?Company
    {
        if (! $this->resolved) {
            $this->resolved = true;
            $id = session(self::SESSION_KEY);
            $this->company = $id ? Company::find($id) : null;

            if ($id && ! $this->company) {
                session()->forget(self::SESSION_KEY); // company was deleted
            }
        }

        return $this->company;
    }

    public function id(): ?int
    {
        return $this->get()?->id;
    }

    public function set(?Company $company): void
    {
        $company ? session([self::SESSION_KEY => $company->id]) : session()->forget(self::SESSION_KEY);
        $this->company = $company;
        $this->resolved = true;
    }

    /** Stable colour per company so the selected one is recognisable at a glance. */
    public static function hue(int $id): int
    {
        return ($id * 47) % 360;
    }

    /**
     * Company filter for list pages: an explicit ?company_id= (including empty
     * for "all") wins, otherwise the top-bar selection.
     */
    public function filter(): ?int
    {
        return request()->has('company_id') ? (request()->integer('company_id') ?: null) : $this->id();
    }
}
