<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Tenant;
use App\Services\Stripe\ClubStripeCustomerService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Customer;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubStripeCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected ClubStripeCustomerService $service;
    protected $mockClientManager;
    protected $mockStripeClient;
    protected $mockCustomersService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock Stripe client for feature tests
        $this->mockCustomersService = Mockery::mock();

        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->customers = $this->mockCustomersService;

        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        $this->service = new ClubStripeCustomerService($this->mockClientManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function club_can_create_stripe_customer_and_persist_to_database()
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Munich Basketball Club',
            'email' => 'info@munich-basketball.de',
            'billing_email' => 'billing@munich-basketball.de',
            'phone' => '+49891234567',
            'address_street' => 'Marienplatz 1',
            'address_city' => 'München',
            'address_zip' => '80331',
            'address_state' => 'Bayern',
            'address_country' => 'DE',
            'stripe_customer_id' => null,
        ]);

        // Verify club has no Stripe customer initially
        $this->assertNull($club->stripe_customer_id);

        // Mock Stripe customer creation
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_munich_test_123';
        $mockCustomer->email = 'billing@munich-basketball.de';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        // Create Stripe customer
        $customer = $this->service->createCustomer($club);

        // Verify customer was created
        $this->assertEquals('cus_munich_test_123', $customer->id);

        // Verify club was updated in database
        $club->refresh();
        $this->assertEquals('cus_munich_test_123', $club->stripe_customer_id);
        $this->assertDatabaseHas('clubs', [
            'id' => $club->id,
            'stripe_customer_id' => 'cus_munich_test_123',
        ]);
    }

    /** @test */
    public function multiple_clubs_in_same_tenant_can_have_separate_stripe_customers()
    {
        $tenant = Tenant::factory()->create();

        $club1 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club Alpha',
            'email' => 'alpha@test.com',
        ]);

        $club2 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club Beta',
            'email' => 'beta@test.com',
        ]);

        // Mock customer creation for club1
        $mockCustomer1 = Mockery::mock(Customer::class);
        $mockCustomer1->id = 'cus_alpha_123';

        // Mock customer creation for club2
        $mockCustomer2 = Mockery::mock(Customer::class);
        $mockCustomer2->id = 'cus_beta_456';

        $this->mockCustomersService->shouldReceive('create')
            ->twice()
            ->andReturn($mockCustomer1, $mockCustomer2);

        $this->service->createCustomer($club1);
        $this->service->createCustomer($club2);

        $club1->refresh();
        $club2->refresh();

        // Verify both clubs have different Stripe customer IDs
        $this->assertEquals('cus_alpha_123', $club1->stripe_customer_id);
        $this->assertEquals('cus_beta_456', $club2->stripe_customer_id);
        $this->assertNotEquals($club1->stripe_customer_id, $club2->stripe_customer_id);
    }

    /** @test */
    public function club_can_update_stripe_customer_information()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_existing_123',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_existing_123';
        $mockCustomer->email = 'newemail@club.com';

        $updateData = [
            'email' => 'newemail@club.com',
            'phone' => '+49123456789',
            'address' => [
                'city' => 'Berlin',
                'country' => 'DE',
            ],
        ];

        $this->mockCustomersService->shouldReceive('update')
            ->once()
            ->with('cus_existing_123', $updateData)
            ->andReturn($mockCustomer);

        $result = $this->service->updateCustomer($club, $updateData);

        $this->assertEquals('cus_existing_123', $result->id);
        $this->assertEquals('newemail@club.com', $result->email);
    }

    /** @test */
    public function club_lifecycle_creates_retrieves_and_deletes_stripe_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lifecycle Test Club',
            'email' => 'lifecycle@test.com',
        ]);

        // Step 1: Create customer
        $mockCustomerCreated = Mockery::mock(Customer::class);
        $mockCustomerCreated->id = 'cus_lifecycle_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomerCreated);

        $createdCustomer = $this->service->createCustomer($club);
        $club->refresh();

        $this->assertEquals('cus_lifecycle_123', $createdCustomer->id);
        $this->assertEquals('cus_lifecycle_123', $club->stripe_customer_id);

        // Step 2: Retrieve existing customer
        $mockCustomerRetrieved = Mockery::mock(Customer::class);
        $mockCustomerRetrieved->id = 'cus_lifecycle_123';

        $this->mockCustomersService->shouldReceive('retrieve')
            ->once()
            ->with('cus_lifecycle_123')
            ->andReturn($mockCustomerRetrieved);

        $retrievedCustomer = $this->service->getOrCreateCustomer($club);
        $this->assertEquals('cus_lifecycle_123', $retrievedCustomer->id);

        // Step 3: Delete customer
        $this->mockCustomersService->shouldReceive('delete')
            ->once()
            ->with('cus_lifecycle_123')
            ->andReturn(true);

        $this->service->deleteCustomer($club);

        // Verify no exceptions were thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function tenant_isolation_is_maintained_across_clubs()
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        $club1 = Club::factory()->create([
            'tenant_id' => $tenant1->id,
            'name' => 'Tenant 1 Club',
            'email' => 'club1@test.com',
        ]);

        $club2 = Club::factory()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'Tenant 2 Club',
            'email' => 'club2@test.com',
        ]);

        // Verify clubs belong to different tenants
        $this->assertNotEquals($club1->tenant_id, $club2->tenant_id);
        $this->assertEquals($tenant1->id, $club1->tenant_id);
        $this->assertEquals($tenant2->id, $club2->tenant_id);

        // Mock customer creation
        $mockCustomer1 = Mockery::mock(Customer::class);
        $mockCustomer1->id = 'cus_tenant1_123';

        $mockCustomer2 = Mockery::mock(Customer::class);
        $mockCustomer2->id = 'cus_tenant2_456';

        $this->mockCustomersService->shouldReceive('create')
            ->twice()
            ->andReturn($mockCustomer1, $mockCustomer2);

        $this->service->createCustomer($club1);
        $this->service->createCustomer($club2);

        $club1->refresh();
        $club2->refresh();

        // Verify each club has its own Stripe customer
        $this->assertEquals('cus_tenant1_123', $club1->stripe_customer_id);
        $this->assertEquals('cus_tenant2_456', $club2->stripe_customer_id);

        // Verify clubs remain in separate tenants
        $this->assertEquals($tenant1->id, $club1->tenant_id);
        $this->assertEquals($tenant2->id, $club2->tenant_id);
    }

    /** @test */
    public function club_with_complete_address_includes_all_address_fields()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Address Test Club',
            'email' => 'address@test.com',
            'address_street' => 'Hauptstraße 42',
            'address_city' => 'Hamburg',
            'address_zip' => '20095',
            'address_state' => 'Hamburg',
            'address_country' => 'DE',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_address_test_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['address'])
                    && $data['address']['line1'] === 'Hauptstraße 42'
                    && $data['address']['city'] === 'Hamburg'
                    && $data['address']['postal_code'] === '20095'
                    && $data['address']['state'] === 'Hamburg'
                    && $data['address']['country'] === 'DE';
            }))
            ->andReturn($mockCustomer);

        $this->service->createCustomer($club);
    }

    /** @test */
    public function club_without_address_creates_customer_successfully()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'No Address Club',
            'email' => 'noaddress@test.com',
            'address_street' => null,
            'address_city' => null,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_no_address_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                // Verify address is NOT included
                return !isset($data['address']);
            }))
            ->andReturn($mockCustomer);

        $customer = $this->service->createCustomer($club);

        $this->assertEquals('cus_no_address_123', $customer->id);
    }
}
