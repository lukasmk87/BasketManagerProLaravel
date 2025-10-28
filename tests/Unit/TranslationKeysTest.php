<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Lang;

class TranslationKeysTest extends TestCase
{
    /**
     * Test that all subscription keys exist in German.
     */
    public function test_all_subscription_keys_exist_in_german(): void
    {
        $locale = 'de';
        $translations = Lang::get('subscription', [], $locale);

        $this->assertIsArray($translations, "Subscription translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Subscription translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'subscription.title',
            'subscription.plans.available',
            'subscription.billing.monthly',
            'subscription.billing.yearly',
            'subscription.swap.title',
            'subscription.swap.confirm',
            'subscription.swap.confirming',
            'subscription.swap.details_count',
            'subscription.swap.description',
            'subscription.swap.period',
            'subscription.swap.amount',
            'subscription.swap.total',
            'subscription.swap.proration',
            'subscription.swap.next_billing.title',
            'subscription.swap.next_billing.monthly',
            'subscription.swap.next_billing.yearly',
            'subscription.swap.important_notes.title',
            'subscription.swap.important_notes.list.immediate',
            'subscription.swap.important_notes.list.refund',
            'subscription.swap.important_notes.list.payment',
            'subscription.swap.important_notes.list.next_billing',
            'subscription.swap.important_notes.list.can_change',
            'subscription.common.cancel',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that all subscription keys exist in English.
     */
    public function test_all_subscription_keys_exist_in_english(): void
    {
        $locale = 'en';
        $translations = Lang::get('subscription', [], $locale);

        $this->assertIsArray($translations, "Subscription translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Subscription translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'subscription.title',
            'subscription.plans.available',
            'subscription.billing.monthly',
            'subscription.billing.yearly',
            'subscription.swap.title',
            'subscription.swap.confirm',
            'subscription.swap.confirming',
            'subscription.swap.details_count',
            'subscription.swap.description',
            'subscription.swap.period',
            'subscription.swap.amount',
            'subscription.swap.total',
            'subscription.swap.proration',
            'subscription.swap.next_billing.title',
            'subscription.swap.next_billing.monthly',
            'subscription.swap.next_billing.yearly',
            'subscription.swap.important_notes.title',
            'subscription.swap.important_notes.list.immediate',
            'subscription.swap.important_notes.list.refund',
            'subscription.swap.important_notes.list.payment',
            'subscription.swap.important_notes.list.next_billing',
            'subscription.swap.important_notes.list.can_change',
            'subscription.common.cancel',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that all billing keys exist in German.
     */
    public function test_all_billing_keys_exist_in_german(): void
    {
        $locale = 'de';
        $translations = Lang::get('billing', [], $locale);

        $this->assertIsArray($translations, "Billing translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Billing translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'billing.title',
            'billing.invoices.title',
            'billing.payment_methods.title',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that all billing keys exist in English.
     */
    public function test_all_billing_keys_exist_in_english(): void
    {
        $locale = 'en';
        $translations = Lang::get('billing', [], $locale);

        $this->assertIsArray($translations, "Billing translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Billing translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'billing.title',
            'billing.invoices.title',
            'billing.payment_methods.title',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that all checkout keys exist in German.
     */
    public function test_all_checkout_keys_exist_in_german(): void
    {
        $locale = 'de';
        $translations = Lang::get('checkout', [], $locale);

        $this->assertIsArray($translations, "Checkout translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Checkout translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'checkout.success.title',
            'checkout.success.congratulations',
            'checkout.cancel.title',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that all checkout keys exist in English.
     */
    public function test_all_checkout_keys_exist_in_english(): void
    {
        $locale = 'en';
        $translations = Lang::get('checkout', [], $locale);

        $this->assertIsArray($translations, "Checkout translations should be an array for locale: {$locale}");
        $this->assertNotEmpty($translations, "Checkout translations should not be empty for locale: {$locale}");

        // Test critical keys exist
        $criticalKeys = [
            'checkout.success.title',
            'checkout.success.congratulations',
            'checkout.cancel.title',
        ];

        foreach ($criticalKeys as $key) {
            $value = __($key, [], $locale);
            $this->assertNotEquals($key, $value, "Translation key '{$key}' should exist for locale: {$locale}");
            $this->assertIsString($value, "Translation value for '{$key}' should be a string for locale: {$locale}");
            $this->assertNotEmpty($value, "Translation value for '{$key}' should not be empty for locale: {$locale}");
        }
    }

    /**
     * Test that translation keys have parity between DE and EN locales.
     */
    public function test_translation_key_parity_between_locales(): void
    {
        $files = ['subscription', 'billing', 'checkout'];

        foreach ($files as $file) {
            $deTranslations = Lang::get($file, [], 'de');
            $enTranslations = Lang::get($file, [], 'en');

            $this->assertIsArray($deTranslations, "German translations should be an array for file: {$file}");
            $this->assertIsArray($enTranslations, "English translations should be an array for file: {$file}");

            $deKeys = $this->flattenArray($deTranslations, $file);
            $enKeys = $this->flattenArray($enTranslations, $file);

            $deOnlyKeys = array_diff($deKeys, $enKeys);
            $enOnlyKeys = array_diff($enKeys, $deKeys);

            $this->assertEmpty(
                $deOnlyKeys,
                "Keys exist in DE but missing in EN for {$file}: " . implode(', ', $deOnlyKeys)
            );

            $this->assertEmpty(
                $enOnlyKeys,
                "Keys exist in EN but missing in DE for {$file}: " . implode(', ', $enOnlyKeys)
            );
        }
    }

    /**
     * Flatten a nested array to get all keys.
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $fullKey));
            } else {
                $result[] = $fullKey;
            }
        }

        return $result;
    }
}
