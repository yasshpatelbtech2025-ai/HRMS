<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role rules on every request (config/access.php):
 * - admin-only pages (by route name) are refused to users;
 * - users cannot delete anything: DELETE requests and bulk "delete"
 *   actions are refused;
 * - a deactivated account is signed out.
 */
class EnforceRoleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => __('This account has been deactivated. Ask an admin.')]);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $route = $request->route()?->getName();
        if ($route && $request->routeIs(...config('access.admin_only'))) {
            abort(403, __('Only admins can open this page.'));
        }

        if ($request->isMethod('DELETE') || $request->input('action') === 'delete') {
            if ($request->expectsJson()) {
                abort(403, __('Only admins can delete.'));
            }

            return back()->with('error', __('Only admins can delete. Ask an admin if something needs removing.'));
        }

        return $next($request);
    }
}
