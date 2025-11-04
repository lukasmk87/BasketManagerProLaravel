<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installation Session Middleware
 *
 * Forces the session driver to use 'array' (in-memory) sessions during installation
 * to avoid database dependency before migrations are run. This prevents the HTTP 500
 * error caused by StartSession middleware trying to access the non-existent 'sessions' table.
 */
class InstallationSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force array-based sessions during installation (no database dependency)
        config(['session.driver' => 'array']);

        return $next($request);
    }
}
