# BasketManager Pro - Production Deployment Guide

## 🚀 Quick Deployment Checklist

### 1. Server Requirements
- **PHP**: 8.3+
- **Database**: PostgreSQL 14+ or MySQL 8.0+
- **Redis**: 7.0+
- **Web Server**: Nginx + PHP-FPM
- **SSL**: Wildcard certificate for multi-tenancy
- **Memory**: 4GB+ RAM recommended

### 1.5. File Structure Requirements

#### ✅ **REQUIRED Files and Folders for Deployment**

```
/app/                     # Complete application logic
/bootstrap/               # Laravel bootstrap files
/config/                  # All configuration files
/database/migrations/     # Database migrations
/database/seeders/        # Seeders (optional, for initial setup)
/public/                  # Public directory (Document Root)
  ├── index.php          # Main entry point
  ├── .htaccess          # Apache configuration (if present)
  ├── favicon.ico
  ├── robots.txt
  ├── manifest.json      # PWA Manifest
  ├── sw.js              # Service Worker
  └── build/             # Compiled assets (after npm run build)
/resources/views/         # Blade templates
/resources/lang/          # Language files
/routes/                  # All route definitions
/storage/                 # Storage directory (WITHOUT contents)
  ├── app/               # Structure must exist
  ├── framework/         # Structure must exist
  └── logs/              # Structure must exist
/vendor/                  # PHP dependencies (after composer install)
artisan                   # Laravel CLI
composer.json             # PHP dependencies definition
composer.lock             # Exact version locks
.env                      # Environment variables (MUST BE CREATED)
```

#### ❌ **NOT NEEDED Files and Folders**

```
/node_modules/            # NPM packages (only for build)
/tests/                   # Test files
/python/                  # Python ML scripts (separate service)
/docker/                  # Docker configuration
/scripts/                 # Local development scripts
/.git/                    # Git repository
/.github/                 # GitHub configuration
package.json              # Only for build
package-lock.json         # Only for build
vite.config.js           # Only for build
tailwind.config.js       # Only for build
postcss.config.js        # Only for build
phpunit.xml              # Test configuration
.gitignore               # Git configuration
*.md                     # Documentation files
/ToDo/                   # Development documentation
/resources/js/           # Uncompiled JS files
/resources/css/          # Uncompiled CSS files
/storage/debugbar/       # Debug files
/storage/logs/*.log      # Local log files
database.sqlite          # Local test database
```

#### 📦 **Build Process Requirements**

Assets must be compiled LOCALLY before upload:
```bash
# Local build process (run before deployment)
npm install
npm run build
# This creates /public/build/ with compiled assets
```

#### ⚠️ **Critical Notes**

- **Document Root**: Server must point to `/public/` directory
- **.env File**: NEVER upload local .env! Create new .env on server
- **Storage Folder**: Structure must exist but WITHOUT local contents
- **Vendor Folder**: Can be created locally or on server with `composer install`
- **Build Assets**: `/public/build/` must be uploaded after local compilation

### 2. Environment Setup

#### Option A: Server with SSH Access (Recommended)

```bash
# Clone and install dependencies
git clone <repository>
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --force
php artisan db:seed --class=TenantSeeder
php artisan setup:rls
```

#### Option B: Shared Hosting without SSH Access

**Step 1: Local Preparation**
```bash
# Local build process
npm install
npm run build
composer install --optimize-autoloader --no-dev

# Create deployment package (exclude unnecessary files)
tar -czf basketmanager-deploy.tar.gz \
  --exclude='node_modules' \
  --exclude='.git' \
  --exclude='tests' \
  --exclude='*.md' \
  --exclude='package*.json' \
  --exclude='vite.config.js' \
  --exclude='tailwind.config.js' \
  --exclude='storage/logs/*.log' \
  .
```

**Step 2: Upload and Configure**
```bash
# Upload files to web hosting (via FTP/Panel)
# 1. Extract files to root directory
# 2. Move /public/ contents to public_html/ or www/
# 3. Update index.php paths:
```

**Modified public/index.php for shared hosting:**
```php
<?php
// Update these paths if Laravel files are outside public_html
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Step 3: Manual Configuration**
```bash
# Create .env file via hosting panel file manager
# Set proper permissions via hosting panel:
# - storage/ folder: 755
# - bootstrap/cache/ folder: 755

# Run setup commands via hosting panel terminal (if available):
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 💡 **Minimal Upload Strategy for Shared Hosting**

Upload only these essential directories:
```
/app/                    # Application logic
/bootstrap/              # Laravel bootstrap
/config/                 # Configuration
/database/migrations/    # Database structure
/public/build/          # Compiled assets
/resources/views/       # Templates
/resources/lang/        # Language files
/routes/                # Routes
/storage/               # Empty structure only
/vendor/                # PHP dependencies
artisan                 # CLI tool
composer.json           # Dependencies
index.php               # Entry point
.htaccess              # Apache rules (if needed)
```

