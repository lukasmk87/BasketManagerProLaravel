<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\ClubTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubFinancialService
{
    /**
     * Get financial summary for a club.
     */
    public function getFinancialSummary(Club $club, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ClubTransaction::where('club_id', $club->id);

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        $income = (clone $query)->income()->sum('amount');
        $expenses = (clone $query)->expense()->sum('amount');

        return [
            'total_income' => (float) $income,
            'total_expenses' => (float) $expenses,
            'balance' => (float) ($income - $expenses),
            'transaction_count' => $query->count(),
            'period' => [
                'start' => $startDate?->toDateString(),
                'end' => $endDate?->toDateString(),
            ],
        ];
    }

    /**
     * Get paginated transactions for a club.
     */
    public function getTransactions(Club $club, array $filters = []): LengthAwarePaginator
    {
        $query = ClubTransaction::where('club_id', $club->id)
            ->with('creator:id,name')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new transaction.
     */
    public function createTransaction(Club $club, array $data, User $creator): ClubTransaction
    {
        return ClubTransaction::create([
            'club_id' => $club->id,
            'type' => $data['type'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'description' => $data['description'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'reference_number' => $data['reference_number'] ?? null,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * Get monthly report for a club.
     */
    public function getMonthlyReport(Club $club, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = ClubTransaction::where('club_id', $club->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $incomeByCategory = $transactions
            ->where('type', ClubTransaction::TYPE_INCOME)
            ->groupBy('category')
            ->map(fn ($items) => $items->sum('amount'));

        $expensesByCategory = $transactions
            ->where('type', ClubTransaction::TYPE_EXPENSE)
            ->groupBy('category')
            ->map(fn ($items) => $items->sum('amount'));

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $startDate->translatedFormat('F'),
            'total_income' => (float) $transactions->where('type', ClubTransaction::TYPE_INCOME)->sum('amount'),
            'total_expenses' => (float) $transactions->where('type', ClubTransaction::TYPE_EXPENSE)->sum('amount'),
            'net' => (float) ($transactions->where('type', ClubTransaction::TYPE_INCOME)->sum('amount') -
                $transactions->where('type', ClubTransaction::TYPE_EXPENSE)->sum('amount')),
            'income_by_category' => $incomeByCategory->toArray(),
            'expenses_by_category' => $expensesByCategory->toArray(),
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Get category breakdown for a club.
     */
    public function getCategoryBreakdown(Club $club, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ClubTransaction::where('club_id', $club->id);

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        $breakdown = $query
            ->select('type', 'category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('type', 'category')
            ->get();

        $categories = ClubTransaction::getCategories();

        $income = [];
        $expenses = [];

        foreach ($breakdown as $item) {
            $data = [
                'category' => $item->category,
                'label' => $categories[$item->category] ?? $item->category,
                'total' => (float) $item->total,
                'count' => $item->count,
            ];

            if ($item->type === ClubTransaction::TYPE_INCOME) {
                $income[] = $data;
            } else {
                $expenses[] = $data;
            }
        }

        // Sort by total descending
        usort($income, fn ($a, $b) => $b['total'] <=> $a['total']);
        usort($expenses, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'period' => [
                'start' => $startDate?->toDateString(),
                'end' => $endDate?->toDateString(),
            ],
        ];
    }

    /**
     * Get yearly summary with monthly breakdown.
     */
    public function getYearlySummary(Club $club, int $year): array
    {
        $monthlySummary = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $monthData = ClubTransaction::where('club_id', $club->id)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->select(
                    DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
                    DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses')
                )
                ->first();

            $monthlySummary[] = [
                'month' => $month,
                'month_name' => $startDate->translatedFormat('M'),
                'income' => (float) ($monthData->income ?? 0),
                'expenses' => (float) ($monthData->expenses ?? 0),
                'net' => (float) (($monthData->income ?? 0) - ($monthData->expenses ?? 0)),
            ];
        }

        $totalIncome = collect($monthlySummary)->sum('income');
        $totalExpenses = collect($monthlySummary)->sum('expenses');

        return [
            'year' => $year,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net' => $totalIncome - $totalExpenses,
            'monthly' => $monthlySummary,
        ];
    }

    /**
     * Delete a transaction.
     */
    public function deleteTransaction(ClubTransaction $transaction): bool
    {
        try {
            $transaction->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete club transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
