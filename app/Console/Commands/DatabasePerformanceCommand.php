<?php

namespace App\Console\Commands;

use App\Services\QueryOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DatabasePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:performance
                           {action : The action to perform (monitor, optimize, cache, analyze)}
                           {--clear-cache : Clear performance cache}
                           {--warmup : Warm up cache with common queries}
                           {--analyze-slow : Analyze slow queries}
                           {--show-indexes : Show index usage}
                           {--table= : Specific table to analyze}
                           {--limit=10 : Limit results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database performance monitoring and optimization';

    private QueryOptimizationService $queryOptimizer;

    /**
     * Create a new command instance.
     */
    public function __construct(QueryOptimizationService $queryOptimizer)
    {
        parent::__construct();
        $this->queryOptimizer = $queryOptimizer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'monitor' => $this->monitorPerformance(),
            'optimize' => $this->optimizeDatabase(),
            'cache' => $this->manageCaching(),
            'analyze' => $this->analyzeQueries(),
            default => $this->showHelp()
        };
    }

    /**
     * Monitor database performance
     */
    private function monitorPerformance(): int
    {
        $this->info("🔍 Database Performance Monitoring");
        $this->newLine();

        try {
            $metrics = $this->queryOptimizer->getDatabasePerformanceMetrics();

            // Show table sizes
            $this->info("📊 Top Tables by Size:");
            if (!empty($metrics['table_sizes'])) {
                $tableSizeData = [];
                foreach ($metrics['table_sizes'] as $table) {
                    $tableSizeData[] = [
                        $table->table_name,
                        number_format($table->size_mb, 2) . ' MB',
                        number_format($table->table_rows)
                    ];
                }
                $this->table(['Table', 'Size', 'Rows'], $tableSizeData);
            } else {
                $this->warn("No table size data available");
            }

            // Show slow queries if available
            $this->newLine();
            $this->info("🐌 Recent Slow Queries:");
            if (!empty($metrics['slow_queries'])) {
                $slowQueryData = [];
                foreach ($metrics['slow_queries'] as $query) {
                    $slowQueryData[] = [
                        number_format($query->query_time, 3) . 's',
                        number_format($query->rows_examined),
                        substr($query->query_preview, 0, 50) . '...'
                    ];
                }
                $this->table(['Time', 'Rows Examined', 'Query Preview'], $slowQueryData);
            } else {
                $this->info("✅ No slow queries detected");
            }

            // Cache performance
            $this->newLine();
            $this->info("💾 Cache Performance:");
            $cacheHitRate = $metrics['cache_hit_rate'] ?? 0;
            $this->line("Hit Rate: " . ($cacheHitRate > 0 ? $cacheHitRate . '%' : 'Not available'));

            if ($cacheHitRate > 90) {
                $this->info("✅ Excellent cache performance");
            } elseif ($cacheHitRate > 70) {
                $this->comment("⚠️ Good cache performance, room for improvement");
            } else {
                $this->warn("❌ Poor cache performance - consider warming up cache");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error monitoring performance: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Optimize database operations
     */
    private function optimizeDatabase(): int
    {
        $this->info("🚀 Database Optimization");
        $this->newLine();

        if ($this->option('clear-cache')) {
            $this->optimizeCaching();
        }

        if ($this->option('analyze-slow')) {
            $this->analyzeSlowQueries();
        }

        if ($this->option('show-indexes')) {
            $this->showIndexUsage();
        }

        // Run ANALYZE TABLE on main tables
        $this->info("📈 Analyzing table statistics...");
        $mainTables = ['game_actions', 'games', 'players', 'teams', 'ml_models'];
        
        $progressBar = $this->output->createProgressBar(count($mainTables));
        $progressBar->start();

        foreach ($mainTables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to analyze table {$table}: " . $e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Optimize tables
        $this->info("🔧 Optimizing tables...");
        $optimizedTables = 0;

        foreach ($mainTables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $optimizedTables++;
            } catch (\Exception $e) {
                $this->warn("Failed to optimize table {$table}: " . $e->getMessage());
            }
        }

        $this->info("✅ Optimized {$optimizedTables} tables");

        return Command::SUCCESS;
    }

    /**
     * Manage caching
     */
    private function manageCaching(): int
    {
        $this->info("💾 Cache Management");
        $this->newLine();

        if ($this->option('clear-cache')) {
            $this->info("Clearing performance cache...");
            
            $patterns = [
                'player_stats_*',
                'team_trends_*',
                'shot_chart_*',
                'live_game_stats_*',
                'h2h_stats_*',
                'top_performers_*'
            ];

            $totalCleared = 0;
            foreach ($patterns as $pattern) {
                try {
                    $cleared = $this->queryOptimizer->clearCachePattern($pattern);
                    $totalCleared += $cleared;
                    $this->line("Cleared {$cleared} keys matching: {$pattern}");
                } catch (\Exception $e) {
                    $this->warn("Failed to clear pattern {$pattern}: " . $e->getMessage());
                }
            }

            $this->info("✅ Total cache keys cleared: {$totalCleared}");
        }

        if ($this->option('warmup')) {
            $this->info("🔥 Warming up cache...");
            
            try {
                $warmedUp = $this->queryOptimizer->warmUpCache();
                
                $this->table(['Cache Type', 'Items Cached'], [
                    ['Top Scorers', $warmedUp['top_scorers'] ?? 0],
                    ['Recent Games', $warmedUp['recent_games'] ?? 0],
                    ['Player Stats', $warmedUp['player_stats'] ?? 0]
                ]);

                $this->info("✅ Cache warmup completed");
            } catch (\Exception $e) {
                $this->error("Cache warmup failed: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Analyze queries and performance
     */
    private function analyzeQueries(): int
    {
        $this->info("🔬 Query Analysis");
        $this->newLine();

        // Table analysis
        if ($table = $this->option('table')) {
            $this->analyzeSpecificTable($table);
        } else {
            $this->analyzeAllTables();
        }

        // Query performance recommendations
        $this->newLine();
        $this->info("💡 Performance Recommendations:");
        $this->showPerformanceRecommendations();

        return Command::SUCCESS;
    }

    /**
     * Show help information
     */
    private function showHelp(): int
    {
        $this->error("Invalid action specified");
        $this->newLine();
        
        $this->info("Available actions:");
        $this->line("  monitor  - Monitor database performance metrics");
        $this->line("  optimize - Optimize database tables and indexes");
        $this->line("  cache    - Manage query caching");
        $this->line("  analyze  - Analyze query performance");
        
        $this->newLine();
        $this->info("Options:");
        $this->line("  --clear-cache   Clear performance cache");
        $this->line("  --warmup        Warm up cache with common queries");
        $this->line("  --analyze-slow  Analyze slow queries");
        $this->line("  --show-indexes  Show index usage statistics");
        $this->line("  --table=name    Analyze specific table");

        return Command::FAILURE;
    }

    /**
     * Optimize caching operations
     */
    private function optimizeCaching(): void
    {
        $this->info("Clearing and optimizing cache...");
        
        // Clear old cache entries
        $cleared = $this->queryOptimizer->clearCachePattern('*_stats_*');
        $this->line("Cleared {$cleared} statistics cache entries");
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            $this->line("Garbage collected: {$collected} cycles");
        }
    }

    /**
     * Analyze slow queries
     */
    private function analyzeSlowQueries(): void
    {
        $this->info("Analyzing slow queries...");
        
        try {
            // Get slow queries from performance schema
            $slowQueries = DB::select("
                SELECT 
                    ROUND(AVG_TIMER_WAIT/1000000000000, 6) as avg_time_sec,
                    COUNT_STAR as exec_count,
                    ROUND(SUM_ROWS_EXAMINED/COUNT_STAR) as avg_rows_examined,
                    LEFT(DIGEST_TEXT, 100) as query_sample
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE AVG_TIMER_WAIT > 1000000000
                ORDER BY AVG_TIMER_WAIT DESC 
                LIMIT ?
            ", [$this->option('limit')]);

            if (!empty($slowQueries)) {
                $slowQueryData = [];
                foreach ($slowQueries as $query) {
                    $slowQueryData[] = [
                        number_format($query->avg_time_sec, 3) . 's',
                        number_format($query->exec_count),
                        number_format($query->avg_rows_examined),
                        substr($query->query_sample, 0, 60) . '...'
                    ];
                }
                
                $this->table(['Avg Time', 'Executions', 'Avg Rows', 'Query Sample'], $slowQueryData);
            } else {
                $this->info("✅ No slow queries detected");
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not analyze slow queries: " . $e->getMessage());
        }
    }

    /**
     * Show index usage
     */
    private function showIndexUsage(): void
    {
        $this->info("Index usage statistics:");
        
        try {
            $indexStats = DB::select("
                SELECT 
                    OBJECT_NAME as table_name,
                    INDEX_NAME,
                    COUNT_FETCH as reads,
                    COUNT_INSERT as inserts,
                    COUNT_UPDATE as updates,
                    COUNT_DELETE as deletes
                FROM performance_schema.table_io_waits_summary_by_index_usage
                WHERE OBJECT_SCHEMA = DATABASE()
                AND INDEX_NAME IS NOT NULL
                ORDER BY COUNT_FETCH DESC
                LIMIT ?
            ", [$this->option('limit')]);

            if (!empty($indexStats)) {
                $indexData = [];
                foreach ($indexStats as $stat) {
                    $indexData[] = [
                        $stat->table_name,
                        $stat->INDEX_NAME,
                        number_format($stat->reads),
                        number_format($stat->inserts),
                        number_format($stat->updates),
                        number_format($stat->deletes)
                    ];
                }
                
                $this->table(['Table', 'Index', 'Reads', 'Inserts', 'Updates', 'Deletes'], $indexData);
            } else {
                $this->info("No index usage data available");
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not get index usage: " . $e->getMessage());
        }
    }

    /**
     * Analyze specific table
     */
    private function analyzeSpecificTable(string $table): void
    {
        $this->info("Analyzing table: {$table}");
        
        try {
            // Table stats
            $tableStats = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_mb,
                    table_rows,
                    avg_row_length
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);

            if (!empty($tableStats)) {
                $stat = $tableStats[0];
                $this->table(['Metric', 'Value'], [
                    ['Total Size', $stat->size_mb . ' MB'],
                    ['Data Size', $stat->data_mb . ' MB'],
                    ['Index Size', $stat->index_mb . ' MB'],
                    ['Rows', number_format($stat->table_rows)],
                    ['Avg Row Length', $stat->avg_row_length . ' bytes']
                ]);
            }

            // Show indexes for this table
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            if (!empty($indexes)) {
                $this->newLine();
                $this->info("Indexes on {$table}:");
                
                $indexData = [];
                foreach ($indexes as $index) {
                    $indexData[] = [
                        $index->Key_name,
                        $index->Column_name,
                        $index->Index_type,
                        $index->Cardinality ?: 'N/A'
                    ];
                }
                
                $this->table(['Index Name', 'Column', 'Type', 'Cardinality'], $indexData);
            }

        } catch (\Exception $e) {
            $this->error("Error analyzing table {$table}: " . $e->getMessage());
        }
    }

    /**
     * Analyze all main tables
     */
    private function analyzeAllTables(): void
    {
        $mainTables = ['game_actions', 'games', 'players', 'teams', 'ml_models', 'api_usage_tracking'];
        
        $this->info("Analyzing main tables:");
        
        try {
            $tableStats = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows,
                    ROUND((index_length / data_length) * 100, 2) as index_ratio
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name IN ('" . implode("','", $mainTables) . "')
                ORDER BY (data_length + index_length) DESC
            ");

            $tableData = [];
            foreach ($tableStats as $stat) {
                $tableData[] = [
                    $stat->table_name,
                    $stat->size_mb . ' MB',
                    number_format($stat->table_rows),
                    ($stat->index_ratio ?: 0) . '%'
                ];
            }
            
            $this->table(['Table', 'Size', 'Rows', 'Index Ratio'], $tableData);
            
        } catch (\Exception $e) {
            $this->error("Error analyzing tables: " . $e->getMessage());
        }
    }

    /**
     * Show performance recommendations
     */
    private function showPerformanceRecommendations(): void
    {
        $recommendations = [
            "🔍 Monitor slow queries regularly and optimize frequently used queries",
            "📊 Keep table statistics up to date with ANALYZE TABLE",
            "💾 Use Redis caching for frequently accessed data",
            "📈 Consider partitioning large tables (game_actions) by date",
            "🔗 Ensure proper indexing on foreign keys and frequently queried columns",
            "🧹 Regular maintenance: OPTIMIZE TABLE for InnoDB tables",
            "📉 Monitor index usage and remove unused indexes",
            "⚡ Use materialized views for complex aggregations",
            "🔄 Implement query result caching for expensive operations",
            "📊 Consider read replicas for analytics queries"
        ];

        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }
        
        $this->newLine();
        $this->info("💡 Run this command with specific options for targeted optimizations:");
        $this->line("php artisan db:performance optimize --clear-cache --warmup");
    }
}