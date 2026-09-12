<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has a given permission.
 *
 * Usage in routes:
 *     Route::get('/admin', [Controller::class, 'index'])
 *         ->middleware('permission:admin.dashboard');
 *
 * Super admins bypass all permission checks.
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission  The permission slug required to access the route
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Redirect unauthenticated users to login
        if (! $user) {
            return redirect()->route('login');
        }

        // Super admins bypass permission checks entirely
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check the required permission through the user's roles
        if (! $user->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
