<?php

namespace App\Console\Commands;

use App\Contracts\Invoiceable;
use App\Models\Club;
use App\Models\Tenant;
use App\Services\Invoice\InvoiceService;
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
                            {--type= : Filter by type (club, tenant)}
                            {--tenant= : Process only specific tenant}
                            {--dry-run : Preview without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring invoices for clubs and tenants paying via invoice';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $invoiceService): int
    {
        $this->info('Generating recurring invoices...');

        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $type = $this->option('type');

        $created = 0;
        $skipped = 0;
        $errors = 0;

        // Process Clubs (if no type filter or type is 'club')
        if (!$type || $type === 'club') {
            $result = $this->processClubs($invoiceService, $tenantId, $dryRun);
            $created += $result['created'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
        }

        // Process Tenants (if no type filter or type is 'tenant')
        if (!$type || $type === 'tenant') {
            $result = $this->processTenants($invoiceService, $tenantId, $dryRun);
            $created += $result['created'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
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
     * Process clubs that need recurring invoices.
     */
    protected function processClubs(InvoiceService $invoiceService, ?string $tenantId, bool $dryRun): array
    {
        $this->info("\nProcessing Clubs...");

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
            $this->info('  No clubs require recurring invoices.');
            return ['created' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $this->info("  Found {$clubs->count()} club(s) requiring invoices.");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($clubs as $club) {
            $result = $this->processInvoiceable($invoiceService, $club, $dryRun, 'Club');
            $created += $result['created'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Process tenants that need recurring invoices.
     */
    protected function processTenants(InvoiceService $invoiceService, ?string $tenantId, bool $dryRun): array
    {
        $this->info("\nProcessing Tenants...");

        $query = Tenant::where('pays_via_invoice', true)
            ->where('is_active', true)
            ->whereNotNull('subscription_plan_id')
            ->where('subscription_ends_at', '<=', now()->addDays(7));

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('  No tenants require recurring invoices.');
            return ['created' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $this->info("  Found {$tenants->count()} tenant(s) requiring invoices.");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            $result = $this->processInvoiceable($invoiceService, $tenant, $dryRun, 'Tenant');
            $created += $result['created'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Process a single invoiceable entity.
     */
    protected function processInvoiceable(InvoiceService $invoiceService, Invoiceable $entity, bool $dryRun, string $type): array
    {
        $name = $entity->getBillingName();
        $plan = $entity->getSubscriptionPlan();

        // Check if invoice already exists for this period
        $existingInvoice = $entity->invoices()
            ->whereDate('issue_date', '>=', now()->startOfMonth())
            ->exists();

        if ($existingInvoice) {
            $this->line("    - Skipped: {$name} (invoice already exists)");
            return ['created' => 0, 'skipped' => 1, 'errors' => 0];
        }

        if (!$plan) {
            $this->line("    - Skipped: {$name} (no subscription plan)");
            return ['created' => 0, 'skipped' => 1, 'errors' => 0];
        }

        if ($dryRun) {
            $this->line("    - Would create: {$name} ({$plan->name})");
            return ['created' => 1, 'skipped' => 0, 'errors' => 0];
        }

        try {
            $billingInterval = $this->determineBillingInterval($entity);
            $billingPeriod = now()->format('d.m.Y') . ' - ' . ($billingInterval === 'yearly'
                ? now()->addYear()->format('d.m.Y')
                : now()->addMonth()->format('d.m.Y'));

            $invoice = $invoiceService->createForSubscription(
                $entity,
                $plan,
                $billingPeriod,
                $billingInterval
            );

            // Mark as sent and send email
            $invoiceService->markAsSent($invoice);

            $this->info("    - Created: {$name} - Invoice #{$invoice->invoice_number}");

            Log::info("Recurring invoice created", [
                'type' => $type,
                'invoiceable_id' => $entity instanceof Club ? $entity->id : $entity->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return ['created' => 1, 'skipped' => 0, 'errors' => 0];
        } catch (\Exception $e) {
            $this->error("    - Error: {$name} - {$e->getMessage()}");

            Log::error("Failed to create recurring invoice", [
                'type' => $type,
                'invoiceable_name' => $name,
                'error' => $e->getMessage(),
            ]);

            return ['created' => 0, 'skipped' => 0, 'errors' => 1];
        }
    }

    /**
     * Determine billing interval for an invoiceable entity.
     */
    protected function determineBillingInterval(Invoiceable $entity): string
    {
        // Check the previous invoice to determine interval
        $lastInvoice = $entity->invoices()->latest()->first();

        if ($lastInvoice && $lastInvoice->line_items) {
            $description = $lastInvoice->line_items[0]['description'] ?? '';
            if (str_contains(strtolower($description), 'yearly') || str_contains(strtolower($description), 'jährlich')) {
                return 'yearly';
            }
        }

        return 'monthly';
    }
}
