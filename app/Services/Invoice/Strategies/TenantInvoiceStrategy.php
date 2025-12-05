<?php

namespace App\Services\Invoice\Strategies;

use App\Contracts\Invoiceable;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Class TenantInvoiceStrategy
 *
 * Strategy für Tenant-Rechnungen.
 */
class TenantInvoiceStrategy implements InvoiceStrategyInterface
{
    /**
     * Get the invoiceable type this strategy handles.
     */
    public function getInvoiceableType(): string
    {
        return Tenant::class;
    }

    /**
     * Get the invoice number prefix for tenants.
     */
    public function getNumberPrefix(): string
    {
        return config('invoices.types.tenant.number_prefix', 'TEN');
    }

    /**
     * Validate invoice creation for a tenant.
     */
    public function validateCreation(Invoiceable $invoiceable, array $data): void
    {
        /** @var Tenant $tenant */
        $tenant = $invoiceable;

        // Prüfe ob Tenant aktiv ist
        if (!$tenant->is_active) {
            throw ValidationException::withMessages([
                'tenant' => ['Der Tenant ist nicht aktiv und kann keine Rechnungen erhalten.'],
            ]);
        }

        // Prüfe ob Tenant suspendiert ist
        if ($tenant->is_suspended) {
            throw ValidationException::withMessages([
                'tenant' => ['Der Tenant ist suspendiert.'],
            ]);
        }

        // Prüfe ob Tenant einen gültigen Subscription-Plan hat
        if (!$tenant->subscriptionPlan && !isset($data['line_items'])) {
            throw ValidationException::withMessages([
                'subscription' => ['Der Tenant hat keinen Subscription-Plan. Bitte Rechnungsposten manuell angeben.'],
            ]);
        }

        // Prüfe auf überfällige Rechnungen
        if ($tenant->hasOverdueInvoices() && !($data['allow_with_overdue'] ?? false)) {
            throw ValidationException::withMessages([
                'invoices' => ['Der Tenant hat überfällige Rechnungen. Bitte diese zuerst bearbeiten.'],
            ]);
        }
    }

    /**
     * Hook called after an invoice is created.
     */
    public function afterCreate(Invoice $invoice): void
    {
        // Optional: Logging oder Audit-Trail
    }

    /**
     * Hook called after an invoice is sent.
     */
    public function afterSend(Invoice $invoice): void
    {
        // Optional: Logging
    }

    /**
     * Hook called after an invoice is paid.
     */
    public function afterPayment(Invoice $invoice): void
    {
        $invoice->invoiceable->onInvoicePaid($invoice);
    }

    /**
     * Hook called when an invoice becomes overdue.
     */
    public function afterOverdue(Invoice $invoice): void
    {
        $invoice->invoiceable->onInvoiceOverdue($invoice);
    }

    /**
     * Hook called after an invoice is cancelled.
     */
    public function afterCancel(Invoice $invoice): void
    {
        // Optional: Logging
    }

    /**
     * Get the notification recipients for a tenant invoice.
     */
    public function getNotificationRecipients(Invoice $invoice): array
    {
        /** @var Tenant $tenant */
        $tenant = $invoice->invoiceable;

        $recipients = [$invoice->billing_email];

        // CC an Tenant-Admins
        $adminEmails = User::where('tenant_id', $tenant->id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })
            ->whereNotNull('email')
            ->pluck('email')
            ->toArray();

        return array_unique(array_merge($recipients, $adminEmails));
    }

    /**
     * Get additional data for the invoice PDF.
     */
    public function getPdfData(Invoice $invoice): array
    {
        /** @var Tenant $tenant */
        $tenant = $invoice->invoiceable;

        return [
            'tenant_name' => $tenant->name,
            'subscription_tier' => $tenant->subscription_tier,
            'subscription_plan' => $tenant->subscriptionPlan?->name,
        ];
    }

    /**
     * Get additional data for invoice emails.
     */
    public function getEmailData(Invoice $invoice): array
    {
        /** @var Tenant $tenant */
        $tenant = $invoice->invoiceable;

        return [
            'tenant_name' => $tenant->name,
            'subscription_tier' => ucfirst($tenant->subscription_tier),
            'dashboard_url' => $tenant->getUrl(),
        ];
    }

    /**
     * Create subscription-related line items for a tenant.
     */
    public function createSubscriptionLineItems(Invoiceable $invoiceable, string $billingPeriod): array
    {
        /** @var Tenant $tenant */
        $tenant = $invoiceable;
        $plan = $tenant->subscriptionPlan;

        if (!$plan) {
            // Fallback auf Tier-basierte Preise
            $tierConfig = config("tenants.tiers.{$tenant->subscription_tier}");
            if (!$tierConfig || !isset($tierConfig['price'])) {
                return [];
            }

            $price = $tierConfig['price'];
            $description = ucfirst($tenant->subscription_tier) . ' Plan';
        } else {
            $price = $plan->price;
            $description = $plan->name;
        }

        // Jährlicher Rabatt
        if (str_contains(strtolower($billingPeriod), 'jahr') || str_contains(strtolower($billingPeriod), 'year')) {
            $yearlyPrice = ($plan?->yearly_price) ?? ($price * 12 * 0.9); // 10% Rabatt
            return [
                [
                    'description' => "{$description} - Jahreslizenz",
                    'quantity' => 1,
                    'unit_price' => $yearlyPrice,
                    'total' => $yearlyPrice,
                ],
            ];
        }

        return [
            [
                'description' => "{$description} - Monatslizenz",
                'quantity' => 1,
                'unit_price' => $price,
                'total' => $price,
            ],
        ];
    }
}
