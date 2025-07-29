# Phase 1: Core Foundation PRD - BasketManager Pro Laravel

> **Product Requirements Document (PRD) - Phase 1**  
> **Version**: 1.0  
> **Datum**: 28. Juli 2025  
> **Status**: Entwicklungsbereit  
> **Autor**: Claude Code Assistant  
> **Dauer**: 3 Monate (Monate 1-3)

---

## 📋 Inhaltsverzeichnis

1. [Phase 1 Übersicht](#phase-1-übersicht)
2. [Laravel Foundation Setup](#laravel-foundation-setup)
3. [Authentication & Authorization](#authentication--authorization)
4. [Core Models & Database Design](#core-models--database-design)
5. [User Management System](#user-management-system)
6. [Team Management](#team-management)
7. [Player Management](#player-management)
8. [Club Management](#club-management)
9. [Dashboard & Navigation](#dashboard--navigation)
10. [API Foundation](#api-foundation)
11. [Testing Foundation](#testing-foundation)
12. [Deployment Setup](#deployment-setup)
13. [Phase 1 Deliverables](#phase-1-deliverables)

---

## 🎯 Phase 1 Übersicht

### Ziele der Foundation Phase

Phase 1 legt das solide Fundament für das gesamte BasketManager Pro System. In dieser Phase konzentrieren wir uns auf die Kernarchitektur, Authentifizierung, Benutzerverwaltung und die grundlegenden Basketball-Entitäten.

### Kernziele

1. **Laravel 11 Foundation**: Komplette Laravel-Installation mit optimierter Konfiguration
2. **Sichere Authentifizierung**: Multi-Guard Auth mit 2FA und Social Login
3. **RBAC System**: Rollenbasierte Zugriffskontrolle mit Spatie Laravel Permission
4. **Core Entities**: User, Club, Team, Player Models mit Relationships
5. **Admin Dashboard**: Grundlegendes Admin-Interface für Systemverwaltung
6. **API Foundation**: RESTful API-Basis mit Sanctum Authentication
7. **Testing Setup**: Umfassende Test-Infrastruktur
8. **Deployment Pipeline**: CI/CD mit Laravel Forge Integration

### Success Metrics

- ✅ Laravel 11 Setup mit allen Core-Services
- ✅ Authentication funktional (Web + API + 2FA)
- ✅ 4 Core Models implementiert mit Relationships
- ✅ Admin Dashboard mit User/Team/Player Management
- ✅ API v2 Endpoints für Core Entities
- ✅ 80%+ Test Coverage für Core Features
- ✅ Automated Deployment Pipeline

---

## 🏗️ Laravel Foundation Setup

### Laravel 11 Installation & Konfiguration

#### Initial Setup Commands
```bash
# Laravel 11 Installation mit Jetstream
composer create-project laravel/laravel basketmanager-pro
cd basketmanager-pro

# Jetstream mit Inertia.js und Vue 3
composer require laravel/jetstream
php artisan jetstream:install inertia --teams --api

# Essential Packages
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require spatie/laravel-media-library
composer require spatie/laravel-backup
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require intervention/image
composer require pusher/pusher-php-server

# Development Packages
composer require --dev laravel/telescope
composer require --dev barryvdh/laravel-debugbar
composer require --dev nunomaduro/collision

# Frontend Setup
npm install
npm install @inertiajs/vue3 @vitejs/plugin-vue
npm install vue@next @headlessui/vue @heroicons/vue
npm install chart.js vue-chartjs

# Install and configure Telescope
php artisan telescope:install
php artisan migrate

# Generate application key
php artisan key:generate
```

#### Environment Configuration

```env
# .env Configuration
APP_NAME="BasketManager Pro"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://basketmanager-pro.test

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basketmanager_pro
DB_USERNAME=root
DB_PASSWORD=

# Broadcasting
BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Pusher (for WebSockets)
PUSHER_APP_ID=basketmanager-app
PUSHER_APP_KEY=basketmanager-key
PUSHER_APP_SECRET=basketmanager-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@basketmanager-pro.local"
MAIL_FROM_NAME="${APP_NAME}"

# Basketball Specific
DEFAULT_SEASON=2024-25
BASKETBALL_TIMEZONE=Europe/Berlin
EMERGENCY_ACCESS_DURATION=8760 # 1 year in hours
QR_CODE_SERVICE_URL=https://api.qrserver.com/v1/create-qr-code/
```

#### Service Provider Registration

```php
// config/app.php - Additional Service Providers
'providers' => [
    // Laravel Framework Service Providers...
    
    // Package Service Providers
    Spatie\Permission\PermissionServiceProvider::class,
    Spatie\Activitylog\ActivitylogServiceProvider::class,
    Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Intervention\Image\ImageServiceProvider::class,

    // Application Service Providers
    App\Providers\BasketManagerServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
```

### Custom Service Provider

```php
<?php
// app/Providers/BasketManagerServiceProvider.php

namespace App\Providers;

use App\Models\User;
use App\Models\Team;
use App\Models\Player;
use App\Models\Club;
use App\Policies\UserPolicy;
use App\Policies\TeamPolicy;
use App\Policies\PlayerPolicy;
use App\Policies\ClubPolicy;
use App\Services\StatisticsService;
use App\Services\TeamService;
use App\Services\PlayerService;
use App\Services\EmergencyContactService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class BasketManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Services
        $this->app->singleton(StatisticsService::class);
        $this->app->singleton(TeamService::class);
        $this->app->singleton(PlayerService::class);
        $this->app->singleton(EmergencyContactService::class);
    }

    public function boot(): void
    {
        // Register Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
        Gate::policy(Club::class, ClubPolicy::class);

        // Custom Validation Rules
        Validator::extend('basketball_position', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['PG', 'SG', 'SF', 'PF', 'C']);
        });

        Validator::extend('jersey_number', function ($attribute, $value, $parameters, $validator) {
            return is_numeric($value) && $value >= 0 && $value <= 99;
        });

        Validator::extend('current_season', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^\d{4}-\d{2}$/', $value);
        });

        // Custom Blade Directives
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->can({$permission})): ?>";
        });

        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // Basketball-specific Blade Components
        Blade::component('basketball.team-card', 'team-card');
        Blade::component('basketball.player-card', 'player-card');
        Blade::component('basketball.stats-widget', 'stats-widget');
    }
}
```

---

## 🔐 Authentication & Authorization

### Multi-Guard Authentication System

#### Laravel Sanctum Configuration

```php
// config/sanctum.php
<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Str::startsWith(app()->environment(), 'local') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

#### Custom Auth Guards

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
        'hash' => false,
    ],

    'emergency' => [
        'driver' => 'emergency_access',
        'provider' => 'team_access',
    ],

    'admin' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    'team_access' => [
        'driver' => 'team_access',
        'model' => App\Models\TeamAccess::class,
    ],
],
```

### Two-Factor Authentication

#### 2FA Service Implementation

```php
<?php
// app/Services/TwoFactorAuthService.php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->getQrCodeUrl($user, $secret);
        return QrCode::size(200)->generate($qrCodeUrl);
    }

    public function enable2FA(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
        ]);

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes))
        ]);
    }

    public function disable2FA(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function verify(User $user, string $code): bool
    {
        if (!$user->two_factor_enabled) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );

        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $remainingCodes = array_diff($recoveryCodes, [$code]);
            $user->update([
                'two_factor_recovery_codes' => Crypt::encryptString(json_encode($remainingCodes))
            ]);
            
            return true;
        }

        return false;
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        }
        return $codes;
    }
}
```

### Social Authentication

#### Laravel Socialite Configuration

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
],

'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
],
```

#### Social Auth Controller

```php
<?php
// app/Http/Controllers/Auth/SocialAuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if user already exists with social account
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialAccount) {
                Auth::login($socialAccount->user);
                return redirect()->intended('/dashboard');
            }

            // Check if user exists with same email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ]);

                // Assign default role
                $user->assignRole('player');
            }

            // Create social account
            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]);

            Auth::login($user);
            
            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['social' => 'Social login failed. Please try again.']);
        }
    }
}
```

### Role-Based Access Control (RBAC)

#### Permission System Setup

```php
<?php
// database/seeders/RoleAndPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Club Management
            'view clubs',
            'create clubs',
            'edit clubs',
            'delete clubs',
            'manage club settings',
            
            // Team Management
            'view teams',
            'create teams',
            'edit teams',
            'delete teams',
            'manage team rosters',
            
            // Player Management
            'view players',
            'create players',
            'edit players',
            'delete players',
            'view player statistics',
            'edit player statistics',
            
            // Game Management
            'view games',
            'create games',
            'edit games',
            'delete games',
            'score games',
            'view live games',
            
            // Statistics
            'view statistics',
            'export statistics',
            'generate reports',
            
            // Emergency Contacts
            'view emergency contacts',
            'edit emergency contacts',
            'generate emergency qr codes',
            
            // System Administration
            'access admin panel',
            'manage system settings',
            'view activity logs',
            'manage backups',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $clubAdminRole = Role::create(['name' => 'club_admin']);
        $clubAdminRole->givePermissionTo([
            'view users', 'create users', 'edit users',
            'view clubs', 'edit clubs', 'manage club settings',
            'view teams', 'create teams', 'edit teams', 'manage team rosters',
            'view players', 'create players', 'edit players',
            'view games', 'create games', 'edit games',
            'view statistics', 'export statistics', 'generate reports',
            'view emergency contacts', 'edit emergency contacts', 'generate emergency qr codes',
        ]);

        $trainerRole = Role::create(['name' => 'trainer']);
        $trainerRole->givePermissionTo([
            'view teams', 'edit teams', 'manage team rosters',
            'view players', 'create players', 'edit players',
            'view games', 'create games', 'edit games', 'score games',
            'view statistics', 'view player statistics',
            'view emergency contacts', 'edit emergency contacts',
        ]);

        $scorerRole = Role::create(['name' => 'scorer']);
        $scorerRole->givePermissionTo([
            'view games', 'score games', 'view live games',
            'view statistics',
        ]);

        $playerRole = Role::create(['name' => 'player']);
        $playerRole->givePermissionTo([
            'view teams', 'view players', 'view games',
            'view statistics', 'view player statistics',
        ]);

        $parentRole = Role::create(['name' => 'parent']);
        $parentRole->givePermissionTo([
            'view teams', 'view players', 'view games',
            'view emergency contacts',
        ]);
    }
}
```

---

## 🗄️ Core Models & Database Design

### Database Migrations

#### Users Migration Enhancement

```php
<?php
// database/migrations/2024_01_01_000000_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('language')->default('de');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Two-Factor Authentication
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Profile
            $table->string('avatar_path', 2048)->nullable();
            $table->json('preferences')->nullable();
            $table->json('notification_settings')->nullable();
            
            // Basketball specific
            $table->boolean('player_profile_active')->default(false);
            $table->json('coaching_certifications')->nullable();
            
            // Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['email', 'is_active']);
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

#### Clubs Migration

```php
<?php
// database/migrations/2024_01_02_000000_create_clubs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 10)->nullable();
            $table->string('registration_number')->unique()->nullable(); // Vereinsregisternummer
            $table->text('description')->nullable();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            
            // Address
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Deutschland');
            
            // Club Details
            $table->date('founded_date')->nullable();
            $table->string('president')->nullable(); // Vereinspräsident
            $table->string('treasurer')->nullable(); // Schatzmeister
            $table->json('colors')->nullable(); // Vereinsfarben
            $table->string('logo_path')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('membership_type', ['free', 'basic', 'premium', 'enterprise'])->default('free');
            
            // Billing
            $table->string('billing_email')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('tax_id')->nullable(); // Steuernummer
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['name', 'is_active']);
            $table->index('postal_code');
            $table->unique(['registration_number'], 'clubs_registration_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
```

#### Teams Migration

```php
<?php
// database/migrations/2024_01_03_000000_create_teams_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 10)->nullable();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            
            // Team Classification
            $table->enum('category', [
                'U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 
                'Herren', 'Damen', 'Senioren', 'Mixed'
            ]);
            $table->string('age_group')->nullable(); // e.g., "2010/2011"
            $table->enum('gender', ['male', 'female', 'mixed']);
            
            // Season and League
            $table->string('season'); // e.g., "2024-25"
            $table->string('league')->nullable(); // e.g., "Oberliga Bayern"
            $table->string('division')->nullable(); // e.g., "Staffel Nord"
            
            // Coaching Staff
            $table->foreignId('head_coach_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assistant_coach_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Team Details
            $table->text('description')->nullable();
            $table->json('training_days')->nullable(); // e.g., ["monday", "wednesday", "friday"]
            $table->time('training_time')->nullable();
            $table->string('home_venue')->nullable();
            $table->json('team_colors')->nullable();
            $table->string('logo_path')->nullable();
            
            // Statistics
            $table->integer('max_players')->default(15);
            $table->integer('current_players')->default(0);
            $table->decimal('budget', 10, 2)->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'disbanded', 'archived'])->default('active');
            $table->boolean('is_competitive')->default(true); // Wettkampfmannschaft
            $table->boolean('accepts_new_players')->default(true);
            
            // Settings
            $table->json('settings')->nullable();
            $table->json('contact_persons')->nullable(); // Zusätzliche Ansprechpartner
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['club_id', 'season', 'status']);
            $table->index(['category', 'gender']);
            $table->index(['season', 'league']);
            $table->unique(['club_id', 'name', 'season'], 'teams_club_name_season_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

#### Players Migration

```php
<?php
// database/migrations/2024_01_04_000000_create_players_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nickname')->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality')->default('Deutsch');
            $table->string('birth_place')->nullable();
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('parent_phone')->nullable(); // For minors
            
            // Address
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            
            // Basketball Information
            $table->integer('jersey_number')->nullable();
            $table->enum('position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable();
            $table->enum('preferred_position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable();
            $table->integer('height')->nullable(); // in cm
            $table->decimal('weight', 5, 2)->nullable(); // in kg
            $table->enum('dominant_hand', ['left', 'right', 'both'])->default('right');
            
            // Experience
            $table->date('basketball_since')->nullable();
            $table->text('previous_clubs')->nullable();
            $table->text('achievements')->nullable();
            
            // Team Information
            $table->date('joined_team_at');
            $table->date('contract_until')->nullable();
            $table->boolean('is_captain')->default(false);
            $table->boolean('is_vice_captain')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('player_status', [
                'active', 'injured', 'suspended', 'inactive', 'transferred'
            ])->default('active');
            
            // Medical Information (encrypted)
            $table->text('medical_conditions')->nullable(); // Encrypted
            $table->text('medications')->nullable(); // Encrypted
            $table->text('allergies')->nullable(); // Encrypted
            $table->text('doctor_contact')->nullable(); // Encrypted
            $table->text('insurance_info')->nullable(); // Encrypted
            
            // Legal Information
            $table->boolean('medical_consent_given')->default(false);
            $table->timestamp('medical_consent_date')->nullable();
            $table->boolean('photo_consent_given')->default(false);
            $table->timestamp('photo_consent_date')->nullable();
            $table->boolean('data_processing_consent')->default(false);
            $table->timestamp('data_processing_consent_date')->nullable();
            
            // Parent/Guardian Information (for minors)
            $table->string('parent_name')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('parent_phone_primary')->nullable();
            $table->string('parent_phone_secondary')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_contact')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            $table->text('notes')->nullable(); // Coach notes
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'is_active']);
            $table->index(['first_name', 'last_name']);
            $table->index(['jersey_number', 'team_id']);
            $table->index('birth_date');
            $table->unique(['team_id', 'jersey_number'], 'players_team_jersey_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
```

### Model Relationships

#### User Model

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'gender',
        'bio',
        'timezone',
        'language',
        'avatar_path',
        'preferences',
        'notification_settings',
        'player_profile_active',
        'coaching_certifications',
        'last_login_at',
        'last_login_ip',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'preferences' => 'array',
        'notification_settings' => 'array',
        'coaching_certifications' => 'array',
        'player_profile_active' => 'boolean',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships
    public function playerProfile(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    public function coachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'head_coach_id');
    }

    public function assistantCoachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'assistant_coach_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_members')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCoaches($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['trainer', 'club_admin', 'admin']);
        });
    }

    public function scopePlayers($query)
    {
        return $query->where('player_profile_active', true)
                    ->whereHas('playerProfile');
    }

    // Accessors
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar_path 
            ? asset('storage/' . $this->avatar_path)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    // Helper Methods
    public function isCoach(): bool
    {
        return $this->hasAnyRole(['trainer', 'club_admin', 'admin']);
    }

    public function isPlayer(): bool
    {
        return $this->player_profile_active && $this->playerProfile()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasTeamAccess(Team $team, array $permissions = []): bool
    {
        // Admin has access to everything
        if ($this->hasRole('admin')) {
            return true;
        }

        // Club admin has access to all teams in their clubs
        if ($this->hasRole('club_admin')) {
            return $this->clubs()->where('clubs.id', $team->club_id)->exists();
        }

        // Coach has access to their teams
        if ($team->head_coach_id === $this->id || $team->assistant_coach_id === $this->id) {
            return true;
        }

        // Player has access to their own team
        if ($this->playerProfile && $this->playerProfile->team_id === $team->id) {
            return true;
        }

        return false;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### Club Model

```php
<?php
// app/Models/Club.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Club extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'name',
        'short_name',
        'registration_number',
        'description',
        'email',
        'phone',
        'website',
        'street',
        'street_number',
        'postal_code',
        'city',
        'state',
        'country',
        'founded_date',
        'president',
        'treasurer',
        'colors',
        'logo_path',
        'settings',
        'is_active',
        'membership_type',
        'billing_email',
        'billing_address',
        'tax_id',
    ];

    protected $casts = [
        'founded_date' => 'date',
        'colors' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'billing_address' => 'array',
    ];

    // Relationships
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_members')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(Player::class, Team::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMembershipType($query, string $type)
    {
        return $query->where('membership_type', $type);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street . ($this->street_number ? ' ' . $this->street_number : ''),
            $this->postal_code . ($this->city ? ' ' . $this->city : ''),
            $this->state,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logos');
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logos')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('documents')
              ->acceptsMimeTypes(['application/pdf', 'application/msword']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(300)
              ->height(300)
              ->performOnCollections('logos');
    }

    // Helper Methods
    public function getActiveTeamsCount(): int
    {
        return $this->teams()->active()->count();
    }

    public function getActivePlyersCount(): int
    {
        return $this->players()->whereHas('team', function ($query) {
            $query->where('status', 'active');
        })->where('is_active', true)->count();
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_active', 'membership_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### Team Model

```php
<?php
// app/Models/Team.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

class Team extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Searchable;

    protected $fillable = [
        'name',
        'short_name',
        'club_id',
        'category',
        'age_group',
        'gender',
        'season',
        'league',
        'division',
        'head_coach_id',
        'assistant_coach_id',
        'description',
        'training_days',
        'training_time',
        'home_venue',
        'team_colors',
        'logo_path',
        'max_players',
        'current_players',
        'budget',
        'status',
        'is_competitive',
        'accepts_new_players',
        'settings',
        'contact_persons',
    ];

    protected $casts = [
        'training_days' => 'array',
        'training_time' => 'datetime:H:i',
        'team_colors' => 'array',
        'budget' => 'decimal:2',
        'is_competitive' => 'boolean',
        'accepts_new_players' => 'boolean',
        'settings' => 'array',
        'contact_persons' => 'array',
    ];

    // Relationships
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function headCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_coach_id');
    }

    public function assistantCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_coach_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->hasMany(Player::class)->where('is_active', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeCompetitive($query)
    {
        return $query->where('is_competitive', true);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->club->short_name . ' ' . $this->name;
    }

    public function getPlayersSlotsAvailableAttribute(): int
    {
        return max(0, $this->max_players - $this->activePlayers()->count());
    }

    public function getAveragePlayerAgeAttribute(): ?float
    {
        return $this->activePlayers()
                   ->whereNotNull('birth_date')
                   ->get()
                   ->avg(fn($player) => $player->birth_date->age);
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'category' => $this->category,
            'gender' => $this->gender,
            'season' => $this->season,
            'league' => $this->league,
            'club_name' => $this->club->name,
            'status' => $this->status,
        ];
    }

    // Helper Methods
    public function updatePlayerCount(): void
    {
        $this->update([
            'current_players' => $this->activePlayers()->count()
        ]);
    }

    public function canAcceptNewPlayer(): bool
    {
        return $this->accepts_new_players && 
               $this->status === 'active' && 
               $this->activePlayers()->count() < $this->max_players;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'head_coach_id', 'max_players'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### Player Model

```php
<?php
// app/Models/Player.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

class Player extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Searchable;

    protected $fillable = [
        'user_id',
        'team_id',
        'first_name',
        'last_name',
        'nickname',
        'birth_date',
        'gender',
        'nationality',
        'birth_place',
        'email',
        'phone',
        'parent_phone',
        'street',
        'street_number',
        'postal_code',
        'city',
        'jersey_number',
        'position',
        'preferred_position',
        'height',
        'weight',
        'dominant_hand',
        'basketball_since',
        'previous_clubs',
        'achievements',
        'joined_team_at',
        'contract_until',
        'is_captain',
        'is_vice_captain',
        'is_active',
        'player_status',
        'medical_conditions',
        'medications',
        'allergies',
        'doctor_contact',
        'insurance_info',
        'medical_consent_given',
        'medical_consent_date',
        'photo_consent_given',
        'photo_consent_date',
        'data_processing_consent',
        'data_processing_consent_date',
        'parent_name',
        'parent_email',
        'parent_phone_primary',
        'parent_phone_secondary',
        'guardian_name',
        'guardian_contact',
        'settings',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'basketball_since' => 'date',
        'joined_team_at' => 'date',
        'contract_until' => 'date',
        'height' => 'integer',
        'weight' => 'decimal:2',
        'is_captain' => 'boolean',
        'is_vice_captain' => 'boolean',
        'is_active' => 'boolean',
        'medical_consent_given' => 'boolean',
        'medical_consent_date' => 'datetime',
        'photo_consent_given' => 'boolean',
        'photo_consent_date' => 'datetime',
        'data_processing_consent' => 'boolean',
        'data_processing_consent_date' => 'datetime',
        'settings' => 'array',
        'medical_conditions' => 'encrypted',
        'medications' => 'encrypted',
        'allergies' => 'encrypted',
        'doctor_contact' => 'encrypted',
        'insurance_info' => 'encrypted',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeMinors($query)
    {
        return $query->where('birth_date', '>', now()->subYears(18));
    }

    public function scopeCaptains($query)
    {
        return $query->where(function ($q) {
            $q->where('is_captain', true)
              ->orWhere('is_vice_captain', true);
        });
    }

    // Accessors & Mutators
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->first_name . ' ' . $this->last_name,
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->birth_date?->age,
        );
    }

    public function isMinor(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->age < 18,
        );
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->nickname ?: $this->full_name,
        );
    }

    public function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn() => trim(implode(' ', [
                $this->street,
                $this->street_number,
                $this->postal_code,
                $this->city
            ])) ?: null,
        );
    }

    public function bmi(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->height && $this->weight 
                ? round($this->weight / (($this->height / 100) ** 2), 2)
                : null,
        );
    }

    public function basketballExperience(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->basketball_since?->diffInYears(now()),
        );
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'jersey_number' => $this->jersey_number,
            'position' => $this->position,
            'team_name' => $this->team->name,
            'club_name' => $this->team->club->name,
            'category' => $this->team->category,
            'season' => $this->team->season,
        ];
    }

    // Helper Methods
    public function getPrimaryEmergencyContact(): ?EmergencyContact
    {
        return $this->emergencyContacts()->where('is_primary', true)->first();
    }

    public function hasValidConsents(): bool
    {
        $required = [
            'medical_consent_given',
            'photo_consent_given',
            'data_processing_consent'
        ];

        foreach ($required as $consent) {
            if (!$this->$consent) {
                return false;
            }
        }

        return true;
    }

    public function isEligibleToPlay(): bool
    {
        return $this->is_active && 
               $this->player_status === 'active' && 
               $this->hasValidConsents();
    }

    public function getPositionDisplayName(): string
    {
        $positions = [
            'PG' => 'Point Guard',
            'SG' => 'Shooting Guard', 
            'SF' => 'Small Forward',
            'PF' => 'Power Forward',
            'C' => 'Center'
        ];

        return $positions[$this->position] ?? $this->position;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'team_id', 'jersey_number', 'position', 'is_active', 
                'player_status', 'is_captain', 'is_vice_captain'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

---

## 👥 User Management System

### User Management Controller

```php
<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {
        $this->middleware(['auth', 'role:admin|club_admin']);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles', 'playerProfile.team'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => Role::all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::all(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->createUser($request->validated());

        return redirect()->route('admin.users.index')
                        ->with('success', 'Benutzer erfolgreich erstellt.');
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'roles.permissions',
            'playerProfile.team.club',
            'coachedTeams.club',
            'socialAccounts',
            'activities' => function ($query) {
                $query->latest()->limit(20);
            }
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->updateUser($user, $request->validated());

        return redirect()->route('admin.users.show', $user)
                        ->with('success', 'Benutzer erfolgreich aktualisiert.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user);

        return redirect()->route('admin.users.index')
                        ->with('success', 'Benutzer erfolgreich gelöscht.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        return back()->with('success', 'Benutzer wurde aktiviert.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => false]);

        return back()->with('success', 'Benutzer wurde deaktiviert.');
    }

    public function impersonate(User $user): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        session(['impersonating' => $user->id]);

        return redirect()->route('dashboard')
                        ->with('info', "Sie agieren jetzt als {$user->name}.");
    }

    public function stopImpersonating(): RedirectResponse
    {
        session()->forget('impersonating');

        return redirect()->route('admin.users.index')
                        ->with('info', 'Impersonalisierung beendet.');
    }
}
```

### User Service

```php
<?php
// app/Services/UserService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserService
{
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? Str::random(12)),
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'timezone' => $data['timezone'] ?? config('app.timezone'),
                'language' => $data['language'] ?? 'de',
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Assign roles
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            } else {
                $user->assignRole('player'); // Default role
            }

            // Send password reset email if no password provided
            if (!isset($data['password'])) {
                Password::sendResetLink(['email' => $user->email]);
            }

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $user->phone,
                'birth_date' => $data['birth_date'] ?? $user->birth_date,
                'gender' => $data['gender'] ?? $user->gender,
                'bio' => $data['bio'] ?? $user->bio,
                'timezone' => $data['timezone'] ?? $user->timezone,
                'language' => $data['language'] ?? $user->language,
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            // Update password if provided
            if (isset($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            // Sync roles
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user;
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // If user has a player profile, handle it appropriately
            if ($user->playerProfile) {
                // Don't delete if player is active in current season
                if ($user->playerProfile->isEligibleToPlay()) {
                    throw new \Exception('Aktive Spieler können nicht gelöscht werden.');
                }
                
                $user->playerProfile->delete();
            }

            // Remove from all teams as coach
            $user->coachedTeams()->update(['head_coach_id' => null]);
            $user->assistantCoachedTeams()->update(['assistant_coach_id' => null]);

            // Soft delete the user
            return $user->delete();
        });
    }

    public function sendPasswordReset(User $user): string
    {
        return Password::sendResetLink(['email' => $user->email]);
    }

    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);
        return $user;
    }

    public function deactivateUser(User $user): User
    {
        $user->update(['is_active' => false]);
        return $user;
    }

    public function getUserStatistics(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'coaches' => User::coaches()->count(),
            'players' => User::players()->count(),
            'recent_signups' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'with_2fa' => User::where('two_factor_enabled', true)->count(),
        ];
    }
}
```

---

## 🏀 Team Management

### Team Management Controller

```php
<?php
// app/Http/Controllers/TeamController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use App\Models\Club;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->with(['club', 'headCoach', 'assistantCoach'])
            ->withCount(['players', 'activePlayers'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('club', function ($club) use ($search) {
                          $club->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->club_id, function ($query, $clubId) {
                $query->where('club_id', $clubId);
            })
            ->when($request->season, function ($query, $season) {
                $query->where('season', $season);
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'filters' => $request->only(['search', 'club_id', 'season', 'category', 'status']),
            'clubs' => Club::active()->get(['id', 'name']),
            'seasons' => $this->getAvailableSeasons(),
            'categories' => $this->getTeamCategories(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Teams/Create', [
            'clubs' => Club::active()->get(['id', 'name']),
            'coaches' => User::coaches()->active()->get(['id', 'name']),
            'categories' => $this->getTeamCategories(),
            'genderOptions' => $this->getGenderOptions(),
            'currentSeason' => $this->getCurrentSeason(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->authorize('create', Team::class);

        $team = $this->teamService->createTeam($request->validated());

        return redirect()->route('teams.show', $team)
                        ->with('success', 'Team erfolgreich erstellt.');
    }

    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load([
            'club',
            'headCoach',
            'assistantCoach',
            'players' => function ($query) {
                $query->with('emergencyContacts')
                     ->orderBy('jersey_number')
                     ->orderBy('last_name');
            }
        ]);

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'statistics' => $this->teamService->getTeamStatistics($team),
            'canEdit' => auth()->user()->can('update', $team),
            'canManageRoster' => auth()->user()->can('manageRoster', $team),
        ]);
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'clubs' => Club::active()->get(['id', 'name']),
            'coaches' => User::coaches()->active()->get(['id', 'name']),
            'categories' => $this->getTeamCategories(),
            'genderOptions' => $this->getGenderOptions(),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $this->teamService->updateTeam($team, $request->validated());

        return redirect()->route('teams.show', $team)
                        ->with('success', 'Team erfolgreich aktualisiert.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $this->teamService->deleteTeam($team);

        return redirect()->route('teams.index')
                        ->with('success', 'Team erfolgreich gelöscht.');
    }

    private function getAvailableSeasons(): array
    {
        return Team::distinct('season')
                  ->orderBy('season', 'desc')
                  ->pluck('season')
                  ->toArray();
    }

    private function getTeamCategories(): array
    {
        return [
            'U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20',
            'Herren', 'Damen', 'Senioren', 'Mixed'
        ];
    }

    private function getGenderOptions(): array
    {
        return [
            ['value' => 'male', 'label' => 'Männlich'],
            ['value' => 'female', 'label' => 'Weiblich'],
            ['value' => 'mixed', 'label' => 'Mixed'],
        ];
    }

    private function getCurrentSeason(): string
    {
        return config('basketball.current_season', '2024-25');
    }
}
```

### Team Service

```php
<?php
// app/Services/TeamService.php

namespace App\Services;

use App\Models\Team;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function createTeam(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'name' => $data['name'],
                'short_name' => $data['short_name'] ?? null,
                'club_id' => $data['club_id'],
                'category' => $data['category'],
                'age_group' => $data['age_group'] ?? null,
                'gender' => $data['gender'],
                'season' => $data['season'],
                'league' => $data['league'] ?? null,
                'division' => $data['division'] ?? null,
                'head_coach_id' => $data['head_coach_id'] ?? null,
                'assistant_coach_id' => $data['assistant_coach_id'] ?? null,
                'description' => $data['description'] ?? null,
                'training_days' => $data['training_days'] ?? null,
                'training_time' => $data['training_time'] ?? null,
                'home_venue' => $data['home_venue'] ?? null,
                'team_colors' => $data['team_colors'] ?? null,
                'max_players' => $data['max_players'] ?? 15,
                'budget' => $data['budget'] ?? null,
                'is_competitive' => $data['is_competitive'] ?? true,
                'accepts_new_players' => $data['accepts_new_players'] ?? true,
                'settings' => $data['settings'] ?? null,
                'contact_persons' => $data['contact_persons'] ?? null,
            ]);

            // Log team creation
            activity()
                ->performedOn($team)
                ->causedBy(auth()->user())
                ->log('team_created');

            return $team;
        });
    }

    public function updateTeam(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            $team->update($data);

            // Update player count if it changed
            if ($team->wasChanged('max_players')) {
                $team->updatePlayerCount();
            }

            // Log team update
            activity()
                ->performedOn($team)
                ->causedBy(auth()->user())
                ->withProperties(['changes' => $team->getChanges()])
                ->log('team_updated');

            return $team;
        });
    }

    public function deleteTeam(Team $team): bool
    {
        return DB::transaction(function () use ($team) {
            // Check if team has active players
            if ($team->activePlayers()->count() > 0) {
                throw new \Exception('Teams mit aktiven Spielern können nicht gelöscht werden.');
            }

            // Log team deletion
            activity()
                ->performedOn($team)
                ->causedBy(auth()->user())
                ->log('team_deleted');

            return $team->delete();
        });
    }

    public function getTeamStatistics(Team $team): array
    {
        $players = $team->activePlayers()->with('emergencyContacts')->get();

        return [
            'total_players' => $players->count(),
            'players_with_emergency_contacts' => $players->filter(function ($player) {
                return $player->emergencyContacts->count() > 0;
            })->count(),
            'average_age' => $players->avg(fn($player) => $player->age),
            'positions' => $players->groupBy('position')->map->count(),
            'captains' => $players->where('is_captain', true)->count() + 
                        $players->where('is_vice_captain', true)->count(),
            'players_with_consents' => $players->filter(function ($player) {
                return $player->hasValidConsents();
            })->count(),
            'minors' => $players->filter(fn($player) => $player->is_minor)->count(),
        ];
    }

    public function addPlayerToTeam(Team $team, array $playerData): Player
    {
        if (!$team->canAcceptNewPlayer()) {
            throw new \Exception('Team kann keine neuen Spieler aufnehmen.');
        }

        return DB::transaction(function () use ($team, $playerData) {
            $player = Player::create(array_merge($playerData, [
                'team_id' => $team->id,
                'joined_team_at' => now(),
            ]));

            $team->updatePlayerCount();

            // Log player addition
            activity()
                ->performedOn($team)
                ->causedBy(auth()->user())
                ->withProperties(['player_id' => $player->id])
                ->log('player_added_to_team');

            return $player;
        });
    }

    public function removePlayerFromTeam(Team $team, Player $player): bool
    {
        return DB::transaction(function () use ($team, $player) {
            $player->update(['is_active' => false]);

            $team->updatePlayerCount();

            // Log player removal
            activity()
                ->performedOn($team)
                ->causedBy(auth()->user())
                ->withProperties(['player_id' => $player->id])
                ->log('player_removed_from_team');

            return true;
        });
    }

    public function generateTeamReport(Team $team): array
    {
        return [
            'team_info' => [
                'name' => $team->full_name,
                'season' => $team->season,
                'category' => $team->category,
                'league' => $team->league,
                'head_coach' => $team->headCoach?->name,
            ],
            'roster' => $team->activePlayers()->with('emergencyContacts')->get(),
            'statistics' => $this->getTeamStatistics($team),
            'emergency_contacts' => $team->activePlayers()
                ->with('emergencyContacts')
                ->get()
                ->flatMap(fn($player) => $player->emergencyContacts)
                ->groupBy('player_id'),
        ];
    }
}
```

---

## 🎯 Dashboard & Navigation

### Main Dashboard Controller

```php
<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\Player;
use App\Models\Club;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {
        $this->middleware('auth');
    }

    public function index(): Response
    {
        $user = auth()->user();
        
        $dashboardData = match (true) {
            $user->hasRole('admin') => $this->getAdminDashboard($user),
            $user->hasRole('club_admin') => $this->getClubAdminDashboard($user),
            $user->hasRole('trainer') => $this->getTrainerDashboard($user),
            $user->hasRole('player') => $this->getPlayerDashboard($user),
            default => $this->getBasicDashboard($user),
        };

        return Inertia::render('Dashboard', $dashboardData);
    }

    private function getAdminDashboard(User $user): array
    {
        return [
            'userRole' => 'admin',
            'statistics' => [
                'total_clubs' => Club::count(),
                'active_clubs' => Club::active()->count(),
                'total_teams' => Team::count(),
                'active_teams' => Team::active()->count(),
                'total_players' => Player::count(),
                'active_players' => Player::active()->count(),
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
            ],
            'recent_activities' => activity()
                ->latest()
                ->limit(10)
                ->with('causer', 'subject')
                ->get(),
            'system_health' => $this->getSystemHealth(),
        ];
    }

    private function getClubAdminDashboard(User $user): array
    {
        $clubs = $user->clubs()->with(['teams.players'])->get();
        
        return [
            'userRole' => 'club_admin',
            'clubs' => $clubs,
            'statistics' => [
                'managed_clubs' => $clubs->count(),
                'total_teams' => $clubs->sum(fn($club) => $club->teams->count()),
                'total_players' => $clubs->sum(fn($club) => $club->players->count()),
                'active_teams' => $clubs->sum(fn($club) => $club->teams->where('status', 'active')->count()),
            ],
            'recent_activities' => activity()
                ->where('causer_id', $user->id)
                ->orWhereIn('subject_id', $clubs->pluck('id'))
                ->latest()
                ->limit(10)
                ->with('subject')
                ->get(),
        ];
    }

    private function getTrainerDashboard(User $user): array
    {
        $coachedTeams = $user->coachedTeams()
            ->with(['players', 'club'])
            ->active()
            ->get();

        $assistantTeams = $user->assistantCoachedTeams()
            ->with(['players', 'club'])
            ->active()
            ->get();

        $allTeams = $coachedTeams->merge($assistantTeams);

        return [
            'userRole' => 'trainer',
            'teams' => $allTeams,
            'statistics' => [
                'head_coach_teams' => $coachedTeams->count(),
                'assistant_coach_teams' => $assistantTeams->count(),
                'total_players' => $allTeams->sum(fn($team) => $team->players->count()),
                'upcoming_games' => 0, // Will be implemented in Phase 2
            ],
            'recent_activities' => activity()
                ->where('causer_id', $user->id)
                ->whereIn('subject_type', ['App\\Models\\Team', 'App\\Models\\Player'])
                ->latest()
                ->limit(10)
                ->with('subject')
                ->get(),
        ];
    }

    private function getPlayerDashboard(User $user): array
    {
        $player = $user->playerProfile;
        
        if (!$player) {
            return [
                'userRole' => 'player',
                'message' => 'Kein Spielerprofil gefunden. Bitte wenden Sie sich an Ihren Trainer.',
            ];
        }

        $team = $player->team()->with('club')->first();

        return [
            'userRole' => 'player',
            'player' => $player,
            'team' => $team,
            'statistics' => [
                'games_played' => 0, // Will be implemented in Phase 2
                'points_scored' => 0,
                'season_avg' => 0,
                'position_rank' => null,
            ],
            'upcoming_events' => [], // Will be implemented in Phase 2
            'emergency_contacts' => $player->emergencyContacts,
        ];
    }

    private function getBasicDashboard(User $user): array
    {
        return [
            'userRole' => 'basic',
            'message' => 'Willkommen bei BasketManager Pro!',
            'available_actions' => [
                'view_teams' => $user->can('view teams'),
                'view_players' => $user->can('view players'),
                'view_statistics' => $user->can('view statistics'),
            ],
        ];
    }

    private function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabaseConnection(),
            'redis' => $this->checkRedisConnection(),
            'storage' => $this->checkStorageWritable(),
            'queue' => $this->checkQueueWorking(),
        ];
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkRedisConnection(): bool
    {
        try {
            \Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkStorageWritable(): bool
    {
        return is_writable(storage_path());
    }

    private function checkQueueWorking(): bool
    {
        // Simple check - in production you'd want more sophisticated monitoring
        return true;
    }
}
```

### Navigation Menu Component

```vue
<!-- resources/js/Components/Navigation/MainNavigation.vue -->
<template>
  <nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo and primary navigation -->
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <Link :href="route('dashboard')" class="flex items-center">
              <img class="h-8 w-auto" src="/images/logo.svg" alt="BasketManager Pro" />
              <span class="ml-2 text-xl font-semibold text-gray-900">BasketManager Pro</span>
            </Link>
          </div>

          <!-- Primary Navigation Menu -->
          <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
              Dashboard
            </NavLink>

            <NavLink 
              v-if="$page.props.auth.user.permissions.includes('view teams')"
              :href="route('teams.index')" 
              :active="route().current('teams.*')"
            >
              Teams
            </NavLink>

            <NavLink 
              v-if="$page.props.auth.user.permissions.includes('view players')"
              :href="route('players.index')" 
              :active="route().current('players.*')"
            >
              Spieler
            </NavLink>

            <NavLink 
              v-if="$page.props.auth.user.permissions.includes('view games')"
              :href="route('games.index')" 
              :active="route().current('games.*')"
            >
              Spiele
            </NavLink>

            <NavLink 
              v-if="$page.props.auth.user.permissions.includes('view statistics')"
              :href="route('statistics.index')" 
              :active="route().current('statistics.*')"
            >
              Statistiken
            </NavLink>

            <NavLink 
              v-if="$page.props.auth.user.roles.includes('admin')"
              :href="route('admin.index')" 
              :active="route().current('admin.*')"
            >
              Administration
            </NavLink>
          </div>
        </div>

        <!-- User menu -->
        <div class="hidden sm:flex sm:items-center sm:ml-6">
          <!-- Notifications -->
          <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <BellIcon class="h-6 w-6" />
          </button>

          <!-- Profile dropdown -->
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <img class="h-8 w-8 rounded-full" :src="$page.props.auth.user.avatar_url" :alt="$page.props.auth.user.name" />
              </button>
            </template>

            <template #content>
              <div class="px-4 py-2 text-xs text-gray-400">
                {{ $page.props.auth.user.email }}
              </div>

              <DropdownLink :href="route('profile.show')">
                Profil
              </DropdownLink>

              <DropdownLink :href="route('settings.index')">
                Einstellungen
              </DropdownLink>

              <div class="border-t border-gray-100"></div>

              <form @submit.prevent="logout">
                <DropdownLink as="button">
                  Abmelden
                </DropdownLink>
              </form>
            </template>
          </Dropdown>
        </div>

        <!-- Mobile menu button -->
        <div class="-mr-2 flex items-center sm:hidden">
          <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
      <div class="pt-2 pb-3 space-y-1">
        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
          Dashboard
        </ResponsiveNavLink>

        <ResponsiveNavLink 
          v-if="$page.props.auth.user.permissions.includes('view teams')"
          :href="route('teams.index')" 
          :active="route().current('teams.*')"
        >
          Teams
        </ResponsiveNavLink>

        <!-- Add other mobile nav links -->
      </div>

      <!-- Mobile user menu -->
      <div class="pt-4 pb-1 border-t border-gray-200">
        <div class="px-4">
          <div class="font-medium text-base text-gray-800">{{ $page.props.auth.user.name }}</div>
          <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
        </div>

        <div class="mt-3 space-y-1">
          <ResponsiveNavLink :href="route('profile.show')">
            Profil
          </ResponsiveNavLink>

          <ResponsiveNavLink :href="route('settings.index')">
            Einstellungen
          </ResponsiveNavLink>

          <form method="POST" @submit.prevent="logout">
            <ResponsiveNavLink as="button">
              Abmelden
            </ResponsiveNavLink>
          </form>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { BellIcon } from '@heroicons/vue/24/outline'

const showingNavigationDropdown = ref(false)

const logout = () => {
    router.post(route('logout'))
}
</script>
```

---

## 🔌 API Foundation

### API Base Controller

```php
<?php
// app/Http/Controllers/Api/V2/ApiController.php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected int $defaultPerPage = 15;
    protected int $maxPerPage = 100;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    protected function successResponse($data = null, string $message = '', int $status = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', $this->defaultPerPage);
        
        return min($perPage, $this->maxPerPage);
    }

    protected function getRequestedIncludes(Request $request, array $allowed = []): array
    {
        $includes = explode(',', $request->get('include', ''));
        
        return array_intersect($allowed, array_filter($includes));
    }
}
```

### Teams API Controller

```php
<?php
// app/Http/Controllers/Api/V2/TeamsController.php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\StoreTeamRequest;
use App\Http\Requests\Api\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamsController extends ApiController
{
    public function __construct(
        private TeamService $teamService
    ) {
        parent::__construct();
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()
            ->when($request->club_id, fn($q) => $q->where('club_id', $request->club_id))
            ->when($request->season, fn($q) => $q->where('season', $request->season))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhereHas('club', fn($club) => $club->where('name', 'like', "%{$search}%"));
                });
            })
            ->with($this->getRequestedIncludes($request, [
                'club', 'headCoach', 'assistantCoach', 'players', 'activePlayers'
            ]))
            ->withCount(['players', 'activePlayers'])
            ->paginate($this->getPerPage($request));

        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $team = $this->teamService->createTeam($request->validated());

        return $this->successResponse(
            new TeamResource($team->load('club', 'headCoach')),
            'Team erfolgreich erstellt.',
            201
        );
    }

    public function show(Team $team, Request $request): TeamResource
    {
        $this->authorize('view', $team);

        $team->load($this->getRequestedIncludes($request, [
            'club', 'headCoach', 'assistantCoach', 'players', 'activePlayers'
        ]));

        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $team = $this->teamService->updateTeam($team, $request->validated());

        return $this->successResponse(
            new TeamResource($team),
            'Team erfolgreich aktualisiert.'
        );
    }

    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        $this->teamService->deleteTeam($team);

        return $this->successResponse(null, 'Team erfolgreich gelöscht.');
    }

    public function statistics(Team $team): JsonResponse
    {
        $this->authorize('viewStatistics', $team);

        $statistics = $this->teamService->getTeamStatistics($team);

        return $this->successResponse($statistics);
    }

    public function roster(Team $team): AnonymousResourceCollection
    {
        $this->authorize('viewRoster', $team);

        $players = $team->players()
            ->with(['emergencyContacts'])
            ->orderBy('jersey_number')
            ->orderBy('last_name')
            ->get();

        return PlayerResource::collection($players);
    }
}
```

### Team API Resource

```php
<?php
// app/Http/Resources/TeamResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'full_name' => $this->full_name,
            'category' => $this->category,
            'age_group' => $this->age_group,
            'gender' => $this->gender,
            'season' => $this->season,
            'league' => $this->league,
            'division' => $this->division,
            'description' => $this->description,
            'training_days' => $this->training_days,
            'training_time' => $this->training_time?->format('H:i'),
            'home_venue' => $this->home_venue,
            'team_colors' => $this->team_colors,
            'max_players' => $this->max_players,
            'current_players' => $this->current_players,
            'players_slots_available' => $this->players_slots_available,
            'budget' => $this->budget,
            'status' => $this->status,
            'is_competitive' => $this->is_competitive,
            'accepts_new_players' => $this->accepts_new_players,
            'average_player_age' => $this->when(
                $this->relationLoaded('players'),
                $this->average_player_age
            ),
            
            // Relationships
            'club' => new ClubResource($this->whenLoaded('club')),
            'head_coach' => new UserResource($this->whenLoaded('headCoach')),
            'assistant_coach' => new UserResource($this->whenLoaded('assistantCoach')),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'active_players' => PlayerResource::collection($this->whenLoaded('activePlayers')),
            
            // Counts
            'players_count' => $this->when(isset($this->players_count), $this->players_count),
            'active_players_count' => $this->when(isset($this->active_players_count), $this->active_players_count),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## 🧪 Testing Foundation

### Feature Test Example

```php
<?php
// tests/Feature/TeamManagementTest.php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private User $clubAdmin;
    private User $trainer;
    private Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->club = Club::factory()->create();
        
        // Associate club admin with club
        $this->club->members()->attach($this->clubAdmin, [
            'role' => 'admin',
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_all_teams(): void
    {
        Team::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
                        ->get('/teams');

        $response->assertStatus(200)
                ->assertInertia(fn($page) => 
                    $page->component('Teams/Index')
                         ->has('teams.data', 5)
                );
    }

    public function test_club_admin_can_create_team(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'club_id' => $this->club->id,
            'category' => 'Herren',
            'gender' => 'male',
            'season' => '2024-25',
            'head_coach_id' => $this->trainer->id,
            'max_players' => 15,
        ];

        $response = $this->actingAs($this->clubAdmin)
                        ->post('/teams', $teamData);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'club_id' => $this->club->id,
            'head_coach_id' => $this->trainer->id,
        ]);
    }

    public function test_trainer_can_update_their_team(): void
    {
        $team = Team::factory()->create([
            'club_id' => $this->club->id,
            'head_coach_id' => $this->trainer->id,
        ]);

        $updateData = [
            'name' => 'Updated Team Name',
            'description' => 'Updated description',
            'max_players' => 20,
        ];

        $response = $this->actingAs($this->trainer)
                        ->put("/teams/{$team->id}", array_merge($team->toArray(), $updateData));

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team Name',
            'description' => 'Updated description',
            'max_players' => 20,
        ]);
    }

    public function test_trainer_cannot_update_other_teams(): void
    {
        $otherTeam = Team::factory()->create([
            'club_id' => $this->club->id,
            'head_coach_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($this->trainer)
                        ->put("/teams/{$otherTeam->id}", [
                            'name' => 'Unauthorized Update',
                        ]);

        $response->assertStatus(403);
    }

    public function test_api_returns_teams_with_pagination(): void
    {
        Team::factory()->count(25)->create(['club_id' => $this->club->id]);

        $response = $this->actingAs($this->admin)
                        ->getJson('/api/v2/teams?per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => ['*' => ['id', 'name', 'category', 'season']],
                    'links',
                    'meta' => ['current_page', 'per_page', 'total'],
                ])
                ->assertJsonCount(10, 'data');
    }

    public function test_api_filters_teams_by_season(): void
    {
        Team::factory()->create(['season' => '2023-24', 'club_id' => $this->club->id]);
        Team::factory()->create(['season' => '2024-25', 'club_id' => $this->club->id]);
        Team::factory()->create(['season' => '2024-25', 'club_id' => $this->club->id]);

        $response = $this->actingAs($this->admin)
                        ->getJson('/api/v2/teams?season=2024-25');

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    public function test_team_deletion_prevents_deletion_with_active_players(): void
    {
        $team = Team::factory()->create([
            'club_id' => $this->club->id,
            'head_coach_id' => $this->trainer->id,
        ]);

        // Add active player
        Player::factory()->create([
            'team_id' => $team->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
                        ->delete("/teams/{$team->id}");

        $response->assertRedirect()
                ->assertSessionHas('error');

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }
}
```

### Unit Test Example

```php
<?php
// tests/Unit/Models/TeamTest.php

namespace Tests\Unit\Models;

use App\Models\Club;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_belongs_to_club(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $this->assertInstanceOf(Club::class, $team->club);
        $this->assertEquals($club->id, $team->club->id);
    }

    public function test_team_has_many_players(): void
    {
        $team = Team::factory()->create();
        $players = Player::factory()->count(3)->create(['team_id' => $team->id]);

        $this->assertCount(3, $team->players);
        $this->assertInstanceOf(Player::class, $team->players->first());
    }

    public function test_team_can_have_head_and_assistant_coach(): void
    {
        $headCoach = User::factory()->create();
        $assistantCoach = User::factory()->create();
        
        $team = Team::factory()->create([
            'head_coach_id' => $headCoach->id,
            'assistant_coach_id' => $assistantCoach->id,
        ]);

        $this->assertEquals($headCoach->id, $team->headCoach->id);
        $this->assertEquals($assistantCoach->id, $team->assistantCoach->id);
    }

    public function test_full_name_accessor(): void
    {
        $club = Club::factory()->create(['short_name' => 'ABC']);
        $team = Team::factory()->create([
            'name' => 'Herren 1',
            'club_id' => $club->id,
        ]);

        $this->assertEquals('ABC Herren 1', $team->full_name);
    }

    public function test_players_slots_available_calculation(): void
    {
        $team = Team::factory()->create(['max_players' => 15]);
        Player::factory()->count(10)->create([
            'team_id' => $team->id,
            'is_active' => true,
        ]);

        $this->assertEquals(5, $team->players_slots_available);
    }

    public function test_can_accept_new_player(): void
    {
        $team = Team::factory()->create([
            'max_players' => 15,
            'accepts_new_players' => true,
            'status' => 'active',
        ]);

        Player::factory()->count(10)->create([
            'team_id' => $team->id,
            'is_active' => true,
        ]);

        $this->assertTrue($team->canAcceptNewPlayer());

        // Fill up the team
        Player::factory()->count(5)->create([
            'team_id' => $team->id,
            'is_active' => true,
        ]);

        $team->refresh();
        $this->assertFalse($team->canAcceptNewPlayer());
    }

    public function test_average_player_age_calculation(): void
    {
        $team = Team::factory()->create();
        
        // Create players with specific birth dates
        Player::factory()->create([
            'team_id' => $team->id,
            'birth_date' => now()->subYears(20),
            'is_active' => true,
        ]);
        
        Player::factory()->create([
            'team_id' => $team->id,
            'birth_date' => now()->subYears(22),
            'is_active' => true,
        ]);

        $this->assertEquals(21.0, $team->average_player_age);
    }

    public function test_team_scopes(): void
    {
        Team::factory()->create(['status' => 'active']);
        Team::factory()->create(['status' => 'inactive']);
        Team::factory()->create(['season' => '2024-25']);
        Team::factory()->create(['category' => 'Herren']);

        $this->assertCount(1, Team::active()->get());
        $this->assertCount(1, Team::bySeason('2024-25')->get());
        $this->assertCount(1, Team::byCategory('Herren')->get());
    }
}
```

---

## 🚀 Phase 1 Deliverables

### Completed Features Checklist

#### ✅ Laravel Foundation
- [x] Laravel 11 Installation mit Jetstream
- [x] Package Integration (Spatie, Excel, PDF, etc.)
- [x] Environment Configuration
- [x] Service Provider Setup
- [x] Custom Blade Directives

#### ✅ Authentication & Security
- [x] Multi-Guard Authentication (Web, API, Emergency)
- [x] Two-Factor Authentication Service
- [x] Social Login (Google, Facebook, GitHub)
- [x] Role-Based Access Control (RBAC)
- [x] Permission System mit Spatie Laravel Permission

#### ✅ Core Models & Database
- [x] Users Migration & Model mit 2FA Support
- [x] Clubs Migration & Model mit Media Library
- [x] Teams Migration & Model mit Scout Search
- [x] Players Migration & Model mit Encrypted Fields
- [x] Eloquent Relationships & Scopes
- [x] Model Observers & Activity Logging

#### ✅ Management Systems
- [x] User Management Controller & Service
- [x] Team Management Controller & Service
- [x] Club Management System
- [x] Player Management Foundation
- [x] Admin Dashboard mit Role-based Views

#### ✅ API Foundation
- [x] API Base Controller
- [x] Teams API Controller mit CRUD
- [x] API Resources (Team, User, Club, Player)
- [x] Sanctum Token Authentication
- [x] API Request Validation

#### ✅ Frontend Foundation
- [x] Inertia.js Setup mit Vue 3
- [x] Main Navigation Component
- [x] Dashboard Views für alle Rollen
- [x] Responsive Design mit Tailwind CSS
- [x] Role-based UI Components

#### ✅ Testing Infrastructure
- [x] Feature Tests für Team Management
- [x] Unit Tests für Models
- [x] API Tests mit Authentication
- [x] Database Testing Setup
- [x] Test Coverage für Core Features

### API Endpoints (Phase 1)

```php
// API Routes (routes/api.php)
Route::prefix('v2')->middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/tokens', [ApiTokenController::class, 'store']);
    Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy']);
    
    // Users
    Route::apiResource('users', Api\V2\UsersController::class);
    Route::patch('users/{user}/activate', [Api\V2\UsersController::class, 'activate']);
    Route::patch('users/{user}/deactivate', [Api\V2\UsersController::class, 'deactivate']);
    
    // Clubs
    Route::apiResource('clubs', Api\V2\ClubsController::class);
    Route::get('clubs/{club}/teams', [Api\V2\ClubsController::class, 'teams']);
    Route::get('clubs/{club}/statistics', [Api\V2\ClubsController::class, 'statistics']);
    
    // Teams
    Route::apiResource('teams', Api\V2\TeamsController::class);
    Route::get('teams/{team}/roster', [Api\V2\TeamsController::class, 'roster']);
    Route::get('teams/{team}/statistics', [Api\V2\TeamsController::class, 'statistics']);
    
    // Players
    Route::apiResource('players', Api\V2\PlayersController::class);
    Route::get('players/{player}/statistics', [Api\V2\PlayersController::class, 'statistics']);
    Route::get('players/{player}/emergency-contacts', [Api\V2\PlayersController::class, 'emergencyContacts']);
});
```

### Deployment Configuration

#### Laravel Forge Server Configuration
```yaml
# Server Setup
PHP Version: 8.3
Database: MySQL 8.0
Queue Worker: Horizon
Scheduler: Enabled
SSL: Let's Encrypt

