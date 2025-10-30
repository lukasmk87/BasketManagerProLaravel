<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\SubscriptionAnalyticsReportMail;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SubscriptionAnalyticsReportMail.
 *
 * @see \App\Mail\ClubSubscription\SubscriptionAnalyticsReportMail
 */
class SubscriptionAnalyticsReportMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_includes_tenant_name_and_report_date_in_subject()
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => ['total' => 5000],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Tenant', $envelope->subject);
        $this->assertStringContainsString('2024-10', $envelope->subject);
        $this->assertStringContainsString('Analytics Report', $envelope->subject);
        $this->assertStringContainsString('📊', $envelope->subject);
    }

    /** @test */
    public function it_includes_mrr_metrics_in_content()
    {
        $tenant = Tenant::factory()->create();

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => [
                'total' => 5000.00,
                'growth_rate_3m' => 15.5,
                'by_plan' => [
                    ['plan_name' => 'Premium', 'mrr' => 3000.00],
                    ['plan_name' => 'Standard', 'mrr' => 2000.00],
                ],
            ],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.analytics-report', $content->markdown);
        $this->assertArrayHasKey('totalMRR', $content->with);
        $this->assertArrayHasKey('mrrGrowthRate', $content->with);
        $this->assertArrayHasKey('mrrByPlan', $content->with);

        $this->assertEquals(5000.00, $content->with['totalMRR']);
        $this->assertEquals(15.5, $content->with['mrrGrowthRate']);
        $this->assertCount(2, $content->with['mrrByPlan']);
    }

    /** @test */
    public function it_includes_churn_metrics_in_content()
    {
        $tenant = Tenant::factory()->create();

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => ['total' => 5000],
            'churn' => [
                'monthly_rate' => 3.2,
                'revenue_churn' => 2.8,
                'reasons' => [
                    'voluntary' => ['count' => 5, 'percentage' => 50],
                    'payment_failed' => ['count' => 5, 'percentage' => 50],
                ],
            ],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $content = $mail->content();

        $this->assertArrayHasKey('churnRate', $content->with);
        $this->assertArrayHasKey('revenueChurn', $content->with);
        $this->assertArrayHasKey('churnReasons', $content->with);

        $this->assertEquals(3.2, $content->with['churnRate']);
        $this->assertEquals(2.8, $content->with['revenueChurn']);
        $this->assertCount(2, $content->with['churnReasons']);
    }

    /** @test */
    public function it_includes_ltv_and_health_metrics_in_content()
    {
        $tenant = Tenant::factory()->create();

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => ['total' => 5000],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => [
                'average' => 1200.00,
                'by_plan' => [
                    ['plan_name' => 'Premium', 'ltv' => 1800.00],
                ],
            ],
            'health' => [
                'active_subscriptions' => 50,
                'trial_conversion' => 65.5,
                'avg_duration_days' => 180,
                'upgrade_downgrade' => [
                    'upgrades' => 5,
                    'downgrades' => 2,
                ],
            ],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $content = $mail->content();

        $this->assertArrayHasKey('averageLTV', $content->with);
        $this->assertArrayHasKey('ltvByPlan', $content->with);
        $this->assertArrayHasKey('activeSubscriptions', $content->with);
        $this->assertArrayHasKey('trialConversionRate', $content->with);
        $this->assertArrayHasKey('avgSubscriptionDuration', $content->with);
        $this->assertArrayHasKey('upgradeDowngradeRates', $content->with);

        $this->assertEquals(1200.00, $content->with['averageLTV']);
        $this->assertEquals(50, $content->with['activeSubscriptions']);
        $this->assertEquals(65.5, $content->with['trialConversionRate']);
    }

    /** @test */
    public function it_detects_positive_mrr_growth_in_key_insights()
    {
        $tenant = Tenant::factory()->create();

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => [
                'total' => 5000,
                'growth_rate_3m' => 15.5, // > 10%
            ],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50, 'trial_conversion' => 65],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $content = $mail->content();

        $insights = $content->with['keyInsights'];

        $this->assertNotEmpty($insights);

        // Should have a positive MRR growth insight
        $mrrInsight = collect($insights)->firstWhere('type', 'positive');
        $this->assertNotNull($mrrInsight);
        $this->assertStringContainsString('15.5', $mrrInsight['text']);
        $this->assertStringContainsString('Wachstum', $mrrInsight['text']);
    }

    /** @test */
    public function it_detects_negative_mrr_growth_in_key_insights()
    {
        $tenant = Tenant::factory()->create();

        $reportData = [
            'date' => '2024-10-15',
            'mrr' => [
                'total' => 5000,
                'growth_rate_3m' => -5.5, // Negative
            ],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50, 'trial_conversion' => 65],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);

        $content = $mail->content();

        $insights = $content->with['keyInsights'];

        // Should have a negative MRR growth insight
        $mrrInsight = collect($insights)->firstWhere('type', 'negative');
        $this->assertNotNull($mrrInsight);
        $this->assertStringContainsString('5.5', $mrrInsight['text']);
        $this->assertStringContainsString('Rückgang', $mrrInsight['text']);
    }

    /** @test */
    public function it_detects_high_and_healthy_churn_rates_in_key_insights()
    {
        $tenant = Tenant::factory()->create();

        // Test high churn rate (> 5%)
        $reportData = [
            'date' => '2024-10-15',
            'mrr' => ['total' => 5000, 'growth_rate_3m' => 5],
            'churn' => ['monthly_rate' => 7.5], // > 5%
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50, 'trial_conversion' => 65],
        ];

        $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData);
        $content = $mail->content();
        $insights = $content->with['keyInsights'];

        $churnInsight = collect($insights)->firstWhere(function ($insight) {
            return str_contains($insight['text'], '7.5');
        });
        $this->assertNotNull($churnInsight);
        $this->assertEquals('warning', $churnInsight['type']);

        // Test healthy churn rate (<= 5%)
        $reportData['churn']['monthly_rate'] = 3.2;
        $mail2 = new SubscriptionAnalyticsReportMail($tenant, $reportData);
        $content2 = $mail2->content();
        $insights2 = $content2->with['keyInsights'];

        $churnInsight2 = collect($insights2)->firstWhere(function ($insight) {
            return str_contains($insight['text'], '3.2');
        });
        $this->assertNotNull($churnInsight2);
        $this->assertEquals('positive', $churnInsight2['type']);
        $this->assertStringContainsString('Gesunde', $churnInsight2['text']);
    }
}
