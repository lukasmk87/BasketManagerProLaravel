<?php

namespace App\Console\Commands;

use App\Services\DatabasePerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDatabasePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze-performance 
                          {--days=7 : Number of days to analyze}
                          {--export= : Export results to file}
                          {--tenant= : Analyze specific tenant}
                          {--recommendations : Show optimization recommendations}
                          {--tables : Include table statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and provide optimization recommendations';

    private DatabasePerformanceMonitor $monitor;

    public function __construct(DatabasePerformanceMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Analyzing Database Performance...');
        $this->newLine();

        $days = $this->option('days');
        $tenantId = $this->option('tenant');
        $showRecommendations = $this->option('recommendations');
        $includeTables = $this->option('tables');
        $exportFile = $this->option('export');

        // Get historical statistics
        $this->displayHistoricalStats($days, $tenantId);
        
        // Show connection statistics
        $this->displayConnectionStats();
        
        // Show table statistics if requested
        if ($includeTables) {
            $this->displayTableStatistics();
        }
        
        // Show recommendations if requested
        if ($showRecommendations) {
            $this->displayRecommendations($days);
        }
        
        // Export results if requested
        if ($exportFile) {
            $this->exportResults($exportFile, $days, $tenantId);
        }

        $this->newLine();
        $this->info('✅ Database performance analysis complete!');
        
        return Command::SUCCESS;
    }

    /**
     * Display historical performance statistics
     */
    private function displayHistoricalStats(int $days, ?string $tenantId): void
    {
        $this->info("📊 Historical Performance Statistics (Last {$days} days)");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $stats = $this->monitor->getHistoricalStats($days);
        
        if (empty($stats)) {
            $this->warn('No historical data available.');
            return;
        }

        $headers = ['Date', 'Slow Queries', 'Avg Execution Time', 'Unique Tenants'];
        $rows = [];

        foreach ($stats as $date => $data) {
            $rows[] = [
                $date,
                $data['slow_query_count'],
                round($data['avg_execution_time'], 2) . 'ms',
                $data['unique_tenants'],
            ];
        }

        $this->table($headers, $rows);
        
        // Calculate trends
        $this->displayTrends($stats);
    }

    /**
     * Display performance trends
     */
    private function displayTrends(array $stats): void
    {
        $this->newLine();
        $this->info('📈 Performance Trends');
        
        $dates = array_keys($stats);
        $slowQueries = array_column($stats, 'slow_query_count');
        $avgTimes = array_column($stats, 'avg_execution_time');
        
        // Calculate trends (simple linear comparison)
        $recentSlowQueries = array_slice($slowQueries, 0, 3);
        $olderSlowQueries = array_slice($slowQueries, -3);
        
        $slowQueryTrend = array_sum($recentSlowQueries) - array_sum($olderSlowQueries);
        
        if ($slowQueryTrend > 0) {
            $this->error("↗ Slow queries increasing by {$slowQueryTrend} over the period");
        } elseif ($slowQueryTrend < 0) {
            $this->info("↘ Slow queries decreasing by " . abs($slowQueryTrend) . " over the period");
        } else {
            $this->comment("→ Slow queries remain stable");
        }
    }

    /**
     * Display database connection statistics
     */
    private function displayConnectionStats(): void
    {
        $this->newLine();
        $this->info('🔌 Database Connection Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $connections = $this->monitor->getConnectionStats();
        
        $headers = ['Connection', 'Status', 'Driver', 'Default'];
        $rows = [];

        foreach ($connections as $name => $info) {
            $status = $info['active'] ? '✅ Active' : '❌ Inactive';
            $default = $info['is_default'] ? '✓' : '';
            
            $rows[] = [
                $name,
                $status,
                $info['driver'] ?? 'Unknown',
                $default,
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Display table statistics and analysis
     */
    private function displayTableStatistics(): void
    {
        $this->newLine();
        $this->info('🗃️ Table Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $tableStats = $this->monitor->analyzeTableStatistics();
            
            if (isset($tableStats['error'])) {
                $this->warn('Unable to retrieve table statistics: ' . $tableStats['error']);
                return;
            }
            
            $this->comment("Total Database Size: {$tableStats['total_db_size']}");
            $this->newLine();
            
            // Display largest tables
            if (!empty($tableStats['table_sizes'])) {
                $this->info('📋 Largest Tables');
                $headers = ['Schema', 'Table', 'Size'];
                $rows = [];
                
                foreach (array_slice($tableStats['table_sizes'], 0, 10) as $table) {
                    $rows[] = [
                        $table->schemaname ?? 'public',
                        $table->tablename,
                        $table->size ?? 'Unknown',
                    ];
                }
                
                $this->table($headers, $rows);
            }
            
            // Display index usage
            if (!empty($tableStats['index_usage'])) {
                $this->newLine();
                $this->info('📊 Index Usage Statistics');
                $headers = ['Schema', 'Table', 'Index', 'Reads', 'Fetches'];
                $rows = [];
                
                foreach (array_slice($tableStats['index_usage'], 0, 10) as $index) {
                    $rows[] = [
                        $index->schemaname ?? 'public',
                        $index->tablename,
                        $index->indexname,
                        number_format($index->idx_tup_read ?? 0),
                        number_format($index->idx_tup_fetch ?? 0),
                    ];
                }
                
                $this->table($headers, $rows);
            }
            
        } catch (\Exception $e) {
            $this->error('Failed to analyze table statistics: ' . $e->getMessage());
        }
    }

    /**
     * Display optimization recommendations
     */
    private function displayRecommendations(int $days): void
    {
        $this->newLine();
        $this->info('💡 Optimization Recommendations');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $recommendations = $this->generateOptimizationRecommendations($days);
        
        if (empty($recommendations)) {
            $this->info('🎉 No specific optimizations needed at this time.');
            return;
        }

        foreach ($recommendations as $recommendation) {
            $icon = match($recommendation['severity']) {
                'error' => '🚨',
                'warning' => '⚠️',
                'info' => 'ℹ️',
                default => '💡'
            };
            
            $this->line("{$icon} {$recommendation['title']}");
            $this->comment("   {$recommendation['description']}");
            $this->comment("   Action: {$recommendation['action']}");
            $this->newLine();
        }
    }

    /**
     * Generate optimization recommendations based on historical data
     */
    private function generateOptimizationRecommendations(int $days): array
    {
        $recommendations = [];
        $stats = $this->monitor->getHistoricalStats($days);
        
        if (empty($stats)) {
            return $recommendations;
        }

        $totalSlowQueries = array_sum(array_column($stats, 'slow_query_count'));
        $avgSlowQueries = $totalSlowQueries / count($stats);
        
        // High number of slow queries
        if ($avgSlowQueries > 10) {
            $recommendations[] = [
                'severity' => 'error',
                'title' => 'High number of slow queries detected',
                'description' => "Average of {$avgSlowQueries} slow queries per day over {$days} days.",
                'action' => 'Review slow query patterns and add appropriate database indexes.',
            ];
        }

        // Increasing trend in slow queries
        $recentAvg = array_sum(array_slice(array_column($stats, 'slow_query_count'), 0, 3)) / 3;
        $olderAvg = array_sum(array_slice(array_column($stats, 'slow_query_count'), -3)) / 3;
        
        if ($recentAvg > $olderAvg * 1.5) {
            $recommendations[] = [
                'severity' => 'warning',
                'title' => 'Performance degradation trend detected',
                'description' => 'Slow queries have increased significantly in recent days.',
                'action' => 'Investigate recent code changes and database growth patterns.',
            ];
        }

        // Database size recommendations
        try {
            $tableStats = $this->monitor->analyzeTableStatistics();
            if (isset($tableStats['table_sizes']) && !empty($tableStats['table_sizes'])) {
                $largestTable = $tableStats['table_sizes'][0];
                
                if ($largestTable->size_bytes > 1024 * 1024 * 1024) { // > 1GB
                    $recommendations[] = [
                        'severity' => 'warning',
                        'title' => 'Large table detected',
                        'description' => "Table '{$largestTable->tablename}' is {$largestTable->size}.",
                        'action' => 'Consider partitioning, archiving old data, or adding specific indexes.',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Skip table size recommendations if we can't get the data
        }

        return $recommendations;
    }

    /**
     * Export results to file
     */
    private function exportResults(string $filename, int $days, ?string $tenantId): void
    {
        $this->info("📄 Exporting results to {$filename}...");
        
        $data = [
            'export_date' => now()->toISOString(),
            'analysis_period_days' => $days,
            'tenant_filter' => $tenantId,
            'historical_stats' => $this->monitor->getHistoricalStats($days),
            'connection_stats' => $this->monitor->getConnectionStats(),
            'table_statistics' => $this->monitor->analyzeTableStatistics(),
            'recommendations' => $this->generateOptimizationRecommendations($days),
        ];
        
        $exported = file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        if ($exported) {
            $this->info("✅ Results exported successfully to {$filename}");
        } else {
            $this->error("❌ Failed to export results to {$filename}");
        }
    }
}