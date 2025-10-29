<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Stripe\ClubInvoiceService;
use App\Services\Stripe\ClubPaymentMethodService;
use App\Services\Stripe\ClubSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ClubBillingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockInvoiceService;

    protected $mockPaymentMethodService;

    protected $mockSubscriptionService;

    protected Tenant $tenant;

    protected Club $club;

    protected User $admin;

    protected User $unauthorizedUser;

    protected ClubSubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Spatie roles and permissions
        \Spatie\Permission\Models\Role::create(['name' => 'club_admin']);
        \Spatie\Permission\Models\Permission::create(['name' => 'view financial data']);

        // Create test data
        $this->tenant = Tenant::factory()->create();

        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Premium Plan',
            'price' => 99.00,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_product_id' => 'prod_test_123',
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_456',
        ]);

        $this->club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => $this->plan->id,
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
            'subscription_status' => 'active',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('club_admin'); // Spatie role
        $this->admin->givePermissionTo('view financial data'); // Required permission

        $this->club->users()->attach($this->admin->id, [
            'role' => 'admin',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->unauthorizedUser = User::factory()->create();

        // Mock services
        $this->mockInvoiceService = Mockery::mock(ClubInvoiceService::class);
        $this->mockPaymentMethodService = Mockery::mock(ClubPaymentMethodService::class);
        $this->mockSubscriptionService = Mockery::mock(ClubSubscriptionService::class);

        $this->app->instance(ClubInvoiceService::class, $this->mockInvoiceService);
        $this->app->instance(ClubPaymentMethodService::class, $this->mockPaymentMethodService);
        $this->app->instance(ClubSubscriptionService::class, $this->mockSubscriptionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================
    // INVOICE MANAGEMENT TESTS
    // ============================

    /** @test */
    public function authorized_user_can_list_invoices()
    {
        $mockInvoices = collect([
            [
                'id' => 'in_test_1',
                'amount_due' => 9900,
                'status' => 'paid',
                'created' => now()->timestamp,
            ],
            [
                'id' => 'in_test_2',
                'amount_due' => 9900,
                'status' => 'open',
                'created' => now()->subMonth()->timestamp,
            ],
        ]);

        $this->mockInvoiceService
            ->shouldReceive('getInvoices')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), Mockery::type('array'))
            ->andReturn($mockInvoices);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.index', $this->club));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoices',
                'club_id',
                'club_name',
            ])
            ->assertJson([
                'club_id' => $this->club->id,
                'club_name' => $this->club->name,
            ]);
    }

    /** @test */
    public function can_list_invoices_with_status_filter()
    {
        $mockInvoices = collect([
            ['id' => 'in_paid_1', 'status' => 'paid'],
        ]);

        $this->mockInvoiceService
            ->shouldReceive('getInvoices')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), ['status' => 'paid'])
            ->andReturn($mockInvoices);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.index', $this->club) . '?status=paid');

        $response->assertStatus(200);
    }

    /** @test */
    public function can_list_invoices_with_pagination()
    {
        $this->mockInvoiceService
            ->shouldReceive('getInvoices')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), [
                'limit' => '10',
                'starting_after' => 'in_start_123',
            ])
            ->andReturn(collect([]));

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.index', $this->club) . '?limit=10&starting_after=in_start_123');

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthorized_user_cannot_list_invoices()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.index', $this->club));

        $response->assertStatus(403);
    }

    /** @test */
    public function can_show_single_invoice()
    {
        $mockInvoice = [
            'id' => 'in_test_123',
            'amount_due' => 9900,
            'status' => 'paid',
            'lines' => [],
        ];

        $this->mockInvoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'in_test_123')
            ->andReturn($mockInvoice);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.show', [$this->club, 'in_test_123']));

        $response->assertStatus(200)
            ->assertJson([
                'invoice' => $mockInvoice,
                'club_id' => $this->club->id,
            ]);
    }

    /** @test */
    public function show_invoice_handles_errors()
    {
        $this->mockInvoiceService
            ->shouldReceive('getInvoice')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'in_nonexistent')
            ->andThrow(new \Exception('Invoice not found'));

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.show', [$this->club, 'in_nonexistent']));

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    /** @test */
    public function can_get_upcoming_invoice()
    {
        $mockUpcoming = [
            'amount_due' => 9900,
            'period_start' => now()->addMonth()->timestamp,
            'period_end' => now()->addMonths(2)->timestamp,
        ];

        $this->mockInvoiceService
            ->shouldReceive('getUpcomingInvoice')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }))
            ->andReturn($mockUpcoming);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.upcoming', $this->club));

        $response->assertStatus(200)
            ->assertJson([
                'invoice' => $mockUpcoming,
                'club_id' => $this->club->id,
            ]);
    }

    /** @test */
    public function returns_404_when_no_upcoming_invoice()
    {
        $this->mockInvoiceService
            ->shouldReceive('getUpcomingInvoice')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }))
            ->andReturn(null);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.invoices.upcoming', $this->club));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No upcoming invoice available',
            ]);
    }

    /** @test */
    public function can_download_invoice_pdf()
    {
        $pdfUrl = 'https://stripe.com/invoices/in_test_123/pdf';

        $this->mockInvoiceService
            ->shouldReceive('getInvoicePdfUrl')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'in_test_123')
            ->andReturn($pdfUrl);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->get(route('club.billing.invoices.pdf', [$this->club, 'in_test_123']));

        $response->assertRedirect($pdfUrl);
    }

    // ============================
    // PAYMENT METHOD MANAGEMENT TESTS
    // ============================

    /** @test */
    public function can_list_payment_methods()
    {
        $mockPaymentMethods = [
            [
                'id' => 'pm_card_123',
                'type' => 'card',
                'card' => [
                    'brand' => 'visa',
                    'last4' => '4242',
                ],
            ],
        ];

        $germanMethods = ['card', 'sepa_debit', 'sofort'];
        $localizedNames = ['card' => 'Kreditkarte'];

        $this->mockPaymentMethodService
            ->shouldReceive('listPaymentMethods')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'card')
            ->andReturn($mockPaymentMethods);

        $this->mockPaymentMethodService
            ->shouldReceive('getGermanPaymentMethods')
            ->once()
            ->andReturn($germanMethods);

        $this->mockPaymentMethodService
            ->shouldReceive('getLocalizedPaymentMethodNames')
            ->once()
            ->andReturn($localizedNames);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.payment-methods.index', $this->club));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payment_methods',
                'club_id',
                'type',
                'available_types',
                'localized_names',
            ])
            ->assertJson([
                'type' => 'card',
            ]);
    }

    /** @test */
    public function can_list_payment_methods_with_type_filter()
    {
        $this->mockPaymentMethodService
            ->shouldReceive('listPaymentMethods')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'sepa_debit')
            ->andReturn([]);

        $this->mockPaymentMethodService->shouldReceive('getGermanPaymentMethods')->andReturn([]);
        $this->mockPaymentMethodService->shouldReceive('getLocalizedPaymentMethodNames')->andReturn([]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->getJson(route('club.billing.payment-methods.index', $this->club) . '?type=sepa_debit');

        $response->assertStatus(200)
            ->assertJson(['type' => 'sepa_debit']);
    }

    /** @test */
    public function can_create_setup_intent()
    {
        $mockSetupIntent = (object) [
            'id' => 'seti_test_123',
            'client_secret' => 'seti_test_123_secret_abc',
        ];

        $this->mockPaymentMethodService
            ->shouldReceive('createSetupIntent')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), [])
            ->andReturn($mockSetupIntent);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(route('club.billing.payment-methods.setup', $this->club));

        $response->assertStatus(200)
            ->assertJson([
                'client_secret' => 'seti_test_123_secret_abc',
                'setup_intent_id' => 'seti_test_123',
            ]);
    }

    /** @test */
    public function can_create_setup_intent_with_options()
    {
        $mockSetupIntent = (object) [
            'id' => 'seti_test_456',
            'client_secret' => 'seti_test_456_secret_xyz',
        ];

        $this->mockPaymentMethodService
            ->shouldReceive('createSetupIntent')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), [
                'usage' => 'off_session',
                'return_url' => 'https://example.com/return',
            ])
            ->andReturn($mockSetupIntent);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.payment-methods.setup', $this->club),
                [
                    'usage' => 'off_session',
                    'return_url' => 'https://example.com/return',
                ]
            );

        $response->assertStatus(200);
    }

    /** @test */
    public function can_attach_payment_method()
    {
        $mockPaymentMethod = (object) [
            'id' => 'pm_test_123',
            'type' => 'card',
        ];

        $this->mockPaymentMethodService
            ->shouldReceive('attachPaymentMethod')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'pm_test_123', false)
            ->andReturn($mockPaymentMethod);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.payment-methods.attach', $this->club),
                ['payment_method_id' => 'pm_test_123']
            );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment method attached successfully',
                'payment_method_id' => 'pm_test_123',
                'is_default' => false,
            ]);
    }

    /** @test */
    public function can_attach_payment_method_and_set_as_default()
    {
        $mockPaymentMethod = (object) ['id' => 'pm_test_456'];

        $this->mockPaymentMethodService
            ->shouldReceive('attachPaymentMethod')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'pm_test_456', true)
            ->andReturn($mockPaymentMethod);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.payment-methods.attach', $this->club),
                [
                    'payment_method_id' => 'pm_test_456',
                    'set_as_default' => true,
                ]
            );

        $response->assertStatus(200)
            ->assertJson(['is_default' => true]);
    }

    /** @test */
    public function attach_payment_method_requires_payment_method_id()
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.payment-methods.attach', $this->club),
                []
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method_id']);
    }

    /** @test */
    public function can_detach_payment_method()
    {
        $this->mockPaymentMethodService
            ->shouldReceive('detachPaymentMethod')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'pm_test_123')
            ->andReturn(true);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->deleteJson(route('club.billing.payment-methods.detach', [$this->club, 'pm_test_123']));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment method detached successfully',
                'payment_method_id' => 'pm_test_123',
            ]);
    }

    /** @test */
    public function detach_payment_method_handles_errors()
    {
        $this->mockPaymentMethodService
            ->shouldReceive('detachPaymentMethod')
            ->once()
            ->andThrow(new \Exception('Payment method not found'));

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->deleteJson(route('club.billing.payment-methods.detach', [$this->club, 'pm_invalid']));

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    /** @test */
    public function can_update_payment_method_billing_details()
    {
        $mockPaymentMethod = (object) ['id' => 'pm_test_123'];

        $billingDetails = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->mockPaymentMethodService
            ->shouldReceive('updatePaymentMethod')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'pm_test_123', $billingDetails)
            ->andReturn($mockPaymentMethod);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->putJson(
                route('club.billing.payment-methods.update', [$this->club, 'pm_test_123']),
                ['billing_details' => $billingDetails]
            );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment method updated successfully',
                'payment_method_id' => 'pm_test_123',
            ]);
    }

    /** @test */
    public function update_payment_method_requires_billing_details()
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->putJson(
                route('club.billing.payment-methods.update', [$this->club, 'pm_test_123']),
                []
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['billing_details']);
    }

    /** @test */
    public function can_set_default_payment_method()
    {
        $this->mockPaymentMethodService
            ->shouldReceive('setDefaultPaymentMethod')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), 'pm_test_123')
            ->andReturn(true);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(route('club.billing.payment-methods.default', [$this->club, 'pm_test_123']));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Default payment method set successfully',
                'payment_method_id' => 'pm_test_123',
            ]);
    }

    // ============================
    // PLAN SWAP TESTS
    // ============================

    /** @test */
    public function can_preview_plan_swap()
    {
        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Enterprise Plan',
            'price' => 199.00,
        ]);

        $mockPreview = [
            'current_plan' => [
                'id' => $this->plan->id,
                'name' => 'Premium Plan',
                'price' => 99.00,
            ],
            'new_plan' => [
                'id' => $newPlan->id,
                'name' => 'Enterprise Plan',
                'price' => 199.00,
            ],
            'proration' => [
                'amount' => 100.00,
                'credit' => 50.00,
                'debit' => 150.00,
            ],
        ];

        $this->mockSubscriptionService
            ->shouldReceive('previewPlanSwap')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), Mockery::on(function ($arg) use ($newPlan) {
                return $arg->id === $newPlan->id;
            }), [
                'billing_interval' => 'monthly',
                'proration_behavior' => 'create_prorations',
            ])
            ->andReturn($mockPreview);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.preview-plan-swap', $this->club),
                ['new_plan_id' => $newPlan->id]
            );

        $response->assertStatus(200)
            ->assertJson([
                'preview' => $mockPreview,
                'club_id' => $this->club->id,
            ]);
    }

    /** @test */
    public function preview_plan_swap_validates_plan_belongs_to_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $otherPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.preview-plan-swap', $this->club),
                ['new_plan_id' => $otherPlan->id]
            );

        $response->assertStatus(403)
            ->assertJson([
                'error' => "Plan does not belong to club's tenant",
            ]);
    }

    /** @test */
    public function preview_plan_swap_requires_new_plan_id()
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.preview-plan-swap', $this->club),
                []
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_plan_id']);
    }

    /** @test */
    public function can_execute_plan_swap()
    {
        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Basic Plan',
            'price' => 49.00,
        ]);

        $this->mockSubscriptionService
            ->shouldReceive('swapPlan')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), Mockery::on(function ($arg) use ($newPlan) {
                return $arg->id === $newPlan->id;
            }), [
                'billing_interval' => 'monthly',
                'proration_behavior' => 'create_prorations',
            ])
            ->andReturnNull();

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.swap-plan', $this->club),
                ['new_plan_id' => $newPlan->id]
            );

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Plan swapped successfully',
                'club_id' => $this->club->id,
                'new_plan_id' => $newPlan->id,
                'new_plan_name' => 'Basic Plan',
            ]);
    }

    /** @test */
    public function plan_swap_supports_yearly_billing()
    {
        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->mockSubscriptionService
            ->shouldReceive('swapPlan')
            ->once()
            ->with(Mockery::on(function ($club) {
                return $club instanceof \App\Models\Club && $club->id === $this->club->id;
            }), Mockery::any(), [
                'billing_interval' => 'yearly',
                'proration_behavior' => 'create_prorations',
            ])
            ->andReturnNull();

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.swap-plan', $this->club),
                [
                    'new_plan_id' => $newPlan->id,
                    'billing_interval' => 'yearly',
                ]
            );

        $response->assertStatus(200);
    }

    /** @test */
    public function plan_swap_validates_tenant_ownership()
    {
        $otherTenant = Tenant::factory()->create();
        $otherPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.swap-plan', $this->club),
                ['new_plan_id' => $otherPlan->id]
            );

        $response->assertStatus(403)
            ->assertJson([
                'error' => "Plan does not belong to club's tenant",
            ]);
    }

    /** @test */
    public function plan_swap_requires_new_plan_id()
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant-ID', $this->tenant->id)
            ->followingRedirects()
            ->postJson(
                route('club.billing.swap-plan', $this->club),
                []
            );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_plan_id']);
    }

    // ============================
    // AUTHORIZATION TESTS
    // ============================

    /** @test */
    public function unauthorized_user_cannot_access_payment_methods()
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson(
            route('club.billing.payment-methods.index', $this->club)
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_create_setup_intent()
    {
        $response = $this->actingAs($this->unauthorizedUser)->postJson(
            route('club.billing.payment-methods.setup', $this->club)
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_preview_plan_swap()
    {
        $response = $this->actingAs($this->unauthorizedUser)->postJson(
            route('club.billing.preview-plan-swap', $this->club),
            ['new_plan_id' => $this->plan->id]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_swap_plan()
    {
        $response = $this->actingAs($this->unauthorizedUser)->postJson(
            route('club.billing.swap-plan', $this->club),
            ['new_plan_id' => $this->plan->id]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_billing_endpoints()
    {
        $response = $this->getJson(
            route('club.billing.invoices.index', $this->club)
        );

        $response->assertStatus(401);
    }
}
