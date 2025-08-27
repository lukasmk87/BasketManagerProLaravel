<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

class EnhancedCsrfProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for API routes and GET requests
        if ($request->isMethod('GET') || $request->is('api/*')) {
            return $next($request);
        }

        // Add additional debugging for staging environment
        if (config('app.env') === 'staging') {
            \Log::info('CSRF Protection Debug', [
                'method' => $request->method(),
                'url' => $request->url(),
                'csrf_token_header' => $request->header('X-CSRF-TOKEN'),
                'csrf_token_input' => $request->input('_token'),
                'session_token' => $request->session()->token(),
                'user_agent' => $request->header('User-Agent'),
                'referer' => $request->header('Referer'),
            ]);
        }

        return $next($request);
    }
}