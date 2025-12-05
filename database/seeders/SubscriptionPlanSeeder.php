<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Kostenloser Plan für kleine Vereine zum Testen der Plattform',
                'price' => 0, // 0 Cents = €0
                'currency' => 'EUR',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'basic_team_management',
                    'basic_player_profiles',
                    'game_scheduling',
                    'basic_statistics',
                    'mobile_web_access',
                ],
                'limits' => [
                    'users' => 10,
                    'teams' => 2,
                    'players' => 30,
                    'storage_gb' => 5,
                    'api_calls_per_hour' => 100,
                    'games_per_month' => 20,
                    'training_sessions_per_month' => 50,
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Ideal für kleine bis mittelgroße Vereine mit erweiterten Funktionen',
                'price' => 4900, // 4900 Cents = €49
                'currency' => 'EUR',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    'basic_team_management',
                    'basic_player_profiles',
                    'game_scheduling',
                    'basic_statistics',
                    'mobile_web_access',
                    'advanced_statistics',
                    'live_scoring',
                    'training_management',
                    'emergency_contacts',
                    'email_notifications',
                    'basic_analytics',
                ],
                'limits' => [
                    'users' => 50,
                    'teams' => 5,
                    'players' => 100,
                    'storage_gb' => 20,
                    'api_calls_per_hour' => 1000,
                    'games_per_month' => 100,
                    'training_sessions_per_month' => 200,
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Für professionelle Vereine mit umfassenden Anforderungen',
                'price' => 14900, // 14900 Cents = €149
                'currency' => 'EUR',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'features' => [
                    'basic_team_management',
                    'basic_player_profiles',
                    'game_scheduling',
                    'basic_statistics',
                    'mobile_web_access',
                    'advanced_statistics',
                    'live_scoring',
                    'training_management',
                    'emergency_contacts',
                    'email_notifications',
                    'basic_analytics',
                    'tournament_management',
                    'video_analysis',
                    'ai_insights',
                    'custom_reports',
                    'api_access',
                    'push_notifications',
                    'advanced_analytics',
                    'data_export',
                    'custom_branding',
                ],
                'limits' => [
                    'users' => 200,
                    'teams' => 20,
                    'players' => 500,
                    'storage_gb' => 100,
                    'api_calls_per_hour' => 5000,
                    'games_per_month' => 500,
                    'training_sessions_per_month' => 1000,
                    'video_storage_gb' => 50,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unbegrenzte Möglichkeiten für große Organisationen und Verbände',
                'price' => 49900, // 49900 Cents = €499
                'currency' => 'EUR',
                'billing_period' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'features' => [
                    'basic_team_management',
                    'basic_player_profiles',
                    'game_scheduling',
                    'basic_statistics',
                    'mobile_web_access',
                    'advanced_statistics',
                    'live_scoring',
                    'training_management',
                    'emergency_contacts',
                    'email_notifications',
                    'basic_analytics',
                    'tournament_management',
                    'video_analysis',
                    'ai_insights',
                    'custom_reports',
                    'api_access',
                    'push_notifications',
                    'advanced_analytics',
                    'data_export',
                    'custom_branding',
                    'federation_integration',
                    'white_label',
                    'dedicated_support',
                    'sla_guarantee',
                    'custom_integrations',
                    'unlimited_api',
                    'multi_club_management',
                    'advanced_security',
                    'audit_logs',
                    'compliance_tools',
                ],
                'limits' => [
                    'users' => -1, // Unlimited
                    'teams' => -1,
                    'players' => -1,
                    'storage_gb' => 1000,
                    'api_calls_per_hour' => -1,
                    'games_per_month' => -1,
                    'training_sessions_per_month' => -1,
                    'video_storage_gb' => 500,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
