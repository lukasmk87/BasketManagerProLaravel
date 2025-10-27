<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Services\Stripe\ClubInvoiceService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Collection as StripeCollection;
use Stripe\Invoice;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubInvoiceService $service;

    protected StripeClientManager $clientManager;

    protected StripeClient $stripeClient;

    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test club with Stripe customer
        $this->club = Club::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => 'sub_test_123',
        ]);

        // Mock StripeClientManager
        $this->clientManager = Mockery::mock(StripeClientManager::class);

        // Mock StripeClient
        $this->stripeClient = Mockery::mock(StripeClient::class);
        $this->stripeClient->invoices = Mockery::mock();

        // Configure client manager to return mocked client
        $this->clientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->stripeClient);

        // Create service with mocked dependencies
        $this->service = new ClubInvoiceService($this->clientManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_retrieves_club_invoices()
    {
        $mockInvoices = $this->createMockInvoiceCollection();

        $this->stripeClient->invoices
            ->shouldReceive('all')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'limit' => 100,
            ])
            ->andReturn($mockInvoices);

        $invoices = $this->service->getInvoices($this->club);

        $this->assertCount(2, $invoices);
        $this->assertEquals('in_test_001', $invoices->first()['id']);
        $this->assertEquals(49.99, $invoices->first()['amount_due']);
    }

    /** @test */
    public function it_retrieves_invoices_with_custom_limit()
    {
        $mockInvoices = $this->createMockInvoiceCollection();

        $this->stripeClient->invoices
            ->shouldReceive('all')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'limit' => 50,
            ])
            ->andReturn($mockInvoices);

        $invoices = $this->service->getInvoices($this->club, ['limit' => 50]);

        $this->assertCount(2, $invoices);
    }

    /** @test */
    public function it_retrieves_invoices_with_status_filter()
    {
        $mockInvoices = $this->createMockInvoiceCollection();

        $this->stripeClient->invoices
            ->shouldReceive('all')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'limit' => 100,
                'status' => 'paid',
            ])
            ->andReturn($mockInvoices);

        $invoices = $this->service->getInvoices($this->club, ['status' => 'paid']);

        $this->assertCount(2, $invoices);
    }

    /** @test */
    public function it_throws_exception_when_club_has_no_stripe_customer()
    {
        $clubWithoutCustomer = Club::factory()->create([
            'stripe_customer_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no Stripe customer');

        $this->service->getInvoices($clubWithoutCustomer);
    }

    /** @test */
    public function it_retrieves_single_invoice()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'paid');

        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        $invoice = $this->service->getInvoice($this->club, 'in_test_001');

        $this->assertEquals('in_test_001', $invoice['id']);
        $this->assertEquals(49.99, $invoice['amount_due']);
        $this->assertEquals('paid', $invoice['status']);
    }

    /** @test */
    public function it_throws_exception_when_invoice_does_not_belong_to_club()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'paid', 'cus_other_456');

        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice does not belong to this club');

        $this->service->getInvoice($this->club, 'in_test_001');
    }

    /** @test */
    public function it_retrieves_upcoming_invoice()
    {
        $mockInvoice = $this->createMockInvoice('upcoming', 14900, 'draft');

        $this->stripeClient->invoices
            ->shouldReceive('upcoming')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
            ])
            ->andReturn($mockInvoice);

        $upcomingInvoice = $this->service->getUpcomingInvoice($this->club);

        $this->assertNotNull($upcomingInvoice);
        $this->assertEquals(149.00, $upcomingInvoice['amount_due']);
    }

    /** @test */
    public function it_returns_null_when_club_has_no_subscription_for_upcoming_invoice()
    {
        $clubWithoutSubscription = Club::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
            'stripe_subscription_id' => null,
        ]);

        $upcomingInvoice = $this->service->getUpcomingInvoice($clubWithoutSubscription);

        $this->assertNull($upcomingInvoice);
    }

    /** @test */
    public function it_retrieves_upcoming_invoice_with_proration_date()
    {
        $mockInvoice = $this->createMockInvoice('upcoming', 14900, 'draft');

        $this->stripeClient->invoices
            ->shouldReceive('upcoming')
            ->once()
            ->with([
                'customer' => 'cus_test_123',
                'subscription_proration_date' => 1234567890,
            ])
            ->andReturn($mockInvoice);

        $upcomingInvoice = $this->service->getUpcomingInvoice($this->club, [
            'subscription_proration_date' => 1234567890,
        ]);

        $this->assertNotNull($upcomingInvoice);
    }

    /** @test */
    public function it_gets_invoice_pdf_url()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'paid');

        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        $pdfUrl = $this->service->getInvoicePdfUrl($this->club, 'in_test_001');

        $this->assertEquals('https://stripe.com/invoice/pdf/in_test_001', $pdfUrl);
    }

    /** @test */
    public function it_gets_invoice_payment_intent()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'open');
        $mockInvoice->payment_intent = 'pi_test_123';

        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        $paymentIntentId = $this->service->getInvoicePaymentIntent($this->club, 'in_test_001');

        $this->assertEquals('pi_test_123', $paymentIntentId);
    }

    /** @test */
    public function it_pays_invoice()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'open');

        // First call to retrieve for verification
        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        // Second call to pay
        $mockInvoice->status = 'paid';
        $mockInvoice->amount_paid = 4999;
        $this->stripeClient->invoices
            ->shouldReceive('pay')
            ->once()
            ->with('in_test_001', [])
            ->andReturn($mockInvoice);

        $paidInvoice = $this->service->payInvoice($this->club, 'in_test_001');

        $this->assertEquals('paid', $paidInvoice['status']);
        $this->assertEquals(49.99, $paidInvoice['amount_paid']);
    }

    /** @test */
    public function it_pays_invoice_with_payment_method()
    {
        $mockInvoice = $this->createMockInvoice('in_test_001', 4999, 'open');

        $this->stripeClient->invoices
            ->shouldReceive('retrieve')
            ->once()
            ->with('in_test_001')
            ->andReturn($mockInvoice);

        $mockInvoice->status = 'paid';
        $mockInvoice->amount_paid = 4999;
        $this->stripeClient->invoices
            ->shouldReceive('pay')
            ->once()
            ->with('in_test_001', ['payment_method' => 'pm_test_123'])
            ->andReturn($mockInvoice);

        $paidInvoice = $this->service->payInvoice($this->club, 'in_test_001', [
            'payment_method' => 'pm_test_123',
        ]);

        $this->assertEquals('paid', $paidInvoice['status']);
    }

    /**
     * Helper: Create a mock Stripe Invoice
     */
    protected function createMockInvoice(
        string $id,
        int $amountDue,
        string $status,
        string $customer = null
    ): Invoice {
        $invoice = Mockery::mock(Invoice::class);
        $invoice->id = $id;
        $invoice->object = 'invoice';
        $invoice->amount_due = $amountDue;
        $invoice->amount_paid = $status === 'paid' ? $amountDue : 0;
        $invoice->amount_remaining = $status === 'paid' ? 0 : $amountDue;
        $invoice->currency = 'eur';
        $invoice->status = $status;
        $invoice->paid = $status === 'paid';
        $invoice->attempted = true;
        $invoice->number = 'INV-'.strtoupper($id);
        $invoice->invoice_pdf = 'https://stripe.com/invoice/pdf/'.$id;
        $invoice->hosted_invoice_url = 'https://stripe.com/invoice/'.$id;
        $invoice->created = time();
        $invoice->due_date = time() + 86400;
        $invoice->period_start = time() - 86400 * 30;
        $invoice->period_end = time();
        $invoice->subscription = 'sub_test_123';
        $invoice->customer = $customer ?? 'cus_test_123';
        $invoice->payment_intent = null;
        $invoice->description = 'Test Invoice';
        $invoice->subtotal = $amountDue;
        $invoice->total = $amountDue;
        $invoice->tax = null;
        $invoice->discount = null;
        $invoice->billing_reason = 'subscription_cycle';
        $invoice->collection_method = 'charge_automatically';
        $invoice->charge = null;

        // Mock invoice lines
        $invoice->lines = $this->createMockInvoiceLines($amountDue);

        return $invoice;
    }

    /**
     * Helper: Create mock invoice lines
     */
    protected function createMockInvoiceLines(int $amount): StripeCollection
    {
        $line = Mockery::mock();
        $line->id = 'il_test_001';
        $line->amount = $amount;
        $line->currency = 'eur';
        $line->description = 'Subscription line item';
        $line->quantity = 1;
        $line->period = (object) [
            'start' => time() - 86400 * 30,
            'end' => time(),
        ];
        $line->proration = false;
        $line->plan = null;

        $collection = Mockery::mock(StripeCollection::class);
        $collection->data = [$line];

        return $collection;
    }

    /**
     * Helper: Create mock invoice collection
     */
    protected function createMockInvoiceCollection(): StripeCollection
    {
        $invoice1 = $this->createMockInvoice('in_test_001', 4999, 'paid');
        $invoice2 = $this->createMockInvoice('in_test_002', 14900, 'open');

        $collection = Mockery::mock(StripeCollection::class);
        $collection->data = [$invoice1, $invoice2];

        return $collection;
    }
}
