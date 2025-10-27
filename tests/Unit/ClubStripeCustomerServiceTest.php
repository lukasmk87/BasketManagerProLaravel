<?php

namespace Tests\Unit;

use App\Models\Club;
use App\Models\Tenant;
use App\Services\Stripe\ClubStripeCustomerService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubStripeCustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubStripeCustomerService $service;
    protected $mockClientManager;
    protected $mockStripeClient;
    protected $mockCustomersService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock Stripe client
        $this->mockCustomersService = Mockery::mock();

        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->customers = $this->mockCustomersService;

        // Create mock StripeClientManager
        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        // Create service with mocked dependencies
        $this->service = new ClubStripeCustomerService($this->mockClientManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_stripe_customer_for_club()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Basketball Club',
            'email' => 'test@club.com',
            'billing_email' => 'billing@club.com',
            'phone' => '+49123456789',
            'address_street' => 'Test Street 123',
            'address_city' => 'Munich',
            'address_zip' => '80331',
            'address_state' => 'Bavaria',
            'address_country' => 'DE',
        ]);

        // Mock Stripe customer creation
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($club) {
                return $data['name'] === $club->name
                    && $data['email'] === 'billing@club.com'
                    && $data['metadata']['club_id'] === $club->id
                    && $data['address']['city'] === 'Munich';
            }))
            ->andReturn($mockCustomer);

        $result = $this->service->createCustomer($club);

        $this->assertEquals('cus_test_123', $result->id);

        // Verify club was updated
        $club->refresh();
        $this->assertEquals('cus_test_123', $club->stripe_customer_id);
    }

    /** @test */
    public function it_creates_customer_without_address_if_not_provided()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Club',
            'email' => 'test@club.com',
            'address_street' => null,
            'address_city' => null,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_456';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return !isset($data['address']);
            }))
            ->andReturn($mockCustomer);

        $result = $this->service->createCustomer($club);

        $this->assertEquals('cus_test_456', $result->id);
    }

    /** @test */
    public function it_retrieves_existing_stripe_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_existing_123',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_existing_123';

        $this->mockCustomersService->shouldReceive('retrieve')
            ->once()
            ->with('cus_existing_123')
            ->andReturn($mockCustomer);

        $result = $this->service->getOrCreateCustomer($club);

        $this->assertEquals('cus_existing_123', $result->id);
    }

    /** @test */
    public function it_creates_new_customer_if_existing_not_found()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Club',
            'email' => 'test@club.com',
            'stripe_customer_id' => 'cus_deleted_123',
        ]);

        // Mock retrieve throwing exception (customer not found)
        $this->mockCustomersService->shouldReceive('retrieve')
            ->once()
            ->with('cus_deleted_123')
            ->andThrow(new InvalidRequestException('No such customer'));

        // Mock creating new customer
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_new_789';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        $result = $this->service->getOrCreateCustomer($club);

        $this->assertEquals('cus_new_789', $result->id);

        $club->refresh();
        $this->assertEquals('cus_new_789', $club->stripe_customer_id);
    }

    /** @test */
    public function it_updates_stripe_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $updateData = [
            'email' => 'newemail@club.com',
            'phone' => '+49987654321',
        ];

        $this->mockCustomersService->shouldReceive('update')
            ->once()
            ->with('cus_test_123', $updateData)
            ->andReturn($mockCustomer);

        $result = $this->service->updateCustomer($club, $updateData);

        $this->assertEquals('cus_test_123', $result->id);
    }

    /** @test */
    public function it_throws_exception_when_updating_club_without_stripe_id()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no Stripe Customer ID');

        $this->service->updateCustomer($club, ['email' => 'test@test.com']);
    }

    /** @test */
    public function it_deletes_stripe_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $this->mockCustomersService->shouldReceive('delete')
            ->once()
            ->with('cus_test_123')
            ->andReturn(true);

        $this->service->deleteCustomer($club);

        // Should complete without throwing
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_delete_gracefully_when_no_stripe_id()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => null,
        ]);

        // Should not call Stripe API
        $this->mockCustomersService->shouldNotReceive('delete');

        $this->service->deleteCustomer($club);

        // Should complete without throwing
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_delete_failure_gracefully()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $this->mockCustomersService->shouldReceive('delete')
            ->once()
            ->with('cus_test_123')
            ->andThrow(new InvalidRequestException('API Error'));

        // Should NOT throw exception (logs error instead)
        $this->service->deleteCustomer($club);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_uses_billing_email_over_regular_email()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Club',
            'email' => 'regular@club.com',
            'billing_email' => 'billing@club.com',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['email'] === 'billing@club.com';
            }))
            ->andReturn($mockCustomer);

        $this->service->createCustomer($club);
    }

    /** @test */
    public function it_includes_correct_metadata()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Metadata Test Club',
            'email' => 'test@club.com',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($club) {
                return isset($data['metadata'])
                    && $data['metadata']['club_id'] === $club->id
                    && $data['metadata']['tenant_id'] === $club->tenant_id
                    && $data['metadata']['club_name'] === $club->name
                    && $data['metadata']['club_uuid'] === $club->uuid
                    && $data['metadata']['created_by'] === 'basketmanager_pro';
            }))
            ->andReturn($mockCustomer);

        $this->service->createCustomer($club);
    }
}
