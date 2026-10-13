<?php

namespace App\Http\Middleware;

use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the top-bar company and the pages in step: a page opened without ?company= gets the
 * picked company, and choosing a company in a page's own filter becomes the picked company.
 */
class UseCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->user() && ! $request->expectsJson()) {
            $asked = $request->query('company');
            $askedId = $request->query('company_id');

            if (is_string($asked) && $asked !== '') {
                if (CurrentCompany::accessible()->where('uuid', $asked)->exists()) {
                    CurrentCompany::set($asked);
                }
            } elseif (is_string($askedId) && ctype_digit($askedId)) {
                if ($uuid = CurrentCompany::accessible()->whereKey((int) $askedId)->value('uuid')) {
                    CurrentCompany::set($uuid);
                }
            } elseif (! $request->query->has('company') && ($company = CurrentCompany::get())) {
                $request->query->set('company', $company->uuid);
                // The employee list filters by id; fill that too unless the page asked for one.
                if (! $request->query->has('company_id')) {
                    $request->query->set('company_id', (string) $company->id);
                }
                $request->attributes->set('company_from_picker', true);
            }
        }

        return $next($request);
    }
}
