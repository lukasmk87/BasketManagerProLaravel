<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Console\Commands\SubscriptionAnalyticsReportCommand;
use App\Services\Stripe\SubscriptionAnalyticsService;
use App\Models\Tenant;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

class SubscriptionAnalyticsReportCommandTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private $analyticsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'is_active' => true,
        ]);

        // Create some test data
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 100.00,
        ]);

        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $plan->id,
        ]);

        // Mock SubscriptionAnalyticsService
        $this->analyticsServiceMock = Mockery::mock(SubscriptionAnalyticsService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    /** @test */
    public function command_generates_report_for_single_tenant()
    {
        $this->mockAnalyticsService();

        $this->artisan('subscription:report', ['--tenant' => $this->tenant->id])
            ->assertExitCode(0)
            ->expectsOutput('🏀 Generating Subscription Analytics Report...');
    }

    /** @test */
    /** @test */
    public function command_generates_report_for_all_active_tenants()
    {
        // Create additional tenants
        Tenant::factory()->count(2)->create(['is_active' => true]);
        Tenant::factory()->create(['is_active' => false]); // Inactive, should be skipped

        $this->mockAnalyticsService();

        $this->artisan('subscription:report')
            ->assertExitCode(0)
            ->expectsOutput('🏀 Generating Subscription Analytics Report...');
    }

    /** @test */
    /** @test */
    public function command_output_format_table()
    {
        $this->mockAnalyticsService();

        $this->artisan('subscription:report', [
            '--tenant' => $this->tenant->id,
            '--format' => 'table',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('📊 MRR (Monthly Recurring Revenue)')
            ->expectsOutputToContain('📉 Churn Metrics')
            ->expectsOutputToContain('💰 LTV (Lifetime Value)')
            ->expectsOutputToContain('💚 Health Metrics');
    }

    /** @test */
    /** @test */
    public function command_output_format_json()
    {
        $this->mockAnalyticsService();

        $output = $this->artisan('subscription:report', [
            '--tenant' => $this->tenant->id,
            '--format' => 'json',
        ])
            ->assertExitCode(0)
            ->run();

        // JSON output should be parseable
        $this->assertNotEmpty($output);
    }

    /** @test */
    /** @test */
    public function command_output_format_csv()
    {
        $this->mockAnalyticsService();

        $this->artisan('subscription:report', [
            '--tenant' => $this->tenant->id,
            '--format' => 'csv',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Metric,Value')
            ->expectsOutputToContain('Tenant,' . $this->tenant->name);
    }

    /** @test */
    /** @test */
    public function command_handles_invalid_format_gracefully()
    {
        $this->artisan('subscription:report', [
            '--tenant' => $this->tenant->id,
            '--format' => 'invalid_format',
        ])
            ->assertExitCode(1)
            ->expectsOutput("Invalid format: invalid_format. Must be 'table', 'json', or 'csv'.");
    }

    /** @test */
    /** @test */
    public function command_handles_invalid_tenant_id()
    {
        $this->artisan('subscription:report', ['--tenant' => 99999])
            ->assertExitCode(1)
            ->expectsOutput('No tenants found to process.');
    }

    /** @test */
    /** @test */
    public function command_logs_errors_on_failure()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Analytics report generation failed', Mockery::any());

        // Mock service to throw exception
        $this->analyticsServiceMock->shouldReceive('calculateTenantMRR')
            ->andThrow(new \Exception('Test error'));

        $this->app->instance(SubscriptionAnalyticsService::class, $this->analyticsServiceMock);

        $this->artisan('subscription:report', ['--tenant' => $this->tenant->id])
            ->assertExitCode(1);
    }

    /**
     * Mock the SubscriptionAnalyticsService with realistic return values.
     */
    private function mockAnalyticsService(): void
    {
        // Mock MRR methods
        $this->analyticsServiceMock->shouldReceive('calculateTenantMRR')
            ->andReturn(1500.00);

        $this->analyticsServiceMock->shouldReceive('getMRRGrowthRate')
            ->andReturn(15.5);

        $this->analyticsServiceMock->shouldReceive('getMRRByPlan')
            ->andReturn([
                1 => [
                    'plan_name' => 'Basic',
                    'mrr' => 500.00,
                    'club_count' => 5,
                    'percentage' => 33.33,
                ],
                2 => [
                    'plan_name' => 'Pro',
                    'mrr' => 1000.00,
                    'club_count' => 5,
                    'percentage' => 66.67,
                ],
            ]);

        // Mock Churn methods
        $this->analyticsServiceMock->shouldReceive('calculateMonthlyChurnRate')
            ->andReturn([
                'period' => now()->format('Y-m'),
                'customers_start' => 100,
                'customers_end' => 95,
                'churned_customers' => 5,
                'churn_rate' => 5.0,
                'voluntary_churn' => 3,
                'involuntary_churn' => 2,
            ]);

        $this->analyticsServiceMock->shouldReceive('calculateRevenueChurn')
            ->andReturn(4.5);

        $this->analyticsServiceMock->shouldReceive('getChurnReasons')
            ->andReturn([
                'voluntary' => ['count' => 10, 'percentage' => 50.0],
                'payment_failed' => ['count' => 7, 'percentage' => 35.0],
                'trial_expired' => ['count' => 3, 'percentage' => 15.0],
            ]);

        // Mock LTV methods
        $this->analyticsServiceMock->shouldReceive('calculateAverageLTV')
            ->andReturn(2400.00);

        $this->analyticsServiceMock->shouldReceive('getLTVByPlan')
            ->andReturn([
                1 => [
                    'plan_name' => 'Basic',
                    'avg_ltv' => 1200.00,
                    'avg_duration_months' => 24.0,
                    'club_count' => 10,
                ],
            ]);

        $this->analyticsServiceMock->shouldReceive('getCustomerLifetimeStats')
            ->andReturn([
                'avg_subscription_duration_days' => 365,
                'median_subscription_duration_days' => 300,
                'avg_ltv' => 2400.00,
                'median_ltv' => 2000.00,
                'total_lifetime_revenue' => 120000.00,
                'total_active_clubs' => 50,
            ]);

        // Mock Health Metrics methods
        $this->analyticsServiceMock->shouldReceive('getActiveSubscriptionsCount')
            ->andReturn(95);

        $this->analyticsServiceMock->shouldReceive('getTrialConversionRate')
            ->andReturn(65.0);

        $this->analyticsServiceMock->shouldReceive('getAverageSubscriptionDuration')
            ->andReturn(365.0);

        $this->analyticsServiceMock->shouldReceive('getUpgradeDowngradeRates')
            ->andReturn([
                'upgrades' => 10,
                'downgrades' => 3,
                'upgrade_rate' => 10.53,
                'downgrade_rate' => 3.16,
                'net_change' => 7,
            ]);

        // Bind the mock to the service container
        $this->app->instance(SubscriptionAnalyticsService::class, $this->analyticsServiceMock);
    }
}
