# Installation Wizard - Technical Architecture

🏀 **BasketManager Pro - Web-Based Installation System**

---

## 📋 Overview

The Installation Wizard is a comprehensive 7-step web-based setup system that guides administrators through the installation process of BasketManager Pro without requiring CLI access.

**Key Features:**
- 🌐 **Multi-language** (German/English)
- ✅ **Real-time validation** (Server requirements, DB connection, Stripe API)
- 🔒 **Triple-lock security** (File marker + .env + Middleware)
- 📊 **Live migration output** with progress tracking
- 🎨 **Modern UI** (Vue 3 + Inertia.js + Tailwind CSS)

---

## 🏗️ Architecture Overview

### Directory Structure

```
app/
├── Console/Commands/
│   └── UnlockInstallationCommand.php          # php artisan install:unlock
├── Http/
│   ├── Controllers/
│   │   └── InstallController.php              # Main installation controller (10 methods)
│   └── Middleware/
│       ├── RedirectIfNotInstalled.php         # Redirect to /install if not installed
│       └── PreventInstalledAccess.php         # Block /install after completion
├── Services/Install/
│   ├── RequirementChecker.php                 # PHP version, extensions, config
│   ├── PermissionChecker.php                  # File permissions checker
│   ├── EnvironmentManager.php                 # .env file management
│   ├── InstallationService.php                # Main installation logic
│   └── StripeValidator.php                    # Stripe API validation

resources/
├── js/Pages/Install/
│   ├── Layout.vue                             # Installation layout with progress bar
│   ├── Index.vue                              # Step 0: Language selection
│   ├── Welcome.vue                            # Step 1: Welcome screen
│   ├── Requirements.vue                       # Step 2: Server requirements
│   ├── Permissions.vue                        # Step 3: File permissions
│   ├── Environment.vue                        # Step 4: Configuration (4 tabs)
│   ├── Database.vue                           # Step 5: Migration runner
│   ├── Admin.vue                              # Step 6: Super Admin creation
│   └── Complete.vue                           # Step 7: Success page
└── lang/
    ├── de/install.php                         # German translations (80+ keys)
    └── en/install.php                         # English translations (80+ keys)

routes/
└── install.php                                 # Installation routes (14 routes)

config/
└── app.php                                     # Added 'installed' => env('APP_INSTALLED', false)
```

---

## 🔄 Installation Flow

### Step-by-Step Process

