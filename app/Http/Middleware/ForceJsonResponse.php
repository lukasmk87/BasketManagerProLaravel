<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\AuthenticationException;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to API routes, not web routes or Inertia requests
        $isApiRoute = $request->is('api/*');
        $isInertiaRequest = $request->header('X-Inertia');
        
        // Skip middleware for non-API routes or Inertia requests
        if (!$isApiRoute || $isInertiaRequest) {
            return $next($request);
        }

        // Force JSON response for API routes
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        try {
            $response = $next($request);
        } catch (AuthenticationException $e) {
            // Handle authentication exceptions for API routes
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'authentication_required',
                'api_version' => '4.0',
                'timestamp' => now()->toISOString(),
                'debug' => [
                    'route' => $request->path(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                ]
            ], 401);
        }

        // If this is an API route and we're redirecting (like to login page),
        // convert to a JSON error response instead
        if ($response->isRedirection()) {
            $location = $response->headers->get('Location');
            
            // Check if it's redirecting to login or dashboard
            if (str_contains($location, '/login') || str_contains($location, '/dashboard')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'authentication_required',
                    'api_version' => '4.0',
                    'timestamp' => now()->toISOString(),
                    'debug' => [
                        'original_redirect' => $location,
                        'route' => $request->path(),
                        'method' => $request->method(),
                    ]
                ], 401);
            }
        }

        return $response;
    }
}