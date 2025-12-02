<?php

namespace App\Console\Commands;

use App\Services\Invoice\ClubInvoiceService;
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
    public function handle(ClubInvoiceService $invoiceService): int
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

        // Invoices that would be marked overdue
        $wouldMarkOverdue = \App\Models\ClubInvoice::sent()
            ->where('due_date', '<', $now)
            ->count();

        // Invoices eligible for reminders
        $maxReminders = config('invoices.reminders.max_reminders', 3);
        $wouldSendReminders = \App\Models\ClubInvoice::overdue()
            ->where('reminder_count', '<', $maxReminders)
            ->count();

        // Clubs that would be suspended
        $suspendAfterDays = config('invoices.suspension.days_after_due', 30);
        $wouldSuspend = \App\Models\ClubInvoice::overdue()
            ->whereDate('due_date', '<=', $now->copy()->subDays($suspendAfterDays))
            ->whereHas('club', function ($query) {
                $query->where('subscription_status', '!=', 'suspended');
            })
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
    }
}