```
┌─────────────────────────────────────────────────────────────┐
│ Step 0: Language Selection                                  │
│ ┌────────────┐  ┌────────────┐                             │
│ │  🇩🇪 Deutsch │  │  🇬🇧 English │                             │
│ └────────────┘  └────────────┘                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Welcome Screen                                      │
│ - Feature overview                                          │
│ - Estimated time: 5-10 minutes                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 2: Server Requirements Check                           │
│ ✓ PHP >= 8.2                                               │
│ ✓ 12 PHP Extensions                                        │
│ ✓ Memory Limit >= 256M                                     │
│ ✓ Upload Max >= 20M                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 3: Folder Permissions Check                            │
│ ✓ storage/framework/ (writable)                            │
│ ✓ storage/logs/ (writable)                                 │
│ ✓ storage/app/ (writable)                                  │
│ ✓ bootstrap/cache/ (writable)                              │
│ ✓ public/uploads/ (writable)                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 4: Environment Configuration (4 Tabs)                  │
│ ┌──────────┬──────────┬──────────┬──────────┐             │
│ │ App      │ Database │ Email    │ Stripe   │             │
│ └──────────┴──────────┴──────────┴──────────┘             │
│ - AJAX validation for DB & Stripe                          │
│ - Saves to .env file                                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 5: Database Setup                                      │
│ - Run migrations (116 migrations)                          │
│ - Seed roles & permissions (11 roles, 136 permissions)    │
│ - Seed legal pages                                         │
│ - Live console output                                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 6: Super Admin Creation                                │
│ - Organization name                                         │
│ - Admin name, email, password                              │
│ - Password strength meter                                  │
│ - Subscription tier selection                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Step 7: Installation Complete! 🎉                          │
│ - Display credentials                                       │
│ - Security reminders                                        │
│ - Next steps guide                                          │
│ - Link to login                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Backend Components

### 1. InstallController.php

**10 Methods:**

| Method | Route | Description |
|--------|-------|-------------|
| `index()` | GET `/install` | Language selection |
| `setLanguage()` | POST `/install/language` | Set language, redirect to welcome |
| `welcome()` | GET `/install/welcome` | Welcome screen |
| `requirements()` | GET `/install/requirements` | Server requirements check |
| `permissions()` | GET `/install/permissions` | Folder permissions check |
| `environment()` | GET `/install/environment` | Environment config form |
| `saveEnvironment()` | POST `/install/environment` | Save .env configuration |
| `testDatabase()` | POST `/install/environment/test-database` | AJAX: Test DB connection |
| `testStripe()` | POST `/install/environment/test-stripe` | AJAX: Validate Stripe keys |
| `database()` | GET `/install/database` | Database setup page |
| `runMigrations()` | POST `/install/database/migrate` | AJAX: Run migrations |
| `admin()` | GET `/install/admin` | Super Admin form |
| `createAdmin()` | POST `/install/admin` | Create Super Admin |
| `complete()` | GET `/install/complete` | Success page |

---

### 2. Services

#### RequirementChecker.php

**Checks:**
- PHP version (>= 8.2)
- PHP extensions (12 required)
- PHP functions (proc_open, symlink)
- Memory limit (>= 256M)
- Upload max filesize (>= 20M)

**Returns:**
```php
[
    'satisfied' => bool,
    'requirements' => [
        'php_version' => ['status' => 'success', 'current' => '8.2.0', ...],
        'extensions' => [...],
        'memory_limit' => [...],
        ...
    ]
]
```

#### PermissionChecker.php

**Checks:**
- `storage/framework/` (writable)
- `storage/logs/` (writable)
- `storage/app/` (writable)
- `bootstrap/cache/` (writable)
- `public/uploads/` (writable)

**Returns:**
```php
[
    'satisfied' => bool,
    'permissions' => [
        'storage/framework/' => [
            'name' => 'Framework Storage',
            'path' => '/path/to/storage/framework',
            'writable' => true,
            'permission' => '0755',
            'status' => 'success'
        ],
        ...
    ]
]
```

#### EnvironmentManager.php

**Features:**
- Read current .env values
- Update .env safely with backup
- Format values (quotes, escaping)
- Clean old backups (keep last 5)

**Methods:**
- `getCurrentEnvironment()` - Read current .env
- `saveEnvironment($data)` - Update .env with backup
- `setEnvironmentValue($content, $key, $value)` - Regex replacement
- `formatValue($value)` - Quote if needed
- `createBackup()` - Copy to `.env.backup.{timestamp}`
- `restoreBackup()` - Restore latest backup
- `cleanOldBackups()` - Keep only 5 newest backups

#### InstallationService.php

**Methods:**
- `testDatabaseConnection($credentials)` - Test DB connection
- `runMigrations()` - Run migrations + seeders
- `seedRequiredData()` - Seed roles, permissions, legal pages
- `createSuperAdmin($data)` - Create user + tenant + club + assign role
- `markAsInstalled()` - Create marker file + update .env + clear caches
- `isInstalled()` - Check if app is installed
- `unlockInstallation()` - Remove marker file + update .env

**Super Admin Creation Process:**
```php
DB::beginTransaction();

1. Create Tenant (name, slug, domain, subscription_tier)
2. Create User (name, email, password)
3. Assign 'super_admin' role
4. Link user to tenant (tenant_user pivot)
5. Create default Club
6. Link user to club (club_user pivot)
7. Sync Stripe plans (optional)

