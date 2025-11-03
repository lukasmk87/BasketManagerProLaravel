<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventInstalledAccess
{
    /**
     * Handle an incoming request.
     *
     * Prevents access to installation routes after the application has been installed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If application is already installed, redirect to dashboard
        if ($this->isInstalled()) {
            // If user is authenticated, redirect to dashboard
            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            // Otherwise redirect to login page
            return redirect()->route('login');
        }

        return $next($request);
    }

    /**
     * Check if the application is installed.
     */
    protected function isInstalled(): bool
    {
        // Check for installation marker file
        $markerFile = storage_path('installed');

        // Check environment variable
        $envInstalled = config('app.installed', false);

        return file_exists($markerFile) || $envInstalled === true;
    }
}
