<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    protected const CACHE_KEY = 'system_settings';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->getAllCached();

        return $settings[$key]['typed_value'] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting) {
            $setting->value = $this->convertValueToString($value, $setting->type);
            $setting->save();
        } else {
            SystemSetting::create([
                'key' => $key,
                'value' => $this->convertValueToString($value, $type ?? 'string'),
                'type' => $type ?? 'string',
                'group' => $this->getGroupFromKey($key),
            ]);
        }

        $this->clearCache();
    }

    /**
     * Get all settings from cache or database.
     */
    public function getAllCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::all()
                ->keyBy('key')
                ->map(fn ($s) => [
                    'value' => $s->value,
                    'typed_value' => $s->typed_value,
                    'type' => $s->type,
                    'group' => $s->group,
                ])
                ->toArray();
        });
    }

    /**
     * Get all settings for a specific group.
     */
    public function getGroup(string $group): array
    {
        $settings = $this->getAllCached();

        return array_filter($settings, fn ($s) => $s['group'] === $group);
    }

    /**
     * Clear the settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if the operator is a small business (Kleinunternehmer).
     */
    public function isSmallBusiness(): bool
    {
        return $this->get('pricing.is_small_business', false);
    }

    /**
     * Check if prices should be displayed gross (including VAT).
     */
    public function displayPricesGross(): bool
    {
        return $this->get('pricing.display_mode', 'gross') === 'gross';
    }

    /**
     * Get the default tax rate.
     */
    public function getDefaultTaxRate(): float
    {
        return (float) $this->get('pricing.default_tax_rate', 19.00);
    }

    /**
     * Get all pricing settings as an array.
     */
    public function getPricingSettings(): array
    {
        return [
            'display_mode' => $this->get('pricing.display_mode', 'gross'),
            'is_small_business' => $this->isSmallBusiness(),
            'default_tax_rate' => $this->getDefaultTaxRate(),
        ];
    }

    /**
     * Update all pricing settings at once.
     */
    public function updatePricingSettings(array $data): void
    {
        if (isset($data['display_mode'])) {
            $this->set('pricing.display_mode', $data['display_mode'], 'string');
        }

        if (isset($data['is_small_business'])) {
            $this->set('pricing.is_small_business', $data['is_small_business'], 'boolean');
        }

        if (isset($data['default_tax_rate'])) {
            $this->set('pricing.default_tax_rate', $data['default_tax_rate'], 'decimal');
        }
    }

    /**
     * Convert a value to string based on its type.
     */
    protected function convertValueToString(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Extract group from key (e.g., 'pricing.display_mode' -> 'pricing').
     */
    protected function getGroupFromKey(string $key): string
    {
        $parts = explode('.', $key);

        return $parts[0] ?? 'general';
    }
}
