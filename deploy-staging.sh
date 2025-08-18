#!/bin/bash

# BasketManager Pro Laravel - Staging Deployment Script
# Behebt "Class name must be a valid object or a string" Fehler

echo "🚀 Starting BasketManager Pro staging deployment..."

# Ensure we're in the correct directory
cd /home/lukasmk/Projekt/BasketManagerProLaravel

echo "📁 Current directory: $(pwd)"

# Set proper permissions for artisan
chmod +x artisan

echo "🧹 Clearing all caches..."

# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear compiled services and packages
php artisan clear-compiled

echo "🔄 Regenerating Composer autoloader..."

# Update composer autoloader (optimized for production)
composer dump-autoload --optimize --classmap-authoritative

echo "⚙️ Setting environment variables..."

# Ensure Telescope is disabled in staging
export TELESCOPE_ENABLED=false
export APP_ENV=staging
export APP_DEBUG=false

echo "⚙️ Optimizing for production..."

# Cache configuration for better performance
php artisan config:cache

# Cache routes for better performance
php artisan route:cache

# Cache views for better performance
php artisan view:cache

echo "🔨 Building frontend assets..."

# Build frontend assets for production
npm run build

echo "📦 Frontend build completed successfully!"

echo "🔧 Running migrations (if needed)..."

# Run migrations safely
php artisan migrate --force

echo "🎯 Optimizing application..."

# Optimize application
php artisan optimize

echo "🔭 Checking Telescope status..."

# Verify Telescope is disabled
php artisan about | grep -i telescope || echo "Telescope is disabled (as expected)"

echo "📊 Checking route status..."

# Verify routes are working
php artisan route:list | grep dashboard

echo "✅ Deployment completed successfully!"
echo ""
echo "🌐 Dashboard should now be accessible at: https://staging.basketmanager-pro.de/dashboard"
echo ""
echo "📋 Next steps for deployment:"
echo "1. Copy all files including public/build/ to staging server:"
echo "   rsync -av --delete ./ user@staging.basketmanager-pro.de:/path/to/staging/"
echo "2. Or if using separate build upload:"
echo "   rsync -av --delete public/build/ user@staging.basketmanager-pro.de:/path/to/staging/public/build/"
echo "3. Run this script on staging server"
echo "4. Ensure .env has correct values:"
echo "   - APP_ENV=staging"
echo "   - APP_DEBUG=false"  
echo "   - TELESCOPE_ENABLED=false"
echo ""
echo "If issues persist, check Laravel logs:"
echo "tail -f storage/logs/laravel.log"