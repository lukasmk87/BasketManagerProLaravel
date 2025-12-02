<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Services\Invoice\ClubInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring
                            {--tenant= : Process only specific tenant}
                            {--dry-run : Preview without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring invoices for clubs paying via invoice';

    /**
     * Execute the console command.
     */
    public function handle(ClubInvoiceService $invoiceService): int
    {
        $this->info('Generating recurring invoices...');

        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');

        // Get clubs that pay via invoice and need a new invoice
        $query = Club::where('payment_method_type', 'invoice')
            ->where('subscription_status', 'active')
            ->whereNotNull('club_subscription_plan_id')
            ->where('subscription_current_period_end', '<=', now()->addDays(7))
            ->with('subscriptionPlan', 'tenant');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $clubs = $query->get();

        if ($clubs->isEmpty()) {
            $this->info('No clubs require recurring invoices.');
            return Command::SUCCESS;
        }

        $this->info("Found {$clubs->count()} club(s) requiring invoices.");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($clubs as $club) {
            // Check if invoice already exists for this period
            $existingInvoice = $club->invoices()
                ->whereDate('issue_date', '>=', now()->startOfMonth())
                ->exists();

            if ($existingInvoice) {
                $this->line("  - Skipped: {$club->name} (invoice already exists)");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  - Would create: {$club->name} ({$club->subscriptionPlan->name})");
                $created++;
                continue;
            }

            try {
                $billingInterval = $this->determineBillingInterval($club);
                $billingPeriod = now()->format('d.m.Y') . ' - ' . ($billingInterval === 'yearly'
                    ? now()->addYear()->format('d.m.Y')
                    : now()->addMonth()->format('d.m.Y'));

                $invoice = $invoiceService->createForSubscription(
                    $club,
                    $club->subscriptionPlan,
                    $billingPeriod,
                    $billingInterval
                );

                // Mark as sent and send email
                $invoiceService->markAsSent($invoice);

                $this->info("  - Created: {$club->name} - Invoice #{$invoice->invoice_number}");
                $created++;

                Log::info("Recurring invoice created", [
                    'club_id' => $club->id,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ]);
            } catch (\Exception $e) {
                $this->error("  - Error: {$club->name} - {$e->getMessage()}");
                $errors++;

                Log::error("Failed to create recurring invoice", [
                    'club_id' => $club->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $created],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('Dry run - no invoices were actually created.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Determine billing interval for club.
     */
    protected function determineBillingInterval(Club $club): string
    {
        // Check the previous invoice to determine interval
        $lastInvoice = $club->invoices()->latest()->first();

        if ($lastInvoice && $lastInvoice->line_items) {
            $description = $lastInvoice->line_items[0]['description'] ?? '';
            if (str_contains(strtolower($description), 'yearly') || str_contains(strtolower($description), 'jährlich')) {
                return 'yearly';
            }
        }

        return 'monthly';
    }
}
