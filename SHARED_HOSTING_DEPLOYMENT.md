# Shared Hosting Deployment Guide

This guide explains how to deploy BasketManager Pro on shared hosting environments (e.g., KAS Server, 1&1/IONOS, Strato, HostEurope, All-Inkl) where Redis is not available.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Step-by-Step Deployment](#step-by-step-deployment)
4. [Troubleshooting](#troubleshooting)
5. [Performance Optimization](#performance-optimization)
6. [Limitations & Workarounds](#limitations--workarounds)
7. [Migration from Development](#migration-from-development)

---

## Prerequisites

### Server Requirements

- **PHP**: 8.2 or higher
- **MySQL/MariaDB**: 8.0+ / 10.3+
- **PHP Extensions**:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - cURL
  - GD or Imagick
  - Zip

### What You **DO NOT** Need

- ❌ Redis (the app works without it!)
- ❌ Root/sudo access
- ❌ SSH access (optional, but recommended)
- ❌ Node.js/npm on server (build assets locally)

---

## Quick Start

```bash
# 1. Upload files via FTP/SFTP to your web root
# 2. SSH into your server (if available)
# 3. Run setup commands:

cd /path/to/your/domain

# Copy environment file
cp .env.shared-hosting.example .env

# Edit .env with your database credentials
nano .env  # or use FTP to edit

# Generate application key
php artisan key:generate

# Clear configuration cache
php artisan config:clear

# Run migrations
php artisan migrate --force

# Initialize first tenant (IMPORTANT for new installations!)
php artisan tenant:initialize --force

# Alternative: Use seeder
# php artisan db:seed --class=InitialTenantSeeder

# Create storage symlink
php artisan storage:link

# Cache configuration (for performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

---

## Step-by-Step Deployment

### Step 1: Prepare Your Local Environment

Before uploading to shared hosting, prepare your application locally:

```bash
# 1. Build frontend assets locally
npm install
npm run build

# 2. Install Composer dependencies (production)
composer install --no-dev --optimize-autoloader

# 3. Create a deployment archive
tar -czf basketmanager-pro.tar.gz \
  --exclude='node_modules' \
  --exclude='.git' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.env' \
  .
```

### Step 2: Upload to Shared Hosting

#### Option A: Via FTP/SFTP (Recommended for first deployment)

1. Connect to your server via FTP client (FileZilla, Cyberduck, etc.)
2. Upload the extracted files to your web root (usually `htdocs/`, `public_html/`, or `www/`)
3. **Important**: The Laravel `public/` directory should be your document root

#### Option B: Via SSH (Faster, if available)

```bash
# On your server:
cd /www/htdocs/w015b7e3/your-domain.com

# Upload and extract
scp basketmanager-pro.tar.gz user@your-server.com:/www/htdocs/w015b7e3/your-domain.com/
tar -xzf basketmanager-pro.tar.gz
rm basketmanager-pro.tar.gz
```

### Step 3: Configure Document Root

Your web server's document root must point to the `public/` directory, not the project root.

#### KAS Server (.htaccess method)

If you can't change the document root, create a `.htaccess` in your project root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### Via Hosting Control Panel

Most hosting providers allow document root configuration:
- **KAS**: Server > PHP Settings > Document Root
- **1&1/IONOS**: Domain Settings > Document Root
- **Strato**: Package Administration > PHP Settings

Set it to: `/www/htdocs/w015b7e3/your-domain.com/public`

### Step 4: Configure Environment

```bash
# Copy shared hosting template
cp .env.shared-hosting.example .env

# Edit with your credentials
nano .env  # or use FTP/hosting control panel file manager
```

**Required Changes in `.env`:**

```env
APP_NAME="Your Basketball Club"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=your-database-host.kasserver.com
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password

# Email configuration
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@your-domain.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS="noreply@your-domain.com"

# Stripe (production keys)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
```

**Important**: Ensure these are set for shared hosting:
```env
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
REDIS_CLIENT=phpredis  # Keep this, even though Redis is not used
```

### Step 5: Run Migrations

```bash
# Generate application key
php artisan key:generate

# Clear any cached config
php artisan config:clear

# Run database migrations
php artisan migrate --force

# **REQUIRED**: Initialize first tenant for this installation
php artisan tenant:initialize --force

# Alternative: Use seeder (non-interactive)
# php artisan db:seed --class=InitialTenantSeeder

# OPTIONAL: Seed initial data (plans, permissions, etc.)
php artisan db:seed --class=SubscriptionPlanSeeder --force
php artisan db:seed --class=RoleSeeder --force
```

**Important**: The `tenant:initialize` command creates the initial tenant for your installation using values from `.env`. Make sure you have set:
```env
TENANT_NAME="Your Organization Name"
TENANT_BILLING_EMAIL=admin@your-domain.com
TENANT_SUBSCRIPTION_TIER=professional
```

### Step 6: Set Up Cron Jobs

BasketManager Pro requires cron jobs for scheduled tasks (analytics, notifications, queue processing).

#### Via Hosting Control Panel

Navigate to: **Cron Jobs** or **Scheduled Tasks**

**Add these cron jobs:**

1. **Laravel Scheduler** (runs every minute):
   ```
   * * * * * cd /www/htdocs/w015b7e3/your-domain.com && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Queue Worker** (runs every 5 minutes):
   ```
   */5 * * * * cd /www/htdocs/w015b7e3/your-domain.com && php artisan queue:work --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1
   ```

3. **Cache Optimization** (runs daily at 3 AM):
   ```
   0 3 * * * cd /www/htdocs/w015b7e3/your-domain.com && php artisan optimize >> /dev/null 2>&1
   ```

#### KAS Server Specific:

1. Log in to KAS Control Panel
2. Navigate to: **Werkzeuge** > **Cronjobs**
3. Click **Neuer Cronjob**
4. Set PHP interpreter: `/usr/bin/php8.2` (or your PHP version)
5. Add command: `cd /www/htdocs/w015b7e3/your-domain.com && php artisan schedule:run`
6. Set interval: **Every minute** (`* * * * *`)

### Step 7: Configure File Permissions

```bash
# Make storage and cache writable
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Create symbolic link for public storage
php artisan storage:link
```

### Step 8: Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Full optimization
php artisan optimize
```

### Step 9: Configure Webhooks (Stripe)

For subscription webhooks to work:

1. Log in to **Stripe Dashboard**
2. Navigate to: **Developers** > **Webhooks**
3. Click **Add endpoint**
4. URL: `https://your-domain.com/webhooks/stripe/club`
5. Events to send:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
6. Copy the **Signing Secret** and add to `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET_CLUB=whsec_...
   ```

---

## Troubleshooting

### Problem: "Tenant nicht gefunden" (HTTP 404)

**Symptoms:**
- Browser shows "Tenant nicht gefunden" error page
- HTTP 404 error after successful migrations
- Message: "Der angeforderte Verein oder die Organisation konnte nicht gefunden werden"

**Root Cause:**
No tenant exists in the database for your domain. This is normal for fresh installations.

**Solution:**
```bash
# Option 1: Interactive setup (recommended)
php artisan tenant:initialize

# Option 2: Automatic setup using .env values
php artisan tenant:initialize --force

# Option 3: Use seeder
php artisan db:seed --class=InitialTenantSeeder
```

**Configure .env before running:**
```env
APP_URL=https://your-domain.com
TENANT_NAME="Your Organization Name"
TENANT_BILLING_EMAIL=admin@your-domain.com
TENANT_SUBSCRIPTION_TIER=professional
```

**Verify tenant creation:**
```bash
php artisan tinker
>>> \App\Models\Tenant::count()  // Should return 1 or more
>>> \App\Models\Tenant::first()  // Shows your tenant details
```

**Why it happens:**
- This is a **SaaS/White-Label** application - each installation needs its own tenant
- The tenant links your domain to your organization's data
- Without a tenant, the app cannot resolve which organization to display

### Problem: "Connection Refused" (Redis Error)

**Symptoms:**
```
[RedisException] Connection refused
```

**Solution:**
```bash
# 1. Clear configuration cache
php artisan config:clear

# 2. Ensure .env has database drivers (not redis)
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# 3. The app will automatically fall back to database if Redis is unavailable
```

**Why it happens:**
- Cached configuration from development environment
- `.env` file has `CACHE_STORE=redis` but Redis is not installed

**Automatic Fallback:**
The app now automatically detects Redis unavailability and switches to database drivers. Check logs:
```bash
tail -f storage/logs/laravel.log
```

You should see:
```
Redis not available: Switched cache driver from redis to database
Redis not available: Switched session driver from redis to database
Redis not available: Switched queue connection from redis to database
```

### Problem: "500 Internal Server Error"

**Check Laravel Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Common Causes:**

1. **Missing .env file**
   ```bash
   cp .env.shared-hosting.example .env
   php artisan key:generate
   ```

2. **Wrong file permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

3. **Cached configuration**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

4. **Database connection error**
   - Check `.env` database credentials
   - Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

5. **PHP version mismatch**
   - Check PHP version: `php -v`
   - Ensure >= 8.2

### Problem: "Class not found" Errors

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Clear all caches
php artisan optimize:clear

# Re-cache
php artisan optimize
```

### Problem: Migrations Fail with Foreign Key Errors

**Symptoms:**
```
SQLSTATE[HY000]: General error: 1005 Can't create table
```

**Solution:**
```bash
# Clear cached config first
php artisan config:clear

# Then run migrations
php artisan migrate:fresh --force
```

If that doesn't work, check that the migrations were deployed with the latest fixes:
- `2025_10_28_162151_create_club_usages_table.php` - line 17 should use `uuid('tenant_id')` not `foreignId()`
- `2025_11_03_114205_create_feature_flags_table.php` - line 18 should use `uuid('tenant_id')` not `unsignedBigInteger()`
- `2025_07_31_150922_create_permission_tables.php` - cache clearing should be wrapped in try-catch

### Problem: Queue Jobs Not Processing

**Check:**
```bash
# View pending jobs
php artisan queue:monitor

# Manually process queue
php artisan queue:work --once

# Check if cron job is running
crontab -l
```

**Solution:**
Ensure cron job is set up (see Step 6 above).

### Problem: Sessions Don't Persist / CSRF Token Mismatch

**Solution:**
1. Ensure `SESSION_DRIVER=database` in `.env`
2. Run migration: `php artisan session:table && php artisan migrate`
3. Clear sessions: `php artisan session:clear`
4. For HTTPS sites, ensure:
   ```env
   SESSION_SECURE_COOKIE=true
   SESSION_SAME_SITE=lax
   ```

---

## Performance Optimization

### Database Optimization

```bash
# 1. Enable query caching
# In .env:
QUERY_CACHE_ENABLED=true
QUERY_CACHE_TTL=3600

# 2. Analyze database performance
php artisan db:analyze-performance

# 3. Add database indexes (if needed)
php artisan migrate
```

### Application Caching

```bash
# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Full optimization
php artisan optimize
```

### Disable Debugging Tools

In `.env`:
```env
APP_DEBUG=false
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false
```

### Enable OPcache

Ask your hosting provider to enable PHP OPcache, or add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
```

---

## Limitations & Workarounds

### Limitation 1: No Real-Time Features (Without Pusher)

**Impact:**
- Live game scoring won't update in real-time
- Users need to refresh page to see updates

**Workaround:**
- Use Pusher (free tier: 100 concurrent connections)
- Configure in `.env`:
  ```env
  BROADCAST_CONNECTION=pusher
  PUSHER_APP_ID=your-id
  PUSHER_APP_KEY=your-key
  PUSHER_APP_SECRET=your-secret
  PUSHER_APP_CLUSTER=eu
  ```

### Limitation 2: Slower Cache Performance

**Impact:**
- Database-based cache is slower than Redis
- High traffic sites may experience lag

**Workaround:**
- Enable query caching
- Use CDN for static assets
- Consider upgrading to VPS with Redis

### Limitation 3: Queue Processing Delays

**Impact:**
- Background jobs (emails, exports) may take up to 5 minutes to process

**Workaround:**
- Reduce cron interval to every minute (if allowed):
  ```
  * * * * * cd /path/to/project && php artisan queue:work --stop-when-empty
  ```

### Limitation 4: No Horizontal Scaling

**Impact:**
- Cannot run multiple app instances
- Single server handles all traffic

**Workaround:**
- Optimize database queries
- Use CDN for assets
- Consider load balancer + Redis when traffic increases

---

## Migration from Development

If you developed locally with Redis, follow these steps to migrate to shared hosting:

```bash
# 1. On your local machine, export database
php artisan db:export --connection=mysql > database.sql

# 2. Update .env for shared hosting
cp .env.shared-hosting.example .env
# Edit database credentials

# 3. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 4. Upload files via FTP/SSH

# 5. On server, import database
mysql -u your_user -p your_database < database.sql

# 6. Run migrations for any new changes
php artisan migrate --force

# 7. Optimize
php artisan optimize
```

---

## Hosting-Specific Guides

### KAS Server (kasserver.com)

**Document Root Setup:**
1. Log in to KAS Control Panel
2. Navigate to: **Server** > **PHP-Einstellungen**
3. Set **Document Root**: `/www/htdocs/w015b7e3/your-domain.com/public`
4. PHP Version: Select **8.2 oder höher**

**Database Setup:**
1. Navigate to: **Datenbanken** > **MySQL-Datenbank**
2. Click **Neue Datenbank**
3. Note host: Usually `w015b7e3.kasserver.com`
4. Connection string format: `d0453b99` (database name)

**Email Configuration:**
```env
MAIL_HOST=w015b7e3.kasserver.com
MAIL_PORT=587
MAIL_USERNAME=your-email@your-domain.com
MAIL_FROM_ADDRESS=your-email@your-domain.com
```

### 1&1 / IONOS

**Document Root:**
- Navigate to: **Domains & SSL** > Your domain > **Pfad**
- Set to: `/htdocs/your-folder/public`

**PHP Version:**
- Navigate to: **Hosting** > **PHP-Einstellungen**
- Select **PHP 8.2** or higher

**Database:**
- Usually: MySQL 8.0 available
- Host: Often `db1234.1and1.com`

### Strato

**Document Root:**
1. Log in to Strato Control Panel
2. Navigate to: **Paket-Verwaltung** > **Webspace**
3. Click **Einstellungen**
4. Set **Document Root** to `/public` subdirectory

**Cron Jobs:**
- Navigate to: **Paket-Verwaltung** > **Cronjobs**
- Add scheduler command

---

## Security Checklist

- [ ] `.env` file is NOT in public directory
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` is generated and unique
- [ ] Database user has minimal required permissions
- [ ] SSL/TLS certificate is installed (HTTPS)
- [ ] `SESSION_SECURE_COOKIE=true` for HTTPS
- [ ] `storage/` and `bootstrap/cache/` are writable but not web-accessible
- [ ] `.git/` directory is removed or blocked via `.htaccess`
- [ ] Stripe webhook secret is configured
- [ ] Rate limiting is enabled
- [ ] CORS is properly configured
- [ ] Content Security Policy is enabled

---

## Support & Resources

- **Main Documentation**: [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)
- **Redis Alternative Setup**: This guide
- **Dedicated Server Guide**: [.env.dedicated-server.example](.env.dedicated-server.example)
- **Issues**: https://github.com/your-repo/issues

---

## Frequently Asked Questions

### Q: Can I use Redis later if I upgrade to VPS?

**A:** Yes! Just:
1. Install Redis on your VPS
2. Update `.env`:
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```
3. Clear config cache: `php artisan config:clear`
4. No code changes needed - the app automatically detects Redis availability

### Q: Is performance significantly worse without Redis?

**A:** For small to medium traffic (< 1000 concurrent users), database-based caching is perfectly adequate. You'll notice a difference with:
- High traffic (> 5000 concurrent users)
- Frequent cache reads/writes
- Real-time features

### Q: Can I mix Redis and non-Redis environments?

**A:** Yes! The `RedisAvailabilityService` automatically detects availability. You can deploy the same codebase to:
- Development (with Redis)
- Shared hosting (without Redis)
- Production VPS (with Redis)

No configuration changes needed - it adapts automatically.

### Q: What about WebSockets for real-time features?

**A:** Options:
1. **Pusher** (recommended for shared hosting) - Free tier available
2. **Laravel WebSockets** / **Soketi** (requires VPS)
3. **Polling fallback** - App automatically uses polling when WebSockets unavailable

---

## Changelog

- **2025-11-03**: Added automatic Redis detection and graceful degradation
- **2025-10-28**: Fixed UUID/BIGINT foreign key mismatches
- **2025-10-13**: Initial shared hosting deployment guide

---

## Next Steps

After successful deployment:

1. **Test the application**:
   - Create a test account
   - Test club creation
   - Test Stripe checkout (in test mode first!)

2. **Configure production Stripe**:
   - Switch to live keys
   - Set up webhooks
   - Test a real subscription

3. **Monitor logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Set up backups**:
   - Database: Daily automated backups (via hosting control panel)
   - Files: Weekly backups via FTP

5. **Performance monitoring**:
   - Enable slow query logging
   - Monitor disk space
   - Track response times

---

**Congratulations! Your BasketManager Pro is now live on shared hosting! 🎉**
