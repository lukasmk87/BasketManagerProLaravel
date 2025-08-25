#!/bin/bash

echo "🧪 Testing TenantSeeder Fix..."
echo "=============================="

echo "📊 Current tenant count:"
php artisan tinker --execute="echo \App\Models\Tenant::count() . ' tenants in database';"

echo ""
echo "🌱 Running db:seed to test fix..."
php artisan db:seed

echo ""
echo "📊 Final tenant count:"
php artisan tinker --execute="echo \App\Models\Tenant::count() . ' tenants in database';"

echo ""
echo "✅ TenantSeeder fix test completed!"