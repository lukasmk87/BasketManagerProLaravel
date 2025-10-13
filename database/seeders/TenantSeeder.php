<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create production tenant
        Tenant::firstOrCreate(
            ['slug' => 'basketmanager-pro'],
            [
                'name' => 'BasketManager Pro',
                'domain' => 'basketmanager-pro.de',
                'billing_email' => 'info@basketmanager-pro.de',
                'billing_name' => 'Cathrin Hein MBA.',
                'billing_address' => 'Klosestr. 39\n22094 Bernburg (Saale)',
                'vat_number' => 'DE485844778',
                'country_code' => 'AT',
                'timezone' => 'Europe/Berlin',
                'locale' => 'de',
                'currency' => 'EUR',
                'subscription_tier' => 'enterprise',
                'is_active' => true,
                'is_suspended' => false,
                'features' => ['white_label', 'dedicated_support', 'custom_integrations', 'advanced_analytics'],
                'max_users' => -1,
                'max_teams' => -1,
                'max_storage_gb' => 1000,
                'max_api_calls_per_hour' => -1,
            ]
        );

        // Create staging tenant for development/testing
        Tenant::firstOrCreate(
            ['slug' => 'staging'],
            [
                'name' => 'BasketManager Pro Staging',
                'domain' => 'staging.basketmanager-pro.de',
                'billing_email' => 'staging@basketmanager-pro.de',
                'billing_name' => 'BasketManager Pro Staging',
                'country_code' => 'DE',
                'timezone' => 'Europe/Berlin',
                'locale' => 'de',
                'currency' => 'EUR',
                'subscription_tier' => 'professional',
                'is_active' => true,
                'is_suspended' => false,
                'max_users' => 100,
                'max_teams' => 20,
                'max_storage_gb' => 100,
                'max_api_calls_per_hour' => 1000,
            ]
        );

        // Only create demo/test tenants in local and testing environments
        if (app()->environment(['local', 'testing'])) {
            // Create demo tenant on free trial
            Tenant::firstOrCreate(
                ['slug' => 'demo'],
                [
                    'name' => 'Demo Basketball Club',
                    'subdomain' => 'demo',
                    'billing_email' => 'demo@basketmanager-pro.com',
                    'billing_name' => 'Demo Basketball Club',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'free',
                    'trial_ends_at' => now()->addDays(14),
                    'is_active' => true,
                    'is_suspended' => false,
                    'max_users' => 10,
                    'max_teams' => 3,
                    'max_storage_gb' => 1,
                    'max_api_calls_per_hour' => 100,
                ]
            );

            // Create basic tier tenant
            Tenant::firstOrCreate(
                ['slug' => 'munich-eagles'],
                [
                    'name' => 'Munich Eagles Basketball',
                    'subdomain' => 'munich-eagles',
                    'billing_email' => 'info@munich-eagles.de',
                    'billing_name' => 'Munich Eagles Basketball',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'basic',
                    'is_active' => true,
                    'is_suspended' => false,
                    'max_users' => 25,
                    'max_teams' => 5,
                    'max_storage_gb' => 10,
                    'max_api_calls_per_hour' => 500,
                ]
            );

            // Create professional tier tenant
            Tenant::firstOrCreate(
                ['slug' => 'berlin-thunder'],
                [
                    'name' => 'Berlin Thunder Basketball',
                    'domain' => 'basketball.berlin-thunder.de',
                    'billing_email' => 'admin@berlin-thunder.de',
                    'billing_name' => 'Berlin Thunder Basketball',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'professional',
                    'is_active' => true,
                    'is_suspended' => false,
                    'max_users' => 100,
                    'max_teams' => 20,
                    'max_storage_gb' => 100,
                    'max_api_calls_per_hour' => 1000,
                ]
            );

            // Create enterprise tier tenant
            Tenant::firstOrCreate(
                ['slug' => 'german-academy'],
                [
                    'name' => 'German Basketball Academy',
                    'domain' => 'app.german-basketball-academy.de',
                    'billing_email' => 'enterprise@german-basketball-academy.de',
                    'billing_name' => 'German Basketball Academy',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'enterprise',
                    'is_active' => true,
                    'is_suspended' => false,
                    'features' => [
                        'white_label',
                        'dedicated_support',
                        'custom_integrations',
                        'advanced_analytics',
                        'federation_integration',
                        'multi_club_management',
                    ],
                    'max_users' => -1,
                    'max_teams' => -1,
                    'max_storage_gb' => 1000,
                    'max_api_calls_per_hour' => -1,
                ]
            );

            // Create an expired trial tenant
            Tenant::firstOrCreate(
                ['slug' => 'expired-trial'],
                [
                    'name' => 'Expired Trial Club',
                    'billing_email' => 'expired@example.com',
                    'billing_name' => 'Expired Trial Club',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'free',
                    'trial_ends_at' => now()->subDays(7), // Expired 7 days ago
                    'is_active' => false,
                    'is_suspended' => false,
                    'max_users' => 10,
                    'max_teams' => 3,
                    'max_storage_gb' => 1,
                    'max_api_calls_per_hour' => 100,
                ]
            );

            // Create a suspended tenant
            Tenant::firstOrCreate(
                ['slug' => 'suspended'],
                [
                    'name' => 'Suspended Basketball Club',
                    'billing_email' => 'suspended@example.com',
                    'billing_name' => 'Suspended Basketball Club',
                    'country_code' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'locale' => 'de',
                    'currency' => 'EUR',
                    'subscription_tier' => 'basic',
                    'is_active' => false,
                    'is_suspended' => true,
                    'suspension_reason' => 'Payment failed',
                    'max_users' => 25,
                    'max_teams' => 5,
                    'max_storage_gb' => 10,
                    'max_api_calls_per_hour' => 500,
                ]
            );

            // Create some additional random tenants for testing (only if we don't have many tenants yet)
            $existingTenantCount = Tenant::count();
            if ($existingTenantCount < 10) {
                Tenant::factory()->count(3)->trial()->create();
                Tenant::factory()->count(2)->basic()->create();
                Tenant::factory()->professional()->create();
            }
        }
    }
}
