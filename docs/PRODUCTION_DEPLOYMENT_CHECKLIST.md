# 🚀 Production Deployment Checklist

**Projekt:** BasketManager Pro - Multi-Club Subscriptions mit Stripe Integration
**Version:** 1.0.0
**Stand:** November 2025

---

## 📋 Pre-Deployment Checklist

### 1. Environment Configuration

- [ ] **Copy `.env.production.example` to `.env`**
  ```bash
  cp .env.production.example .env
  ```

- [ ] **Generate Application Key**
  ```bash
  php artisan key:generate
  ```

- [ ] **Configure Application Settings**
  - [ ] Set `APP_NAME` to "BasketManager Pro"
  - [ ] Set `APP_ENV=production`
  - [ ] Set `APP_DEBUG=false` ⚠️ **CRITICAL**
  - [ ] Set `APP_URL` to production URL (https://basketmanager-pro.de)
  - [ ] Set `APP_LOCALE=de` and `APP_FALLBACK_LOCALE=de`

- [ ] **Configure Database**
  - [ ] Set `DB_CONNECTION=mysql`
  - [ ] Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`
  - [ ] Create secure database username and password
  - [ ] Test database connection: `php artisan db:show`

- [ ] **Configure Redis**
  - [ ] Install Redis on server
  - [ ] Set `REDIS_HOST`, `REDIS_PORT`
  - [ ] Set strong `REDIS_PASSWORD`
  - [ ] Test Redis connection: `redis-cli ping`

- [ ] **Configure Mail**
  - [ ] Set `MAIL_MAILER=smtp`
  - [ ] Configure SMTP credentials
  - [ ] Set `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`
  - [ ] Test email: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com'))`

---

### 2. Stripe Configuration (LIVE MODE)

⚠️ **WARNING: Only use LIVE keys on production!**

- [ ] **Switch to Stripe Live Mode**
  - [ ] Login to [Stripe Dashboard](https://dashboard.stripe.com)
  - [ ] Toggle from "Test mode" to "Live mode" (top right)

- [ ] **Get Stripe API Keys**
  - [ ] Navigate to Developers → API keys
  - [ ] Copy **Publishable key** (`pk_live_...`)
  - [ ] Copy **Secret key** (`sk_live_...`)
  - [ ] Set `STRIPE_KEY=pk_live_...` in `.env`
  - [ ] Set `STRIPE_SECRET=sk_live_...` in `.env`
  - [ ] Set `VITE_STRIPE_KEY="${STRIPE_KEY}"` in `.env`

- [ ] **Create Stripe Webhook**
  - [ ] Navigate to Developers → Webhooks
  - [ ] Click "Add endpoint"
  - [ ] Set Endpoint URL: `https://basketmanager-pro.de/webhooks/stripe/club-subscriptions`
  - [ ] Select **11 events**:
    - `checkout.session.completed`
    - `customer.subscription.created`
    - `customer.subscription.updated`
    - `customer.subscription.deleted`
    - `invoice.payment_succeeded`
    - `invoice.payment_failed`
    - `invoice.created`
    - `invoice.finalized`
    - `invoice.payment_action_required`
    - `payment_method.attached`
    - `payment_method.detached`
  - [ ] Copy **Signing secret** (`whsec_...`)
  - [ ] Set `STRIPE_WEBHOOK_SECRET=whsec_...` in `.env`

- [ ] **Create Subscription Products in Stripe**
  - [ ] Run seeder: `php artisan db:seed --class=ClubSubscriptionPlanSeeder`
  - [ ] Sync plans with Stripe: `php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe`
  - [ ] Copy Product IDs and Price IDs to `.env`:
    - `STRIPE_STANDARD_CLUB_PRODUCT_ID`
    - `STRIPE_STANDARD_CLUB_PRICE_MONTHLY`
    - `STRIPE_STANDARD_CLUB_PRICE_YEARLY`
    - `STRIPE_PREMIUM_CLUB_PRODUCT_ID`
    - `STRIPE_PREMIUM_CLUB_PRICE_MONTHLY`
    - `STRIPE_PREMIUM_CLUB_PRICE_YEARLY`
    - `STRIPE_ENTERPRISE_CLUB_PRODUCT_ID`
    - `STRIPE_ENTERPRISE_CLUB_PRICE_MONTHLY`
    - `STRIPE_ENTERPRISE_CLUB_PRICE_YEARLY`

- [ ] **Test Stripe Configuration**
  - [ ] Verify webhook delivers to endpoint: `php artisan log:tail`
  - [ ] Test checkout with live card: Use Stripe test card `4242 4242 4242 4242` in test mode first
  - [ ] Verify subscription appears in Stripe Dashboard

---

### 3. Feature Flags Configuration

- [ ] **Enable Club Subscription Features**
  ```bash
  # In .env file
  FEATURE_CLUB_SUBSCRIPTIONS_ENABLED=true
  FEATURE_CLUB_SUBSCRIPTIONS_CHECKOUT_ENABLED=true
  FEATURE_CLUB_SUBSCRIPTIONS_BILLING_PORTAL_ENABLED=true
  FEATURE_CLUB_SUBSCRIPTIONS_PLAN_SWAP_ENABLED=true
  FEATURE_CLUB_SUBSCRIPTIONS_NOTIFICATIONS_ENABLED=true
  ```

- [ ] **Configure Rollout Strategy**
  ```bash
  FEATURE_ROLLOUT_METHOD=percentage  # or 'whitelist' for gradual rollout
  FEATURE_CLUB_SUBSCRIPTIONS_ROLLOUT=100  # Start with 0-20% for soft launch
  ```

- [ ] **Optional: Gradual Rollout**
  - [ ] If using gradual rollout, set `FEATURE_ROLLOUT_METHOD=whitelist`
  - [ ] Add initial tenant IDs: `FEATURE_ROLLOUT_WHITELIST_TENANTS=1,2,3`

---

### 4. Security Configuration

- [ ] **HTTPS Configuration**
  - [ ] SSL certificate installed (Let's Encrypt recommended)
  - [ ] Force HTTPS in web server configuration
  - [ ] Set `SESSION_SECURE_COOKIE=true` in `.env`

- [ ] **File Permissions**
  ```bash
  chmod 755 /path/to/app
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  chmod 600 .env  # Restrict .env file access
  ```

- [ ] **Environment Security**
  - [ ] Ensure `.env` is in `.gitignore`
  - [ ] Never commit API keys or secrets to Git
  - [ ] Disable directory listing in web server

- [ ] **Firewall Rules**
  - [ ] Allow HTTP (80) and HTTPS (443)
  - [ ] Restrict database port (3306) to localhost
  - [ ] Restrict Redis port (6379) to localhost

---

### 5. Monitoring & Error Tracking

- [ ] **Sentry Integration**
  - [ ] Create Sentry project at https://sentry.io
  - [ ] Get DSN
  - [ ] Set `SENTRY_LARAVEL_DSN` in `.env`
  - [ ] Test: `php artisan sentry:test`

- [ ] **Logging Configuration**
  - [ ] Set `LOG_STACK=daily` for daily log rotation
  - [ ] Set `LOG_LEVEL=info` or `warning` for production

- [ ] **Monitoring Setup**
  - [ ] Configure server monitoring (CPU, RAM, Disk)
  - [ ] Set up uptime monitoring (e.g., UptimeRobot, Pingdom)
  - [ ] Configure Laravel Horizon for queue monitoring

---

## 🚀 Deployment Steps

### Step 1: Code Deployment

```bash
# 1. Clone repository (first time only)
git clone git@github.com:yourorg/basketmanager-pro.git
cd basketmanager-pro

# 2. Pull latest code
git pull origin main

# 3. Copy environment file
cp .env.production.example .env
# Edit .env with actual values

# 4. Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# 5. Generate app key
php artisan key:generate
```

### Step 2: Database Setup

```bash
# 1. Create database backup directory
mkdir -p backups

# 2. Preview migrations
php artisan migrate --pretend

# 3. Run migrations
php artisan migrate --force

# 4. Seed subscription plans
php artisan db:seed --class=ClubSubscriptionPlanSeeder --sync-stripe

# 5. Validate database
php artisan subscriptions:validate
```

### Step 3: Cache & Optimization

```bash
# 1. Clear all caches
php artisan optimize:clear

# 2. Cache configuration
php artisan config:cache

# 3. Cache routes
php artisan route:cache

# 4. Cache views
php artisan view:cache

# 5. Optimize autoloader
composer dump-autoload --optimize
```

### Step 4: Queue & Scheduler Setup

```bash
# 1. Start queue workers (use supervisor or systemd)
# Create supervisor config: /etc/supervisor/conf.d/basketmanager-worker.conf
# [program:basketmanager-worker]
# process_name=%(program_name)s_%(process_num)02d
# command=php /path/to/app/artisan queue:work redis --sleep=3 --tries=3 --timeout=60
# autostart=true
# autorestart=true
# user=www-data
# numprocs=4
# redirect_stderr=true
# stdout_logfile=/path/to/app/storage/logs/worker.log

# 2. Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start basketmanager-worker:*

# 3. Setup cron for scheduler (run as www-data user)
# * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Step 5: Final Checks

```bash
# 1. Verify application is responding
curl -I https://basketmanager-pro.de

# 2. Check queue workers are running
php artisan queue:monitor

# 3. Check database migrations
php artisan migrate:status

# 4. Validate subscription data
php artisan subscriptions:validate

# 5. Check logs for errors
tail -f storage/logs/laravel.log
```

---

## ✅ Post-Deployment Checklist

### 1. Smoke Tests

- [ ] **Authentication**
  - [ ] Login as Super Admin
  - [ ] Login as Club Admin
  - [ ] Test 2FA if enabled

- [ ] **Club Subscription Flow**
  - [ ] Navigate to Subscription page
  - [ ] View available plans
  - [ ] Click "Subscribe" button
  - [ ] Verify Stripe Checkout opens
  - [ ] Complete test purchase (use test card in Stripe test mode first!)
  - [ ] Verify subscription appears in database
  - [ ] Check webhook was received: `tail -f storage/logs/laravel.log | grep webhook`

- [ ] **Billing Portal**
  - [ ] Access billing portal
  - [ ] Verify invoices display
  - [ ] Test plan swap (upgrade/downgrade)
  - [ ] Test payment method management

- [ ] **Analytics**
  - [ ] Verify MRR snapshots are being created
  - [ ] Run: `php artisan subscription:update-mrr --dry-run`
  - [ ] Check analytics dashboard (if enabled)

### 2. Monitoring & Alerts

- [ ] **Verify Monitoring**
  - [ ] Check Sentry for errors
  - [ ] Verify uptime monitoring is active
  - [ ] Check queue workers are running: `php artisan horizon:status`

- [ ] **Test Alerts**
  - [ ] Trigger test error to verify Sentry integration
  - [ ] Verify email notifications work: `php artisan subscription:test-notifications`

### 3. Performance Checks

- [ ] **Response Times**
  - [ ] Homepage: < 1s
  - [ ] Subscription page: < 2s
  - [ ] Checkout redirect: < 1s

- [ ] **Database Performance**
  - [ ] Run: `php artisan db:analyze-performance`
  - [ ] Check slow query log
  - [ ] Verify indexes are in place

- [ ] **Cache Performance**
  - [ ] Verify Redis is being used: `redis-cli info stats`
  - [ ] Check cache hit rate: > 80%

---

## 🔄 Rollback Procedure

If issues are encountered after deployment:

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Rollback database migrations
php artisan migrate:rollback --step=1

# 3. Restore previous code version
git reset --hard HEAD~1

# 4. Reinstall dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Clear caches
php artisan optimize:clear

# 6. Restart queue workers
php artisan queue:restart

# 7. Disable maintenance mode
php artisan up

# 8. Monitor logs
tail -f storage/logs/laravel.log
```

**Or use the automated rollback script:**
```bash
./deploy.sh --rollback
```

---

## 📞 Support & Troubleshooting

### Common Issues

1. **Webhook not receiving events**
   - Check webhook URL is publicly accessible
   - Verify webhook secret matches Stripe Dashboard
   - Check firewall rules allow Stripe IPs
   - View webhook attempts in Stripe Dashboard

2. **CSRF 419 Errors**
   - Ensure `SESSION_SECURE_COOKIE=true` (for HTTPS)
   - Set `SESSION_DOMAIN=.basketmanager-pro.de`
   - Clear browser cookies
   - Verify SSL certificate is valid

3. **Queue jobs not processing**
   - Check supervisor is running: `sudo supervisorctl status`
   - Restart queue workers: `php artisan queue:restart`
   - Check Redis connection: `redis-cli ping`
   - Check worker logs: `tail -f storage/logs/worker.log`

4. **Stripe checkout fails**
   - Verify Stripe LIVE keys are used
   - Check webhook delivers successfully
   - Verify plan is synced with Stripe
   - Check logs: `tail -f storage/logs/laravel.log | grep Stripe`

### Emergency Contacts

- **Technical Lead:** [Name] - [Email] - [Phone]
- **DevOps:** [Name] - [Email] - [Phone]
- **On-Call:** [Phone]

---

## 📚 Additional Documentation

- [Subscription API Reference](/docs/SUBSCRIPTION_API_REFERENCE.md)
- [Integration Guide](/docs/SUBSCRIPTION_INTEGRATION_GUIDE.md)
- [Architecture Documentation](/docs/SUBSCRIPTION_ARCHITECTURE.md)
- [Admin Guide](/docs/SUBSCRIPTION_ADMIN_GUIDE.md)
- [Rollout Strategy](/docs/ROLLOUT_STRATEGY.md)

---

**Last Updated:** November 2025
**Reviewed By:** [Name]
**Next Review Date:** December 2025
