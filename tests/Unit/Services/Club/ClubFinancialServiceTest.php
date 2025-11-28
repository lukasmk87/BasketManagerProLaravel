<?php

namespace Tests\Unit\Services\Club;

use App\Models\Club;
use App\Models\ClubTransaction;
use App\Models\User;
use App\Services\Club\ClubFinancialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubFinancialServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubFinancialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClubFinancialService();
    }

    public function test_gets_financial_summary(): void
    {
        $club = Club::factory()->create();

        // Create income transactions
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(1000)
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(500)
            ->create();

        // Create expense transactions
        ClubTransaction::factory()
            ->forClub($club)
            ->expense()
            ->amount(300)
            ->create();

        $summary = $this->service->getFinancialSummary($club);

        $this->assertEquals(1500.00, $summary['total_income']);
        $this->assertEquals(300.00, $summary['total_expenses']);
        $this->assertEquals(1200.00, $summary['balance']);
        $this->assertEquals(3, $summary['transaction_count']);
    }

    public function test_gets_financial_summary_with_date_range(): void
    {
        $club = Club::factory()->create();

        // Create transaction in range
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(1000)
            ->onDate('2024-06-15')
            ->create();

        // Create transaction outside range
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(500)
            ->onDate('2024-01-15')
            ->create();

        $startDate = Carbon::create(2024, 6, 1);
        $endDate = Carbon::create(2024, 6, 30);

        $summary = $this->service->getFinancialSummary($club, $startDate, $endDate);

        $this->assertEquals(1000.00, $summary['total_income']);
        $this->assertEquals(0.00, $summary['total_expenses']);
        $this->assertEquals(1, $summary['transaction_count']);
        $this->assertEquals('2024-06-01', $summary['period']['start']);
        $this->assertEquals('2024-06-30', $summary['period']['end']);
    }

    public function test_returns_empty_summary_for_club_without_transactions(): void
    {
        $club = Club::factory()->create();

        $summary = $this->service->getFinancialSummary($club);

        $this->assertEquals(0.00, $summary['total_income']);
        $this->assertEquals(0.00, $summary['total_expenses']);
        $this->assertEquals(0.00, $summary['balance']);
        $this->assertEquals(0, $summary['transaction_count']);
    }

    public function test_gets_paginated_transactions(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->count(20)
            ->create();

        $transactions = $this->service->getTransactions($club, ['per_page' => 10]);

        $this->assertCount(10, $transactions);
        $this->assertEquals(20, $transactions->total());
        $this->assertEquals(2, $transactions->lastPage());
    }

    public function test_filters_transactions_by_type(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->income()->count(3)->create();
        ClubTransaction::factory()->forClub($club)->expense()->count(2)->create();

        $incomeTransactions = $this->service->getTransactions($club, ['type' => 'income']);
        $expenseTransactions = $this->service->getTransactions($club, ['type' => 'expense']);

        $this->assertEquals(3, $incomeTransactions->total());
        $this->assertEquals(2, $expenseTransactions->total());
    }

    public function test_filters_transactions_by_category(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->category(ClubTransaction::CATEGORY_MEMBERSHIP_FEE)
            ->count(4)
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->category(ClubTransaction::CATEGORY_EQUIPMENT)
            ->count(2)
            ->create();

        $membershipTransactions = $this->service->getTransactions($club, [
            'category' => ClubTransaction::CATEGORY_MEMBERSHIP_FEE,
        ]);

        $this->assertEquals(4, $membershipTransactions->total());
    }

    public function test_filters_transactions_by_date_range(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->onDate('2024-03-15')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-06-15')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-09-15')->create();

        $transactions = $this->service->getTransactions($club, [
            'start_date' => '2024-04-01',
            'end_date' => '2024-08-31',
        ]);

        $this->assertEquals(1, $transactions->total());
    }

    public function test_filters_transactions_by_search(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->create([
            'description' => 'Hallenmiete Januar',
            'reference_number' => 'INV-2024-001',
        ]);

        ClubTransaction::factory()->forClub($club)->create([
            'description' => 'Ausrüstung Bälle',
            'reference_number' => 'INV-2024-002',
        ]);

        $hallTransactions = $this->service->getTransactions($club, ['search' => 'Hallenmiete']);
        $this->assertEquals(1, $hallTransactions->total());

        $refTransactions = $this->service->getTransactions($club, ['search' => 'INV-2024-001']);
        $this->assertEquals(1, $refTransactions->total());
    }

    public function test_creates_transaction(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $data = [
            'type' => ClubTransaction::TYPE_INCOME,
            'category' => ClubTransaction::CATEGORY_MEMBERSHIP_FEE,
            'amount' => 150.50,
            'currency' => 'EUR',
            'description' => 'Mitgliedsbeitrag Max Mustermann',
            'transaction_date' => '2024-06-01',
            'reference_number' => 'MB-2024-001',
        ];

        $transaction = $this->service->createTransaction($club, $data, $user);

        $this->assertInstanceOf(ClubTransaction::class, $transaction);
        $this->assertEquals($club->id, $transaction->club_id);
        $this->assertEquals($user->id, $transaction->created_by);
        $this->assertEquals(ClubTransaction::TYPE_INCOME, $transaction->type);
        $this->assertEquals(ClubTransaction::CATEGORY_MEMBERSHIP_FEE, $transaction->category);
        $this->assertEquals(150.50, $transaction->amount);
        $this->assertEquals('Mitgliedsbeitrag Max Mustermann', $transaction->description);
        $this->assertEquals('MB-2024-001', $transaction->reference_number);

        $this->assertDatabaseHas('club_transactions', [
            'id' => $transaction->id,
            'club_id' => $club->id,
            'type' => 'income',
            'category' => 'membership_fee',
        ]);
    }

    public function test_creates_transaction_with_minimal_data(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $data = [
            'type' => ClubTransaction::TYPE_EXPENSE,
            'category' => ClubTransaction::CATEGORY_OTHER,
            'amount' => 50.00,
            'transaction_date' => '2024-06-15',
        ];

        $transaction = $this->service->createTransaction($club, $data, $user);

        $this->assertInstanceOf(ClubTransaction::class, $transaction);
        $this->assertEquals('EUR', $transaction->currency);
        $this->assertNull($transaction->description);
        $this->assertNull($transaction->reference_number);
    }

    public function test_gets_monthly_report(): void
    {
        $club = Club::factory()->create();

        // Create income transactions for June 2024
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->category(ClubTransaction::CATEGORY_MEMBERSHIP_FEE)
            ->amount(500)
            ->onDate('2024-06-10')
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->category(ClubTransaction::CATEGORY_SPONSOR)
            ->amount(1000)
            ->onDate('2024-06-15')
            ->create();

        // Create expense transactions for June 2024
        ClubTransaction::factory()
            ->forClub($club)
            ->expense()
            ->category(ClubTransaction::CATEGORY_FACILITY)
            ->amount(300)
            ->onDate('2024-06-20')
            ->create();

        $report = $this->service->getMonthlyReport($club, 2024, 6);

        $this->assertEquals(2024, $report['year']);
        $this->assertEquals(6, $report['month']);
        $this->assertEquals(1500.00, $report['total_income']);
        $this->assertEquals(300.00, $report['total_expenses']);
        $this->assertEquals(1200.00, $report['net']);
        $this->assertEquals(3, $report['transaction_count']);

        // Check category breakdown
        $this->assertArrayHasKey('membership_fee', $report['income_by_category']);
        $this->assertArrayHasKey('sponsor', $report['income_by_category']);
        $this->assertEquals(500.00, $report['income_by_category']['membership_fee']);
        $this->assertEquals(1000.00, $report['income_by_category']['sponsor']);

        $this->assertArrayHasKey('facility', $report['expenses_by_category']);
        $this->assertEquals(300.00, $report['expenses_by_category']['facility']);
    }

    public function test_gets_empty_monthly_report(): void
    {
        $club = Club::factory()->create();

        $report = $this->service->getMonthlyReport($club, 2024, 6);

        $this->assertEquals(0.00, $report['total_income']);
        $this->assertEquals(0.00, $report['total_expenses']);
        $this->assertEquals(0.00, $report['net']);
        $this->assertEquals(0, $report['transaction_count']);
        $this->assertEmpty($report['income_by_category']);
        $this->assertEmpty($report['expenses_by_category']);
    }

    public function test_gets_category_breakdown(): void
    {
        $club = Club::factory()->create();

        // Create multiple income categories
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->category(ClubTransaction::CATEGORY_MEMBERSHIP_FEE)
            ->count(5)
            ->amount(100)
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->category(ClubTransaction::CATEGORY_SPONSOR)
            ->count(2)
            ->amount(500)
            ->create();

        // Create expense categories
        ClubTransaction::factory()
            ->forClub($club)
            ->expense()
            ->category(ClubTransaction::CATEGORY_FACILITY)
            ->count(3)
            ->amount(200)
            ->create();

        $breakdown = $this->service->getCategoryBreakdown($club);

        $this->assertArrayHasKey('income', $breakdown);
        $this->assertArrayHasKey('expenses', $breakdown);

        // Check income is sorted by total descending
        $this->assertGreaterThanOrEqual(
            $breakdown['income'][1]['total'] ?? 0,
            $breakdown['income'][0]['total']
        );
    }

    public function test_gets_yearly_summary(): void
    {
        $club = Club::factory()->create();

        // Create transactions for different months
        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(1000)
            ->onDate('2024-01-15')
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->income()
            ->amount(800)
            ->onDate('2024-06-15')
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->expense()
            ->amount(300)
            ->onDate('2024-06-20')
            ->create();

        ClubTransaction::factory()
            ->forClub($club)
            ->expense()
            ->amount(200)
            ->onDate('2024-12-10')
            ->create();

        $summary = $this->service->getYearlySummary($club, 2024);

        $this->assertEquals(2024, $summary['year']);
        $this->assertEquals(1800.00, $summary['total_income']);
        $this->assertEquals(500.00, $summary['total_expenses']);
        $this->assertEquals(1300.00, $summary['net']);

        // Check monthly breakdown
        $this->assertCount(12, $summary['monthly']);

        // January
        $this->assertEquals(1000.00, $summary['monthly'][0]['income']);
        $this->assertEquals(0.00, $summary['monthly'][0]['expenses']);
        $this->assertEquals(1000.00, $summary['monthly'][0]['net']);

        // June (index 5)
        $this->assertEquals(800.00, $summary['monthly'][5]['income']);
        $this->assertEquals(300.00, $summary['monthly'][5]['expenses']);
        $this->assertEquals(500.00, $summary['monthly'][5]['net']);

        // December (index 11)
        $this->assertEquals(0.00, $summary['monthly'][11]['income']);
        $this->assertEquals(200.00, $summary['monthly'][11]['expenses']);
        $this->assertEquals(-200.00, $summary['monthly'][11]['net']);
    }

    public function test_deletes_transaction(): void
    {
        $club = Club::factory()->create();
        $transaction = ClubTransaction::factory()->forClub($club)->create();

        $result = $this->service->deleteTransaction($transaction);

        $this->assertTrue($result);
        $this->assertSoftDeleted('club_transactions', ['id' => $transaction->id]);
    }

    public function test_transactions_are_ordered_by_date_descending(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->onDate('2024-01-01')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-06-01')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-03-01')->create();

        $transactions = $this->service->getTransactions($club);

        $dates = $transactions->pluck('transaction_date')->map(fn ($d) => $d->format('Y-m-d'))->toArray();

        $this->assertEquals('2024-06-01', $dates[0]);
        $this->assertEquals('2024-03-01', $dates[1]);
        $this->assertEquals('2024-01-01', $dates[2]);
    }
}