DB::commit();
```

#### StripeValidator.php

**Methods:**
- `validateKeys($publishableKey, $secretKey)` - Validate Stripe API keys
- `testWebhookEndpoint($url, $secret)` - Test webhook configuration

**Validation:**
- Key format (pk_test_, sk_test_, pk_live_, sk_live_)
- Test/Live key matching
- API connection test via `Stripe\Account::retrieve()`
- Returns account name and mode

---

### 3. Middleware

#### RedirectIfNotInstalled.php

**Purpose:** Redirect all requests to `/install` if app is not installed

**Checks:**
1. `storage/installed` file exists
2. `config('app.installed')` === true

**Exceptions:**
- `install.*` routes
- `horizon.*`, `telescope.*`, `pulse.*` routes
- `api/*` routes
- `up`, `health` routes

#### PreventInstalledAccess.php

**Purpose:** Block access to `/install` after installation is complete

**Redirects:**
- Authenticated users → Dashboard
- Guest users → Login page

---

### 4. Routes (routes/install.php)

**14 Routes:**

```php
Route::middleware(['web', 'guest', 'throttle:60,1', 'prevent.installed'])
    ->prefix('install')
    ->name('install.')
    ->group(function () {
        Route::get('/', [InstallController::class, 'index'])->name('index');
        Route::post('/language', [InstallController::class, 'setLanguage'])->name('language');
        Route::get('/welcome', [InstallController::class, 'welcome'])->name('welcome');
        Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
        Route::get('/permissions', [InstallController::class, 'permissions'])->name('permissions');
        Route::get('/environment', [InstallController::class, 'environment'])->name('environment');
        Route::post('/environment', [InstallController::class, 'saveEnvironment'])->name('environment.save');
        Route::post('/environment/test-database', [InstallController::class, 'testDatabase'])->name('environment.test-database');
        Route::post('/environment/test-stripe', [InstallController::class, 'testStripe'])->name('environment.test-stripe');
        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database/migrate', [InstallController::class, 'runMigrations'])->name('database.migrate');
        Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
        Route::post('/admin', [InstallController::class, 'createAdmin'])->name('admin.create');
        Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
    });
```

---

## 🎨 Frontend Components

### Vue 3 + Inertia.js + Tailwind CSS

**8 Components:**

| Component | Purpose | Features |
|-----------|---------|----------|
| `Layout.vue` | Installation layout | Progress bar, step indicators, responsive |
| `Index.vue` | Language selection | German/English flags, session storage |
| `Welcome.vue` | Welcome screen | Feature list, app overview |
| `Requirements.vue` | Requirements check | Color-coded status, expandable errors |
| `Permissions.vue` | Permissions check | Fix commands, retry button |
| `Environment.vue` | Configuration | 4 tabs, AJAX validation, test buttons |
| `Database.vue` | Migration runner | Live console, progress bar, output stream |
| `Admin.vue` | Admin creation | Password strength meter, subscription cards |
| `Complete.vue` | Success page | Credentials display, next steps |

### Environment.vue - Tab Structure

```vue
┌─────────────────────────────────────────────┐
│ Tabs: [⚙️ Application] [🗄️ Database] [📧 Email] [💳 Stripe] │
├─────────────────────────────────────────────┤
│                                             │
│  Tab Content (Dynamic)                      │
│                                             │
│  - Form fields for selected tab             │
│  - Real-time validation                     │
│  - Test buttons (DB, Stripe)                │
│                                             │
├─────────────────────────────────────────────┤
│  [← Back]                    [Save Config →] │
└─────────────────────────────────────────────┘
```

### Database.vue - Migration Output

```vue
┌─────────────────────────────────────────────┐
│  [Run Migrations] Button                    │
├─────────────────────────────────────────────┤
│  Progress Bar: ████████░░░░░░░░ 60%        │
├─────────────────────────────────────────────┤
│  Console Output:                            │
│  1. Starting database migrations...         │
│  2. Running migration: create_users_table   │
│  3. Running migration: create_tenants_table │
│  4. ✅ All migrations completed!            │
└─────────────────────────────────────────────┘
```

### Admin.vue - Password Strength Meter

```vue
┌─────────────────────────────────────────────┐
│  Password: [**********]                     │
│                                             │
│  Password Strength: Strong ✅               │
│  ████████████████████░░░░░░ 80%            │
│                                             │
│  Rules:                                     │
│  ✅ At least 8 characters                   │
│  ✅ Uppercase + Lowercase                   │
│  ✅ Contains number                         │
│  ✅ Special character                       │
└─────────────────────────────────────────────┘
```

---

## 🔒 Security Features

### Triple-Lock System

After successful installation, the wizard is locked via **3 mechanisms**:

1. **File Marker:**
   ```php
   // storage/installed
   2025-11-03 14:23:15
   ```

2. **.env Variable:**
   ```env
   APP_INSTALLED=true
   ```

3. **Middleware:**
   - `RedirectIfNotInstalled` → Redirects to /install if not installed
   - `PreventInstalledAccess` → Blocks /install after completion

### Unlock (Development Only)

```bash
php artisan install:unlock --force
```

**Actions:**
- Deletes `storage/installed`
- Sets `APP_INSTALLED=false` in .env
- Clears config cache

**⚠️ DOES NOT delete database data!**

---

## 📊 Data Flow

### Environment Configuration Flow

```
User Input (Environment.vue)
    ↓
POST /install/environment
    ↓
InstallController@saveEnvironment
    ↓
EnvironmentManager@saveEnvironment
    ↓
1. Create backup (.env.backup.{timestamp})
2. Regex replace .env values
3. Write to .env
4. Clear config cache
    ↓
Redirect to /install/database
```

### Database Connection Test Flow (AJAX)

```
User clicks "Test Connection" (Environment.vue)
    ↓
POST /install/environment/test-database (AJAX)
    ↓
InstallController@testDatabase
    ↓
InstallationService@testDatabaseConnection
    ↓
1. Create temp DB config
2. Attempt PDO connection
3. Run test query: SELECT 1
4. Return success/failure
    ↓
JSON response → Display in UI
```

### Super Admin Creation Flow

```
User submits form (Admin.vue)
    ↓
POST /install/admin
    ↓
InstallController@createAdmin
    ↓
InstallationService@createSuperAdmin
    ↓
DB::beginTransaction()
1. Create Tenant
2. Create User (hashed password)
3. Assign 'super_admin' role
4. Link user <-> tenant
5. Create default Club
6. Link user <-> club
7. Sync Stripe plans (optional)
DB::commit()
    ↓
InstallationService@markAsInstalled
    ↓
1. Create storage/installed
2. Set APP_INSTALLED=true in .env
3. Clear all caches
    ↓
Redirect to /install/complete
```

---

## 🧪 Testing

### Unit Tests

```bash
# Requirement Checker
php artisan test tests/Unit/RequirementCheckerTest.php

# Permission Checker
php artisan test tests/Unit/PermissionCheckerTest.php

# Environment Manager
php artisan test tests/Unit/EnvironmentManagerTest.php
```

### Feature Tests

```bash
# Installation Flow
php artisan test tests/Feature/InstallationWizardTest.php
```

**Test Coverage:**
- Server requirements detection
- Permission checking
- .env file updates
- Database connection validation
- Super Admin creation
- Installation lock mechanism

---

## 🌍 Internationalization (i18n)

**Supported Languages:**
- 🇩🇪 German (`de`)
- 🇬🇧 English (`en`)

**Translation Files:**
- `resources/lang/de/install.php` (80+ keys)
- `resources/lang/en/install.php` (80+ keys)

**Session-based Language:**
```php
session(['install_language' => 'de']);
app()->setLocale('de');
```

**Adding New Language:**

1. Create translation file:
   ```bash
   cp resources/lang/de/install.php resources/lang/fr/install.php
   ```

2. Translate all keys

3. Add to language selection in `InstallController@index`:
   ```php
   'languages' => [
       'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'code' => 'fr']
   ]
   ```

---

## 🚀 Deployment Checklist

### Production Deployment

- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Configure production database
- [ ] Set up SSL/HTTPS
- [ ] Configure Stripe Live keys (not Test keys)
- [ ] Set up cron job for scheduler
- [ ] Configure queue worker
- [ ] Set up file permissions (755 for directories, 644 for files)
- [ ] Configure Redis for caching (optional)
- [ ] Set up backup strategy
- [ ] Enable error logging to `storage/logs/`

---

## 📝 Future Enhancements

**Potential Improvements:**

1. **Resume Capability**
   - Allow users to pause installation and resume later
   - Save progress in session/database

2. **Pre-flight Check**
   - Run all checks (requirements, permissions) before starting
   - Generate report PDF

3. **Email Verification**
   - Send verification email to admin during setup
   - Confirm email ownership before completion

4. **Multi-step Rollback**
   - Allow users to go back and change previous steps
   - Re-validate affected dependencies

5. **Installation Templates**
   - Pre-configured setups (Development, Production, Multi-tenant)
   - One-click environment selection

6. **Health Dashboard**
   - Post-installation health check
   - System status monitoring

---

## 🤝 Contributing

If you want to extend or improve the Installation Wizard:

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/wizard-improvement`)
3. **Add your changes**
4. **Test thoroughly** (all 7 steps)
5. **Submit a pull request**

**Guidelines:**
- Follow PSR-12 coding standards
- Add unit tests for new services
- Update documentation
- Test in multiple browsers
- Maintain mobile responsiveness

---

## 📚 References

**Related Documentation:**
- `INSTALLATION.md` - User installation guide
- `PRODUCTION_DEPLOYMENT.md` - Production deployment
- `BERECHTIGUNGS_MATRIX.md` - Permission matrix
- `CLAUDE.md` - Project overview

---

**Built with ❤️ for BasketManager Pro**
