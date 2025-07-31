<?php

namespace App\Traits;

use App\Services\LocalizationService;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasLocalePreference
{
    /**
     * Get the user's preferred locale or fall back to system default.
     */
    public function preferredLocale(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->language ?: config('localization.default_locale', 'de')
        );
    }

    /**
     * Set the user's preferred locale.
     */
    public function setPreferredLocale(string $locale): void
    {
        $localizationService = app(LocalizationService::class);
        
        if ($localizationService->isValidLocale($locale)) {
            $this->update(['language' => $locale]);
        }
    }

    /**
     * Get the user's timezone preference.
     */
    public function preferredTimezone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->timezone ?: config('app.timezone', 'Europe/Berlin')
        );
    }

    /**
     * Set the user's preferred timezone.
     */
    public function setPreferredTimezone(string $timezone): void
    {
        if (in_array($timezone, timezone_identifiers_list())) {
            $this->update(['timezone' => $timezone]);
        }
    }

    /**
     * Get localized date format for this user.
     */
    public function getDateFormat(string $type = 'date'): string
    {
        $locale = $this->preferred_locale;
        return config("localization.date_formats.{$locale}.{$type}", 'd.m.Y');
    }

    /**
     * Get localized number format for this user.
     */
    public function getNumberFormat(): array
    {
        $locale = $this->preferred_locale;
        return config("localization.number_formats.{$locale}", [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_symbol' => '€',
            'currency_position' => 'after',
        ]);
    }

    /**
     * Format a number according to the user's locale preference.
     */
    public function formatNumber(float $number, int $decimals = 2): string
    {
        $format = $this->getNumberFormat();
        
        return number_format(
            $number,
            $decimals,
            $format['decimal_separator'],
            $format['thousands_separator']
        );
    }

    /**
     * Format a currency amount according to the user's locale preference.
     */
    public function formatCurrency(float $amount, int $decimals = 2): string
    {
        $format = $this->getNumberFormat();
        $formattedAmount = $this->formatNumber($amount, $decimals);
        
        return $format['currency_position'] === 'before'
            ? $format['currency_symbol'] . ' ' . $formattedAmount
            : $formattedAmount . ' ' . $format['currency_symbol'];
    }

    /**
     * Format a date according to the user's locale and timezone preferences.
     */
    public function formatDate(\DateTimeInterface $date, string $type = 'date'): string
    {
        $format = $this->getDateFormat($type);
        $timezone = $this->preferred_timezone;
        
        return $date->setTimezone(new \DateTimeZone($timezone))->format($format);
    }

    /**
     * Get available locales for this user.
     */
    public function getAvailableLocales(): array
    {
        $localizationService = app(LocalizationService::class);
        return $localizationService->getSupportedLocales();
    }

    /**
     * Check if the user has a specific locale preference.
     */
    public function hasLocalePreference(?string $locale = null): bool
    {
        if ($locale === null) {
            return !empty($this->language);
        }

        return $this->preferred_locale === $locale;
    }

    /**
     * Get basketball-specific translations in the user's preferred locale.
     */
    public function getBasketballTranslations(): array
    {
        // Temporarily set the application locale to user's preference
        $currentLocale = app()->getLocale();
        app()->setLocale($this->preferred_locale);
        
        $translations = [
            'positions' => __('basketball.positions'),
            'categories' => __('basketball.categories'),
            'game_actions' => __('basketball.game_actions'),
            'game_statuses' => __('basketball.game_statuses'),
            'statistics' => __('basketball.statistics'),
            'emergency_relationships' => __('basketball.emergency_relationships'),
        ];
        
        // Restore original locale
        app()->setLocale($currentLocale);
        
        return $translations;
    }

    /**
     * Get notification preferences with locale consideration.
     */
    public function getNotificationPreferences(): array
    {
        $preferences = $this->notification_settings ?? [];
        
        // Add locale-specific defaults
        return array_merge([
            'email_locale' => $this->preferred_locale,
            'date_format' => $this->getDateFormat('datetime'),
            'timezone' => $this->preferred_timezone,
        ], $preferences);
    }

    /**
     * Update notification preferences with locale settings.
     */
    public function updateNotificationPreferences(array $preferences): void
    {
        $currentPreferences = $this->notification_settings ?? [];
        $updatedPreferences = array_merge($currentPreferences, $preferences);
        
        $this->update(['notification_settings' => $updatedPreferences]);
    }
}