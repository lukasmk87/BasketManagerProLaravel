# BasketManager Pro - Production Deployment Guide

## 🚀 Quick Deployment Checklist

### 1. Server Requirements
- **PHP**: 8.3+
- **Database**: PostgreSQL 14+ or MySQL 8.0+
- **Redis**: 7.0+
- **Web Server**: Nginx + PHP-FPM
- **SSL**: Wildcard certificate for multi-tenancy
- **Memory**: 4GB+ RAM recommended

### 2. Environment Setup

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