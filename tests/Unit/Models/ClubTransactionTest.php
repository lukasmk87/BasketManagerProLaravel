<?php

namespace Tests\Unit\Models;

use App\Models\Club;
use App\Models\ClubTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_club(): void
    {
        $club = Club::factory()->create();
        $transaction = ClubTransaction::factory()->forClub($club)->create();

        $this->assertInstanceOf(Club::class, $transaction->club);
        $this->assertEquals($club->id, $transaction->club->id);
    }

    public function test_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $transaction = ClubTransaction::factory()->createdBy($user)->create();

        $this->assertInstanceOf(User::class, $transaction->creator);
        $this->assertEquals($user->id, $transaction->creator->id);
    }

    public function test_scope_income_filters_correctly(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->income()->count(3)->create();
        ClubTransaction::factory()->forClub($club)->expense()->count(2)->create();

        $incomeCount = ClubTransaction::where('club_id', $club->id)->income()->count();

        $this->assertEquals(3, $incomeCount);
    }

    public function test_scope_expense_filters_correctly(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->income()->count(3)->create();
        ClubTransaction::factory()->forClub($club)->expense()->count(2)->create();

        $expenseCount = ClubTransaction::where('club_id', $club->id)->expense()->count();

        $this->assertEquals(2, $expenseCount);
    }

    public function test_scope_category_filters_correctly(): void
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

        $membershipCount = ClubTransaction::where('club_id', $club->id)
            ->category(ClubTransaction::CATEGORY_MEMBERSHIP_FEE)
            ->count();

        $this->assertEquals(4, $membershipCount);
    }

    public function test_scope_date_range_filters_correctly(): void
    {
        $club = Club::factory()->create();

        ClubTransaction::factory()->forClub($club)->onDate('2024-01-15')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-06-15')->create();
        ClubTransaction::factory()->forClub($club)->onDate('2024-12-15')->create();

        $count = ClubTransaction::where('club_id', $club->id)
            ->dateRange('2024-04-01', '2024-09-30')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_is_income_returns_true_for_income_type(): void
    {
        $transaction = ClubTransaction::factory()->income()->create();

        $this->assertTrue($transaction->isIncome());
        $this->assertFalse($transaction->isExpense());
    }

    public function test_is_expense_returns_true_for_expense_type(): void
    {
        $transaction = ClubTransaction::factory()->expense()->create();

        $this->assertTrue($transaction->isExpense());
        $this->assertFalse($transaction->isIncome());
    }

    public function test_signed_amount_is_positive_for_income(): void
    {
        $transaction = ClubTransaction::factory()
            ->income()
            ->amount(500)
            ->create();

        $this->assertEquals(500.00, $transaction->signed_amount);
    }

    public function test_signed_amount_is_negative_for_expense(): void
    {
        $transaction = ClubTransaction::factory()
            ->expense()
            ->amount(500)
            ->create();

        $this->assertEquals(-500.00, $transaction->signed_amount);
    }

    public function test_category_label_attribute(): void
    {
        $transaction = ClubTransaction::factory()
            ->category(ClubTransaction::CATEGORY_MEMBERSHIP_FEE)
            ->create();

        $this->assertEquals('Mitgliedsbeiträge', $transaction->category_label);
    }

    public function test_category_label_returns_raw_value_for_unknown_category(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        // Create directly to bypass factory validation
        $transaction = ClubTransaction::create([
            'club_id' => $club->id,
            'type' => ClubTransaction::TYPE_EXPENSE,
            'category' => 'unknown_category',
            'amount' => 100,
            'currency' => 'EUR',
            'transaction_date' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertEquals('unknown_category', $transaction->category_label);
    }

    public function test_get_categories_returns_all_categories(): void
    {
        $categories = ClubTransaction::getCategories();

        $this->assertIsArray($categories);
        $this->assertCount(9, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_MEMBERSHIP_FEE, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_EQUIPMENT, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_FACILITY, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_EVENT, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_SPONSOR, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_TRAVEL, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_SALARY, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_INSURANCE, $categories);
        $this->assertArrayHasKey(ClubTransaction::CATEGORY_OTHER, $categories);
    }

    public function test_soft_deletes_transaction(): void
    {
        $transaction = ClubTransaction::factory()->create();
        $transactionId = $transaction->id;

        $transaction->delete();

        $this->assertSoftDeleted('club_transactions', ['id' => $transactionId]);
        $this->assertNull(ClubTransaction::find($transactionId));
        $this->assertNotNull(ClubTransaction::withTrashed()->find($transactionId));
    }

    public function test_transaction_date_is_cast_to_date(): void
    {
        $transaction = ClubTransaction::factory()->onDate('2024-06-15')->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->transaction_date);
        $this->assertEquals('2024-06-15', $transaction->transaction_date->format('Y-m-d'));
    }

    public function test_amount_is_cast_to_decimal(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $transaction = ClubTransaction::create([
            'club_id' => $club->id,
            'type' => ClubTransaction::TYPE_INCOME,
            'category' => ClubTransaction::CATEGORY_OTHER,
            'amount' => 150.5555,
            'currency' => 'EUR',
            'transaction_date' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertEquals('150.56', $transaction->amount);
    }

    public function test_constants_are_defined(): void
    {
        $this->assertEquals('income', ClubTransaction::TYPE_INCOME);
        $this->assertEquals('expense', ClubTransaction::TYPE_EXPENSE);

        $this->assertEquals('membership_fee', ClubTransaction::CATEGORY_MEMBERSHIP_FEE);
        $this->assertEquals('equipment', ClubTransaction::CATEGORY_EQUIPMENT);
        $this->assertEquals('facility', ClubTransaction::CATEGORY_FACILITY);
        $this->assertEquals('event', ClubTransaction::CATEGORY_EVENT);
        $this->assertEquals('sponsor', ClubTransaction::CATEGORY_SPONSOR);
        $this->assertEquals('travel', ClubTransaction::CATEGORY_TRAVEL);
        $this->assertEquals('salary', ClubTransaction::CATEGORY_SALARY);
        $this->assertEquals('insurance', ClubTransaction::CATEGORY_INSURANCE);
        $this->assertEquals('other', ClubTransaction::CATEGORY_OTHER);
    }
}
