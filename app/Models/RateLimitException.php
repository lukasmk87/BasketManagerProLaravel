<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class RateLimitException extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'api_key_hash',
        'identifier_type',
        'exception_type',
        'scope',
        'endpoints',
        'custom_request_limit',
        'custom_burst_limit',
        'custom_cost_multiplier',
        'starts_at',
        'expires_at',
        'duration_hours',
        'times_used',
        'max_uses',
        'last_used_at',
        'reason',
        'granted_by',
        'status',
        'notes',
        'alert_on_use',
        'auto_expire',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'custom_cost_multiplier' => 'decimal:2',
        'alert_on_use' => 'boolean',
        'auto_expire' => 'boolean',
        'metadata' => 'array',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('expires_at', '<=', now())
              ->orWhere('status', 'expired');
        });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeForApiKey($query, $apiKeyHash)
    {
        return $query->where('api_key_hash', $apiKeyHash);
    }

    public function scopeByType($query, $exceptionType)
    {
        return $query->where('exception_type', $exceptionType);
    }

    public function scopeForEndpoint($query, $endpoint)
    {
        return $query->where('scope', 'endpoint_specific')
                    ->where('endpoints', 'LIKE', "%{$endpoint}%");
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->status !== 'active') {
                    return false;
                }
                
                $now = now();
                
                // Check if started
                if ($this->starts_at && $this->starts_at->isAfter($now)) {
                    return false;
                }
                
                // Check if expired
                if ($this->expires_at && $this->expires_at->isBefore($now)) {
                    return false;
                }
                
                // Check if max uses reached
                if ($this->max_uses && $this->times_used >= $this->max_uses) {
                    return false;
                }
                
                return true;
            }
        );
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at && $this->expires_at->isPast()
        );
    }

    public function timeRemaining(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->expires_at) {
                    return null;
                }
                
                $diff = now()->diffInSeconds($this->expires_at, false);
                return max(0, $diff);
            }
        );
    }

    public function usesRemaining(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->max_uses) {
                    return null;
                }
                
                return max(0, $this->max_uses - $this->times_used);
            }
        );
    }

    public function endpointList(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->endpoints ? explode(',', $this->endpoints) : []
        );
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if this exception applies to a specific endpoint
     */
    public function appliesTo(string $endpoint): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->scope === 'global') {
            return true;
        }
        
        if ($this->scope === 'endpoint_specific') {
            $endpoints = $this->endpoint_list;
            
            foreach ($endpoints as $pattern) {
                $pattern = trim($pattern);
                
                // Exact match
                if ($pattern === $endpoint) {
                    return true;
                }
                
                // Wildcard match
                if (str_contains($pattern, '*')) {
                    $regexPattern = str_replace(['*', '/'], ['.*', '\/'], $pattern);
                    if (preg_match("/^{$regexPattern}$/", $endpoint)) {
                        return true;
                    }
                }
                
                // Prefix match
                if (str_starts_with($endpoint, rtrim($pattern, '/'))) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Apply this exception and record usage
     */
    public function apply(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        // Increment usage count
        $this->increment('times_used');
        $this->last_used_at = now();
        $this->save();
        
        // Send alert if configured
        if ($this->alert_on_use) {
            $this->sendUsageAlert();
        }
        
        // Auto-expire if max uses reached
        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            $this->expire('Max uses reached');
        }
        
        return true;
    }

    /**
     * Get the effective rate limits with this exception applied
     */
    public function getEffectiveLimits(array $baseLimits): array
    {
        $limits = $baseLimits;
        
        switch ($this->exception_type) {
            case 'unlimited':
                return [
                    'requests_per_hour' => PHP_INT_MAX,
                    'burst_per_minute' => PHP_INT_MAX,
                    'concurrent_requests' => PHP_INT_MAX,
                    'cost_multiplier' => 0,
                ];
                
            case 'bypass':
                return [
                    'requests_per_hour' => PHP_INT_MAX,
                    'burst_per_minute' => PHP_INT_MAX,
                    'concurrent_requests' => $limits['concurrent_requests'] ?? 10,
                    'cost_multiplier' => $limits['cost_multiplier'] ?? 1,
                ];
                
            case 'increase':
                if ($this->custom_request_limit) {
                    $limits['requests_per_hour'] = $this->custom_request_limit;
                }
                
                if ($this->custom_burst_limit) {
                    $limits['burst_per_minute'] = $this->custom_burst_limit;
                }
                
                if ($this->custom_cost_multiplier) {
                    $limits['cost_multiplier'] = $this->custom_cost_multiplier;
                }
                
                break;
        }
        
        return $limits;
    }

    /**
     * Expire this exception
     */
    public function expire(string $reason = 'Manually expired'): bool
    {
        $this->status = 'expired';
        $this->expires_at = now();
        $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Expired: {$reason} at " . now();
        
        return $this->save();
    }

    /**
     * Revoke this exception
     */
    public function revoke(string $reason = 'Revoked by admin'): bool
    {
        $this->status = 'revoked';
        $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Revoked: {$reason} at " . now();
        
        return $this->save();
    }

    /**
     * Extend this exception
     */
    public function extend(int $additionalHours, string $reason = 'Extended by admin'): bool
    {
        if (!$this->expires_at) {
            $this->expires_at = now()->addHours($additionalHours);
        } else {
            $this->expires_at = $this->expires_at->addHours($additionalHours);
        }
        
        $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Extended by {$additionalHours} hours: {$reason} at " . now();
        
        return $this->save();
    }

    /**
     * Find active exceptions for identifier
     */
    public static function findActiveFor(string $identifierType, string $identifier, ?string $endpoint = null): \Illuminate\Support\Collection
    {
        $query = self::active();
        
        switch ($identifierType) {
            case 'user':
                $query->forUser($identifier);
                break;
            case 'ip':
                $query->forIp($identifier);
                break;
            case 'api_key':
                $query->forApiKey($identifier);
                break;
        }
        
        $exceptions = $query->get();
        
        if ($endpoint) {
            $exceptions = $exceptions->filter(function($exception) use ($endpoint) {
                return $exception->appliesTo($endpoint);
            });
        }
        
        return $exceptions;
    }

    /**
     * Create temporary exception
     */
    public static function createTemporary(array $data, int $durationHours): self
    {
        $data['starts_at'] = now();
        $data['expires_at'] = now()->addHours($durationHours);
        $data['duration_hours'] = $durationHours;
        $data['status'] = 'active';
        
        return self::create($data);
    }

    /**
     * Clean up expired exceptions
     */
    public static function cleanupExpired(): int
    {
        $expired = self::expired()->where('auto_expire', true);
        $count = $expired->count();
        
        $expired->update(['status' => 'expired']);
        
        return $count;
    }

    /**
     * Send usage alert
     */
    protected function sendUsageAlert(): void
    {
        // Implementation would send notification to admins
        // This is a placeholder for the notification system
        logger()->info('Rate limit exception used', [
            'exception_id' => $this->id,
            'user_id' => $this->user_id,
            'ip_address' => $this->ip_address,
            'times_used' => $this->times_used,
            'max_uses' => $this->max_uses,
            'reason' => $this->reason,
        ]);
    }
}