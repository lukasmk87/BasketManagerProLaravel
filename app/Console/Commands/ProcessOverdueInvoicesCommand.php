<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-overdue
                            {--type= : Filter by type (club, tenant)}
                            {--dry-run : Preview without processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process overdue invoices, send reminders, and suspend subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $invoiceService): int
    {
        $this->info('Processing overdue invoices...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->newLine();
        }

        try {
            if ($dryRun) {
                // Just show what would be processed
                $this->showDryRunPreview();
                return Command::SUCCESS;
            }

            $results = $invoiceService->processOverdueInvoices();

            $this->newLine();
            $this->table(
                ['Action', 'Count'],
                [
                    ['Marked overdue', $results['marked_overdue']],
                    ['Reminders sent', $results['reminders_sent']],
                    ['Subscriptions suspended', $results['subscriptions_suspended']],
                ]
            );

            Log::info('Processed overdue invoices', $results);

            $this->info('Overdue invoice processing completed.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error processing overdue invoices: {$e->getMessage()}");

            Log::error('Failed to process overdue invoices', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Show preview of what would be processed.
     */
    protected function showDryRunPreview(): void
    {
        $now = now();
        $type = $this->option('type');

        // Build base query with type filter
        $baseQuery = Invoice::query();
        if ($type === 'club') {
            $baseQuery->forClubs();
        } elseif ($type === 'tenant') {
            $baseQuery->forTenants();
        }

        // Invoices that would be marked overdue
        $wouldMarkOverdue = (clone $baseQuery)->sent()
            ->where('due_date', '<', $now)
            ->count();

        // Invoices eligible for reminders
        $maxReminders = config('invoices.reminders.max_reminders', 3);
        $wouldSendReminders = (clone $baseQuery)->overdue()
            ->where('reminder_count', '<', $maxReminders)
            ->count();

        // Entities that would be suspended
        $suspendAfterDays = config('invoices.suspension.days_after_due', 30);
        $wouldSuspend = (clone $baseQuery)->overdue()
            ->whereDate('due_date', '<=', $now->copy()->subDays($suspendAfterDays))
            ->count();

        $this->table(
            ['Action', 'Would Process'],
            [
                ['Mark overdue', $wouldMarkOverdue],
                ['Send reminders (max)', $wouldSendReminders],
                ['Suspend subscriptions', $wouldSuspend],
            ]
        );

        $this->newLine();
        $this->info("Reminder intervals: " . implode(', ', config('invoices.reminders.intervals', [7, 14, 21])) . " days");
        $this->info("Suspension after: {$suspendAfterDays} days overdue");

        if ($type) {
            $this->info("Filtered by type: {$type}");
        }
    }
}
