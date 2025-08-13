<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Push Subscription Model
 * 
 * Stores web push notification subscriptions for users
 * 
 * @property string $id
 * @property string $user_id
 * @property string $endpoint
 * @property string $p256dh_key
 * @property string $auth_token
 * @property string|null $user_agent
 * @property bool $is_active
 * @property array|null $subscription_data
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PushSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh_key',
        'auth_token',
        'user_agent',
        'is_active',
        'subscription_data',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_data' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active subscriptions only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subscriptions that haven't been used recently
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStale($query, int $days = 30)
    {
        return $query->where('last_used_at', '<', now()->subDays($days))
                    ->orWhereNull('last_used_at');
    }

    /**
     * Mark subscription as used
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Mark subscription as inactive
     *
     * @return bool
     */
    public function markAsInactive(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Get subscription data in the format expected by web-push libraries
     *
     * @return array
     */
    public function getWebPushSubscription(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh_key,
                'auth' => $this->auth_token,
            ]
        ];
    }

    /**
     * Get browser info from user agent
     *
     * @return array
     */
    public function getBrowserInfo(): array
    {
        if (!$this->user_agent) {
            return ['browser' => 'unknown', 'platform' => 'unknown'];
        }

        $userAgent = $this->user_agent;
        $browser = 'unknown';
        $platform = 'unknown';

        // Detect browser
        if (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }

        // Detect platform
        if (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        }

        return compact('browser', 'platform');
    }

    /**
     * Check if subscription is likely still valid
     *
     * @return bool
     */
    public function isLikelyValid(): bool
    {
        // Consider subscription invalid if it hasn't been used in 60 days
        if ($this->last_used_at && $this->last_used_at->diffInDays(now()) > 60) {
            return false;
        }

        // Check if endpoint looks valid
        if (!filter_var($this->endpoint, FILTER_VALIDATE_URL)) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * Get display name for the subscription
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $browserInfo = $this->getBrowserInfo();
        
        return sprintf(
            '%s auf %s',
            $browserInfo['browser'],
            $browserInfo['platform']
        );
    }

    /**
     * Create or update subscription for user
     *
     * @param string $userId
     * @param array $subscriptionData
     * @return static
     */
    public static function createOrUpdateForUser(string $userId, array $subscriptionData): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'endpoint' => $subscriptionData['endpoint']
            ],
            [
                'p256dh_key' => $subscriptionData['keys']['p256dh'],
                'auth_token' => $subscriptionData['keys']['auth'],
                'user_agent' => request()->userAgent(),
                'is_active' => true,
                'subscription_data' => $subscriptionData,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Clean up old/invalid subscriptions
     *
     * @param int $days
     * @return int Number of cleaned up subscriptions
     */
    public static function cleanup(int $days = 90): int
    {
        return static::where('last_used_at', '<', now()->subDays($days))
                    ->orWhere('is_active', false)
                    ->delete();
    }
}