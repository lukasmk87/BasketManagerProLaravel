<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Install\RequirementChecker;
use App\Services\Install\PermissionChecker;
use App\Services\Install\EnvironmentManager;
use App\Services\Install\InstallationService;
use App\Services\Install\StripeValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InstallController extends Controller
{
    public function __construct(
        protected RequirementChecker $requirementChecker,
        protected PermissionChecker $permissionChecker,
        protected EnvironmentManager $environmentManager,
        protected InstallationService $installationService,
        protected StripeValidator $stripeValidator
    ) {}

    /**
     * Get the application name for the installation wizard
     * Priority: Session > Config > Default
     */
    protected function getAppName(): string
    {
        return session('installation_app_name', config('app.name', 'BasketManager Pro'));
    }

    /**
     * Step 0: Language Selection
     */
    public function index(): Response
    {
        // Create temporary installation lock to prevent service providers
        // from accessing database before migrations are completed
        if (!file_exists(storage_path('installing'))) {
            file_put_contents(storage_path('installing'), now()->toDateTimeString());
        }

        return Inertia::render('Install/Index', [
            'appName' => $this->getAppName(),
            'languages' => [
                'de' => [
                    'name' => 'Deutsch',
                    'flag' => '🇩🇪',
                    'code' => 'de',
                ],
                'en' => [
                    'name' => 'English',
                    'flag' => '🇬🇧',
                    'code' => 'en',
                ],
            ],
        ]);
    }

    /**
     * Set installation language
     */
    public function setLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|in:de,en',
        ]);

        session(['install_language' => $request->language]);
        app()->setLocale($request->language);

        return redirect()->route('install.welcome');
    }

    /**
     * Step 1: Welcome Screen
     */
    public function welcome(): Response
    {
        return Inertia::render('Install/Welcome', [
            'appName' => $this->getAppName(),
            'language' => session('install_language', 'de'),
        ]);
    }

    /**
     * Step 2: Server Requirements Check
     */
    public function requirements(): Response
    {
        $requirements = $this->requirementChecker->check();

        return Inertia::render('Install/Requirements', [
            'appName' => $this->getAppName(),
            'requirements' => $requirements['requirements'],
            'canProceed' => $requirements['satisfied'],
            'language' => session('install_language', 'de'),
        ]);
    }

    /**
     * Step 3: Folder Permissions Check
     */
    public function permissions(): Response
    {
        $permissions = $this->permissionChecker->check();

        return Inertia::render('Install/Permissions', [
            'appName' => $this->getAppName(),
            'permissions' => $permissions,
            'canProceed' => $permissions['satisfied'],
            'language' => session('install_language', 'de'),
        ]);
    }

    /**
     * Step 4: Environment Configuration Form
     */
    public function environment(): Response
    {
        $currentEnv = $this->environmentManager->getCurrentEnvironment();

        return Inertia::render('Install/Environment', [
            'appName' => $this->getAppName(),
            'currentEnv' => $currentEnv,
            'language' => session('install_language', 'de'),
        ]);
    }

    /**
     * Save environment configuration
     */
    public function saveEnvironment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Application Settings
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_env' => 'required|in:local,production,staging',
            'app_debug' => 'required|boolean',

            // Database Settings
            'db_connection' => 'required|in:mysql,pgsql,sqlite',
            'db_host' => 'required_unless:db_connection,sqlite|string',
            'db_port' => 'required_unless:db_connection,sqlite|integer',
            'db_database' => 'required|string',
            'db_username' => 'required_unless:db_connection,sqlite|string',
            'db_password' => 'nullable|string',

            // Mail Settings (optional)
            'mail_mailer' => 'nullable|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string',

            // Stripe Settings (optional - can be configured later)
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Save environment variables
            $this->environmentManager->saveEnvironment($request->all());

            // Store that environment has been configured
            session(['environment_configured' => true]);

            // Store app name in session for wizard header and auto-fill
            session(['installation_app_name' => $request->app_name]);

            return redirect()->route('install.database')->with('success', __('install.environment_saved'));
        } catch (\Exception $e) {
            return back()->with('error', __('install.environment_save_failed').': '.$e->getMessage())->withInput();
        }
    }

    /**
     * AJAX: Test database connection
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'db_connection' => 'required|in:mysql,pgsql,sqlite',
            'db_host' => 'required_unless:db_connection,sqlite|string',
            'db_port' => 'required_unless:db_connection,sqlite|integer',
            'db_database' => 'required|string',
            'db_username' => 'required_unless:db_connection,sqlite|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $result = $this->installationService->testDatabaseConnection([
                'connection' => $request->db_connection,
                'host' => $request->db_host,
                'port' => $request->db_port,
                'database' => $request->db_database,
                'username' => $request->db_username,
                'password' => $request->db_password,
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX: Test Stripe API connection
     */
    public function testStripe(Request $request)
    {
        $request->validate([
            'stripe_key' => 'required|string',
            'stripe_secret' => 'required|string',
        ]);

        try {
            $result = $this->stripeValidator->validateKeys(
                $request->stripe_key,
                $request->stripe_secret
            );

            return response()->json([
                'success' => $result['valid'],
                'message' => $result['message'] ?? __('install.stripe_valid'),
                'account_name' => $result['account_name'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 5: Database Setup Page
     */
    public function database(): Response|RedirectResponse
    {
        // Check if environment is configured
        if (! session('environment_configured')) {
            return redirect()->route('install.environment')->with('warning', __('install.configure_environment_first'));
        }

        // Test database connection
        $dbStatus = [
            'connected' => false,
            'message' => null,
            'database_name' => config('database.connections.'.config('database.default').'.database'),
        ];

        try {
            DB::connection()->getPdo();
            $dbStatus['connected'] = true;
            $dbStatus['message'] = 'Database connection successful';

            // For MySQL, check if database exists
            if (config('database.default') === 'mysql') {
                try {
                    $dbName = $dbStatus['database_name'];
                    $databases = DB::select('SHOW DATABASES');
                    $databaseList = collect($databases)->pluck('Database')->toArray();
                    $exists = in_array($dbName, $databaseList);

                    if (!$exists) {
                        $dbStatus['connected'] = false;
                        $dbStatus['message'] = "Database '{$dbName}' does not exist. Please create it before running migrations.";
                    }
                } catch (\Exception $e) {
                    // If SHOW DATABASES fails, try to USE the database
                    try {
                        DB::statement("USE `{$dbStatus['database_name']}`");
                    } catch (\Exception $useEx) {
                        $dbStatus['connected'] = false;
                        $dbStatus['message'] = "Cannot access database '{$dbStatus['database_name']}'. It may not exist.";
                    }
                }
            }
        } catch (\Exception $e) {
            $dbStatus['connected'] = false;
            $dbStatus['message'] = 'Database connection failed: ' . $e->getMessage();
        }

        return Inertia::render('Install/Database', [
            'appName' => $this->getAppName(),
            'language' => session('install_language', 'de'),
            'migrationStatus' => session('migrations_completed', false),
            'databaseStatus' => $dbStatus,
        ]);
    }

    /**
     * Run database migrations and seeders
     */
    public function runMigrations(Request $request)
    {
        // Check if environment is configured
        if (! session('environment_configured')) {
            return response()->json([
                'success' => false,
                'message' => __('install.configure_environment_first'),
            ], 422);
        }

        try {
            $result = $this->installationService->runMigrations();

            if ($result['success']) {
                session(['migrations_completed' => true]);
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'output' => $result['output'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('install.migration_failed').': '.$e->getMessage(),
                'output' => [],
            ], 500);
        }
    }

    /**
     * Step 6: Super Admin Creation Form
     */
    public function admin(): Response|RedirectResponse
    {
        // Check if migrations are completed
        if (! session('migrations_completed')) {
            return redirect()->route('install.database')->with('warning', __('install.complete_migrations_first'));
        }

        return Inertia::render('Install/Admin', [
            'appName' => $this->getAppName(),
            'language' => session('install_language', 'de'),
            'subscriptionTiers' => [
                'free' => [
                    'name' => __('install.subscription_free'),
                    'price' => '€0',
                    'limits' => [
                        'users' => 10,
                        'teams' => 5,
                        'storage' => '5GB',
                    ],
                ],
                'basic' => [
                    'name' => __('install.subscription_basic'),
                    'price' => '€29',
                    'limits' => [
                        'users' => 50,
                        'teams' => 20,
                        'storage' => '50GB',
                    ],
                ],
                'professional' => [
                    'name' => __('install.subscription_professional'),
                    'price' => '€99',
                    'limits' => [
                        'users' => 200,
                        'teams' => 50,
                        'storage' => '200GB',
                    ],
                ],
                'enterprise' => [
                    'name' => __('install.subscription_enterprise'),
                    'price' => __('install.subscription_custom'),
                    'limits' => [
                        'users' => __('install.unlimited'),
                        'teams' => __('install.unlimited'),
                        'storage' => __('install.unlimited'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create Super Admin and complete installation
     */
    public function createAdmin(Request $request)
    {
        // Check if migrations are completed
        if (! session('migrations_completed')) {
            return back()->with('error', __('install.complete_migrations_first'));
        }

        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'subscription_tier' => 'required|in:free,basic,professional,enterprise',
        ]);

        if ($validator->fails()) {
            \Log::error('Admin creation validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except('admin_password', 'admin_password_confirmation')
            ]);
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Create Super Admin, Tenant, and finalize installation
            $result = $this->installationService->createSuperAdmin([
                'tenant_name' => $request->tenant_name,
                'admin_name' => $request->admin_name,
                'admin_email' => $request->admin_email,
                'admin_password' => $request->admin_password,
                'subscription_tier' => $request->subscription_tier,
                'language' => session('install_language', 'de'),
            ]);

            if ($result['success']) {
                // Mark installation as complete
                $this->installationService->markAsInstalled();

                // Automatically log in the Super Admin
                $user = User::where('email', $request->admin_email)->first();
                if ($user) {
                    Auth::login($user);
                    \Log::info('Super Admin automatically logged in after installation', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                }

                // Clear installation session data
                session()->forget([
                    'install_language',
                    'environment_configured',
                    'migrations_completed',
                ]);

                // Store admin credentials for display on complete page
                session([
                    'installation_complete' => true,
                    'admin_email' => $request->admin_email,
                ]);

                return redirect()->route('install.complete');
            }

            return back()->with('error', $result['message'] ?? __('install.admin_creation_failed'))->withInput();
        } catch (\Exception $e) {
            \Log::error('Admin creation exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('admin_password', 'admin_password_confirmation')
            ]);

            // Provide more helpful error messages based on exception type
            $errorMessage = __('install.admin_creation_failed') . ': ';

            if (str_contains($e->getMessage(), 'Super Admin role not found')) {
                $errorMessage .= 'Database roles were not seeded correctly. Please go back to the Database step and run migrations with seed option.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage .= 'This email address is already registered. Please use a different email.';
            } elseif (str_contains($e->getMessage(), 'connection') || str_contains($e->getMessage(), 'database')) {
                $errorMessage .= 'Database connection failed. Please check your database configuration.';
            } else {
                $errorMessage .= $e->getMessage();
            }

            return back()->with('error', $errorMessage)->withInput();
        }
    }

    /**
     * Step 7: Installation Complete
     */
    public function complete(): Response|RedirectResponse
    {
        if (! session('installation_complete')) {
            return redirect()->route('install.index');
        }

        $adminEmail = session('admin_email');

        // Remove temporary installation lock - installation is now complete
        if (file_exists(storage_path('installing'))) {
            @unlink(storage_path('installing'));
        }

        // Clear installation complete flag
        session()->forget(['installation_complete', 'admin_email']);

        return Inertia::render('Install/Complete', [
            'appName' => $this->getAppName(),
            'adminEmail' => $adminEmail,
            'dashboardUrl' => route('dashboard'),
            'language' => app()->getLocale(),
        ]);
    }
}