**Total upload size**: ~50-100MB (vs 500MB+ with all files)

### 3. Multi-Tenant SSL Configuration

```nginx
# Nginx configuration for wildcard SSL
server {
    listen 443 ssl http2;
    server_name *.basketmanager-pro.com basketmanager-pro.com;
    
    ssl_certificate /path/to/wildcard.crt;
    ssl_certificate_key /path/to/wildcard.key;
    
    root /var/www/basketmanager/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 4. Production Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://basketmanager-pro.com
APP_BASE_DOMAIN=basketmanager-pro.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=basketmanager_pro
DB_USERNAME=basketmanager
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...

# Performance
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 5. Performance Optimization

```bash
# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# OPcache configuration (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
```

### 5.5. Build Process Documentation

#### Pre-Deployment Build Checklist

**Frontend Assets Build:**
```bash
# 1. Install Node dependencies
npm install

# 2. Build production assets
npm run build

# 3. Verify build output
ls -la public/build/
# Should contain:
# - manifest.json (build manifest)
# - assets/ folder with compiled CSS/JS
```

**PHP Dependencies:**
```bash
# 1. Install production dependencies only
composer install --optimize-autoloader --no-dev

# 2. Verify no dev dependencies
composer show --installed | grep -v "dev-"
```

**Asset Verification:**
```bash
# Check asset sizes (should be optimized)
du -sh public/build/assets/*

# Verify Vite manifest
cat public/build/manifest.json | jq '.'
```

#### Common Build Issues & Solutions

**Issue**: `npm run build` fails with permission errors
```bash
# Solution: Fix node_modules ownership
sudo chown -R $(whoami) node_modules
rm -rf node_modules package-lock.json
npm install
npm run build
```

**Issue**: Vite assets not loading in production
```bash
# Solution: Verify APP_URL in .env matches domain
APP_URL=https://your-domain.com

# Clear Laravel caches
php artisan config:clear
php artisan config:cache
```

**Issue**: CSS/JS files missing after upload
```bash
# Solution: Ensure public/build/ folder uploaded
# Check .gitignore doesn't exclude build folder in deployment
```

#### Asset Optimization Settings

**Vite Production Config** (already configured in vite.config.js):
```javascript
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'axios'],
                    charts: ['chart.js']
                }
            }
        },
        chunkSizeWarningLimit: 1600
    }
});
```

**Expected Build Output Sizes:**
- Main app.js: ~100-300KB (gzipped)
- Main app.css: ~50-100KB (gzipped)
- Vendor chunks: ~200-400KB (gzipped)
- Chart.js chunk: ~200KB (gzipped)

### 6. Security Hardening

```bash
# File permissions
sudo chown -R www-data:www-data /var/www/basketmanager
sudo chmod -R 755 /var/www/basketmanager
sudo chmod -R 775 /var/www/basketmanager/storage
sudo chmod -R 775 /var/www/basketmanager/bootstrap/cache

# Security headers middleware
# Already implemented in SecurityHeadersMiddleware
```

### 7. Monitoring Setup

```bash
# Health checks
curl https://basketmanager-pro.com/health

# Performance monitoring
php artisan db:analyze-performance --recommendations

# Log monitoring
tail -f storage/logs/laravel.log
```

### 8. Multi-Tenant Verification

```bash
# Test tenant isolation
curl -H "Host: lakers.basketmanager-pro.com" https://basketmanager-pro.com/api/v4/teams
curl -H "Host: warriors.basketmanager-pro.com" https://basketmanager-pro.com/api/v4/teams

# Verify different responses for different tenants
```

### 9. Backup Strategy

```bash
# Database backup
pg_dump basketmanager_pro > backup_$(date +%Y%m%d).sql

# File storage backup
rsync -av storage/ /backup/storage/

# Automated backup script
0 2 * * * /usr/local/bin/backup-basketmanager.sh
```

### 10. Go-Live Verification

- ✅ SSL certificates valid for all subdomains
- ✅ Database queries under 100ms average
- ✅ API endpoints return correct tenant data
- ✅ Stripe webhooks processing successfully
- ✅ PWA installation working on mobile
- ✅ Error rates under 1%

## 🔧 Troubleshooting

**High Memory Usage**: Implement chunked processing
**Slow Queries**: Enable query monitoring and add indexes
**Tenant Isolation Issues**: Check Row Level Security policies
**SSL Certificate Issues**: Verify wildcard certificate covers all subdomains

## 📊 Performance Targets

- **Response Time**: 95% requests < 200ms
- **Database Queries**: < 50ms average
- **Memory Usage**: < 256MB per request
- **Uptime**: 99.9% SLA
- **Error Rate**: < 0.1%

## 🚨 Critical Monitoring Alerts

- Memory usage > 80%
- Database response > 1000ms
- Error rate > 1%
- SSL certificate expires in 30 days
- Disk space > 85%