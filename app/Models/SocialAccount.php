<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SocialAccount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'provider_expires_at',
        'provider_data',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'provider_expires_at' => 'datetime',
        'provider_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'provider_token',
        'provider_refresh_token',
    ];

    /**
     * Supported social providers.
     */
    public static array $supportedProviders = [
        'google',
        'facebook',
        'github',
        'twitter',
        'linkedin',
        'apple',
    ];

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the user that owns the social account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active social accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to find by provider and provider ID.
     */
    public function scopeByProviderAndId($query, string $provider, string $providerId)
    {
        return $query->where('provider', $provider)
                    ->where('provider_id', $providerId);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the provider display name.
     */
    public function getProviderDisplayNameAttribute(): string
    {
        $displayNames = [
            'google' => 'Google',
            'facebook' => 'Facebook',
            'github' => 'GitHub',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'apple' => 'Apple',
        ];

        return $displayNames[$this->provider] ?? ucfirst($this->provider);
    }

    /**
     * Get the provider icon class.
     */
    public function getProviderIconAttribute(): string
    {
        $icons = [
            'google' => 'fab fa-google',
            'facebook' => 'fab fa-facebook-f',
            'github' => 'fab fa-github',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin-in',
            'apple' => 'fab fa-apple',
        ];

        return $icons[$this->provider] ?? 'fas fa-link';
    }

    /**
     * Get the provider color for UI.
     */
    public function getProviderColorAttribute(): string
    {
        $colors = [
            'google' => '#db4437',
            'facebook' => '#4267B2',
            'github' => '#333',
            'twitter' => '#1DA1F2',
            'linkedin' => '#0077B5',
            'apple' => '#000',
        ];

        return $colors[$this->provider] ?? '#6B7280';
    }

    /**
     * Check if the token is expired.
     */
    public function getIsTokenExpiredAttribute(): bool
    {
        if (!$this->provider_expires_at) {
            return false;
        }

        return now()->isAfter($this->provider_expires_at);
    }

    /**
     * Get the user's avatar from provider data.
     */
    public function getProviderAvatarAttribute(): ?string
    {
        if (!$this->provider_data) {
            return null;
        }

        $avatarKeys = ['avatar', 'avatar_url', 'picture', 'profile_image_url'];
        
        foreach ($avatarKeys as $key) {
            if (isset($this->provider_data[$key])) {
                return $this->provider_data[$key];
            }
        }

        return null;
    }

    /**
     * Get the user's name from provider data.
     */
    public function getProviderNameAttribute(): ?string
    {
        if (!$this->provider_data) {
            return null;
        }

        return $this->provider_data['name'] ?? $this->provider_data['display_name'] ?? null;
    }

    /**
     * Get the user's email from provider data.
     */
    public function getProviderEmailAttribute(): ?string
    {
        if (!$this->provider_data) {
            return null;
        }

        return $this->provider_data['email'] ?? null;
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if the provider is supported.
     */
    public static function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::$supportedProviders);
    }

    /**
     * Get all supported providers with their display names.
     */
    public static function getSupportedProviders(): array
    {
        $providers = [];
        
        foreach (self::$supportedProviders as $provider) {
            $providers[$provider] = [
                'name' => ucfirst($provider),
                'enabled' => config("services.{$provider}.client_id") !== null,
            ];
        }

        return $providers;
    }

    /**
     * Update provider data with fresh information.
     */
    public function updateProviderData(array $data): void
    {
        $this->update([
            'provider_data' => array_merge($this->provider_data ?? [], $data),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update provider tokens.
     */
    public function updateTokens(string $token, ?string $refreshToken = null, ?\DateTimeInterface $expiresAt = null): void
    {
        $this->update([
            'provider_token' => $token,
            'provider_refresh_token' => $refreshToken,
            'provider_expires_at' => $expiresAt,
            'updated_at' => now(),
        ]);
    }

    /**
     * Deactivate the social account.
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'provider_token' => null,
            'provider_refresh_token' => null,
            'provider_expires_at' => null,
        ]);
    }

    /**
     * Reactivate the social account.
     */
    public function reactivate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Check if account needs token refresh.
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->provider_expires_at || !$this->provider_refresh_token) {
            return false;
        }

        // Refresh if expires within next 5 minutes
        return now()->addMinutes(5)->isAfter($this->provider_expires_at);
    }

    /**
     * Get login statistics for this social account.
     */
    public function getLoginStats(): array
    {
        // This would typically be implemented with a login tracking system
        return [
            'total_logins' => 0,
            'last_login' => $this->updated_at,
            'first_login' => $this->created_at,
        ];
    }

    /**
     * Create a social account from socialite user data.
     */
    public static function createFromSocialite(User $user, string $provider, $socialiteUser): self
    {
        return self::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken,
            'provider_expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
            'provider_data' => [
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'avatar' => $socialiteUser->getAvatar(),
                'raw' => $socialiteUser->getRaw(),
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Find or create a social account from socialite user data.
     */
    public static function findOrCreateFromSocialite(User $user, string $provider, $socialiteUser): self
    {
        $socialAccount = self::byProviderAndId($provider, $socialiteUser->getId())->first();

        if ($socialAccount) {
            // Update existing account
            $socialAccount->updateTokens(
                $socialiteUser->token,
                $socialiteUser->refreshToken,
                $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null
            );

            $socialAccount->updateProviderData([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'avatar' => $socialiteUser->getAvatar(),
                'raw' => $socialiteUser->getRaw(),
            ]);

            if (!$socialAccount->is_active) {
                $socialAccount->reactivate();
            }

            return $socialAccount;
        }

        return self::createFromSocialite($user, $provider, $socialiteUser);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['provider', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}