#!/bin/bash

# Fix Drill Permissions Script
# This script adds the missing drill permissions and clears caches

echo "🔧 Fixing Drill Permissions for Club Admin and Super Admin..."

# Run the migration to add drill permissions
echo "📊 Running drill permissions migration..."
php artisan migrate --path=database/migrations/2025_08_25_000001_add_drill_permissions.php

# Clear permission cache
echo "🧹 Clearing permission cache..."
php artisan permission:cache-reset

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear

# Clear config cache
echo "🧹 Clearing config cache..."
php artisan config:clear

# Optimize application
echo "⚡ Optimizing application..."
php artisan optimize

echo "✅ Drill permissions fix completed!"
echo "🎯 Club Admins and Super Admins should now be able to create drills."