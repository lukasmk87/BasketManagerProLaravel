<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware für Admin-Bereich Zugriff.
 *
 * Hierarchie:
 * - Super Admin: Hat immer Zugriff (tenant_id = null)
 * - Tenant Admin: Hat Zugriff auf zugewiesene Tenants
 * - Users mit 'manage-subscriptions' Permission: Fallback
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Nicht authentifiziert');
        }

        // Super Admin hat immer Zugriff
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Tenant Admin mit Tenant-Scope-Prüfung
        if ($user->hasRole('tenant_admin')) {
            $tenant = app('tenant');

            // Wenn kein Tenant im Kontext, Zugriff erlauben (z.B. Tenant-Auswahl)
            if (!$tenant) {
                return $next($request);
            }

            // Prüfe ob User Admin für diesen Tenant ist
            if ($user->isTenantAdminFor($tenant)) {
                return $next($request);
            }
        }

        // Fallback: Users mit manage-subscriptions Permission
        if ($user->can('manage-subscriptions')) {
            return $next($request);
        }

        abort(403, 'Keine Berechtigung für Admin-Bereich');
    }
}