# Environment Variables
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Scheduled Jobs
- php artisan model:prune --hours=168 (weekly)
- php artisan telescope:prune --hours=48 (daily)
- php artisan backup:run (daily at 2:00 AM)
```

### Database Schema Summary

**Phase 1 Tables:**
- `users` - 24 columns mit 2FA, preferences, basketball-specific fields
- `clubs` - 22 columns mit address, billing, settings
- `teams` - 26 columns mit basketball categories, coaching, training
- `players` - 45 columns mit personal, basketball, medical, legal data
- `roles` - Spatie Permission tables (roles, permissions, model_has_*)
- `activity_log` - Spatie Activity Log für Audit Trail
- `personal_access_tokens` - Laravel Sanctum API tokens

### Next Steps für Phase 2

1. **Game Management System**
   - Games, GameActions, LiveGames Models
   - Live-Scoring Interface
   - Real-time Broadcasting Setup

2. **Statistics Engine**
   - PlayerStatistics, TeamStatistics Models  
   - Statistics Calculation Service
   - Reporting System

3. **Advanced Features**
   - File Upload für Player Photos
   - Advanced Search mit Scout
   - Mobile PWA Features

---

*Phase 1 Foundation erfolgreich implementiert! 🏀*  
*Bereit für Phase 2: Game & Statistics Management*

---

*© 2025 BasketManager Pro - Phase 1 Core Foundation PRD v1.0*