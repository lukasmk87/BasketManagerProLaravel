#!/bin/bash

# Fix Ziggy Routes - Deployment Script
# This script fixes the Ziggy route caching issue for drill routes

echo "🔧 Fixing Ziggy Route Error for Drill Management..."
echo "==============================================="

# Clear all caches first
echo "🧹 Clearing all Laravel caches..."
php artisan route:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Regenerate route cache with new drill routes
echo "📋 Regenerating route cache..."
php artisan route:cache

# Regenerate config cache
echo "⚙️ Regenerating config cache..."
php artisan config:cache

# Optimize the application
echo "⚡ Optimizing application..."
php artisan optimize

# Generate Ziggy routes (if available)
echo "🗺️ Attempting to generate Ziggy routes..."
if php artisan list | grep -q "ziggy:generate"; then
    php artisan ziggy:generate
    echo "✅ Ziggy routes regenerated"
else
    echo "ℹ️ Ziggy:generate command not available - routes will be generated from cache"
fi

# Check if routes are available
echo "🔍 Verifying drill routes are available..."
php artisan route:list --name="training.drills" | head -10

echo ""
echo "✅ Ziggy route fix completed!"
echo "🎯 The drill management routes should now be available in the frontend."
echo ""
echo "📦 Don't forget to deploy the updated frontend assets:"
echo "   - Upload the new files from public/build/ to the server"
echo "   - Or run 'npm run build' on the server if Node.js is available"