<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\FortifyServiceProvider::class,
        \Laravel\Jetstream\JetstreamServiceProvider::class,
        \App\Providers\StripeServiceProvider::class,
        \App\Providers\CashierServiceProvider::class,
        \App\Providers\FeatureGateServiceProvider::class,
    ])
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register WEB routes FIRST to ensure they take precedence
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/web.php'));
            
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/subscription.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/checkout.php'));
            \Illuminate\Support\Facades\Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));
            \Illuminate\Support\Facades\Route::middleware('api')
                ->group(base_path('routes/federation.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/pwa.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/notifications.php'));
            // Emergency access routes (mixed web/api middleware)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/emergency.php'));
            // GDPR compliance routes (mixed web/api middleware)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/gdpr.php'));
            
            // Register API routes LAST to avoid conflicts with web routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
            
            // Training API routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api_training.php'));
            
            // Tournament API routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api_tournament.php'));
            
            // Game Registration API routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api_game_registrations.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\ResolveTenantMiddleware::class,
            \App\Http\Middleware\ConfigureTenantStripe::class,
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API Middleware - Only apply to /api/* routes
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->api(append: [
            \App\Http\Middleware\ResolveTenantMiddleware::class,
            \App\Http\Middleware\ConfigureTenantStripe::class,
            \App\Http\Middleware\ApiVersioningMiddleware::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenantMiddleware::class,
            'api.version' => \App\Http\Middleware\ApiVersioningMiddleware::class,
            'feature.gate' => \App\Http\Middleware\EnforceFeatureGates::class,
            'tenant.rate_limit' => \App\Http\Middleware\TenantRateLimitMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authorization exceptions
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            // For Inertia requests, return a proper 403 response instead of redirect
            if ($request->header('X-Inertia')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Sie haben keine Berechtigung für diese Aktion.',
                    'errors' => [],
                ], 403);
            }
            
            // For regular web requests, return the default Laravel behavior
            return response()->view('errors.403', ['exception' => $e], 403);
        });
        
        // Handle HTTP exceptions (like abort(403))
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                // For Inertia requests, return a proper 403 response
                if ($request->header('X-Inertia')) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Sie haben keine Berechtigung für diese Aktion.',
                        'errors' => [],
                    ], 403);
                }
                
                // For regular web requests
                return response()->view('errors.403', ['exception' => $e], 403);
            }
        });
    })->create();
