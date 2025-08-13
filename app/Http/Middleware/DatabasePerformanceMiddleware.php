<?php

namespace App\Http\Middleware;

use App\Services\DatabasePerformanceMonitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DatabasePerformanceMiddleware
{
    public function __construct(
        private DatabasePerformanceMonitor $monitor
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip monitoring in testing environment or if explicitly disabled
        if (app()->environment('testing') || !Config::get('app.db_monitoring_enabled', true)) {
            return $next($request);
        }

        // Skip monitoring for certain routes to avoid overhead
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        // Enable query monitoring
        $this->monitor->enableQueryMonitoring();

        // Process the request
        $response = $next($request);

        // Add performance data to response headers (in debug mode)
        if (Config::get('app.debug') && $this->shouldAddDebugHeaders($request)) {
            $this->addPerformanceHeaders($response);
        }

        // Log performance report if thresholds are exceeded
        $this->logPerformanceIfNeeded($request);

        return $response;
    }

    /**
     * Determine if monitoring should be skipped for this request
     */
    private function shouldSkipMonitoring(Request $request): bool
    {
        $skipRoutes = [
            'telescope/*',
            'horizon/*',
            '_debugbar/*',
            'health-check',
            'ping',
            'metrics',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Skip for static assets
        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.gif', '*.svg', '*.ico', '*.woff*')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if debug headers should be added
     */
    private function shouldAddDebugHeaders(Request $request): bool
    {
        // Only add headers for API requests or if explicitly requested
        return $request->is('api/*') || $request->has('debug-performance');
    }

    /**
     * Add performance metrics to response headers
     */
    private function addPerformanceHeaders(Response $response): void
    {
        try {
            $report = $this->monitor->getPerformanceReport();
            $summary = $report['request_summary'];

            $response->headers->add([
                'X-DB-Query-Count' => $summary['total_queries'],
                'X-DB-Query-Time' => $summary['total_query_time'] . 'ms',
                'X-Request-Duration' => $summary['request_duration'] . 'ms',
                'X-Memory-Usage' => $this->formatBytes($summary['memory_usage']),
                'X-Memory-Peak' => $this->formatBytes($summary['peak_memory']),
            ]);

            // Add warning headers if thresholds are exceeded
            if ($summary['total_queries'] > 50) {
                $response->headers->add(['X-DB-Warning' => 'High query count']);
            }

            if ($summary['total_query_time'] > 1000) {
                $response->headers->add(['X-DB-Warning' => 'High query time']);
            }
        } catch (\Exception $e) {
            // Don't let monitoring break the response
            Log::warning('Failed to add performance headers', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log performance report if thresholds are exceeded
     */
    private function logPerformanceIfNeeded(Request $request): void
    {
        try {
            $report = $this->monitor->getPerformanceReport();
            $summary = $report['request_summary'];
            $recommendations = $report['recommendations'];

            // Log if performance thresholds are exceeded
            if ($this->shouldLogPerformance($summary, $recommendations)) {
                $this->logPerformanceReport($request, $report);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate performance report', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
            ]);
        }
    }

    /**
     * Determine if performance should be logged
     */
    private function shouldLogPerformance(array $summary, array $recommendations): bool
    {
        // Log if we have any error-level recommendations
        foreach ($recommendations as $recommendation) {
            if ($recommendation['severity'] === 'error') {
                return true;
            }
        }

        // Log if request took longer than 5 seconds total
        if ($summary['request_duration'] > 5000) {
            return true;
        }

        // Log if we have more than 100 queries
        if ($summary['total_queries'] > 100) {
            return true;
        }

        // Log if total query time exceeds 3 seconds
        if ($summary['total_query_time'] > 3000) {
            return true;
        }

        return false;
    }

    /**
     * Log the performance report
     */
    private function logPerformanceReport(Request $request, array $report): void
    {
        $summary = $report['request_summary'];
        $tenant = app('tenant');

        Log::warning('Database Performance Alert', [
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'user_id' => auth()->id(),
            'tenant_id' => $tenant?->id,
            'tenant_name' => $tenant?->name,
            'performance_summary' => [
                'total_queries' => $summary['total_queries'],
                'total_query_time' => $summary['total_query_time'] . 'ms',
                'request_duration' => $summary['request_duration'] . 'ms',
                'memory_usage' => $this->formatBytes($summary['memory_usage']),
                'peak_memory' => $this->formatBytes($summary['peak_memory']),
            ],
            'recommendations' => $report['recommendations'],
            'slow_queries_count' => count($report['slow_queries']),
            'top_query_patterns' => array_slice($report['query_analysis']['top_patterns'] ?? [], 0, 5, true),
        ]);

        // Log individual slow queries for detailed analysis
        foreach ($report['slow_queries'] as $slowQuery) {
            Log::warning('Slow Query Detail', [
                'sql_pattern' => $slowQuery['sql'] ?? 'unknown',
                'execution_time' => $slowQuery['time'] . 'ms',
                'tenant_id' => $slowQuery['tenant_id'] ?? null,
                'request_url' => $request->fullUrl(),
            ]);
        }
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
}