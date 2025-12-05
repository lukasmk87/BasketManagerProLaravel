<?php

namespace App\Services\Invoice\Strategies;

use App\Contracts\Invoiceable;
use App\Models\Club;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

/**
 * Class ClubInvoiceStrategy
 *
 * Strategy für Club-Rechnungen.
 */
class ClubInvoiceStrategy implements InvoiceStrategyInterface
{
    /**
     * Get the invoiceable type this strategy handles.
     */
    public function getInvoiceableType(): string
    {
        return Club::class;
    }

    /**
     * Get the invoice number prefix for clubs.
     */
    public function getNumberPrefix(): string
    {
        return config('invoices.types.club.number_prefix', 'CLUB');
    }

    /**
     * Validate invoice creation for a club.
     */
    public function validateCreation(Invoiceable $invoiceable, array $data): void
    {
        /** @var Club $club */
        $club = $invoiceable;

        // Prüfe ob Club aktiv ist
        if (!$club->is_active) {
            throw ValidationException::withMessages([
                'club' => ['Der Club ist nicht aktiv und kann keine Rechnungen erhalten.'],
            ]);
        }

        // Prüfe ob Club einen gültigen Subscription-Plan hat
        if (!$club->subscriptionPlan && !isset($data['line_items'])) {
            throw ValidationException::withMessages([
                'subscription' => ['Der Club hat keinen Subscription-Plan. Bitte Rechnungsposten manuell angeben.'],
            ]);
        }

        // Prüfe auf unbezahlte Rechnungen
        if ($club->hasOverdueInvoices() && !($data['allow_with_overdue'] ?? false)) {
            throw ValidationException::withMessages([
                'invoices' => ['Der Club hat überfällige Rechnungen. Bitte diese zuerst bearbeiten.'],
            ]);
        }
    }

    /**
     * Hook called after an invoice is created.
     */
    public function afterCreate(Invoice $invoice): void
    {
        /** @var Club $club */
        $club = $invoice->invoiceable;

        // Erstelle Subscription-Event
        $club->subscriptionEvents()->create([
            'event_type' => 'invoice_created',
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'plan_id' => $club->club_subscription_plan_id,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->gross_amount,
            ],
        ]);
    }

    /**
     * Hook called after an invoice is sent.
     */
    public function afterSend(Invoice $invoice): void
    {
        /** @var Club $club */
        $club = $invoice->invoiceable;

        $club->subscriptionEvents()->create([
            'event_type' => 'invoice_sent',
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'plan_id' => $club->club_subscription_plan_id,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
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
        /** @var Club $club */
        $club = $invoice->invoiceable;

        $club->subscriptionEvents()->create([
            'event_type' => 'invoice_cancelled',
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'plan_id' => $club->club_subscription_plan_id,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
    }

    /**
     * Get the notification recipients for a club invoice.
     */
    public function getNotificationRecipients(Invoice $invoice): array
    {
        /** @var Club $club */
        $club = $invoice->invoiceable;

        $recipients = [$invoice->billing_email];

        // CC an Club-Administratoren
        $adminEmails = $club->administrators()
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
        /** @var Club $club */
        $club = $invoice->invoiceable;

        return [
            'club_name' => $club->name,
            'club_logo' => $club->logo_url,
            'subscription_plan' => $club->subscriptionPlan?->name,
            'subscription_interval' => $club->subscriptionPlan?->billing_interval,
        ];
    }

    /**
     * Get additional data for invoice emails.
     */
    public function getEmailData(Invoice $invoice): array
    {
        /** @var Club $club */
        $club = $invoice->invoiceable;

        return [
            'club_name' => $club->name,
            'subscription_plan' => $club->subscriptionPlan?->name,
            'dashboard_url' => route('clubs.show', $club),
        ];
    }

    /**
     * Create subscription-related line items for a club.
     */
    public function createSubscriptionLineItems(Invoiceable $invoiceable, string $billingPeriod): array
    {
        /** @var Club $club */
        $club = $invoiceable;
        $plan = $club->subscriptionPlan;

        if (!$plan) {
            return [];
        }

        $price = $plan->price;
        $description = $plan->name;

        // Jährlicher Rabatt
        if (str_contains(strtolower($billingPeriod), 'jahr') || str_contains(strtolower($billingPeriod), 'year')) {
            $yearlyPrice = $plan->yearly_price ?? ($price * 12 * 0.9); // 10% Rabatt
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
