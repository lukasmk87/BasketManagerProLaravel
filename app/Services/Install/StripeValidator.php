<?php

namespace App\Services\Install;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;

class StripeValidator
{
    /**
     * Validate Stripe API keys
     *
     * @param  string  $publishableKey  Stripe publishable key (pk_...)
     * @param  string  $secretKey  Stripe secret key (sk_...)
     * @return array{valid: bool, message: string|null, account_name: string|null, errors: array}
     */
    public function validateKeys(string $publishableKey, string $secretKey): array
    {
        $errors = [];

        // Validate key format
        if (! str_starts_with($publishableKey, 'pk_')) {
            $errors[] = 'Publishable key must start with "pk_"';
        }

        if (! str_starts_with($secretKey, 'sk_')) {
            $errors[] = 'Secret key must start with "sk_"';
        }

        if (! empty($errors)) {
            return [
                'valid' => false,
                'message' => 'Invalid Stripe key format',
                'account_name' => null,
                'errors' => $errors,
            ];
        }

        // Check if keys match (both test or both live)
        $publishableIsTest = str_starts_with($publishableKey, 'pk_test_');
        $secretIsTest = str_starts_with($secretKey, 'sk_test_');

        if ($publishableIsTest !== $secretIsTest) {
            return [
                'valid' => false,
                'message' => 'Publishable key and secret key must both be test keys or both be live keys',
                'account_name' => null,
                'errors' => ['Keys do not match (test vs live)'],
            ];
        }

        // Test API connection
        try {
            Stripe::setApiKey($secretKey);

            // Retrieve account information
            $account = Account::retrieve();

            $accountName = $account->business_profile->name ?? $account->email ?? 'Unknown';
            $mode = $secretIsTest ? 'Test Mode' : 'Live Mode';

            return [
                'valid' => true,
                'message' => "Connected to Stripe account: {$accountName} ({$mode})",
                'account_name' => $accountName,
                'mode' => $mode,
                'errors' => [],
            ];
        } catch (ApiErrorException $e) {
            return [
                'valid' => false,
                'message' => 'Stripe API error: '.$e->getMessage(),
                'account_name' => null,
                'errors' => [$e->getMessage()],
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error connecting to Stripe: '.$e->getMessage(),
                'account_name' => null,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Test webhook endpoint configuration
     */
    public function testWebhookEndpoint(string $url, string $secret): array
    {
        // Validate webhook secret format
        if (! str_starts_with($secret, 'whsec_')) {
            return [
                'valid' => false,
                'message' => 'Webhook secret must start with "whsec_"',
                'errors' => ['Invalid webhook secret format'],
            ];
        }

        // Validate URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'message' => 'Invalid webhook URL format',
                'errors' => ['URL is not valid'],
            ];
        }

        // Check if URL is accessible (optional, might fail in local dev)
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);

            $headers = @get_headers($url, false, $context);

            if ($headers === false) {
                return [
                    'valid' => true, // Don't fail, just warn
                    'message' => 'Webhook secret format is valid, but URL could not be reached (may be normal for local development)',
                    'warning' => true,
                    'errors' => [],
                ];
            }

            return [
                'valid' => true,
                'message' => 'Webhook configuration is valid',
                'errors' => [],
            ];
        } catch (\Exception $e) {
            return [
                'valid' => true, // Don't fail, just warn
                'message' => 'Webhook secret format is valid, but URL test failed: '.$e->getMessage(),
                'warning' => true,
                'errors' => [],
            ];
        }
    }
}
