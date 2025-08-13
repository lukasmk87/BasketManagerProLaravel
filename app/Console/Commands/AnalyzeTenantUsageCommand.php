<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Services\FeatureGateService;
use Illuminate\Console\Command;

class AnalyzeTenantUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:analyze-usage 
                            {--tenant= : Specific tenant ID to analyze}
                            {--metric= : Specific metric to analyze}
                            {--days=30 : Number of days to analyze}
                            {--recommend : Show upgrade recommendations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze tenant usage patterns and provide insights';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Analyzing tenant usage patterns...');

        $tenantId = $this->option('tenant');
        $metric = $this->option('metric');
        $days = (int) $this->option('days');
        $recommend = $this->option('recommend');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
            if ($tenants->isEmpty()) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return self::FAILURE;
            }
        } else {
            $tenants = Tenant::where('is_active', true)->get();
        }

        foreach ($tenants as $tenant) {
            $this->analyzeTenant($tenant, $metric, $days, $recommend);
        }

        return self::SUCCESS;
    }

    private function analyzeTenant(Tenant $tenant, ?string $metric, int $days, bool $recommend): void
    {
        $this->info("\n=== {$tenant->name} ({$tenant->subscription_tier}) ===");

        $featureGate = new FeatureGateService();
        $featureGate->setTenant($tenant);

        // Get current usage
        $currentUsage = $featureGate->getAllUsage();
        
        if (empty($currentUsage)) {
            $this->warn("No usage data available for this tenant.");
            return;
        }

        // Display usage table
        $headers = ['Metric', 'Current', 'Limit', 'Usage %', 'Status'];
        $rows = [];

        foreach ($currentUsage as $metricName => $data) {
            if ($metric && $metricName !== $metric) {
                continue;
            }

            $status = $this->getUsageStatus($data['percentage'], $data['unlimited']);
            
            $rows[] = [
                $metricName,
                number_format($data['current']),
                $data['unlimited'] ? 'Unlimited' : number_format($data['limit']),
                $data['unlimited'] ? 'N/A' : number_format($data['percentage'], 1) . '%',
                $status,
            ];
        }

        $this->table($headers, $rows);

        // Historical trends
        $this->analyzeHistoricalTrends($tenant, $metric, $days);

        // Recommendations
        if ($recommend) {
            $this->provideRecommendations($tenant, $featureGate, $currentUsage);
        }
    }

    private function getUsageStatus(float $percentage, bool $unlimited): string
    {
        if ($unlimited) {
            return '<info>Unlimited</info>';
        }

        if ($percentage > 100) {
            return '<error>Over Limit</error>';
        } elseif ($percentage > 90) {
            return '<comment>Critical</comment>';
        } elseif ($percentage > 75) {
            return '<comment>High</comment>';
        } elseif ($percentage > 50) {
            return '<info>Moderate</info>';
        } else {
            return '<info>Low</info>';
        }
    }

    private function analyzeHistoricalTrends(Tenant $tenant, ?string $metric, int $days): void
    {
        $this->line("\n📊 Historical Trends (Last {$days} days):");

        $query = TenantUsage::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays($days));

        if ($metric) {
            $query->where('metric', $metric);
        }

        $trends = $query->selectRaw('
                metric,
                AVG(usage_count) as avg_daily,
                MAX(usage_count) as peak_daily,
                COUNT(*) as days_active
            ')
            ->groupBy('metric')
            ->get();

        if ($trends->isEmpty()) {
            $this->warn("No historical data available.");
            return;
        }

        $trendHeaders = ['Metric', 'Avg/Day', 'Peak/Day', 'Active Days'];
        $trendRows = [];

        foreach ($trends as $trend) {
            $trendRows[] = [
                $trend->metric,
                number_format($trend->avg_daily, 1),
                number_format($trend->peak_daily),
                $trend->days_active . '/' . $days,
            ];
        }

        $this->table($trendHeaders, $trendRows);
    }

    private function provideRecommendations(Tenant $tenant, FeatureGateService $featureGate, array $currentUsage): void
    {
        $this->line("\n💡 Recommendations:");

        // Check for upgrade recommendations
        $recommendedTier = $featureGate->getRecommendedUpgrade();
        
        if ($recommendedTier) {
            $this->warn("⬆️  Consider upgrading to {$recommendedTier} tier due to high usage.");
        } else {
            $this->info("✅ Current subscription tier appears adequate for usage patterns.");
        }

        // Check for specific issues
        $criticalMetrics = array_filter($currentUsage, function ($data) {
            return !$data['unlimited'] && $data['percentage'] > 90;
        });

        if (!empty($criticalMetrics)) {
            $this->warn("⚠️  Critical usage detected:");
            foreach ($criticalMetrics as $metric => $data) {
                $this->line("   • {$metric}: {$data['percentage']}% used");
            }
        }

        // Feature utilization
        $tierConfig = config("tenants.tiers.{$tenant->subscription_tier}");
        if ($tierConfig) {
            $availableFeatures = $tierConfig['features'] ?? [];
            $this->info("🎯 Available features (" . count($availableFeatures) . " total)");
            
            // Show most valuable features they're not using heavily
            $underutilized = [];
            foreach (['advanced_analytics', 'video_analysis', 'tournament_management'] as $feature) {
                if (in_array($feature, $availableFeatures)) {
                    $usage = $currentUsage[$feature . '_usage'] ?? null;
                    if (!$usage || $usage['current'] < 10) {
                        $underutilized[] = $feature;
                    }
                }
            }

            if (!empty($underutilized)) {
                $this->info("📈 Consider exploring: " . implode(', ', $underutilized));
            }
        }

        // Trial status
        if ($featureGate->isInTrial()) {
            $daysLeft = $featureGate->trialDaysRemaining();
            $this->warn("⏰ Trial expires in {$daysLeft} days. Consider subscribing to continue access.");
        }
    }
}
