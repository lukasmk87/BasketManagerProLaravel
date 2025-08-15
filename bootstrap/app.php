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
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
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
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\ResolveTenantMiddleware::class,
            \App\Http\Middleware\ConfigureTenantStripe::class,
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API Middleware
        $middleware->api(prepend: [
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
