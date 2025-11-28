<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\StoreClubTransactionRequest;
use App\Models\ClubTransaction;
use App\Services\Club\ClubFinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubFinancialController extends Controller
{
    public function __construct(
        private ClubFinancialService $financialService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show financial management page.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $filters = [
            'type' => $request->get('type'),
            'category' => $request->get('category'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $summary = $this->financialService->getFinancialSummary($primaryClub);
        $transactions = $this->financialService->getTransactions($primaryClub, $filters);
        $categoryBreakdown = $this->financialService->getCategoryBreakdown($primaryClub);
        $monthlyReport = $this->financialService->getMonthlyReport($primaryClub, now()->year);

        return Inertia::render('ClubAdmin/Financial/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'financial_data' => [
                'total_income' => $summary['total_income'],
                'total_expenses' => $summary['total_expenses'],
                'balance' => $summary['balance'],
                'transaction_count' => $summary['transaction_count'],
            ],
            'transactions' => $transactions,
            'category_breakdown' => $categoryBreakdown,
            'monthly_report' => $monthlyReport,
            'categories' => ClubTransaction::getCategories(),
            'filters' => $filters,
        ]);
    }

    /**
     * Show form to create a new transaction.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        return Inertia::render('ClubAdmin/Financial/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'categories' => ClubTransaction::getCategories(),
            'types' => [
                ['value' => 'income', 'label' => 'Einnahme'],
                ['value' => 'expense', 'label' => 'Ausgabe'],
            ],
        ]);
    }

    /**
     * Store a new transaction.
     */
    public function store(StoreClubTransactionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $validated = $request->validated();

        try {
            $transaction = $this->financialService->createTransaction(
                $primaryClub,
                $validated,
                $user
            );

            Log::info('Club admin created transaction', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'transaction_id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
            ]);

            return redirect()->route('club-admin.financial.index')
                ->with('success', 'Transaktion wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            Log::error('Failed to create transaction', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Erstellen der Transaktion: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show a specific transaction.
     */
    public function show(ClubTransaction $transaction): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (! in_array($transaction->club_id, $adminClubIds)) {
            abort(403, 'Diese Transaktion gehört nicht zu einem Ihrer Clubs.');
        }

        $transaction->load('creator:id,name');

        return Inertia::render('ClubAdmin/Financial/Show', [
            'club' => [
                'id' => $transaction->club_id,
                'name' => $transaction->club->name,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'category_label' => ClubTransaction::getCategories()[$transaction->category] ?? $transaction->category,
                'amount' => $transaction->amount,
                'formatted_amount' => $transaction->formatted_amount,
                'currency' => $transaction->currency,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                'reference_number' => $transaction->reference_number,
                'created_by' => $transaction->creator?->name,
                'created_at' => $transaction->created_at,
            ],
        ]);
    }

    /**
     * Delete a transaction.
     */
    public function destroy(ClubTransaction $transaction): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (! in_array($transaction->club_id, $adminClubIds)) {
            abort(403, 'Diese Transaktion gehört nicht zu einem Ihrer Clubs.');
        }

        try {
            $this->financialService->deleteTransaction($transaction);

            Log::info('Club admin deleted transaction', [
                'club_admin_id' => $user->id,
                'club_id' => $transaction->club_id,
                'transaction_id' => $transaction->id,
            ]);

            return redirect()->route('club-admin.financial.index')
                ->with('success', 'Transaktion wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            Log::error('Failed to delete transaction', [
                'club_admin_id' => $user->id,
                'club_id' => $transaction->club_id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Löschen der Transaktion: ' . $e->getMessage());
        }
    }

    /**
     * Export transactions as CSV.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $filters = [
            'type' => $request->get('type'),
            'category' => $request->get('category'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $transactions = $this->financialService->getTransactions($primaryClub, $filters, false);

        $filename = 'transaktionen_' . $primaryClub->id . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Datum',
                'Typ',
                'Kategorie',
                'Betrag',
                'Währung',
                'Beschreibung',
                'Referenznummer',
            ], ';');

            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->transaction_date->format('d.m.Y'),
                    $transaction->type === 'income' ? 'Einnahme' : 'Ausgabe',
                    ClubTransaction::getCategories()[$transaction->category] ?? $transaction->category,
                    number_format($transaction->amount, 2, ',', '.'),
                    $transaction->currency,
                    $transaction->description,
                    $transaction->reference_number,
                ], ';');
            }

            fclose($handle);
        };

        Log::info('Club admin exported transactions', [
            'club_admin_id' => $user->id,
            'club_id' => $primaryClub->id,
            'transaction_count' => $transactions->count(),
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show yearly summary report.
     */
    public function yearlyReport(Request $request): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $year = $request->get('year', now()->year);

        $yearlySummary = $this->financialService->getYearlySummary($primaryClub, $year);
        $monthlyReport = $this->financialService->getMonthlyReport($primaryClub, $year);

        return Inertia::render('ClubAdmin/Financial/YearlyReport', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'year' => $year,
            'yearly_summary' => $yearlySummary,
            'monthly_report' => $monthlyReport,
            'available_years' => range(now()->year, now()->year - 5),
        ]);
    }
}
