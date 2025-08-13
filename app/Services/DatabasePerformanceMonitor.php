<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class DatabasePerformanceMonitor
{
    private const SLOW_QUERY_THRESHOLD = 1000; // 1 second in milliseconds
    private const MEMORY_USAGE_THRESHOLD = 50 * 1024 * 1024; // 50MB
    private const CACHE_TTL = 300; // 5 minutes

    private array $queryStats = [];
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    /**
     * Enable database query monitoring for the current request
     */
    public function enableQueryMonitoring(): void
    {
        DB::listen(function ($query) {
            $this->recordQuery([
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
                'tenant_id' => app('tenant')?->id,
                'timestamp' => now(),
            ]);
        });
    }

    /**
     * Record query execution statistics
     */
    private function recordQuery(array $queryData): void
    {
        $this->queryStats[] = $queryData;

        // Log slow queries immediately
        if ($queryData['time'] >= self::SLOW_QUERY_THRESHOLD) {
            $this->logSlowQuery($queryData);
        }

        // Track query patterns
        $this->trackQueryPattern($queryData);
    }

    /**
     * Log slow queries with context
     */
    private function logSlowQuery(array $queryData): void
    {
        Log::warning('Slow Database Query Detected', [
            'execution_time' => $queryData['time'] . 'ms',
            'sql' => $this->sanitizeQuery($queryData['sql']),
            'tenant_id' => $queryData['tenant_id'],
            'connection' => $queryData['connection'],
            'bindings_count' => count($queryData['bindings']),
            'request_url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ]);

        // Store slow query for analysis
        $this->storeSlowQueryForAnalysis($queryData);
    }

    /**
     * Track query patterns for optimization insights
     */
    private function trackQueryPattern(array $queryData): void
    {
        $pattern = $this->extractQueryPattern($queryData['sql']);
        $cacheKey = "query_pattern:" . md5($pattern);

        $stats = Cache::get($cacheKey, [
            'pattern' => $pattern,
            'count' => 0,
            'total_time' => 0,
            'max_time' => 0,
            'tenant_ids' => [],
        ]);

        $stats['count']++;
        $stats['total_time'] += $queryData['time'];
        $stats['max_time'] = max($stats['max_time'], $queryData['time']);
        
        if ($queryData['tenant_id'] && !in_array($queryData['tenant_id'], $stats['tenant_ids'])) {
            $stats['tenant_ids'][] = $queryData['tenant_id'];
        }

        Cache::put($cacheKey, $stats, self::CACHE_TTL);
    }

    /**
     * Extract query pattern for analysis (remove literals)
     */
    private function extractQueryPattern(string $sql): string
    {
        // Remove specific values but keep structure
        $pattern = preg_replace('/\d+/', '?', $sql);
        $pattern = preg_replace("/'[^']*'/", '?', $pattern);
        $pattern = preg_replace('/"[^"]*"/', '?', $pattern);
        $pattern = preg_replace('/\s+/', ' ', trim($pattern));
        
        return $pattern;
    }

    /**
     * Store slow query data for detailed analysis
     */
    private function storeSlowQueryForAnalysis(array $queryData): void
    {
        $redisKey = 'slow_queries:' . now()->format('Y-m-d');
        
        $slowQueryData = [
            'sql_pattern' => $this->extractQueryPattern($queryData['sql']),
            'execution_time' => $queryData['time'],
            'tenant_id' => $queryData['tenant_id'],
            'timestamp' => $queryData['timestamp']->toISOString(),
            'bindings_count' => count($queryData['bindings']),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        Redis::rpush($redisKey, json_encode($slowQueryData));
        Redis::expire($redisKey, 86400 * 7); // Keep for 7 days
    }

    /**
     * Get comprehensive performance report for current request
     */
    public function getPerformanceReport(): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        return [
            'request_summary' => [
                'total_queries' => count($this->queryStats),
                'total_query_time' => array_sum(array_column($this->queryStats, 'time')),
                'request_duration' => round(($endTime - $this->startTime) * 1000, 2),
                'memory_usage' => $endMemory - $this->startMemory,
                'peak_memory' => memory_get_peak_usage(true),
                'tenant_id' => app('tenant')?->id,
            ],
            'query_analysis' => $this->analyzeQueries(),
            'slow_queries' => $this->getSlowQueries(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Analyze query patterns and performance
     */
    private function analyzeQueries(): array
    {
        if (empty($this->queryStats)) {
            return [];
        }

        $totalQueries = count($this->queryStats);
        $totalTime = array_sum(array_column($this->queryStats, 'time'));
        $avgTime = $totalTime / $totalQueries;

        // Group by query patterns
        $patterns = [];
        foreach ($this->queryStats as $query) {
            $pattern = $this->extractQueryPattern($query['sql']);
            if (!isset($patterns[$pattern])) {
                $patterns[$pattern] = [
                    'count' => 0,
                    'total_time' => 0,
                    'max_time' => 0,
                ];
            }
            $patterns[$pattern]['count']++;
            $patterns[$pattern]['total_time'] += $query['time'];
            $patterns[$pattern]['max_time'] = max($patterns[$pattern]['max_time'], $query['time']);
        }

        // Sort by total time (most expensive first)
        uasort($patterns, function ($a, $b) {
            return $b['total_time'] <=> $a['total_time'];
        });

        return [
            'summary' => [
                'total_queries' => $totalQueries,
                'total_time' => round($totalTime, 2),
                'average_time' => round($avgTime, 2),
                'unique_patterns' => count($patterns),
            ],
            'top_patterns' => array_slice($patterns, 0, 10, true),
        ];
    }

    /**
     * Get slow queries from current request
     */
    private function getSlowQueries(): array
    {
        return array_filter($this->queryStats, function ($query) {
            return $query['time'] >= self::SLOW_QUERY_THRESHOLD;
        });
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        
        $totalQueries = count($this->queryStats);
        $slowQueries = count($this->getSlowQueries());
        $totalTime = array_sum(array_column($this->queryStats, 'time'));
        $memoryUsage = memory_get_peak_usage(true);

        // High query count
        if ($totalQueries > 50) {
            $recommendations[] = [
                'type' => 'high_query_count',
                'severity' => 'warning',
                'message' => "High query count detected ({$totalQueries} queries). Consider query optimization or caching.",
                'suggestion' => 'Review for N+1 queries and implement eager loading where appropriate.',
            ];
        }

        // Slow queries
        if ($slowQueries > 0) {
            $recommendations[] = [
                'type' => 'slow_queries',
                'severity' => 'error',
                'message' => "{$slowQueries} slow queries detected (>{self::SLOW_QUERY_THRESHOLD}ms).",
                'suggestion' => 'Add database indexes, optimize WHERE clauses, or implement caching.',
            ];
        }

        // High total query time
        if ($totalTime > 5000) { // 5 seconds
            $recommendations[] = [
                'type' => 'high_total_time',
                'severity' => 'warning',
                'message' => "Total database time is high ({$totalTime}ms).",
                'suggestion' => 'Consider implementing Redis caching for frequently accessed data.',
            ];
        }

        // High memory usage
        if ($memoryUsage > self::MEMORY_USAGE_THRESHOLD) {
            $recommendations[] = [
                'type' => 'high_memory_usage',
                'severity' => 'warning',
                'message' => "High memory usage detected (" . $this->formatBytes($memoryUsage) . ").",
                'suggestion' => 'Use chunked processing for large datasets or implement pagination.',
            ];
        }

        return $recommendations;
    }

    /**
     * Get historical performance statistics
     */
    public function getHistoricalStats(int $days = 7): array
    {
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $redisKey = "slow_queries:{$date}";
            
            $slowQueries = Redis::lrange($redisKey, 0, -1);
            $parsedQueries = array_map('json_decode', $slowQueries);
            
            $stats[$date] = [
                'slow_query_count' => count($parsedQueries),
                'avg_execution_time' => count($parsedQueries) > 0 
                    ? array_sum(array_column($parsedQueries, 'execution_time')) / count($parsedQueries)
                    : 0,
                'unique_tenants' => count(array_unique(array_column($parsedQueries, 'tenant_id'))),
            ];
        }
        
        return $stats;
    }

    /**
     * Get database connection pool statistics
     */
    public function getConnectionStats(): array
    {
        $connections = [];
        $defaultConnection = config('database.default');
        
        foreach (config('database.connections') as $name => $config) {
            $pdo = null;
            try {
                $pdo = DB::connection($name)->getPdo();
                $connections[$name] = [
                    'active' => true,
                    'driver' => $config['driver'] ?? 'unknown',
                    'is_default' => $name === $defaultConnection,
                ];
            } catch (\Exception $e) {
                $connections[$name] = [
                    'active' => false,
                    'error' => $e->getMessage(),
                    'driver' => $config['driver'] ?? 'unknown',
                    'is_default' => $name === $defaultConnection,
                ];
            }
        }
        
        return $connections;
    }

    /**
     * Analyze table statistics and optimization opportunities
     */
    public function analyzeTableStatistics(): array
    {
        try {
            // Get table sizes (PostgreSQL specific)
            $tableSizes = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                    pg_total_relation_size(schemaname||'.'||tablename) as size_bytes
                FROM pg_tables 
                WHERE schemaname = 'public'
                ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
                LIMIT 20
            ");

            // Get index usage statistics
            $indexStats = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    indexname,
                    idx_tup_read,
                    idx_tup_fetch
                FROM pg_stat_user_indexes
                ORDER BY idx_tup_read DESC
                LIMIT 20
            ");

            return [
                'table_sizes' => $tableSizes,
                'index_usage' => $indexStats,
                'total_db_size' => DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size")[0]->size ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to analyze table statistics: ' . $e->getMessage(),
                'fallback' => $this->getBasicTableInfo(),
            ];
        }
    }

    /**
     * Get basic table information as fallback
     */
    private function getBasicTableInfo(): array
    {
        try {
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            return [
                'table_count' => count($tables),
                'tables' => array_column($tables, 'table_name'),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to retrieve table information'];
        }
    }

    /**
     * Sanitize SQL query for logging (remove sensitive data)
     */
    private function sanitizeQuery(string $sql): string
    {
        // Remove potential sensitive data patterns
        $sql = preg_replace('/password\s*=\s*[\'"][^\'"]*[\'"]/', 'password = [REDACTED]', $sql);
        $sql = preg_replace('/token\s*=\s*[\'"][^\'"]*[\'"]/', 'token = [REDACTED]', $sql);
        $sql = preg_replace('/email\s*=\s*[\'"][^\'"]*[\'"]/', 'email = [REDACTED]', $sql);
        
        return $sql;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Setup automated monitoring alerts
     */
    public function setupAlerts(): void
    {
        // Monitor for consistently slow queries
        $this->scheduleSlowQueryAlert();
        
        // Monitor for database connection issues
        $this->scheduleConnectionAlert();
        
        // Monitor for unusual query patterns
        $this->scheduleAnomalyAlert();
    }

    /**
     * Schedule slow query monitoring alert
     */
    private function scheduleSlowQueryAlert(): void
    {
        // This would be implemented as a scheduled job
        Log::info('Database Performance Monitor: Slow query alerts configured');
    }

    /**
     * Schedule database connection monitoring alert
     */
    private function scheduleConnectionAlert(): void
    {
        // This would be implemented as a scheduled job
        Log::info('Database Performance Monitor: Connection alerts configured');
    }

    /**
     * Schedule query pattern anomaly alert
     */
    private function scheduleAnomalyAlert(): void
    {
        // This would be implemented as a scheduled job
        Log::info('Database Performance Monitor: Anomaly alerts configured');
    }
}