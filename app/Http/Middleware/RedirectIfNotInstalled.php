<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    /**
     * Routes that should be accessible even if the app is not installed.
     *
     * @var array<string>
     */
    protected array $except = [
        'install.*',
        'horizon.*',
        'telescope.*',
        'pulse.*',
        'sanctum.*',
        'livewire.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if application is installed
        if (! $this->isInstalled() && ! $this->inExceptArray($request)) {
            return redirect()->route('install.index');
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

    /**
     * Determine if the request has a URI that should pass through.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        // Also allow access to health checks and API routes during installation
        if ($request->is('api/*') || $request->is('up') || $request->is('health')) {
            return true;
        }

        return false;
    }
}
