<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Services\Stripe\ClubPaymentMethodService;
use App\Services\Stripe\ClubStripeCustomerService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Collection as StripeCollection;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubPaymentMethodServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubPaymentMethodService $service;

    protected StripeClientManager $clientManager;

    protected ClubStripeCustomerService $customerService;

    protected StripeClient $stripeClient;

    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test club
        $this->club = Club::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
            'payment_method_id' => null,
        ]);

        // Mock dependencies
        $this->clientManager = Mockery::mock(StripeClientManager::class);
        $this->customerService = Mockery::mock(ClubStripeCustomerService::class);

        // Mock StripeClient
        $this->stripeClient = Mockery::mock(StripeClient::class);
        $this->stripeClient->setupIntents = Mockery::mock();
        $this->stripeClient->paymentMethods = Mockery::mock();
        $this->stripeClient->customers = Mockery::mock();
        $this->stripeClient->subscriptions = Mockery::mock();

        // Configure client manager
        $this->clientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->stripeClient);

        // Create service
        $this->service = new ClubPaymentMethodService(
            $this->clientManager,
            $this->customerService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_setup_intent_for_club()
    {
        $mockCustomer = $this->createMockCustomer();
        $mockSetupIntent = $this->createMockSetupIntent();

        $this->customerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->with($this->club)
            ->andReturn($mockCustomer);

        $this->stripeClient->setupIntents
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['customer'] === 'cus_test_123'
                    && $params['usage'] === 'off_session'
                    && isset($params['automatic_payment_methods']);
            }))
            ->andReturn($mockSetupIntent);

        $setupIntent = $this->service->createSetupIntent($this->club);

        $this->assertEquals('seti_test_123', $setupIntent->id);
        $this->assertEquals('requires_payment_method', $setupIntent->status);
    }

    /** @test */
    public function it_creates_setup_intent_with_custom_usage()
    {
        $mockCustomer = $this->createMockCustomer();
        $mockSetupIntent = $this->createMockSetupIntent();

        $this->customerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $this->stripeClient->setupIntents
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['usage'] === 'on_session';
            }))
            ->andReturn($mockSetupIntent);

        $setupIntent = $this->service->createSetupIntent($this->club, ['usage' => 'on_session']);

        $this->assertNotNull($setupIntent);
    }

    /** @test */
    public function it_lists_payment_methods()
    {
        $mockPaymentMethods = $this->createMockPaymentMethodCollection();

        $this->stripeClient->paymentMethods
            ->shouldReceive('all')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'type' => 'card',
            ])
            ->andReturn($mockPaymentMethods);

        $paymentMethods = $this->service->listPaymentMethods($this->club, 'card');

        $this->assertCount(2, $paymentMethods);
        $this->assertEquals('pm_test_001', $paymentMethods->first()['id']);
        $this->assertEquals('card', $paymentMethods->first()['type']);
    }

    /** @test */
    public function it_lists_sepa_payment_methods()
    {
        $mockPaymentMethods = $this->createMockSepaPaymentMethodCollection();

        $this->stripeClient->paymentMethods
            ->shouldReceive('all')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'type' => 'sepa_debit',
            ])
            ->andReturn($mockPaymentMethods);

        $paymentMethods = $this->service->listPaymentMethods($this->club, 'sepa_debit');

        $this->assertCount(1, $paymentMethods);
        $this->assertEquals('sepa_debit', $paymentMethods->first()['type']);
        $this->assertStringContainsString('SEPA', $paymentMethods->first()['display_name']);
    }

    /** @test */
    public function it_throws_exception_when_listing_without_stripe_customer()
    {
        $clubWithoutCustomer = Club::factory()->create([
            'stripe_customer_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no Stripe customer');

        $this->service->listPaymentMethods($clubWithoutCustomer);
    }

    /** @test */
    public function it_attaches_payment_method()
    {
        $mockCustomer = $this->createMockCustomer();
        $mockPaymentMethod = $this->createMockPaymentMethod('pm_test_001', 'card');

        $this->customerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $this->stripeClient->paymentMethods
            ->shouldReceive('attach')
            ->once()
            ->with('pm_test_001', ['customer' => 'cus_test_123'])
            ->andReturn($mockPaymentMethod);

        $paymentMethod = $this->service->attachPaymentMethod($this->club, 'pm_test_001');

        $this->assertEquals('pm_test_001', $paymentMethod->id);
        $this->assertEquals('cus_test_123', $paymentMethod->customer);
    }

    /** @test */
    public function it_attaches_and_sets_default_payment_method()
    {
        $mockCustomer = $this->createMockCustomer();
        $mockPaymentMethod = $this->createMockPaymentMethod('pm_test_001', 'card');

        $this->customerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $this->stripeClient->paymentMethods
            ->shouldReceive('attach')
            ->once()
            ->andReturn($mockPaymentMethod);

        // Expect setDefaultPaymentMethod to be called
        $this->stripeClient->customers
            ->shouldReceive('update')
            ->once()
            ->with('cus_test_123', Mockery::on(function ($params) {
                return isset($params['invoice_settings']['default_payment_method'])
                    && $params['invoice_settings']['default_payment_method'] === 'pm_test_001';
            }));

        $this->stripeClient->subscriptions
            ->shouldReceive('update')
            ->once()
            ->with('sub_test_123', ['default_payment_method' => 'pm_test_001']);

        $paymentMethod = $this->service->attachPaymentMethod($this->club, 'pm_test_001', true);

        $this->assertEquals('pm_test_001', $paymentMethod->id);
        $this->club->refresh();
        $this->assertEquals('pm_test_001', $this->club->payment_method_id);
    }

    /** @test */
    public function it_detaches_payment_method()
    {
        $mockPaymentMethod = $this->createMockPaymentMethod('pm_test_001', 'card');
        $mockPaymentMethod->customer = 'cus_test_123';

        $this->stripeClient->paymentMethods
            ->shouldReceive('retrieve')
            ->once()
            ->with('pm_test_001')
            ->andReturn($mockPaymentMethod);

        $mockPaymentMethod->customer = null;
        $this->stripeClient->paymentMethods
            ->shouldReceive('detach')
            ->once()
            ->with('pm_test_001')
            ->andReturn($mockPaymentMethod);

        $detachedPaymentMethod = $this->service->detachPaymentMethod($this->club, 'pm_test_001');

        $this->assertEquals('pm_test_001', $detachedPaymentMethod->id);
        $this->assertNull($detachedPaymentMethod->customer);
    }

    /** @test */
    public function it_throws_exception_when_detaching_payment_method_from_other_club()
    {
        $mockPaymentMethod = $this->createMockPaymentMethod('pm_test_001', 'card');
        $mockPaymentMethod->customer = 'cus_other_456';

        $this->stripeClient->paymentMethods
            ->shouldReceive('retrieve')
            ->once()
            ->andReturn($mockPaymentMethod);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment method does not belong to this club');

        $this->service->detachPaymentMethod($this->club, 'pm_test_001');
    }

    /** @test */
    public function it_sets_default_payment_method()
    {
        $this->stripeClient->customers
            ->shouldReceive('update')
            ->once()
            ->with('cus_test_123', Mockery::on(function ($params) {
                return $params['invoice_settings']['default_payment_method'] === 'pm_test_001';
            }));

        $this->stripeClient->subscriptions
            ->shouldReceive('update')
            ->once()
            ->with('sub_test_123', ['default_payment_method' => 'pm_test_001']);

        $this->service->setDefaultPaymentMethod($this->club, 'pm_test_001');

        $this->club->refresh();
        $this->assertEquals('pm_test_001', $this->club->payment_method_id);
    }

    /** @test */
    public function it_updates_payment_method_billing_details()
    {
        $mockPaymentMethod = $this->createMockPaymentMethod('pm_test_001', 'card');
        $mockPaymentMethod->customer = 'cus_test_123';

        $this->stripeClient->paymentMethods
            ->shouldReceive('retrieve')
            ->once()
            ->andReturn($mockPaymentMethod);

        $billingDetails = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->stripeClient->paymentMethods
            ->shouldReceive('update')
            ->once()
            ->with('pm_test_001', ['billing_details' => $billingDetails])
            ->andReturn($mockPaymentMethod);

        $updatedPaymentMethod = $this->service->updatePaymentMethod($this->club, 'pm_test_001', $billingDetails);

        $this->assertEquals('pm_test_001', $updatedPaymentMethod->id);
    }

    /** @test */
    public function it_gets_german_payment_methods()
    {
        $methods = $this->service->getGermanPaymentMethods();

        $this->assertContains('card', $methods);
        $this->assertContains('sepa_debit', $methods);
        $this->assertContains('sofort', $methods);
        $this->assertContains('giropay', $methods);
    }

    /** @test */
    public function it_gets_localized_payment_method_names()
    {
        $names = $this->service->getLocalizedPaymentMethodNames();

        $this->assertEquals('Kreditkarte / EC-Karte', $names['card']);
        $this->assertEquals('SEPA Lastschrift', $names['sepa_debit']);
        $this->assertEquals('SOFORT Überweisung', $names['sofort']);
    }

    /**
     * Helper: Create mock Stripe Customer
     */
    protected function createMockCustomer(): Customer
    {
        $customer = Mockery::mock(Customer::class);
        $customer->id = 'cus_test_123';
        $customer->email = $this->club->email;
        $customer->name = $this->club->name;

        return $customer;
    }

    /**
     * Helper: Create mock SetupIntent
     */
    protected function createMockSetupIntent(): SetupIntent
    {
        $setupIntent = Mockery::mock(SetupIntent::class);
        $setupIntent->id = 'seti_test_123';
        $setupIntent->object = 'setup_intent';
        $setupIntent->status = 'requires_payment_method';
        $setupIntent->client_secret = 'seti_test_123_secret_abc';
        $setupIntent->customer = 'cus_test_123';
        $setupIntent->usage = 'off_session';

        return $setupIntent;
    }

    /**
     * Helper: Create mock PaymentMethod
     */
    protected function createMockPaymentMethod(string $id, string $type): PaymentMethod
    {
        $paymentMethod = Mockery::mock(PaymentMethod::class);
        $paymentMethod->id = $id;
        $paymentMethod->type = $type;
        $paymentMethod->customer = 'cus_test_123';
        $paymentMethod->created = time();

        if ($type === 'card') {
            $paymentMethod->card = (object) [
                'brand' => 'visa',
                'last4' => '4242',
                'exp_month' => 12,
                'exp_year' => 2025,
                'country' => 'DE',
                'funding' => 'credit',
            ];
        } elseif ($type === 'sepa_debit') {
            $paymentMethod->sepa_debit = (object) [
                'bank_code' => '37040044',
                'country' => 'DE',
                'last4' => '3000',
                'fingerprint' => 'abc123',
            ];
        }

        $paymentMethod->billing_details = (object) [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => null,
            'address' => null,
        ];

        return $paymentMethod;
    }

    /**
     * Helper: Create mock PaymentMethod collection
     */
    protected function createMockPaymentMethodCollection(): StripeCollection
    {
        $pm1 = $this->createMockPaymentMethod('pm_test_001', 'card');
        $pm2 = $this->createMockPaymentMethod('pm_test_002', 'card');

        $collection = Mockery::mock(StripeCollection::class);
        $collection->data = [$pm1, $pm2];

        return $collection;
    }

    /**
     * Helper: Create mock SEPA PaymentMethod collection
     */
    protected function createMockSepaPaymentMethodCollection(): StripeCollection
    {
        $pm = $this->createMockPaymentMethod('pm_test_sepa_001', 'sepa_debit');

        $collection = Mockery::mock(StripeCollection::class);
        $collection->data = [$pm];

        return $collection;
    }
}
