<?php

namespace App\Services\User;

use App\Models\ApiUsageTracking;
use App\Models\RateLimitException;
use App\Models\Subscription;
use App\Models\User;

/**
 * Service für User-API-Management und Subscription-Handling.
 *
 * Extrahiert aus dem User Model zur Reduzierung der Model-Komplexität.
 * Bietet zentrale Methoden für API-Key-Verwaltung, Rate Limiting und Subscriptions.
 */
class UserApiService
{
    /**
     * Liefert die aktuelle Subscription des Users oder erstellt eine Free-Subscription.
     */
    public function getSubscription(User $user): Subscription
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            $subscription = $user->subscription()->create([
                'tier' => 'free',
                'plan_name' => 'Free Plan',
                'status' => 'active',
                'api_requests_limit' => 1000,
                'burst_limit' => 100,
                'concurrent_requests_limit' => 10,
            ]);
        }

        return $subscription;
    }

    /**
     * Liefert die effektiven API-Rate-Limits für den User.
     *
     * Berücksichtigt Basis-Limits und aktive Rate-Limit-Exceptions.
     *
     * @return array{requests_per_hour: int, burst_limit: int, concurrent_requests_limit: int}
     */
    public function getApiLimits(User $user): array
    {
        $subscription = $this->getSubscription($user);
        $baseLimits = $subscription->getApiLimits();

        // Prüfe auf aktive Rate-Limit-Exceptions
        $exceptions = RateLimitException::findActiveFor('user', $user->id);

        foreach ($exceptions as $exception) {
            $baseLimits = $exception->getEffectiveLimits($baseLimits);
        }

        return $baseLimits;
    }

    /**
     * Prüft ob der User sein API-Kontingent überschritten hat.
     */
    public function hasExceededApiQuota(User $user): bool
    {
        $limits = $this->getApiLimits($user);
        $usage = $this->getCurrentApiUsage($user);

        return $usage['total_requests'] >= $limits['requests_per_hour'];
    }

    /**
     * Liefert die aktuelle API-Nutzung des Users.
     *
     * @return array{total_requests: int, window_start: string, window_end: string}
     */
    public function getCurrentApiUsage(User $user): array
    {
        return ApiUsageTracking::getCurrentWindowUsage($user->id, null, 'hourly');
    }

    /**
     * Setzt das API-Kontingent zurück (wird stündlich aufgerufen).
     */
    public function resetApiQuota(User $user): void
    {
        $user->current_api_usage = 0;
        $user->api_quota_reset_at = now()->addHour();
        $user->save();
    }

    /**
     * Generiert einen neuen API-Key für den User.
     *
     * @return string Der generierte API-Key (nur einmal sichtbar!)
     */
    public function generateApiKey(User $user): string
    {
        $apiKey = 'bmp_' . bin2hex(random_bytes(20)); // BasketManager Pro prefix
        $user->api_key_hash = hash('sha256', $apiKey);
        $user->api_access_enabled = true;
        $user->save();

        return $apiKey;
    }

    /**
     * Verifiziert einen API-Key.
     */
    public function verifyApiKey(User $user, string $apiKey): bool
    {
        if (!$user->api_access_enabled) {
            return false;
        }

        $hashedKey = hash('sha256', $apiKey);
        return $user->api_key_hash === $hashedKey;
    }

    /**
     * Aktualisiert den Timestamp der letzten API-Key-Verwendung.
     */
    public function updateApiKeyUsage(User $user): void
    {
        $user->api_key_last_used_at = now();
        $user->save();
    }

    /**
     * Widerruft den API-Key des Users.
     */
    public function revokeApiKey(User $user): void
    {
        $user->api_key_hash = null;
        $user->api_access_enabled = false;
        $user->api_key_last_used_at = null;
        $user->save();
    }

    /**
     * Liefert API-Nutzungsstatistiken für einen Zeitraum.
     *
     * @param string $period 'today', 'yesterday', 'last_7_days', 'last_30_days', 'current_month', 'last_month'
     * @return array<string, mixed>
     */
    public function getApiUsageStats(User $user, string $period = 'last_30_days'): array
    {
        $start = match($period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'current_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };

        $end = match($period) {
            'yesterday' => now()->subDay()->endOfDay(),
            'last_month' => now()->subMonth()->endOfMonth(),
            default => now(),
        };

        return ApiUsageTracking::getUserUsageSummary($user->id, $start, $end);
    }

    /**
     * Prüft ob der User Zugriff auf ein bestimmtes API-Feature hat.
     */
    public function canAccessApiFeature(User $user, string $feature): bool
    {
        $subscription = $this->getSubscription($user);
        return $subscription->hasFeature($feature);
    }

    /**
     * Liefert den Anzeigenamen der Subscription-Stufe.
     */
    public function getSubscriptionTierName(User $user): string
    {
        return match($user->subscription_tier) {
            'free' => 'Free',
            'basic' => 'Basic',
            'premium' => 'Premium',
            'enterprise' => 'Enterprise',
            'unlimited' => 'Unlimited',
            default => 'Free',
        };
    }

    /**
     * Prüft ob der User Premium-Tier oder höher hat.
     */
    public function isPremiumUser(User $user): bool
    {
        return in_array($user->subscription_tier, ['premium', 'enterprise', 'unlimited']);
    }

    /**
     * Prüft ob der User Enterprise-Tier oder höher hat.
     */
    public function isEnterpriseUser(User $user): bool
    {
        return in_array($user->subscription_tier, ['enterprise', 'unlimited']);
    }

    /**
     * Prüft ob der User Unlimited-Tier hat.
     */
    public function isUnlimitedUser(User $user): bool
    {
        return $user->subscription_tier === 'unlimited';
    }

    /**
     * Berechnet Überschreitungskosten für die aktuelle Nutzung.
     */
    public function calculateOverageCosts(User $user): float
    {
        $limits = $this->getApiLimits($user);
        $usage = $this->getCurrentApiUsage($user);

        $excessRequests = max(0, $usage['total_requests'] - $limits['requests_per_hour']);

        if ($excessRequests > 0) {
            $subscription = $this->getSubscription($user);
            return $subscription->calculateOverageCost($excessRequests);
        }

        return 0.0;
    }

    /**
     * Prüft ob API-Zugriff aktiviert ist.
     */
    public function isApiAccessEnabled(User $user): bool
    {
        return (bool) $user->api_access_enabled;
    }

    /**
     * Aktiviert API-Zugriff für den User.
     */
    public function enableApiAccess(User $user): void
    {
        $user->api_access_enabled = true;
        $user->save();
    }

    /**
     * Deaktiviert API-Zugriff für den User.
     */
    public function disableApiAccess(User $user): void
    {
        $user->api_access_enabled = false;
        $user->save();
    }

    /**
     * Liefert die verbleibenden API-Requests bis zum Limit.
     */
    public function getRemainingApiRequests(User $user): int
    {
        $limits = $this->getApiLimits($user);
        $usage = $this->getCurrentApiUsage($user);

        return max(0, $limits['requests_per_hour'] - $usage['total_requests']);
    }

    /**
     * Liefert den Prozentsatz der API-Quota-Nutzung.
     */
    public function getApiQuotaUsagePercentage(User $user): float
    {
        $limits = $this->getApiLimits($user);
        $usage = $this->getCurrentApiUsage($user);

        if ($limits['requests_per_hour'] === 0) {
            return 0.0;
        }

        return min(100.0, ($usage['total_requests'] / $limits['requests_per_hour']) * 100);
    }

    /**
     * Prüft ob der User kurz vor dem Limit ist (> 80% Nutzung).
     */
    public function isApproachingApiLimit(User $user, float $threshold = 80.0): bool
    {
        return $this->getApiQuotaUsagePercentage($user) >= $threshold;
    }

    /**
     * Aktualisiert die Subscription-Stufe des Users.
     */
    public function updateSubscriptionTier(User $user, string $tier): void
    {
        $user->subscription_tier = $tier;
        $user->save();

        // Subscription-Record auch aktualisieren
        if ($user->subscription) {
            $user->subscription->update(['tier' => $tier]);
        }
    }

    /**
     * Liefert Informationen zum API-Key-Status.
     *
     * @return array{has_key: bool, enabled: bool, last_used_at: ?string}
     */
    public function getApiKeyStatus(User $user): array
    {
        return [
            'has_key' => !empty($user->api_key_hash),
            'enabled' => (bool) $user->api_access_enabled,
            'last_used_at' => $user->api_key_last_used_at?->toIso8601String(),
        ];
    }

    /**
     * Rotiert den API-Key (widerruft alten, generiert neuen).
     *
     * @return string Der neue API-Key
     */
    public function rotateApiKey(User $user): string
    {
        $this->revokeApiKey($user);
        return $this->generateApiKey($user);
    }
}
