# starting5.eu - Production Setup Commands

This guide contains the exact commands to run on the **starting5.eu production server** to fix the "Tenant nicht gefunden" error and complete the installation.

---

## Current Status

✅ Migrations completed successfully
✅ Database is ready
✅ Redis graceful degradation implemented
❌ **No tenant exists for starting5.eu domain**

---

## Solution: Create Tenant for starting5.eu

### Step 1: Update .env File

Edit your `.env` file on the production server and add/update these values:

```env
# Application Settings
APP_NAME="BasketManager Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://starting5.eu

# Tenant Configuration (for automatic initialization)
TENANT_NAME="BasketManager Pro - Starting5"
TENANT_BILLING_EMAIL=admin@starting5.eu
TENANT_SUBSCRIPTION_TIER=professional
TENANT_COUNTRY=DE
TENANT_CURRENCY=EUR
TENANT_TRIAL_DAYS=30

# Ensure database drivers are used (not Redis)
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Step 2: Run Tenant Initialization

SSH into your server and run:

```bash
cd /www/htdocs/w015b7e3/starting5.eu

# Option A: Automatic (uses .env values) - RECOMMENDED
php artisan tenant:initialize --force

# Option B: Interactive (asks questions)
php artisan tenant:initialize

# Option C: Use seeder directly
php artisan db:seed --class=InitialTenantSeeder
```

### Step 3: Verify Tenant Creation

```bash
php artisan tinker
```

Then in tinker:
```php
// Check tenant count
\App\Models\Tenant::count();
// Should return: 1

// View tenant details
\App\Models\Tenant::first();
// Should show your tenant with domain: starting5.eu

// Check specific domain
\App\Models\Tenant::where('domain', 'starting5.eu')->first();

// Exit tinker
exit
```

### Step 4: Clear Caches

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Re-cache for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Test in Browser

Visit: **https://starting5.eu**

You should now see the application instead of the "Tenant nicht gefunden" error.

---

## Expected Tenant Details

After successful creation, your tenant will have:

| Property | Value |
|----------|-------|
| **Domain** | starting5.eu |
| **Name** | BasketManager Pro - Starting5 |
| **Slug** | starting5-eu |
| **Billing Email** | admin@starting5.eu |
| **Subscription Tier** | Professional |
| **Max Users** | 200 |
| **Max Teams** | 50 |
| **Max Storage** | 200 GB |
| **API Calls/Hour** | 5,000 |
| **Status** | Active |
| **Trial Period** | 30 days |

---

## Features Available (Professional Tier)

✅ Basic & Advanced Statistics
✅ Team Management
✅ Player Roster
✅ Training Management
✅ Game Scheduling
✅ **Live Game Scoring**
✅ **Video Analysis**
✅ **Tournament Management**
✅ **API Access**

---

## Troubleshooting

### Issue: Command not found

**Cause**: New files not uploaded to production server.

**Solution**: Upload these new files via FTP or Git:
- `database/seeders/InitialTenantSeeder.php`
- `app/Console/Commands/InitializeTenantCommand.php`
- Updated `.env.shared-hosting.example`
- Updated `app/Providers/AppServiceProvider.php`

Then run:
```bash
composer dump-autoload
php artisan config:clear
```

### Issue: "Tenant already exists for domain..."

**Cause**: A tenant was already created for this domain.

**Solution**: Check existing tenants:
```bash
php artisan tinker
>>> \App\Models\Tenant::all()
>>> \App\Models\Tenant::where('domain', 'starting5.eu')->first()
```

If the wrong domain is registered, you can update it:
```bash
php artisan tinker
>>> $tenant = \App\Models\Tenant::first();
>>> $tenant->update(['domain' => 'starting5.eu']);
>>> $tenant->update(['name' => 'BasketManager Pro - Starting5']);
>>> exit
```

### Issue: Still showing "Tenant nicht gefunden"

**Solution**:
1. Clear browser cache (Ctrl+Shift+Delete)
2. Clear server caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. Check `.env` has correct APP_URL:
   ```bash
   grep APP_URL .env
   # Should show: APP_URL=https://starting5.eu
   ```
4. Verify tenant domain matches:
   ```bash
   php artisan tinker
   >>> \App\Models\Tenant::first()->domain
   # Should output: "starting5.eu"
   ```

---

## Complete Setup Script (Copy & Paste)

For convenience, here's the complete script:

```bash
# Navigate to project directory
cd /www/htdocs/w015b7e3/starting5.eu

# Upload new files first (if not done via Git)
# - database/seeders/InitialTenantSeeder.php
# - app/Console/Commands/InitializeTenantCommand.php
# - Updated app/Providers/AppServiceProvider.php

# Regenerate autoloader
composer dump-autoload

# Clear configuration cache
php artisan config:clear

# Initialize tenant (uses .env values)
php artisan tenant:initialize --force

# Verify creation
php artisan tinker --execute="echo 'Tenant Count: ' . \App\Models\Tenant::count() . PHP_EOL; echo 'Tenant Domain: ' . \App\Models\Tenant::first()->domain . PHP_EOL;"

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Done!
echo "✅ Setup complete! Visit https://starting5.eu"
```

---

## Next Steps After Tenant Creation

1. **Create Admin User**:
   ```bash
   php artisan tinker
   ```
   ```php
   $user = \App\Models\User::create([
       'name' => 'Admin',
       'email' => 'admin@starting5.eu',
       'password' => bcrypt('your-secure-password'),
       'email_verified_at' => now(),
   ]);

   // Assign Super Admin role
   $user->assignRole('super_admin');
   ```

2. **Configure Stripe Webhooks** (for subscriptions):
   - Webhook URL: `https://starting5.eu/webhooks/stripe/club`
   - Events: `checkout.session.completed`, `customer.subscription.*`, `invoice.*`

3. **Set up Cron Jobs** (see SHARED_HOSTING_DEPLOYMENT.md):
   ```
   * * * * * cd /www/htdocs/w015b7e3/starting5.eu && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **Monitor Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Support

If you encounter any issues:

1. Check logs: `storage/logs/laravel.log`
2. Review documentation: `SHARED_HOSTING_DEPLOYMENT.md`
3. Verify database connection in `.env`
4. Ensure all migrations completed successfully

---

**Created**: 2025-11-03
**Server**: KAS Server (w015b7e3)
**Domain**: starting5.eu
**Installation Type**: Shared Hosting (no Redis)
