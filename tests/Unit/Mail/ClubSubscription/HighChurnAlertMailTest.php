<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\HighChurnAlertMail;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for HighChurnAlertMail.
 *
 * @see \App\Mail\ClubSubscription\HighChurnAlertMail
 */
class HighChurnAlertMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_includes_tenant_name_and_churn_rate_in_subject()
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Tenant', $envelope->subject);
        $this->assertStringContainsString('8.5', $envelope->subject);
        $this->assertStringContainsString('Hohe Churn-Rate', $envelope->subject);
        $this->assertStringContainsString('⚠️', $envelope->subject);
    }

    /** @test */
    public function it_includes_all_churn_metrics_in_content()
    {
        $tenant = Tenant::factory()->create();

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
            'revenue_impact' => 1500.00,
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.high-churn-alert', $content->markdown);
        $this->assertArrayHasKey('tenant', $content->with);
        $this->assertArrayHasKey('period', $content->with);
        $this->assertArrayHasKey('churnRate', $content->with);
        $this->assertArrayHasKey('customersStart', $content->with);
        $this->assertArrayHasKey('customersEnd', $content->with);
        $this->assertArrayHasKey('churnedCustomers', $content->with);
        $this->assertArrayHasKey('voluntaryChurn', $content->with);
        $this->assertArrayHasKey('involuntaryChurn', $content->with);
        $this->assertArrayHasKey('revenueImpact', $content->with);

        $this->assertEquals('2024-10', $content->with['period']);
        $this->assertEquals(8.5, $content->with['churnRate']);
        $this->assertEquals(100, $content->with['customersStart']);
        $this->assertEquals(1500.00, $content->with['revenueImpact']);
    }

    /** @test */
    public function it_includes_at_risk_clubs_and_churn_reasons_in_content()
    {
        $tenant = Tenant::factory()->create();

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
            'at_risk_clubs' => [
                ['name' => 'Club A', 'risk_score' => 85],
                ['name' => 'Club B', 'risk_score' => 72],
            ],
            'churn_reasons' => [
                'payment_failed' => ['count' => 3, 'percentage' => 37.5],
                'voluntary' => ['count' => 5, 'percentage' => 62.5],
            ],
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $content = $mail->content();

        $this->assertArrayHasKey('atRiskClubs', $content->with);
        $this->assertArrayHasKey('churnReasons', $content->with);
        $this->assertCount(2, $content->with['atRiskClubs']);
        $this->assertCount(2, $content->with['churnReasons']);
    }

    /** @test */
    public function it_generates_recommended_actions_for_high_involuntary_churn()
    {
        $tenant = Tenant::factory()->create();

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 2,
            'involuntary_churn' => 6, // Higher than voluntary
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $content = $mail->content();

        $this->assertArrayHasKey('recommendedActions', $content->with);
        $actions = $content->with['recommendedActions'];

        // Should include payment-related actions
        $this->assertContains('Zahlungsmethoden-Updates proaktiv anfordern', $actions);
        $this->assertContains('Dunning-Prozess überprüfen', $actions);
    }

    /** @test */
    public function it_generates_recommended_actions_for_very_high_churn_rate()
    {
        $tenant = Tenant::factory()->create();

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 12.0, // > 10%
            'customers_start' => 100,
            'customers_end' => 88,
            'churned_customers' => 12,
            'voluntary_churn' => 8,
            'involuntary_churn' => 4,
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $content = $mail->content();

        $actions = $content->with['recommendedActions'];

        // Should include survey and win-back actions
        $this->assertContains('Kundenbefragung zur Abwanderung durchführen', $actions);
        $this->assertContains('Win-back Kampagne starten', $actions);
    }

    /** @test */
    public function it_includes_priority_high_tag()
    {
        $tenant = Tenant::factory()->create(['id' => 789]);

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
        ];

        $mail = new HighChurnAlertMail($tenant, $churnData);

        $tags = $mail->tags();

        $this->assertCount(4, $tags);
        $this->assertContains('admin', $tags);
        $this->assertContains('churn-alert', $tags);
        $this->assertContains('priority:high', $tags);
        $this->assertContains('tenant:789', $tags);
    }
}
