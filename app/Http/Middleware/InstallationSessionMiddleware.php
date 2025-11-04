<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Installation Session Middleware
 *
 * Forces the session driver to use 'file' sessions during installation
 * to avoid database dependency before migrations are run. File sessions persist
 * between requests (unlike 'array' sessions) allowing CSRF tokens and session data
 * to work correctly. This prevents HTTP 500 and HTTP 419 CSRF errors.
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
        // Force file-based sessions during installation (persists between requests, no database dependency)
        config(['session.driver' => 'file']);

        return $next($request);
    }
}
