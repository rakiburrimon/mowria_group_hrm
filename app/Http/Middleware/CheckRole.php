<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has one of the required roles.
 *
 * Usage in routes:
 *     Route::get('/admin', [Controller::class, 'index'])
 *         ->middleware('role:admin');
 *
 * Multiple roles can be comma-separated:
 *     ->middleware('role:admin,manager');
 *
 * Super admins bypass all role checks.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  One or more role slugs the user must have
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Redirect unauthenticated users to login
        if (! $user) {
            return redirect()->route('login');
        }

        // Super admins bypass all role checks
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if the user holds any of the required roles
        if (! $user->hasAnyRole($roles)) {
            abort(403, 'You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
