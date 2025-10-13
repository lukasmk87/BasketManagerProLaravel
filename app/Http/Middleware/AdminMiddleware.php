<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        // Check if user is Super Admin or Admin or has manage-subscriptions permission
        if (!$user->hasRole('super_admin') && !$user->hasRole('admin') && !$user->can('manage-subscriptions')) {
            abort(403, 'Keine Berechtigung für Admin-Bereich');
        }

        return $next($request);
    }
}
