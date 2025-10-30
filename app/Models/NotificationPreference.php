<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'event_type',
        'is_enabled',
        'settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable entity (polymorphic).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by channel.
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by enabled preferences.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to filter by notifiable.
     */
    public function scopeForNotifiable($query, string $type, int $id)
    {
        return $query->where('notifiable_type', $type)
                     ->where('notifiable_id', $id);
    }

    /**
     * Check if notifications are enabled for this preference.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * Enable notifications for this preference.
     */
    public function enable(): void
    {
        $this->update(['is_enabled' => true]);
    }

    /**
     * Disable notifications for this preference.
     */
    public function disable(): void
    {
        $this->update(['is_enabled' => false]);
    }

    /**
     * Toggle notification preference.
     */
    public function toggle(): void
    {
        $this->update(['is_enabled' => !$this->is_enabled]);
    }

    /**
     * Get setting value by key.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set setting value by key.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }
}
