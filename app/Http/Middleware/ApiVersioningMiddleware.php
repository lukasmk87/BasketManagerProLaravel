<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class ApiVersioningMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip versioning for non-API routes
        if (!$this->isApiRoute($request)) {
            return $next($request);
        }

        // Resolve API version from request
        $version = $this->resolveApiVersion($request);
        
        // Validate version
        if (!$this->isVersionSupported($version)) {
            return response()->json([
                'error' => 'Unsupported API version',
                'message' => "API version '{$version}' is not supported",
                'supported_versions' => $this->getSupportedVersions(),
                'current_version' => config('api.default_version')
            ], 400);
        }

        // Store resolved version in request for later use
        $request->merge(['api_version' => $version]);
        $request->attributes->set('api_version', $version);
        
        // Also set in route parameters for easier access in route closures
        $request->route()?->setParameter('api_version', $version);

        // Execute request
        $response = $next($request);

        // Add version headers to response
        return $this->addVersionHeaders($response, $version);
    }

    /**
     * Determine if the current request is an API route
     */
    protected function isApiRoute(Request $request): bool
    {
        return $request->is('api/*') || 
               $request->expectsJson() || 
               $request->wantsJson() ||
               str_starts_with($request->getPathInfo(), '/api/');
    }

    /**
     * Resolve API version from request headers or query parameters
     */
    protected function resolveApiVersion(Request $request): string
    {
        // 1. Check Accept header (preferred method)
        $acceptHeader = $request->header('Accept', '');
        if (preg_match('/application\/vnd\.basketmanager\.v(\d+(?:\.\d+)?)/', $acceptHeader, $matches)) {
            return $matches[1];
        }

        // 2. Check custom API-Version header (case-insensitive)
        if ($request->hasHeader('API-Version')) {
            return $request->header('API-Version');
        }
        
        // Also try lowercase variant
        if ($request->hasHeader('api-version')) {
            return $request->header('api-version');
        }

        // 3. Check X-API-Version header (case-insensitive)
        if ($request->hasHeader('X-API-Version')) {
            return $request->header('X-API-Version');
        }
        
        // Also try lowercase variant
        if ($request->hasHeader('x-api-version')) {
            return $request->header('x-api-version');
        }

        // 4. Check query parameter (for testing/debugging)
        if ($request->has('api_version')) {
            return $request->get('api_version');
        }

        // 5. Extract from URL path if versioned route (e.g., /api/v4/...)
        $path = $request->getPathInfo();
        if (preg_match('/\/api\/v(\d+(?:\.\d+)?)\//', $path, $matches)) {
            return $matches[1];
        }

        // 6. Default to configured default version
        return config('api.default_version', '4.0');
    }

    /**
     * Check if the specified version is supported
     */
    protected function isVersionSupported(string $version): bool
    {
        $allVersions = config('api.versions', []);
        
        // Check if version exists and is enabled
        foreach ($allVersions as $versionKey => $versionConfig) {
            if ($version === $versionKey && ($versionConfig['enabled'] ?? false)) {
                return true;
            }
            
            // Check for major version compatibility (e.g., "4" matches "4.0")
            if ((str_starts_with($versionKey, $version . '.') || 
                str_starts_with($version, $versionKey . '.')) && 
                ($versionConfig['enabled'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of supported API versions
     */
    protected function getSupportedVersions(): array
    {
        $allVersions = config('api.versions', []);
        $supportedVersions = [];
        
        foreach ($allVersions as $version => $config) {
            if ($config['enabled'] ?? false) {
                $supportedVersions[] = $version;
            }
        }
        
        return $supportedVersions;
    }

    /**
     * Add version-related headers to the response
     */
    protected function addVersionHeaders(Response $response, string $version): Response
    {
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-Supported-Versions', implode(', ', $this->getSupportedVersions()));
        
        // Add deprecation warning if version is deprecated
        $versionConfig = config("api.versions.{$version}", []);
        if (isset($versionConfig['deprecated']) && $versionConfig['deprecated']) {
            $response->headers->set('X-API-Deprecated', 'true');
            
            if (isset($versionConfig['sunset_date'])) {
                $response->headers->set('Sunset', $versionConfig['sunset_date']);
            }
            
            if (isset($versionConfig['deprecation_message'])) {
                $response->headers->set('X-API-Deprecation-Message', $versionConfig['deprecation_message']);
            }
        }

        // Add API documentation link
        if (isset($versionConfig['documentation_url'])) {
            $response->headers->set('Link', '<' . $versionConfig['documentation_url'] . '>; rel="documentation"');
        }

        return $response;
    }

    /**
     * Get the normalized version number for comparison
     */
    protected function normalizeVersion(string $version): string
    {
        // Ensure version has at least major.minor format
        if (!str_contains($version, '.')) {
            return $version . '.0';
        }
        
        return $version;
    }
}