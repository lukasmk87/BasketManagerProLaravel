<?php

namespace App\Services\Install;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLimitsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallationService
{
    /**
     * Test database connection with given credentials
     */
    public function testDatabaseConnection(array $credentials): array
    {
        try {
            // Create a temporary database connection config
            Config::set('database.connections.test_connection', [
                'driver' => $credentials['connection'],
                'host' => $credentials['host'] ?? '127.0.0.1',
                'port' => $credentials['port'] ?? 3306,
                'database' => $credentials['database'],
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]);

            // Try to connect
            DB::purge('test_connection');
            $pdo = DB::connection('test_connection')->getPdo();

            if ($pdo) {
                // Test if we can query the database
                DB::connection('test_connection')->select('SELECT 1');

                return [
                    'success' => true,
                    'message' => 'Database connection successful!',
                ];
            }

            return [
                'success' => false,
                'message' => 'Could not establish database connection.',
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ];
        } finally {
            // Clean up
            DB::purge('test_connection');
        }
    }

    /**
     * Run database migrations and required seeders
     */
    public function runMigrations(): array
    {
        try {
            $output = [];

            // Step 1: Verify database connection
            $output[] = '🔍 Testing database connection...';
            try {
                DB::connection()->getPdo();
                $output[] = '✅ Database connection successful';
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Database connection failed. Please verify your database credentials in the Environment step.',
                    'output' => array_merge($output, [
                        '❌ Cannot connect to database',
                        '💡 Error: ' . $e->getMessage(),
                        '',
                        '📝 Please check:',
                        '  - Database host and port are correct',
                        '  - Database username and password are correct',
                        '  - Database server is running',
                        '  - Firewall allows connection to database port',
                    ]),
                ];
            }

            // Step 2: Check if database exists (for MySQL/MariaDB)
            if (config('database.default') === 'mysql') {
                $dbName = config('database.connections.mysql.database');
                $output[] = "🔍 Checking if database '{$dbName}' exists...";

                try {
                    $databases = DB::select('SHOW DATABASES');
                    $databaseList = collect($databases)->pluck('Database')->toArray();
                    $exists = in_array($dbName, $databaseList);

                    if (!$exists) {
                        return [
                            'success' => false,
                            'message' => "Database '{$dbName}' does not exist. Please create it first.",
                            'output' => array_merge($output, [
                                "❌ Database '{$dbName}' not found",
                                '',
                                '📝 To create the database, run this SQL command:',
                                "   CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                                '',
                                '💡 Or create it via your hosting control panel (e.g., cPanel, Plesk)',
                            ]),
                        ];
                    }

                    $output[] = "✅ Database '{$dbName}' exists";
                } catch (\Exception $e) {
                    // If SHOW DATABASES fails, try to USE the database
                    try {
                        DB::statement("USE `{$dbName}`");
                        $output[] = "✅ Database '{$dbName}' is accessible";
                    } catch (\Exception $useEx) {
                        return [
                            'success' => false,
                            'message' => "Cannot access database '{$dbName}'. It may not exist or you don't have permission.",
                            'output' => array_merge($output, [
                                "❌ Cannot access database '{$dbName}'",
                                '💡 Error: ' . $useEx->getMessage(),
                            ]),
                        ];
                    }
                }
            }

            // Step 3: Run migrations
            $output[] = '🔄 Running database migrations...';
            Artisan::call('migrate', ['--force' => true]);
            $output[] = '✅ Migrations completed successfully';

            // Step 4: Seed required data (roles, permissions, legal pages)
            $output[] = '🌱 Seeding required data (roles, permissions)...';
            $this->seedRequiredData();
            $output[] = '✅ Required data seeded successfully';

            $output[] = '';
            $output[] = '🎉 Database setup completed successfully!';

            return [
                'success' => true,
                'message' => 'Database setup completed successfully',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Migration failed: '.$e->getMessage(),
                'output' => array_merge($output ?? [], [
                    '❌ Migration failed',
                    '💡 Error: ' . $e->getMessage(),
                ]),
            ];
        }
    }

    /**
     * Seed required data (roles, permissions, legal pages)
     */
    protected function seedRequiredData(): void
    {
        // Seed roles and permissions (required for user assignment)
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\RoleAndPermissionSeeder',
            '--force' => true,
        ]);

        // Clear Spatie Permission cache after seeding (critical for Gate::before() to work)
        Artisan::call('permission:cache-reset');

        // Explicitly sync all permissions to super_admin role (failsafe)
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $allPermissions = Permission::all();
            $superAdmin->syncPermissions($allPermissions);
        }

        // Seed legal pages with placeholders
        if (class_exists('Database\\Seeders\\LegalPagesSeeder')) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\LegalPagesSeeder',
                '--force' => true,
            ]);
        }
    }

    /**
     * Create Super Admin user and tenant
     */
    public function createSuperAdmin(array $data): array
    {
        try {
            DB::beginTransaction();

            // Get subscription limits based on tier
            $limits = TenantLimitsService::getLimits($data['subscription_tier']);

            // Extract domain from APP_URL
            $domain = parse_url(config('app.url'), PHP_URL_HOST);

            // Create tenant first
            $tenant = Tenant::create([
                'name' => $data['tenant_name'],
                'app_name' => $data['tenant_name'], // White-Label support: tenant-specific app name
                'slug' => \Str::slug($data['tenant_name']),
                'domain' => $domain,
                'database_name' => config('database.connections.'.config('database.default').'.database'),
                'billing_email' => $data['admin_email'], // Use admin email as billing email
                'country_code' => env('TENANT_COUNTRY', 'DE'),
                'timezone' => config('app.timezone', 'Europe/Berlin'),
                'locale' => $data['language'] ?? config('app.locale', 'de'),
                'currency' => env('TENANT_CURRENCY', 'EUR'),
                'subscription_tier' => $data['subscription_tier'],
                'subscription_status' => 'active',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30), // 30-day trial

                // Subscription limits from TenantLimitsService
                'max_users' => $limits['max_users'],
                'max_teams' => $limits['max_teams'],
                'max_storage_gb' => $limits['max_storage_gb'],
                'max_api_calls_per_hour' => $limits['max_api_calls_per_hour'],

                // Settings with features, branding, and contact
                'settings' => [
                    'language' => $data['language'] ?? 'de',
                    'timezone' => config('app.timezone'),
                    'features' => $limits['features'], // Feature flags
                    'branding' => [
                        'primary_color' => env('TENANT_PRIMARY_COLOR', '#4F46E5'),
                        'logo_url' => env('TENANT_LOGO_URL'),
                    ],
                    'contact' => [
                        'support_email' => env('TENANT_SUPPORT_EMAIL', $data['admin_email']),
                        'phone' => env('TENANT_PHONE'),
                    ],
                ],
            ]);

            // Create Super Admin user (system-level user, NOT bound to any tenant)
            $user = User::create([
                'tenant_id' => null, // ✅ Super Admins are tenant-independent (can manage ALL tenants)
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'language' => $data['language'] ?? 'de',
            ]);

            // Assign Super Admin role
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $user->assignRole($superAdminRole);
            } else {
                throw new \Exception('Super Admin role not found. Please run seeders first.');
            }

            // Create default club for the tenant
            $club = Club::create([
                'tenant_id' => $tenant->id,
                'name' => $data['tenant_name'],
                'description' => 'Default club created during installation',
                'founded_at' => now(),
                'settings' => [
                    'language' => $data['language'] ?? 'de',
                    'timezone' => config('app.timezone'),
                ],
            ]);

            // Super Admin bleibt club-unabhängig (keine club_user Verknüpfung)
            // Super Admin kann später manuell Clubs beitreten

            // Sync subscription plans with Stripe (if Stripe is configured)
            if (config('services.stripe.key') && config('services.stripe.secret')) {
                try {
                    Artisan::call('db:seed', [
                        '--class' => 'Database\\Seeders\\ClubSubscriptionPlanSeeder',
                        '--force' => true,
                    ]);
                } catch (\Exception $e) {
                    // Log but don't fail installation if Stripe sync fails
                    \Log::warning('Stripe plan sync failed during installation: '.$e->getMessage());
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Super Admin created successfully',
                'user' => $user,
                'tenant' => $tenant,
                'club' => $club,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Failed to create Super Admin: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Mark installation as complete
     */
    public function markAsInstalled(): void
    {
        // Create marker file
        $markerPath = storage_path('installed');
        file_put_contents($markerPath, now()->toDateTimeString());

        // Update .env file
        $envManager = app(EnvironmentManager::class);
        $envManager->saveEnvironment(['app_installed' => true]);

        // Clear all caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Clear Spatie Permission cache (critical for Super Admin Gate::before() bypass)
        Artisan::call('permission:cache-reset');
    }

    /**
     * Check if application is installed
     */
    public function isInstalled(): bool
    {
        $markerFile = storage_path('installed');
        $envInstalled = config('app.installed', false);

        return file_exists($markerFile) || $envInstalled === true;
    }

    /**
     * Unlock installation (for fresh installation)
     */
    public function unlockInstallation(): void
    {
        // Remove marker file
        $markerPath = storage_path('installed');
        if (file_exists($markerPath)) {
            unlink($markerPath);
        }

        // Update .env file
        $envManager = app(EnvironmentManager::class);
        $envManager->saveEnvironment(['app_installed' => false]);

        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }
}
