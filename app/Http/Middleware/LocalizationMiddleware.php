<?php

namespace App\Http\Middleware;

use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * The localization service instance.
     */
    protected LocalizationService $localizationService;

    /**
     * Create a new middleware instance.
     */
    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect and set the appropriate locale
        $locale = $this->localizationService->detectAndSetLocale($request);

        // Check if we need to redirect to add/remove locale prefix from URL
        if ($this->shouldRedirectForLocale($request, $locale)) {
            return $this->redirectToLocalizedUrl($request, $locale);
        }

        // Continue with the request
        $response = $next($request);

        // Add locale information to response headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Locale', $locale);
            $response->headers->set('X-Locale-Source', $this->getLocaleSource($request, $locale));
        }

        return $response;
    }

    /**
     * Determine if we should redirect to add or remove locale prefix.
     */
    protected function shouldRedirectForLocale(Request $request, string $locale): bool
    {
        $segments = $request->segments();
        $hasLocaleInUrl = !empty($segments) && $this->localizationService->isValidLocale($segments[0]);
        $defaultLocale = config('localization.default_locale');
        $hideDefaultLocale = config('localization.url.hide_default_locale', true);

        // Special handling for authentication routes - always redirect to non-localized version
        if ($this->isAuthRoute($request, $segments, $hasLocaleInUrl)) {
            return $hasLocaleInUrl; // Redirect if locale is in URL for auth routes
        }

        // If URL has locale prefix but it's the default locale and we should hide it
        if ($hasLocaleInUrl && $locale === $defaultLocale && $hideDefaultLocale) {
            return true;
        }

        // If URL doesn't have locale prefix but it's not the default locale
        if (!$hasLocaleInUrl && $locale !== $defaultLocale) {
            return true;
        }

        // If URL has wrong locale prefix
        if ($hasLocaleInUrl && $segments[0] !== $locale) {
            return true;
        }

        return false;
    }

    /**
     * Redirect to the properly localized URL.
     */
    protected function redirectToLocalizedUrl(Request $request, string $locale): Response
    {
        $segments = $request->segments();
        $hasLocaleInUrl = !empty($segments) && $this->localizationService->isValidLocale($segments[0]);
        $defaultLocale = config('localization.default_locale');
        $hideDefaultLocale = config('localization.url.hide_default_locale', true);

        // Special handling for auth routes - always redirect to non-localized version
        if ($this->isAuthRoute($request, $segments, $hasLocaleInUrl)) {
            // Set locale in session before redirecting to auth route
            session(['locale' => $locale]);
            
            // Remove locale prefix and redirect to non-localized auth route
            if ($hasLocaleInUrl) {
                array_shift($segments);
            }
            
            $newPath = '/' . implode('/', $segments);
            
            // Preserve query string
            if ($request->getQueryString()) {
                $newPath .= '?' . $request->getQueryString();
            }
            
            return redirect($newPath, 301);
        }

        // Remove existing locale from segments if present
        if ($hasLocaleInUrl) {
            array_shift($segments);
        }

        // Add locale prefix if needed
        if ($locale !== $defaultLocale || !$hideDefaultLocale) {
            array_unshift($segments, $locale);
        }

        // Build the new URL
        $newPath = '/' . implode('/', $segments);
        
        // Preserve query string
        if ($request->getQueryString()) {
            $newPath .= '?' . $request->getQueryString();
        }

        return redirect($newPath, 301);
    }

    /**
     * Get the source of the detected locale for debugging.
     */
    protected function getLocaleSource(Request $request, string $locale): string
    {
        $detectionOrder = config('localization.detection_order', []);
        
        foreach ($detectionOrder as $method) {
            $detectedLocale = match ($method) {
                'url' => $this->detectFromUrl($request),
                'session' => session('locale'),
                'cookie' => $request->cookie(config('localization.cookie.name')),
                'user' => auth()->user()?->language,
                'browser' => $this->detectFromBrowser($request),
                'default' => config('localization.default_locale'),
                default => null,
            };

            if ($detectedLocale === $locale) {
                return $method;
            }
        }

        return 'unknown';
    }

    /**
     * Detect locale from URL (helper method).
     */
    protected function detectFromUrl(Request $request): ?string
    {
        $segments = $request->segments();
        
        if (empty($segments)) {
            return null;
        }

        $firstSegment = $segments[0];
        
        return $this->localizationService->isValidLocale($firstSegment) ? $firstSegment : null;
    }

    /**
     * Simple browser locale detection (helper method).
     */
    protected function detectFromBrowser(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Simple extraction of first language code
        if (preg_match('/^([a-z]{2})/', $acceptLanguage, $matches)) {
            $locale = $matches[1];
            return $this->localizationService->isValidLocale($locale) ? $locale : null;
        }

        return null;
    }

    /**
     * Check if the current route is an authentication route.
     */
    protected function isAuthRoute(Request $request, array $segments, bool $hasLocaleInUrl): bool
    {
        // Get the segments without locale prefix
        $pathSegments = $segments;
        if ($hasLocaleInUrl) {
            array_shift($pathSegments);
        }

        // Common authentication routes from Fortify/Jetstream
        $authRoutes = [
            'login',
            'register', 
            'password/reset',
            'password/confirm',
            'email/verify',
            'two-factor-challenge',
            'logout',
            'forgot-password',
            'reset-password',
            'user/confirm-password',
            'user/confirmed-password-status'
        ];

        // Check if first segment matches any auth route
        if (!empty($pathSegments)) {
            $firstSegment = $pathSegments[0];
            
            if (in_array($firstSegment, $authRoutes)) {
                return true;
            }
            
            // Check for nested auth routes like password/reset
            if (count($pathSegments) >= 2) {
                $nestedPath = $pathSegments[0] . '/' . $pathSegments[1];
                if (in_array($nestedPath, $authRoutes)) {
                    return true;
                }
            }
        }

        return false;
    }
}