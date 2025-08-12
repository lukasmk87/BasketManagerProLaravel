<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class ApiUsageTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_key_hash',
        'ip_address',
        'user_agent',
        'method',
        'endpoint',
        'route_name',
        'api_version',
        'request_count',
        'cost_weight',
        'response_time_ms',
        'response_status',
        'response_size_bytes',
        'window_start',
        'window_type',
        'request_timestamp',
        'requests_in_window',
        'cost_in_window',
        'exceeded_limit',
        'limit_type_hit',
        'country_code',
        'region',
        'headers',
        'billable_cost',
        'subscription_tier',
        'is_overage',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'request_timestamp' => 'datetime',
        'cost_weight' => 'decimal:2',
        'cost_in_window' => 'decimal:2',
        'billable_cost' => 'decimal:4',
        'exceeded_limit' => 'boolean',
        'is_overage' => 'boolean',
        'headers' => 'array',
    ];

    // ============================
    // RELATIONSHIPS
    // ============================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeInWindow($query, string $windowType, Carbon $windowStart)
    {
        return $query->where('window_type', $windowType)
                    ->where('window_start', $windowStart);
    }

    public function scopeWithinPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('request_timestamp', [$start, $end]);
    }

    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeBySubscriptionTier($query, string $tier)
    {
        return $query->where('subscription_tier', $tier);
    }

    public function scopeExceededLimits($query)
    {
        return $query->where('exceeded_limit', true);
    }

    public function scopeOverageRequests($query)
    {
        return $query->where('is_overage', true);
    }

    public function scopeSuccessfulRequests($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    public function scopeErrorRequests($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function isSuccess(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->response_status >= 200 && $this->response_status < 300
        );
    }

    public function isError(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->response_status >= 400
        );
    }

    public function responseTimeSeconds(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->response_time_ms ? $this->response_time_ms / 1000 : null
        );
    }

    public function responseSizeKb(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->response_size_bytes ? round($this->response_size_bytes / 1024, 2) : null
        );
    }

    public function routeCategory(): Attribute
    {
        return Attribute::make(
            get: function() {
                // Categorize endpoints for analytics
                if (str_contains($this->endpoint, '/auth/')) return 'authentication';
                if (str_contains($this->endpoint, '/admin/')) return 'administration';
                if (str_contains($this->endpoint, '/games/') && str_contains($this->endpoint, '/live')) return 'live_scoring';
                if (str_contains($this->endpoint, '/analytics/')) return 'analytics';
                if (str_contains($this->endpoint, '/export/')) return 'exports';
                if (str_contains($this->endpoint, '/upload/')) return 'uploads';
                if (str_contains($this->endpoint, '/bulk/')) return 'bulk_operations';
                return 'general';
            }
        );
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Create or update usage tracking record for sliding window
     */
    public static function recordUsage(array $data): self
    {
        $windowStart = self::calculateWindowStart($data['window_type'] ?? 'hourly');
        $windowType = $data['window_type'] ?? 'hourly';
        
        // Try to find existing record for this window
        $existing = self::where('user_id', $data['user_id'] ?? null)
                       ->where('ip_address', $data['ip_address'])
                       ->where('endpoint', $data['endpoint'])
                       ->where('window_start', $windowStart)
                       ->where('window_type', $windowType)
                       ->first();

        if ($existing) {
            // Update existing record
            $existing->increment('request_count', $data['request_count'] ?? 1);
            $existing->increment('requests_in_window', $data['request_count'] ?? 1);
            $existing->increment('cost_in_window', $data['cost_weight'] ?? 1.0);
            $existing->increment('billable_cost', $data['billable_cost'] ?? 0);
            $existing->request_timestamp = now();
            $existing->save();
            
            return $existing;
        } else {
            // Create new record
            return self::create(array_merge($data, [
                'window_start' => $windowStart,
                'window_type' => $windowType,
                'request_timestamp' => now(),
                'requests_in_window' => $data['request_count'] ?? 1,
                'cost_in_window' => $data['cost_weight'] ?? 1.0,
            ]));
        }
    }

    /**
     * Calculate window start time for sliding window
     */
    public static function calculateWindowStart(string $windowType): Carbon
    {
        $now = now();
        
        return match($windowType) {
            'minutely' => $now->copy()->startOfMinute(),
            'hourly' => $now->copy()->startOfHour(),
            'daily' => $now->copy()->startOfDay(),
            'monthly' => $now->copy()->startOfMonth(),
            default => $now->copy()->startOfHour(),
        };
    }

    /**
     * Get usage summary for a user within time period
     */
    public static function getUserUsageSummary($userId, Carbon $start, Carbon $end): array
    {
        $records = self::forUser($userId)
                      ->withinPeriod($start, $end)
                      ->get();

        $grouped = $records->groupBy('route_category');
        
        return [
            'total_requests' => $records->sum('request_count'),
            'total_cost' => $records->sum('billable_cost'),
            'unique_endpoints' => $records->unique('endpoint')->count(),
            'unique_ips' => $records->unique('ip_address')->count(),
            'avg_response_time' => $records->avg('response_time_ms'),
            'success_rate' => $records->whereBetween('response_status', [200, 299])->count() / max($records->count(), 1),
            'total_data_transferred' => $records->sum('response_size_bytes'),
            'exceeded_limits_count' => $records->where('exceeded_limit', true)->count(),
            'overage_cost' => $records->where('is_overage', true)->sum('billable_cost'),
            'by_category' => $grouped->map(function ($categoryRecords) {
                return [
                    'requests' => $categoryRecords->sum('request_count'),
                    'avg_response_time' => $categoryRecords->avg('response_time_ms'),
                    'success_rate' => $categoryRecords->whereBetween('response_status', [200, 299])->count() / max($categoryRecords->count(), 1),
                ];
            }),
            'hourly_distribution' => $records->groupBy(function ($record) {
                return $record->request_timestamp->format('H:00');
            })->map->sum('request_count'),
        ];
    }

    /**
     * Get current usage for sliding window rate limiting
     */
    public static function getCurrentWindowUsage($userId, ?string $ipAddress, string $windowType = 'hourly'): array
    {
        $windowStart = self::calculateWindowStart($windowType);
        
        $query = self::where('window_start', $windowStart)
                    ->where('window_type', $windowType);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }
        
        $usage = $query->get();
        
        return [
            'total_requests' => $usage->sum('requests_in_window'),
            'total_cost' => $usage->sum('cost_in_window'),
            'unique_endpoints' => $usage->unique('endpoint')->count(),
            'window_start' => $windowStart,
            'window_type' => $windowType,
            'time_remaining' => self::getWindowTimeRemaining($windowType),
        ];
    }

    /**
     * Get remaining time in current window
     */
    public static function getWindowTimeRemaining(string $windowType): int
    {
        $now = now();
        
        return match($windowType) {
            'minutely' => 60 - $now->second,
            'hourly' => (60 - $now->minute) * 60 - $now->second,
            'daily' => $now->copy()->endOfDay()->diffInSeconds($now),
            'monthly' => $now->copy()->endOfMonth()->diffInSeconds($now),
            default => (60 - $now->minute) * 60 - $now->second,
        };
    }

    /**
     * Clean up old usage records (for performance)
     */
    public static function cleanupOldRecords(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return self::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get top consumers by requests
     */
    public static function getTopConsumers(int $limit = 10, string $period = 'last_24_hours'): \Illuminate\Support\Collection
    {
        $start = match($period) {
            'last_hour' => now()->subHour(),
            'last_24_hours' => now()->subDay(),
            'last_week' => now()->subWeek(),
            'last_month' => now()->subMonth(),
            default => now()->subDay(),
        };

        return self::where('created_at', '>=', $start)
                  ->selectRaw('user_id, ip_address, subscription_tier, SUM(request_count) as total_requests, SUM(billable_cost) as total_cost, AVG(response_time_ms) as avg_response_time')
                  ->groupBy('user_id', 'ip_address', 'subscription_tier')
                  ->orderByDesc('total_requests')
                  ->limit($limit)
                  ->get();
    }
}