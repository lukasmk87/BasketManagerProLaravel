<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Stripe\StripeClient;
use Stripe\HttpClient\CurlClient;

class StripeClientManager
{
    /**
     * Cache of Stripe clients indexed by tenant ID.
     *
     * @var array<string, StripeClient>
     */
    private array $clients = [];

    /**
     * Get Stripe client for the current tenant.
     */
    public function getCurrentTenantClient(): StripeClient
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            return $this->getDefaultClient();
        }
        
        return $this->getTenantClient($tenant);
    }

    /**
     * Get Stripe client for a specific tenant.
     */
    public function getTenantClient(Tenant $tenant): StripeClient
    {
        $tenantId = $tenant->id;
        
        if (isset($this->clients[$tenantId])) {
            return $this->clients[$tenantId];
        }
        
        // Create tenant-specific client
        $client = $this->createTenantClient($tenant);
        $this->clients[$tenantId] = $client;
        
        return $client;
    }

    /**
     * Get default Stripe client (platform-level).
     */
    public function getDefaultClient(): StripeClient
    {
        if (isset($this->clients['default'])) {
            return $this->clients['default'];
        }
        
        $client = $this->createDefaultClient();
        $this->clients['default'] = $client;
        
        return $client;
    }

    /**
     * Create Stripe client for a specific tenant.
     */
    private function createTenantClient(Tenant $tenant): StripeClient
    {
        $config = $this->getTenantStripeConfig($tenant);
        
        $clientConfig = [
            'api_key' => $config['secret_key'],
            'stripe_version' => config('stripe.api_version'),
        ];
        
        // Add Stripe Account header for Connect accounts
        if (isset($config['stripe_account'])) {
            $clientConfig['stripe_account'] = $config['stripe_account'];
        }
        
        $client = new StripeClient($clientConfig);
        
        // Configure HTTP client with tenant-specific settings
        $this->configureHttpClient($client, $tenant);
        
        return $client;
    }

    /**
     * Create default Stripe client.
     */
    private function createDefaultClient(): StripeClient
    {
        $clientConfig = [
            'api_key' => config('stripe.secret'),
            'stripe_version' => config('stripe.api_version'),
        ];
        
        $client = new StripeClient($clientConfig);
        
        // Configure HTTP client with default settings
        $this->configureHttpClient($client);
        
        return $client;
    }

    /**
     * Get Stripe configuration for a tenant.
     */
    private function getTenantStripeConfig(Tenant $tenant): array
    {
        $mode = config('stripe.multi_tenant.mode', 'shared');
        
        if ($mode === 'separate') {
            // Each tenant has their own Stripe account
            return $this->getSeparateTenantConfig($tenant);
        }
        
        // Shared Stripe account for all tenants
        return $this->getSharedTenantConfig($tenant);
    }

    /**
     * Get Stripe config for separate tenant mode.
     */
    private function getSeparateTenantConfig(Tenant $tenant): array
    {
        // Get tenant's Stripe account credentials
        $stripeSettings = $tenant->getSetting('stripe', []);
        
        return [
            'secret_key' => $stripeSettings['secret_key'] ?? config('stripe.secret'),
            'publishable_key' => $stripeSettings['publishable_key'] ?? config('stripe.api_key'),
            'stripe_account' => $stripeSettings['account_id'] ?? null,
            'webhook_secret' => $stripeSettings['webhook_secret'] ?? null,
        ];
    }

    /**
     * Get Stripe config for shared tenant mode.
     */
    private function getSharedTenantConfig(Tenant $tenant): array
    {
        return [
            'secret_key' => config('stripe.secret'),
            'publishable_key' => config('stripe.api_key'),
            'webhook_secret' => config('stripe.webhooks.signing_secret'),
        ];
    }

    /**
     * Configure HTTP client for Stripe.
     */
    private function configureHttpClient(StripeClient $client, ?Tenant $tenant = null): void
    {
        $curlOptions = [];
        
        // Set timeouts
        $curlOptions[CURLOPT_TIMEOUT] = config('stripe.performance.api_timeout', 30);
        $curlOptions[CURLOPT_CONNECTTIMEOUT] = config('stripe.performance.connect_timeout', 10);
        
        // Configure SSL/TLS
        $curlOptions[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
        
        // Configure proxy if needed
        if ($proxy = config('stripe.proxy')) {
            $curlOptions[CURLOPT_PROXY] = $proxy;
        }
        
        // Set custom CA bundle if specified
        if ($caBundle = config('stripe.ca_bundle_path')) {
            $curlOptions[CURLOPT_CAINFO] = $caBundle;
        }
        
        // Create and configure cURL client
        $httpClient = new CurlClient($curlOptions);
        
        // Set the HTTP client on the Stripe client
        $client->setHttpClient($httpClient);
        
        // Log requests if in development mode
        if (config('stripe.development.log_requests') && $tenant) {
            // Custom request logging could be added here
        }
    }

    /**
     * Get publishable key for current tenant.
     */
    public function getCurrentTenantPublishableKey(): string
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            return config('stripe.api_key');
        }
        
        return $this->getTenantPublishableKey($tenant);
    }

    /**
     * Get publishable key for a specific tenant.
     */
    public function getTenantPublishableKey(Tenant $tenant): string
    {
        $config = $this->getTenantStripeConfig($tenant);
        return $config['publishable_key'];
    }

    /**
     * Get webhook secret for current tenant.
     */
    public function getCurrentTenantWebhookSecret(): ?string
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            return config('stripe.webhooks.signing_secret');
        }
        
        return $this->getTenantWebhookSecret($tenant);
    }

    /**
     * Get webhook secret for a specific tenant.
     */
    public function getTenantWebhookSecret(Tenant $tenant): ?string
    {
        $config = $this->getTenantStripeConfig($tenant);
        return $config['webhook_secret'];
    }

    /**
     * Clear cached clients.
     */
    public function clearCache(?string $tenantId = null): void
    {
        if ($tenantId) {
            unset($this->clients[$tenantId]);
        } else {
            $this->clients = [];
        }
    }

    /**
     * Get customer ID with tenant prefix for shared mode.
     */
    public function getPrefixedCustomerId(string $customerId, ?Tenant $tenant = null): string
    {
        if (!config('stripe.multi_tenant.customer_prefix') || 
            config('stripe.multi_tenant.mode') !== 'shared') {
            return $customerId;
        }
        
        $tenant = $tenant ?: app('tenant');
        
        if (!$tenant) {
            return $customerId;
        }
        
        $prefix = substr($tenant->id, 0, 8); // First 8 chars of tenant UUID
        
        // Check if already prefixed
        if (str_starts_with($customerId, $prefix . '_')) {
            return $customerId;
        }
        
        return $prefix . '_' . $customerId;
    }

    /**
     * Remove tenant prefix from customer ID.
     */
    public function removePrefixFromCustomerId(string $customerId): string
    {
        if (!config('stripe.multi_tenant.customer_prefix')) {
            return $customerId;
        }
        
        // Remove prefix pattern: 8chars_
        if (preg_match('/^[a-f0-9]{8}_(.+)$/', $customerId, $matches)) {
            return $matches[1];
        }
        
        return $customerId;
    }

    /**
     * Check if tenant has valid Stripe configuration.
     */
    public function validateTenantConfig(Tenant $tenant): bool
    {
        $config = $this->getTenantStripeConfig($tenant);
        
        // Check required keys
        if (empty($config['secret_key']) || empty($config['publishable_key'])) {
            return false;
        }
        
        // Validate key format
        $secretKey = $config['secret_key'];
        $publishableKey = $config['publishable_key'];
        
        // Check test/live key consistency
        $isTestSecret = str_starts_with($secretKey, 'sk_test_');
        $isTestPublishable = str_starts_with($publishableKey, 'pk_test_');
        
        return $isTestSecret === $isTestPublishable;
    }
}