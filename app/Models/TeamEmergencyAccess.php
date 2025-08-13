<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToTenant;

class TeamEmergencyAccess extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, BelongsToTenant;

    protected $table = 'team_emergency_access';

    protected $fillable = [
        'team_id',
        'created_by_user_id',
        'access_key',
        'access_type',
        'permissions',
        'expires_at',
        'is_active',
        'max_uses',
        'current_uses',
        'last_used_at',
        'last_used_ip',
        'last_used_user_agent',
        'usage_log',
        'emergency_contact_person',
        'emergency_contact_phone',
        'usage_instructions',
        'venue_information',
        'requires_reason',
        'send_notifications',
        'notification_recipients',
        'log_detailed_access',
        'qr_code_url',
        'qr_code_filename',
        'qr_code_metadata',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'last_used_at' => 'datetime',
        'usage_log' => 'array',
        'venue_information' => 'array',
        'requires_reason' => 'boolean',
        'send_notifications' => 'boolean',
        'notification_recipients' => 'array',
        'log_detailed_access' => 'boolean',
        'qr_code_metadata' => 'array',
    ];

    protected $hidden = [
        'access_key',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeRecentlyUsed($query, int $hours = 24)
    {
        return $query->where('last_used_at', '>=', now()->subHours($hours));
    }

    public function scopeUnused($query)
    {
        return $query->where('current_uses', 0);
    }

    // Accessors & Mutators
    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->expires_at->isPast()
        );
    }

    public function isValid(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->is_active && 
                       !$this->is_expired && 
                       ($this->max_uses === null || $this->current_uses < $this->max_uses);
            }
        );
    }

    public function usagePercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->max_uses) {
                    return 0;
                }
                return ($this->current_uses / $this->max_uses) * 100;
            }
        );
    }

    public function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: fn() => now()->diffInDays($this->expires_at, false)
        );
    }

    public function hoursUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: fn() => now()->diffInHours($this->expires_at, false)
        );
    }

    public function timeSinceLastUse(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->last_used_at) {
                    return null;
                }
                return $this->last_used_at->diffForHumans();
            }
        );
    }

    // Helper Methods
    public static function generateSecureAccessKey(): string
    {
        do {
            $key = Str::random(32);
        } while (static::where('access_key', $key)->exists());

        return $key;
    }

    public function canBeUsed(): bool
    {
        return $this->is_valid;
    }

    public function incrementUsage(array $usageData = []): void
    {
        $this->increment('current_uses');
        
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => request()->ip(),
            'last_used_user_agent' => request()->userAgent(),
        ]);

        if ($usageData) {
            $this->logUsage($usageData);
        }
    }

    public function logUsage(array $usageData): void
    {
        $usageLog = $this->usage_log ?? [];
        
        $usageLog[] = array_merge([
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'usage_count' => $this->current_uses,
        ], $usageData);

        $this->update(['usage_log' => $usageLog]);
    }

    public function deactivate(string $reason = 'Manual deactivation'): void
    {
        $this->update([
            'is_active' => false,
            'expires_at' => now(),
        ]);

        $this->logUsage([
            'action' => 'deactivated',
            'reason' => $reason,
            'deactivated_by' => auth()->user()?->name,
        ]);
    }

    public function extend(int $hours, ?User $extendedBy = null): void
    {
        $oldExpiry = $this->expires_at;
        $newExpiry = $this->expires_at->addHours($hours);
        
        $this->update(['expires_at' => $newExpiry]);

        $this->logUsage([
            'action' => 'extended',
            'old_expiry' => $oldExpiry,
            'new_expiry' => $newExpiry,
            'hours_added' => $hours,
            'extended_by' => $extendedBy?->name ?? auth()->user()?->name,
        ]);
    }

    public function resetUsageCount(?User $resetBy = null): void
    {
        $oldCount = $this->current_uses;
        $this->update(['current_uses' => 0]);

        $this->logUsage([
            'action' => 'usage_reset',
            'old_count' => $oldCount,
            'reset_by' => $resetBy?->name ?? auth()->user()?->name,
        ]);
    }

    public function updatePermissions(array $permissions, ?User $updatedBy = null): void
    {
        $oldPermissions = $this->permissions;
        $this->update(['permissions' => $permissions]);

        $this->logUsage([
            'action' => 'permissions_updated',
            'old_permissions' => $oldPermissions,
            'new_permissions' => $permissions,
            'updated_by' => $updatedBy?->name ?? auth()->user()?->name,
        ]);
    }

    public function getUsageStatistics(): array
    {
        return [
            'total_uses' => $this->current_uses,
            'max_uses' => $this->max_uses,
            'usage_percentage' => $this->usage_percentage,
            'last_used' => $this->last_used_at,
            'time_since_last_use' => $this->time_since_last_use,
            'days_until_expiry' => $this->days_until_expiry,
            'hours_until_expiry' => $this->hours_until_expiry,
            'is_expired' => $this->is_expired,
            'is_valid' => $this->is_valid,
            'created_at' => $this->created_at,
            'usage_log_entries' => count($this->usage_log ?? []),
        ];
    }

    public function getEmergencyAccessUrl(): string
    {
        return route('emergency.access.form', ['accessKey' => $this->access_key]);
    }

    public function getDirectAccessUrl(): string
    {
        return route('emergency.access.direct', ['accessKey' => $this->access_key]);
    }

    public function getPrintableUrl(): string
    {
        return route('emergency.access.printable', ['accessKey' => $this->access_key]);
    }

    public function shouldSendNotifications(): bool
    {
        return $this->send_notifications && !empty($this->notification_recipients);
    }

    public function getNotificationRecipients(): array
    {
        return $this->notification_recipients ?? [];
    }

    public function isUsageLimitReached(): bool
    {
        return $this->max_uses && $this->current_uses >= $this->max_uses;
    }

    public function isNearingExpiry(int $hours = 24): bool
    {
        return $this->hours_until_expiry <= $hours && $this->hours_until_expiry > 0;
    }

    public function getAccessTypeLabel(): string
    {
        return match($this->access_type) {
            'emergency_only' => 'Emergency Only Access',
            'full_contacts' => 'Full Contact Information',
            'medical_info' => 'Medical Information Access',
            'custom' => 'Custom Permissions',
            default => 'Unknown'
        };
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'team_id', 'access_type', 'is_active', 'expires_at',
                'max_uses', 'current_uses', 'requires_reason',
                'send_notifications'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Team emergency access {$eventName}")
            ->dontLogIfAttributesChangedOnly(['last_used_at', 'current_uses']);
    }

    // Boot method to auto-generate access key
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($access) {
            if (empty($access->access_key)) {
                $access->access_key = static::generateSecureAccessKey();
            }
            
            if (empty($access->expires_at)) {
                $access->expires_at = now()->addYear();
            }
        });
    }
}