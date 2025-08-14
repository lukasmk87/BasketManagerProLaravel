<?php

namespace App\Console\Commands;

use App\Services\EmergencyAccessService;
use App\Services\SecurityMonitoringService;
use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyIncident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EmergencyHealthCheckCommand extends Command
{
    protected $signature = 'emergency:health-check 
                           {--interval=30 : Health check interval in seconds}
                           {--alert-webhook= : Webhook URL for critical alerts}
                           {--verbose : Show detailed output}';

    protected $description = 'Comprehensive health check for emergency services';

    private EmergencyAccessService $emergencyService;
    private SecurityMonitoringService $securityService;
    private array $healthMetrics = [];
    private array $criticalIssues = [];
    private int $interval;

    public function __construct(
        EmergencyAccessService $emergencyService,
        SecurityMonitoringService $securityService
    ) {
        parent::__construct();
        $this->emergencyService = $emergencyService;
        $this->securityService = $securityService;
    }

    public function handle(): int
    {
        $this->interval = (int) $this->option('interval');
        
        $this->info("🚨 Starting Emergency Services Health Check");
        $this->info("Check interval: {$this->interval} seconds");
        
        if ($this->interval > 0) {
            $this->runContinuousHealthCheck();
        } else {
            $this->runSingleHealthCheck();
        }
        
        return 0;
    }

    private function runContinuousHealthCheck(): void
    {
        $this->info("Running continuous health monitoring...");
        
        while (true) {
            $this->runSingleHealthCheck();
            
            if ($this->interval > 0) {
                sleep($this->interval);
            } else {
                break;
            }
        }
    }

    private function runSingleHealthCheck(): void
    {
        $startTime = microtime(true);
        $this->healthMetrics = [];
        $this->criticalIssues = [];
        
        $this->newLine();
        $this->info("🔍 Running health checks at " . now()->format('Y-m-d H:i:s'));
        
        // Core health checks
        $this->checkDatabaseHealth();
        $this->checkRedisHealth();
        $this->checkEmergencyAccessSystem();
        $this->checkSecurityMonitoring();
        $this->checkEmergencyIncidents();
        $this->checkPerformanceMetrics();
        $this->checkSystemResources();
        $this->checkCacheHealth();
        $this->checkQueueHealth();
        
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        $this->healthMetrics['total_check_time_ms'] = $totalTime;
        
        // Generate health report
        $this->generateHealthReport();
        
        // Store metrics
        $this->storeHealthMetrics();
        
        // Send alerts if needed
        if (!empty($this->criticalIssues)) {
            $this->sendCriticalAlerts();
        }
        
        $this->info("✅ Health check completed in {$totalTime}ms");
    }

    private function checkDatabaseHealth(): void
    {
        $this->line("Checking database health...");
        
        try {
            $start = microtime(true);
            
            // Test basic connectivity
            DB::select('SELECT 1');
            $connectionTime = round((microtime(true) - $start) * 1000, 2);
            $this->healthMetrics['db_connection_time_ms'] = $connectionTime;
            
            // Check critical tables
            $tablesCheck = [
                'team_emergency_access' => TeamEmergencyAccess::count(),
                'emergency_incidents' => EmergencyIncident::count(),
                'emergency_contacts' => DB::table('emergency_contacts')->count()
            ];
            
            $this->healthMetrics['db_table_counts'] = $tablesCheck;
            
            // Check for long-running queries
            $longQueries = DB::select("
                SELECT count(*) as count 
                FROM pg_stat_activity 
                WHERE state = 'active' 
                AND query_start < now() - interval '30 seconds'
                AND query NOT LIKE '%pg_stat_activity%'
            ");
            
            $longQueryCount = $longQueries[0]->count ?? 0;
            $this->healthMetrics['db_long_queries'] = $longQueryCount;
            
            if ($longQueryCount > 5) {
                $this->criticalIssues[] = "High number of long-running queries: {$longQueryCount}";
                $this->error("⚠️  {$longQueryCount} long-running database queries detected");
            }
            
            if ($connectionTime > 1000) {
                $this->criticalIssues[] = "Slow database connection: {$connectionTime}ms";
                $this->error("⚠️  Slow database connection: {$connectionTime}ms");
            } else {
                $this->info("✅ Database health OK ({$connectionTime}ms)");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Database connection failed: " . $e->getMessage();
            $this->error("❌ Database health check failed: " . $e->getMessage());
        }
    }

    private function checkRedisHealth(): void
    {
        $this->line("Checking Redis health...");
        
        try {
            $start = microtime(true);
            
            // Test connectivity
            $redis = Redis::connection();
            $pingResult = $redis->ping();
            $connectionTime = round((microtime(true) - $start) * 1000, 2);
            
            $this->healthMetrics['redis_connection_time_ms'] = $connectionTime;
            $this->healthMetrics['redis_ping_result'] = $pingResult;
            
            // Get Redis info
            $info = $redis->info();
            $this->healthMetrics['redis_memory_used'] = $info['used_memory_human'] ?? 'unknown';
            $this->healthMetrics['redis_connected_clients'] = $info['connected_clients'] ?? 0;
            
            // Check emergency cache keys
            $emergencyKeys = $redis->keys('emergency_*');
            $this->healthMetrics['redis_emergency_keys_count'] = count($emergencyKeys);
            
            if ($connectionTime > 500) {
                $this->criticalIssues[] = "Slow Redis connection: {$connectionTime}ms";
                $this->error("⚠️  Slow Redis connection: {$connectionTime}ms");
            } else {
                $this->info("✅ Redis health OK ({$connectionTime}ms)");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Redis connection failed: " . $e->getMessage();
            $this->error("❌ Redis health check failed: " . $e->getMessage());
        }
    }

    private function checkEmergencyAccessSystem(): void
    {
        $this->line("Checking emergency access system...");
        
        try {
            // Count active emergency access keys
            $activeAccess = TeamEmergencyAccess::where('is_active', true)
                ->where('expires_at', '>', now())
                ->count();
            
            $this->healthMetrics['active_emergency_access_count'] = $activeAccess;
            
            // Count expired access keys
            $expiredAccess = TeamEmergencyAccess::where('is_active', true)
                ->where('expires_at', '<=', now())
                ->count();
                
            $this->healthMetrics['expired_emergency_access_count'] = $expiredAccess;
            
            // Check for access keys expiring soon (next 7 days)
            $expiringSoon = $this->emergencyService->getExpiringAccess(7);
            $this->healthMetrics['expiring_soon_count'] = $expiringSoon->count();
            
            // Check recent emergency access usage
            $recentUsage = TeamEmergencyAccess::where('last_used_at', '>', now()->subHour())
                ->count();
            $this->healthMetrics['recent_emergency_usage'] = $recentUsage;
            
            if ($expiredAccess > 0) {
                $this->warn("⚠️  {$expiredAccess} expired emergency access keys found");
            }
            
            if ($expiringSoon->count() > 0) {
                $this->warn("⚠️  {$expiringSoon->count()} emergency access keys expiring soon");
            }
            
            $this->info("✅ Emergency access system OK ({$activeAccess} active)");
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Emergency access system check failed: " . $e->getMessage();
            $this->error("❌ Emergency access system check failed: " . $e->getMessage());
        }
    }

    private function checkSecurityMonitoring(): void
    {
        $this->line("Checking security monitoring...");
        
        try {
            // Check recent security events
            $recentEvents = DB::table('security_events')
                ->where('created_at', '>', now()->subHour())
                ->count();
                
            $this->healthMetrics['recent_security_events'] = $recentEvents;
            
            // Check critical security events
            $criticalEvents = DB::table('security_events')
                ->where('severity', 'critical')
                ->where('created_at', '>', now()->subDay())
                ->count();
                
            $this->healthMetrics['critical_security_events_24h'] = $criticalEvents;
            
            // Check emergency access anomalies
            $emergencyAnomalies = DB::table('security_events')
                ->where('event_type', 'emergency_access_anomaly')
                ->where('created_at', '>', now()->subHour())
                ->count();
                
            $this->healthMetrics['emergency_anomalies_1h'] = $emergencyAnomalies;
            
            if ($criticalEvents > 0) {
                $this->criticalIssues[] = "Critical security events detected: {$criticalEvents}";
                $this->error("⚠️  {$criticalEvents} critical security events in last 24h");
            }
            
            if ($emergencyAnomalies > 5) {
                $this->criticalIssues[] = "High emergency access anomalies: {$emergencyAnomalies}";
                $this->error("⚠️  {$emergencyAnomalies} emergency access anomalies in last hour");
            } else {
                $this->info("✅ Security monitoring OK");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Security monitoring check failed: " . $e->getMessage();
            $this->error("❌ Security monitoring check failed: " . $e->getMessage());
        }
    }

    private function checkEmergencyIncidents(): void
    {
        $this->line("Checking emergency incidents...");
        
        try {
            // Count active incidents
            $activeIncidents = EmergencyIncident::where('status', 'active')->count();
            $this->healthMetrics['active_emergency_incidents'] = $activeIncidents;
            
            // Count critical incidents
            $criticalIncidents = EmergencyIncident::where('severity', 'critical')
                ->where('status', 'active')
                ->count();
            $this->healthMetrics['critical_incidents'] = $criticalIncidents;
            
            // Count recent incidents
            $recentIncidents = EmergencyIncident::where('created_at', '>', now()->subDay())
                ->count();
            $this->healthMetrics['recent_incidents_24h'] = $recentIncidents;
            
            if ($criticalIncidents > 0) {
                $this->warn("⚠️  {$criticalIncidents} critical incidents active");
            }
            
            $this->info("✅ Emergency incidents check OK ({$activeIncidents} active)");
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Emergency incidents check failed: " . $e->getMessage();
            $this->error("❌ Emergency incidents check failed: " . $e->getMessage());
        }
    }

    private function checkPerformanceMetrics(): void
    {
        $this->line("Checking performance metrics...");
        
        try {
            // Check average response time from cache
            $avgResponseTime = Cache::get('emergency_avg_response_time', 0);
            $this->healthMetrics['avg_response_time_ms'] = $avgResponseTime;
            
            // Check cache hit ratio
            $cacheHitRatio = Cache::get('emergency_cache_hit_ratio', 0);
            $this->healthMetrics['cache_hit_ratio'] = $cacheHitRatio;
            
            // Check memory usage
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            $this->healthMetrics['memory_usage_mb'] = round($memoryUsage, 2);
            
            if ($avgResponseTime > 2000) {
                $this->criticalIssues[] = "High average response time: {$avgResponseTime}ms";
                $this->error("⚠️  High average response time: {$avgResponseTime}ms");
            } elseif ($avgResponseTime > 0) {
                $this->info("✅ Performance OK (avg: {$avgResponseTime}ms)");
            } else {
                $this->info("✅ Performance metrics initialized");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Performance metrics check failed: " . $e->getMessage();
            $this->error("❌ Performance metrics check failed: " . $e->getMessage());
        }
    }

    private function checkSystemResources(): void
    {
        $this->line("Checking system resources...");
        
        try {
            // Check disk space
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
            
            $this->healthMetrics['disk_usage_percent'] = $diskUsagePercent;
            $this->healthMetrics['disk_free_gb'] = round($diskFree / 1024 / 1024 / 1024, 2);
            
            // Check load average (Linux only)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $this->healthMetrics['load_average'] = $load;
                
                if ($load[0] > 4.0) {
                    $this->criticalIssues[] = "High system load: {$load[0]}";
                    $this->error("⚠️  High system load: {$load[0]}");
                }
            }
            
            if ($diskUsagePercent > 90) {
                $this->criticalIssues[] = "High disk usage: {$diskUsagePercent}%";
                $this->error("⚠️  High disk usage: {$diskUsagePercent}%");
            } elseif ($diskUsagePercent > 80) {
                $this->warn("⚠️  Disk usage warning: {$diskUsagePercent}%");
            } else {
                $this->info("✅ System resources OK (disk: {$diskUsagePercent}%)");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "System resources check failed: " . $e->getMessage();
            $this->error("❌ System resources check failed: " . $e->getMessage());
        }
    }

    private function checkCacheHealth(): void
    {
        $this->line("Checking cache health...");
        
        try {
            $cacheKey = 'emergency_health_check_' . time();
            $testValue = 'test_' . uniqid();
            
            $start = microtime(true);
            
            // Test cache write
            Cache::put($cacheKey, $testValue, 60);
            
            // Test cache read
            $cachedValue = Cache::get($cacheKey);
            
            $cacheTime = round((microtime(true) - $start) * 1000, 2);
            $this->healthMetrics['cache_operation_time_ms'] = $cacheTime;
            
            // Clean up test key
            Cache::forget($cacheKey);
            
            if ($cachedValue !== $testValue) {
                $this->criticalIssues[] = "Cache integrity test failed";
                $this->error("❌ Cache integrity test failed");
            } elseif ($cacheTime > 100) {
                $this->warn("⚠️  Slow cache operations: {$cacheTime}ms");
            } else {
                $this->info("✅ Cache health OK ({$cacheTime}ms)");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Cache health check failed: " . $e->getMessage();
            $this->error("❌ Cache health check failed: " . $e->getMessage());
        }
    }

    private function checkQueueHealth(): void
    {
        $this->line("Checking queue health...");
        
        try {
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $this->healthMetrics['failed_jobs_count'] = $failedJobs;
            
            // Check emergency queue specifically
            $redis = Redis::connection();
            $emergencyQueueSize = $redis->llen('queues:emergency');
            $this->healthMetrics['emergency_queue_size'] = $emergencyQueueSize;
            
            if ($failedJobs > 10) {
                $this->criticalIssues[] = "High number of failed jobs: {$failedJobs}";
                $this->error("⚠️  {$failedJobs} failed jobs in queue");
            } elseif ($failedJobs > 0) {
                $this->warn("⚠️  {$failedJobs} failed jobs in queue");
            }
            
            if ($emergencyQueueSize > 50) {
                $this->criticalIssues[] = "Emergency queue backlog: {$emergencyQueueSize}";
                $this->error("⚠️  Emergency queue backlog: {$emergencyQueueSize}");
            } else {
                $this->info("✅ Queue health OK (emergency: {$emergencyQueueSize})");
            }
            
        } catch (\Exception $e) {
            $this->criticalIssues[] = "Queue health check failed: " . $e->getMessage();
            $this->error("❌ Queue health check failed: " . $e->getMessage());
        }
    }

    private function generateHealthReport(): void
    {
        $this->newLine();
        $this->info("📊 Health Check Summary");
        $this->line(str_repeat("=", 50));
        
        $overallHealth = empty($this->criticalIssues) ? 'HEALTHY' : 'ISSUES_DETECTED';
        $healthColor = $overallHealth === 'HEALTHY' ? 'green' : 'red';
        
        $this->line("Overall Status: <fg={$healthColor}>{$overallHealth}</>");
        $this->line("Check Time: " . now()->format('Y-m-d H:i:s'));
        $this->line("Duration: {$this->healthMetrics['total_check_time_ms']}ms");
        
        if ($this->option('verbose')) {
            $this->newLine();
            $this->line("Detailed Metrics:");
            foreach ($this->healthMetrics as $key => $value) {
                if (is_array($value)) {
                    $this->line("  {$key}: " . json_encode($value, JSON_PRETTY_PRINT));
                } else {
                    $this->line("  {$key}: {$value}");
                }
            }
        }
        
        if (!empty($this->criticalIssues)) {
            $this->newLine();
            $this->error("Critical Issues Detected:");
            foreach ($this->criticalIssues as $issue) {
                $this->line("  ❌ {$issue}");
            }
        }
    }

    private function storeHealthMetrics(): void
    {
        try {
            $timestamp = now()->toISOString();
            
            // Store in Redis with TTL
            Cache::put("emergency_health_metrics_{$timestamp}", $this->healthMetrics, 3600);
            
            // Store latest metrics
            Cache::put('emergency_latest_health_metrics', [
                'timestamp' => $timestamp,
                'metrics' => $this->healthMetrics,
                'issues' => $this->criticalIssues,
                'overall_status' => empty($this->criticalIssues) ? 'healthy' : 'issues'
            ], 300);
            
            // Log health status
            Log::channel('emergency')->info('Health check completed', [
                'metrics' => $this->healthMetrics,
                'issues_count' => count($this->criticalIssues),
                'overall_status' => empty($this->criticalIssues) ? 'healthy' : 'issues'
            ]);
            
        } catch (\Exception $e) {
            $this->error("Failed to store health metrics: " . $e->getMessage());
        }
    }

    private function sendCriticalAlerts(): void
    {
        $webhook = $this->option('alert-webhook');
        
        if (!$webhook) {
            return;
        }
        
        try {
            $alertData = [
                'service' => 'BasketManager Pro Emergency Services',
                'status' => 'CRITICAL',
                'timestamp' => now()->toISOString(),
                'issues_count' => count($this->criticalIssues),
                'issues' => $this->criticalIssues,
                'metrics' => $this->healthMetrics
            ];
            
            $response = file_get_contents($webhook, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($alertData)
                ]
            ]));
            
            $this->info("Critical alert sent to webhook");
            
        } catch (\Exception $e) {
            $this->error("Failed to send critical alert: " . $e->getMessage());
        }
    }
}