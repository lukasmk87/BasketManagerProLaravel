<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LocalizationService
{
    /**
     * The supported locales from configuration.
     */
    protected array $supportedLocales;

    /**
     * The default locale from configuration.
     */
    protected string $defaultLocale;

    /**
     * The locale detection order from configuration.
     */
    protected array $detectionOrder;

    /**
     * Create a new LocalizationService instance.
     */
    public function __construct()
    {
        $this->supportedLocales = config('localization.supported_locales', ['de', 'en']);
        $this->defaultLocale = config('localization.default_locale', 'de');
        $this->detectionOrder = config('localization.detection_order', [
            'url', 'session', 'cookie', 'user', 'browser', 'default'
        ]);
    }

    /**
     * Detect and set the appropriate locale for the current request.
     */
    public function detectAndSetLocale(Request $request): string
    {
        $locale = $this->detectLocale($request);
        $this->setLocale($locale);
        
        return $locale;
    }

    /**
     * Detect the appropriate locale based on the detection order.
     */
    public function detectLocale(Request $request): string
    {
        foreach ($this->detectionOrder as $method) {
            $locale = match ($method) {
                'url' => $this->detectFromUrl($request),
                'session' => $this->detectFromSession(),
                'cookie' => $this->detectFromCookie(),
                'user' => $this->detectFromUser(),
                'browser' => $this->detectFromBrowser($request),
                'default' => $this->defaultLocale,
                default => null,
            };

            if ($locale && $this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return $this->defaultLocale;
    }

    /**
     * Set the application locale and update related settings.
     */
    public function setLocale(string $locale): void
    {
        if (!$this->isValidLocale($locale)) {
            $locale = $this->defaultLocale;
        }

        // Set application locale
        App::setLocale($locale);

        // Set Carbon locale for date formatting
        Carbon::setLocale($locale);

        // Store in session for persistence
        Session::put('locale', $locale);

        // Update cookie for long-term persistence
        $this->setLocaleCookie($locale);
    }

    /**
     * Get the current application locale.
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Check if a locale is supported.
     */
    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales);
    }

    /**
     * Get all supported locales with their display names.
     */
    public function getSupportedLocales(): array
    {
        $locales = [];
        
        foreach ($this->supportedLocales as $locale) {
            $locales[$locale] = [
                'code' => $locale,
                'name' => config("localization.locale_names.{$locale}", $locale),
                'flag' => config("localization.locale_flags.{$locale}", ''),
                'is_current' => $locale === $this->getCurrentLocale(),
            ];
        }

        return $locales;
    }

    /**
     * Get basketball-specific translations for the current locale.
     */
    public function getBasketballTranslations(): array
    {
        return [
            'positions' => __('basketball.positions'),
            'categories' => __('basketball.categories'),
            'game_actions' => __('basketball.game_actions'),
            'game_statuses' => __('basketball.game_statuses'),
            'game_types' => __('basketball.game_types'),
            'player_statuses' => __('basketball.player_statuses'),
            'team_statuses' => __('basketball.team_statuses'),
            'statistics' => __('basketball.statistics'),
            'quarters' => __('basketball.quarters'),
            'emergency_relationships' => __('basketball.emergency_relationships'),
            'consent_types' => __('basketball.consent_types'),
        ];
    }

    /**
     * Get localized date format for the current locale.
     */
    public function getDateFormat(string $type = 'date'): string
    {
        $locale = $this->getCurrentLocale();
        return config("localization.date_formats.{$locale}.{$type}", 'd.m.Y');
    }

    /**
     * Get localized number format settings for the current locale.
     */
    public function getNumberFormat(): array
    {
        $locale = $this->getCurrentLocale();
        return config("localization.number_formats.{$locale}", [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_symbol' => '€',
            'currency_position' => 'after',
        ]);
    }

    /**
     * Format a number according to the current locale.
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
     * Format a currency amount according to the current locale.
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
     * Generate a localized URL for the given route.
     */
    public function localizedRoute(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?: $this->getCurrentLocale();
        
        if ($locale !== $this->defaultLocale || !config('localization.url.hide_default_locale')) {
            $parameters = array_merge(['locale' => $locale], $parameters);
        }

        return route($name, $parameters);
    }

    /**
     * Generate URLs for all locales for the current route.
     */
    public function getAlternateUrls(string $currentRoute, array $parameters = []): array
    {
        $urls = [];
        
        foreach ($this->supportedLocales as $locale) {
            $urls[$locale] = $this->localizedRoute($currentRoute, $parameters, $locale);
        }

        return $urls;
    }

    /**
     * Detect locale from URL segment.
     */
    protected function detectFromUrl(Request $request): ?string
    {
        $segments = $request->segments();
        
        if (empty($segments)) {
            return null;
        }

        $firstSegment = $segments[0];
        
        return $this->isValidLocale($firstSegment) ? $firstSegment : null;
    }

    /**
     * Detect locale from session.
     */
    protected function detectFromSession(): ?string
    {
        return Session::get('locale');
    }

    /**
     * Detect locale from cookie.
     */
    protected function detectFromCookie(): ?string
    {
        $cookieName = config('localization.cookie.name', 'basketmanager_locale');
        return Cookie::get($cookieName);
    }

    /**
     * Detect locale from authenticated user preferences.
     */
    protected function detectFromUser(): ?string
    {
        if (!auth()->check()) {
            return null;
        }

        return auth()->user()->language ?? null;
    }

    /**
     * Detect locale from browser Accept-Language header.
     */
    protected function detectFromBrowser(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $locales = [];
        
        preg_match_all('/([a-z]{2}(-[A-Z]{2})?)(;q=([0-9\.]+))?/', $acceptLanguage, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $locale = substr($matches[1][$i], 0, 2); // Get only language code (de, en, etc.)
            $quality = isset($matches[4][$i]) ? (float) $matches[4][$i] : 1.0;
            $locales[$locale] = $quality;
        }

        // Sort by quality
        arsort($locales);

        // Return first supported locale
        foreach (array_keys($locales) as $locale) {
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Set locale cookie.
     */
    protected function setLocaleCookie(string $locale): void
    {
        $config = config('localization.cookie');
        
        Cookie::queue(
            $config['name'],
            $locale,
            $config['expire'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['http_only']
        );
    }

    /**
     * Get missing translation keys (for development).
     */
    public function getMissingTranslations(): array
    {
        // This would typically be implemented with a custom translation loader
        // that tracks missing keys during development
        return [];
    }

    /**
     * Log missing translation (for development).
     */
    public function logMissingTranslation(string $key, string $locale): void
    {
        if (config('localization.missing_translation.log_missing', false)) {
            \Log::info("Missing translation: {$key} for locale: {$locale}");
        }
    }
}