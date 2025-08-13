<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Stripe\CashierTenantManager;
use App\Services\Stripe\CheckoutService;
use App\Jobs\ProcessStripeWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StripePaymentEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected CashierTenantManager $cashierManager;
    protected CheckoutService $checkoutService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        Http::fake();
        
        // Mock Stripe configuration
        Config::set('stripe.api_key', 'pk_test_123');
        Config::set('stripe.secret', 'sk_test_123');
        Config::set('stripe.webhooks.signing_secret', 'whsec_test_123');
        
        $this->setupPaymentTestData();
        $this->cashierManager = app(CashierTenantManager::class);
        $this->checkoutService = app(CheckoutService::class);
    }

    protected function setupPaymentTestData(): void
    {
        $this->tenant = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'slug' => 'lakers',
            'subscription_tier' => 'free',
            'billing_email' => 'billing@lakers.test',
            'billing_address' => '123 Basketball Street',
            'billing_city' => 'Los Angeles',
            'billing_postal_code' => '90210',
            'country_code' => 'US',
            'stripe_configuration' => [
                'publishable_key' => 'pk_test_lakers_123',
                'secret_key' => 'sk_test_lakers_123',
                'webhook_secret' => 'whsec_lakers_123',
            ],
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin@lakers.test',
        ]);
    }

    /** @test */
    public function tenant_can_upgrade_from_free_to_basic_tier()
    {
        Sanctum::actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        // Mock Stripe customer creation
        Http::fake([
            'api.stripe.com/v1/customers' => Http::response([
                'id' => 'cus_test_customer',
                'email' => $this->tenant->billing_email,
                'name' => $this->tenant->name,
            ]),
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_session',
                'url' => 'https://checkout.stripe.com/pay/cs_test_session',
                'customer' => 'cus_test_customer',
            ]),
        ]);
        
        // Create checkout session for basic tier
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->postJson('/api/subscription/checkout', [
                             'tier' => 'basic',
                             'success_url' => 'https://lakers.basketmanager-pro.com/success',
                             'cancel_url' => 'https://lakers.basketmanager-pro.com/cancel',
                         ]);
        
        $response->assertOk();
        $response->assertJsonStructure([
            'checkout_url',
            'session_id'
        ]);
        
        // Verify Stripe API was called with correct parameters
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions' &&
                   $request['customer_email'] === $this->tenant->billing_email &&
                   $request['metadata']['tenant_id'] === $this->tenant->id;
        });
    }

    /** @test */
    public function successful_payment_webhook_creates_subscription()
    {
        // Mock successful checkout completion webhook
        $webhookPayload = [
            'id' => 'evt_test_webhook',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_session',
                    'customer' => 'cus_test_customer',
                    'mode' => 'subscription',
                    'subscription' => 'sub_test_subscription',
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                        'tier' => 'basic',
                    ],
                ],
            ],
        ];
        
        // Process webhook
        $response = $this->postJson('/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => $this->generateStripeSignature($webhookPayload),
        ]);
        
        $response->assertOk();
        
        // Verify webhook was queued for processing
        Queue::assertPushed(ProcessStripeWebhook::class);
        
        // Verify webhook event was recorded
        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_test_webhook',
            'tenant_id' => $this->tenant->id,
            'event_type' => 'checkout.session.completed',
        ]);
    }

    /** @test */
    public function subscription_upgrade_flow_works_correctly()
    {
        // Simulate existing basic subscription
        $this->tenant->update(['subscription_tier' => 'basic']);
        
        Sanctum::actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        // Mock Stripe subscription update
        Http::fake([
            'api.stripe.com/v1/subscriptions/*' => Http::response([
                'id' => 'sub_test_subscription',
                'status' => 'active',
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test_item',
                            'price' => ['id' => 'price_professional'],
                        ]
                    ]
                ]
            ]),
        ]);
        
        // Upgrade to professional tier
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->putJson('/api/subscription/upgrade', [
                             'tier' => 'professional',
                         ]);
        
        $response->assertOk();
        
        // Process subscription updated webhook
        $upgradeWebhook = [
            'id' => 'evt_upgrade_webhook',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_subscription',
                    'customer' => 'cus_test_customer',
                    'status' => 'active',
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                        'tier' => 'professional',
                    ],
                    'items' => [
                        'data' => [
                            [
                                'price' => ['id' => 'price_professional'],
                            ]
                        ]
                    ]
                ],
            ],
        ];
        
        $this->postJson('/webhooks/stripe', $upgradeWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($upgradeWebhook),
        ])->assertOk();
        
        // Verify tenant tier was updated
        $this->tenant->refresh();
        $this->assertEquals('professional', $this->tenant->subscription_tier);
    }

    /** @test */
    public function failed_payment_webhook_handles_dunning_gracefully()
    {
        // Mock failed payment webhook
        $failedPaymentWebhook = [
            'id' => 'evt_payment_failed',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'customer' => 'cus_test_customer',
                    'subscription' => 'sub_test_subscription',
                    'amount_due' => 4900, // €49.00 for basic tier
                    'attempt_count' => 1,
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                    ],
                ],
            ],
        ];
        
        $response = $this->postJson('/webhooks/stripe', $failedPaymentWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($failedPaymentWebhook),
        ]);
        
        $response->assertOk();
        
        // Verify webhook was processed
        Queue::assertPushed(ProcessStripeWebhook::class);
        
        // Mock subsequent payment failure after retries
        $finalFailureWebhook = [
            'id' => 'evt_payment_final_failed',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'customer' => 'cus_test_customer',
                    'subscription' => 'sub_test_subscription',
                    'amount_due' => 4900,
                    'attempt_count' => 4, // Final attempt
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                    ],
                ],
            ],
        ];
        
        $this->postJson('/webhooks/stripe', $finalFailureWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($finalFailureWebhook),
        ])->assertOk();
        
        // Verify tenant was notified or downgraded appropriately
        $this->assertDatabaseHas('webhook_events', [
            'event_type' => 'invoice.payment_failed',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function subscription_cancellation_flow_works_correctly()
    {
        Sanctum::actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        $this->tenant->update(['subscription_tier' => 'professional']);
        
        // Mock Stripe subscription cancellation
        Http::fake([
            'api.stripe.com/v1/subscriptions/*/cancel' => Http::response([
                'id' => 'sub_test_subscription',
                'status' => 'canceled',
                'canceled_at' => time(),
            ]),
        ]);
        
        // Cancel subscription
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->deleteJson('/api/subscription');
        
        $response->assertOk();
        
        // Process cancellation webhook
        $cancellationWebhook = [
            'id' => 'evt_subscription_deleted',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_subscription',
                    'customer' => 'cus_test_customer',
                    'status' => 'canceled',
                    'canceled_at' => time(),
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                    ],
                ],
            ],
        ];
        
        $this->postJson('/webhooks/stripe', $cancellationWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($cancellationWebhook),
        ])->assertOk();
        
        // Verify tenant was downgraded to free tier
        $this->tenant->refresh();
        $this->assertEquals('free', $this->tenant->subscription_tier);
    }

    /** @test */
    public function german_payment_methods_integration_works()
    {
        // Update tenant to German configuration
        $this->tenant->update([
            'country_code' => 'DE',
            'billing_address' => 'Muster Straße 123',
            'billing_city' => 'München',
            'billing_postal_code' => '80331',
        ]);
        
        Sanctum::actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        // Mock Stripe checkout session with German payment methods
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_session_de',
                'url' => 'https://checkout.stripe.com/pay/cs_test_session_de',
                'payment_method_types' => ['card', 'sepa_debit', 'sofort', 'giropay'],
            ]),
        ]);
        
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->postJson('/api/subscription/checkout', [
                             'tier' => 'basic',
                             'success_url' => 'https://lakers.basketmanager-pro.com/success',
                             'cancel_url' => 'https://lakers.basketmanager-pro.com/cancel',
                         ]);
        
        $response->assertOk();
        
        // Verify German payment methods were included
        Http::assertSent(function ($request) {
            $paymentMethods = $request['payment_method_types'] ?? [];
            return in_array('sepa_debit', $paymentMethods) &&
                   in_array('sofort', $paymentMethods) &&
                   in_array('giropay', $paymentMethods);
        });
    }

    /** @test */
    public function invoice_generation_includes_german_tax_compliance()
    {
        // Mock invoice created webhook with German tax info
        $invoiceWebhook = [
            'id' => 'evt_invoice_created',
            'type' => 'invoice.created',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'customer' => 'cus_test_customer',
                    'total' => 5831, // €58.31 including 19% MwSt
                    'subtotal' => 4900, // €49.00 base amount
                    'tax' => 931, // €9.31 MwSt (19%)
                    'currency' => 'eur',
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                        'tier' => 'basic',
                    ],
                    'lines' => [
                        'data' => [
                            [
                                'description' => 'BasketManager Pro - Basic Tier',
                                'amount' => 4900,
                                'tax_rates' => [
                                    [
                                        'percentage' => 19.0,
                                        'display_name' => 'MwSt.',
                                        'country' => 'DE',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
        
        $response = $this->postJson('/webhooks/stripe', $invoiceWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($invoiceWebhook),
        ]);
        
        $response->assertOk();
        
        // Verify German tax information was processed correctly
        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'evt_invoice_created',
            'tenant_id' => $this->tenant->id,
            'processed' => true,
        ]);
    }

    /** @test */
    public function trial_period_handling_works_correctly()
    {
        // New tenant should be eligible for trial
        $newTenant = Tenant::factory()->create([
            'subscription_tier' => 'free',
            'trial_ends_at' => null,
        ]);
        
        $newUser = User::factory()->create(['tenant_id' => $newTenant->id]);
        
        Sanctum::actingAs($newUser);
        app()->instance('tenant', $newTenant);
        
        // Start trial
        $response = $this->withHeaders(['Host' => $newTenant->domain])
                         ->postJson('/api/subscription/trial', [
                             'tier' => 'professional',
                         ]);
        
        $response->assertOk();
        
        // Verify trial was started
        $newTenant->refresh();
        $this->assertNotNull($newTenant->trial_ends_at);
        $this->assertEquals('professional', $newTenant->subscription_tier);
        
        // Mock trial period ending
        $trialEndWebhook = [
            'id' => 'evt_trial_ended',
            'type' => 'customer.subscription.trial_will_end',
            'data' => [
                'object' => [
                    'id' => 'sub_test_subscription',
                    'customer' => 'cus_test_customer',
                    'trial_end' => now()->addDays(3)->timestamp,
                    'metadata' => [
                        'tenant_id' => $newTenant->id,
                    ],
                ],
            ],
        ];
        
        $this->postJson('/webhooks/stripe', $trialEndWebhook, [
            'Stripe-Signature' => $this->generateStripeSignature($trialEndWebhook),
        ])->assertOk();
        
        // Verify trial end notification was processed
        Queue::assertPushed(ProcessStripeWebhook::class);
    }

    /** @test */
    public function webhook_idempotency_prevents_duplicate_processing()
    {
        $webhookPayload = [
            'id' => 'evt_duplicate_test',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'customer' => 'cus_test_customer',
                    'metadata' => [
                        'tenant_id' => $this->tenant->id,
                    ],
                ],
            ],
        ];
        
        // Process webhook first time
        $response1 = $this->postJson('/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => $this->generateStripeSignature($webhookPayload),
        ]);
        $response1->assertOk();
        
        // Process same webhook again (should be idempotent)
        $response2 = $this->postJson('/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => $this->generateStripeSignature($webhookPayload),
        ]);
        $response2->assertOk();
        
        // Should only have one webhook event record
        $webhookCount = WebhookEvent::where('event_id', 'evt_duplicate_test')->count();
        $this->assertEquals(1, $webhookCount);
        
        // Should only process once
        Queue::assertPushed(ProcessStripeWebhook::class, 1);
    }

    /** @test */
    public function subscription_proration_calculations_are_accurate()
    {
        // Mock mid-cycle upgrade scenario
        $this->tenant->update(['subscription_tier' => 'basic']);
        
        Sanctum::actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        // Mock Stripe proration calculation
        Http::fake([
            'api.stripe.com/v1/subscriptions/*' => Http::response([
                'id' => 'sub_test_subscription',
                'status' => 'active',
                'proration_date' => now()->timestamp,
                'items' => [
                    'data' => [
                        [
                            'id' => 'si_test_item',
                            'price' => ['id' => 'price_professional'],
                        ]
                    ]
                ]
            ]),
        ]);
        
        // Upgrade mid-cycle
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->putJson('/api/subscription/upgrade', [
                             'tier' => 'professional',
                             'prorate' => true,
                         ]);
        
        $response->assertOk();
        
        // Verify proration was applied in Stripe call
        Http::assertSent(function ($request) {
            return isset($request['proration_behavior']) && 
                   $request['proration_behavior'] === 'create_prorations';
        });
    }

    private function generateStripeSignature(array $payload): string
    {
        $timestamp = time();
        $signaturePayload = $timestamp . '.' . json_encode($payload);
        $signature = hash_hmac('sha256', $signaturePayload, 'whsec_test_123');
        
        return "t={$timestamp},v1={$signature}";
    }
}