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
        Tenant::factory()->enterprise()->create([
            'name' => 'BasketManager Pro',
            'slug' => 'basketmanager-pro',
            'domain' => 'basketmanager-pro.de',
            'billing_email' => 'info@basketmanager-pro.de',
        ]);

        // Create staging tenant for development/testing
        Tenant::factory()->professional()->create([
            'name' => 'BasketManager Pro Staging',
            'slug' => 'staging',
            'domain' => 'staging.basketmanager-pro.de',
            'billing_email' => 'staging@basketmanager-pro.de',
        ]);

        // Create demo tenant on free trial
        Tenant::factory()->create([
            'name' => 'Demo Basketball Club',
            'slug' => 'demo',
            'subdomain' => 'demo',
            'billing_email' => 'demo@basketmanager-pro.com',
            'subscription_tier' => 'free',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create basic tier tenant
        Tenant::factory()->basic()->create([
            'name' => 'Munich Eagles Basketball',
            'slug' => 'munich-eagles',
            'subdomain' => 'munich-eagles',
            'billing_email' => 'info@munich-eagles.de',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);

        // Create professional tier tenant
        Tenant::factory()->professional()->create([
            'name' => 'Berlin Thunder Basketball',
            'slug' => 'berlin-thunder',
            'domain' => 'basketball.berlin-thunder.de',
            'billing_email' => 'admin@berlin-thunder.de',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);

        // Create enterprise tier tenant
        Tenant::factory()->enterprise()->create([
            'name' => 'German Basketball Academy',
            'slug' => 'german-academy',
            'domain' => 'app.german-basketball-academy.de',
            'billing_email' => 'enterprise@german-basketball-academy.de',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
            'features' => [
                'white_label',
                'dedicated_support',
                'custom_integrations',
                'advanced_analytics',
                'federation_integration',
                'multi_club_management',
            ],
        ]);

        // Create some additional random tenants for testing
        Tenant::factory()->count(3)->trial()->create();
        Tenant::factory()->count(2)->basic()->create();
        Tenant::factory()->professional()->create();
        
        // Create an expired trial tenant
        Tenant::factory()->expiredTrial()->create([
            'name' => 'Expired Trial Club',
            'slug' => 'expired-trial',
            'billing_email' => 'expired@example.com',
        ]);
        
        // Create a suspended tenant
        Tenant::factory()->suspended()->create([
            'name' => 'Suspended Basketball Club',
            'slug' => 'suspended',
            'billing_email' => 'suspended@example.com',
        ]);
    }
}
