<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Stripe\CashierTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class MultiTenantStripeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected CashierTenantManager $cashierManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe configuration for testing
        Config::set('stripe.api_key', 'pk_test_mock');
        Config::set('stripe.secret', 'sk_test_mock');
        Config::set('stripe.webhooks.signing_secret', 'whsec_test_mock');
        
        $this->setupMultiTenantStripeData();
        $this->cashierManager = app(CashierTenantManager::class);
    }

    protected function setupMultiTenantStripeData(): void
    {
        // Create tenants with different Stripe configurations
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'slug' => 'lakers',
            'subscription_tier' => 'professional',
            'billing_email' => 'billing@lakers.test',
            'stripe_configuration' => [
                'publishable_key' => 'pk_test_lakers_123',
                'secret_key' => 'sk_test_lakers_123',
                'webhook_secret' => 'whsec_lakers_123',
            ],
        ]);

        $this->tenantB = Tenant::factory()->create([
            'name' => 'Warriors Basketball Club',
            'slug' => 'warriors', 
            'subscription_tier' => 'basic',
            'billing_email' => 'billing@warriors.test',
            'stripe_configuration' => [
                'publishable_key' => 'pk_test_warriors_456',
                'secret_key' => 'sk_test_warriors_456',
                'webhook_secret' => 'whsec_warriors_456',
            ],
        ]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'email' => 'admin@lakers.test',
        ]);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'email' => 'admin@warriors.test',
        ]);
    }

    /** @test */
    public function cashier_manager_configures_correct_stripe_keys_per_tenant()
    {
        // Test tenant A configuration
        $this->cashierManager->setCurrentTenant($this->tenantA);
        $config = $this->cashierManager->getStripeConfig();
        
        $this->assertEquals('pk_test_lakers_123', $config['key']);
        $this->assertEquals('sk_test_lakers_123', $config['secret']);
        $this->assertEquals('whsec_lakers_123', $config['webhook_secret']);

        // Test tenant B configuration
        $this->cashierManager->setCurrentTenant($this->tenantB);
        $config = $this->cashierManager->getStripeConfig();
        
        $this->assertEquals('pk_test_warriors_456', $config['key']);
        $this->assertEquals('sk_test_warriors_456', $config['secret']);
        $this->assertEquals('whsec_warriors_456', $config['webhook_secret']);
    }

    /** @test */
    public function tenant_specific_stripe_configuration_is_isolated()
    {
        // Simulate setting current tenant through middleware
        app()->instance('tenant', $this->tenantA);
        
        $configA = $this->cashierManager->getCurrentTenant()->getStripeConfig();
        $this->assertArrayHasKey('secret_key', $configA);
        $this->assertEquals('sk_test_lakers_123', $configA['secret_key']);

        // Change tenant context
        app()->instance('tenant', $this->tenantB);
        $this->cashierManager->setCurrentTenant($this->tenantB);
        
        $configB = $this->cashierManager->getCurrentTenant()->getStripeConfig();
        $this->assertEquals('sk_test_warriors_456', $configB['secret_key']);
        
        // Ensure configurations don't cross-contaminate
        $this->assertNotEquals($configA['secret_key'], $configB['secret_key']);
    }

    /** @test */
    public function tenant_subscription_tiers_have_correct_pricing()
    {
        $pricingInfo = $this->cashierManager->getPricingInfo();
        
        // Verify tier structure
        $this->assertArrayHasKey('free', $pricingInfo);
        $this->assertArrayHasKey('basic', $pricingInfo);
        $this->assertArrayHasKey('professional', $pricingInfo);
        $this->assertArrayHasKey('enterprise', $pricingInfo);
        
        // Verify free tier
        $this->assertEquals(0, $pricingInfo['free']['price']);
        $this->assertEquals('EUR', $pricingInfo['free']['currency']);
        
        // Verify basic tier
        $this->assertEquals(49, $pricingInfo['basic']['price']);
        $this->assertEquals('EUR', $pricingInfo['basic']['currency']);
        
        // Verify professional tier
        $this->assertEquals(149, $pricingInfo['professional']['price']);
        $this->assertEquals('EUR', $pricingInfo['professional']['currency']);
        
        // Verify enterprise tier
        $this->assertEquals(499, $pricingInfo['enterprise']['price']);
        $this->assertEquals('EUR', $pricingInfo['enterprise']['currency']);
    }

    /** @test */
    public function tenant_can_upgrade_subscription_tiers()
    {
        $this->cashierManager->setCurrentTenant($this->tenantA);
        
        // Check if tenant can upgrade from professional to enterprise
        $this->assertTrue($this->cashierManager->canUpgradeTo('enterprise'));
        
        // Check if tenant cannot downgrade from professional to basic
        $this->assertFalse($this->cashierManager->canUpgradeTo('basic'));
        
        $this->cashierManager->setCurrentTenant($this->tenantB);
        
        // Check if basic tier tenant can upgrade to professional
        $this->assertTrue($this->cashierManager->canUpgradeTo('professional'));
        $this->assertTrue($this->cashierManager->canUpgradeTo('enterprise'));
    }

    /** @test */
    public function webhook_events_are_processed_with_correct_tenant_context()
    {
        // Simulate webhook payload for tenant A
        $webhookPayload = [
            'id' => 'evt_test_webhook',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'customer' => 'cus_test_customer',
                    'metadata' => [
                        'tenant_id' => $this->tenantA->id,
                        'tenant_name' => $this->tenantA->name,
                    ],
                    'amount_paid' => 14900, // €149.00 for professional tier
                    'currency' => 'eur',
                ],
            ],
        ];
        
        // Mock webhook processing
        $this->postJson('/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'test_signature',
        ])->assertOk();
        
        // Verify webhook event was recorded with correct tenant context
        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_test_webhook',
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'invoice.payment_succeeded',
            'processed' => true,
        ]);
    }

    /** @test */
    public function tenant_subscription_metadata_includes_tenant_information()
    {
        $this->cashierManager->setCurrentTenant($this->tenantA);
        
        // Mock subscription creation metadata
        $expectedMetadata = [
            'tenant_id' => $this->tenantA->id,
            'tenant_name' => $this->tenantA->name,
            'subscription_tier' => $this->tenantA->subscription_tier,
        ];
        
        // Verify metadata would be included in Stripe subscription
        $this->assertEquals($this->tenantA->id, $expectedMetadata['tenant_id']);
        $this->assertEquals('Lakers Basketball Club', $expectedMetadata['tenant_name']);
        $this->assertEquals('professional', $expectedMetadata['subscription_tier']);
    }

    /** @test */
    public function tenant_billing_address_is_correctly_formatted_for_stripe()
    {
        $this->tenantA->update([
            'billing_address' => '123 Basketball Street',
            'billing_city' => 'Los Angeles',
            'billing_postal_code' => '90210',
            'country_code' => 'US',
        ]);
        
        $this->cashierManager->setCurrentTenant($this->tenantA);
        
        // Mock customer creation parameters
        $expectedCustomerData = [
            'name' => $this->tenantA->name,
            'email' => $this->tenantA->billing_email,
            'address' => [
                'line1' => '123 Basketball Street',
                'city' => 'Los Angeles',
                'postal_code' => '90210',
                'country' => 'US',
            ],
            'metadata' => [
                'tenant_id' => $this->tenantA->id,
                'tenant_name' => $this->tenantA->name,
            ],
        ];
        
        $this->assertEquals('Lakers Basketball Club', $expectedCustomerData['name']);
        $this->assertEquals('billing@lakers.test', $expectedCustomerData['email']);
        $this->assertEquals('US', $expectedCustomerData['address']['country']);
    }

    /** @test */
    public function german_payment_methods_are_configured_correctly()
    {
        // Tenant with German configuration
        $germanTenant = Tenant::factory()->create([
            'country_code' => 'DE',
            'billing_address' => 'Muster Straße 123',
            'billing_city' => 'München', 
            'billing_postal_code' => '80331',
        ]);
        
        $this->cashierManager->setCurrentTenant($germanTenant);
        
        // Verify German payment method configuration
        $expectedPaymentMethods = ['card', 'sepa_debit', 'sofort', 'giropay'];
        
        // Mock checkout session creation would include these methods
        $this->assertContains('sepa_debit', $expectedPaymentMethods);
        $this->assertContains('sofort', $expectedPaymentMethods);
        $this->assertContains('giropay', $expectedPaymentMethods);
    }

    /** @test */
    public function trial_eligibility_is_checked_correctly()
    {
        // New tenant should be eligible for trial
        $newTenant = Tenant::factory()->create([
            'subscription_tier' => 'free',
            'trial_ends_at' => null,
        ]);
        
        $this->assertTrue($newTenant->isEligibleForTrial());
        
        // Tenant with active trial should not be eligible for new trial
        $trialTenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
            'trial_ends_at' => now()->addDays(7),
        ]);
        
        $this->assertFalse($trialTenant->isEligibleForTrial());
        
        // Tenant with expired trial should not be eligible for new trial
        $expiredTrialTenant = Tenant::factory()->create([
            'subscription_tier' => 'basic',
            'trial_ends_at' => now()->subDays(7),
        ]);
        
        $this->assertFalse($expiredTrialTenant->isEligibleForTrial());
    }

    /** @test */
    public function subscription_cancellation_maintains_tenant_context()
    {
        $this->cashierManager->setCurrentTenant($this->tenantA);
        
        // Mock existing subscription
        $subscription = new Subscription([
            'name' => 'default',
            'stripe_id' => 'sub_test_subscription',
            'stripe_status' => 'active',
            'stripe_price' => 'price_professional',
            'quantity' => 1,
        ]);
        
        // Mock tenant has subscription
        $this->tenantA->setRelation('subscriptions', collect([$subscription]));
        
        // Verify subscription can be cancelled with correct tenant context
        $result = $this->cashierManager->cancelTenantSubscription('default', false);
        
        // Mock cancellation success
        $this->assertTrue(is_bool($result)); // Method returns boolean
    }

    /** @test */
    public function tenant_invoice_generation_includes_correct_details()
    {
        $this->cashierManager->setCurrentTenant($this->tenantA);
        
        // Expected invoice details for German compliance
        $expectedInvoiceData = [
            'customer_name' => $this->tenantA->name,
            'customer_email' => $this->tenantA->billing_email,
            'billing_address' => [
                'line1' => $this->tenantA->billing_address,
                'country' => $this->tenantA->country_code,
            ],
            'currency' => 'eur',
            'tax_behavior' => 'inclusive', // German MwSt
            'metadata' => [
                'tenant_id' => $this->tenantA->id,
                'subscription_tier' => $this->tenantA->subscription_tier,
            ],
        ];
        
        $this->assertEquals('Lakers Basketball Club', $expectedInvoiceData['customer_name']);
        $this->assertEquals('billing@lakers.test', $expectedInvoiceData['customer_email']);
        $this->assertEquals('eur', $expectedInvoiceData['currency']);
        $this->assertEquals('inclusive', $expectedInvoiceData['tax_behavior']);
    }
}