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
        \App\Providers\ClubTransferServiceProvider::class,
    ])
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Installation routes (MUST BE FIRST - uses array-based sessions, no database dependency)
            \Illuminate\Support\Facades\Route::middleware('install')
                ->group(base_path('routes/install.php'));

            // Register WEB routes FIRST to ensure they take precedence
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Debug routes (temporary - DELETE AFTER DEBUGGING)
            if (file_exists(base_path('routes/debug.php'))) {
                \Illuminate\Support\Facades\Route::middleware('web')
                    ->group(base_path('routes/debug.php'));
            }

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
            // Player registration routes (trainer + public)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/player_registration.php'));

            // Club invitation routes (club admin + public)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/club_invitation.php'));

            // Club checkout routes (club subscription management)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/club_checkout.php'));

            // Admin routes (web middleware with admin auth)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));

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

            // Health Check API routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/health.php'));

            // Season Management API routes
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/season.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // NOTE: Installation routes use 'install' middleware group (defined below) which is separate
        // from 'web' middleware group. These middleware below have installation checks and skip for /install routes.
        $middleware->web(append: [
            \App\Http\Middleware\RedirectIfNotInstalled::class, // FIRST: Check if app is installed before any other middleware
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\ResolveTenantMiddleware::class, // Has installation check, uses lazy loading
            \App\Http\Middleware\ConfigureTenantStripe::class, // Has installation check
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

        // Installation middleware group - Uses array-based sessions (no database dependency)
        // before migrations run. InstallationSessionMiddleware forces 'array' session driver.
        $middleware->group('install', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\InstallationSessionMiddleware::class, // MUST run before StartSession
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'tenant' => \App\Http\Middleware\ResolveTenantMiddleware::class,
            'api.version' => \App\Http\Middleware\ApiVersioningMiddleware::class,
            'feature.gate' => \App\Http\Middleware\EnforceFeatureGates::class,
            'tenant.rate_limit' => \App\Http\Middleware\TenantRateLimitMiddleware::class,
            'enforce.club.limits' => \App\Http\Middleware\EnforceClubLimits::class,
            'prevent.installed' => \App\Http\Middleware\PreventInstalledAccess::class,
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
            
            // For API requests, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
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
                
                // For API requests, return JSON response
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'Sie haben keine Berechtigung für diese Aktion.',
                        'errors' => [],
                    ], 403);
                }
                
                // For regular web requests
                return response()->view('errors.403', ['exception' => $e], 403);
            }
        });

        // Handle model not found exceptions for API routes
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                \Log::info('Model not found for API request', [
                    'model' => $e->getModel(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Der angeforderte Datensatz wurde nicht gefunden.',
                    'errors' => []
                ], 404);
            }
        });

        // Handle database query exceptions for API routes
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                \Log::error('Database query error on API request', [
                    'sql_error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'request_data' => $request->all()
                ]);
                
                // Check for specific error types
                $errorMessage = 'Ein Datenbankfehler ist aufgetreten.';
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $errorMessage = 'Dieser Datensatz existiert bereits.';
                } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $errorMessage = 'Operation nicht möglich aufgrund von Datenabhängigkeiten.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => []
                ], 422);
            }
        });

        // Handle validation exceptions with improved API response format
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Die eingegebenen Daten sind ungültig.',
                    'errors' => $e->errors()
                ], 422);
            }
        });

        // Generic API error handler
        $exceptions->render(function (\Exception $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Only log non-standard exceptions to avoid spam
                if (!($e instanceof \Illuminate\Validation\ValidationException) &&
                    !($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) &&
                    !($e instanceof \Illuminate\Auth\Access\AuthorizationException) &&
                    !($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException)) {
                    
                    \Log::error('Unhandled API exception', [
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                        'url' => $request->fullUrl(),
                        'user_id' => auth()->id(),
                        'request_data' => $request->all(),
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => app()->environment('production') ? 
                        'Ein unerwarteter Fehler ist aufgetreten.' : 
                        $e->getMessage(),
                    'errors' => []
                ], 500);
            }
        });
    })->create();
