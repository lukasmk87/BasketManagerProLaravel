<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class InvoicePdfService
 *
 * Service für die PDF-Generierung von Rechnungen.
 */
class InvoicePdfService
{
    /**
     * Generate PDF for an invoice.
     */
    public function generate(Invoice $invoice): string
    {
        $data = $this->formatInvoiceData($invoice);

        $pdf = Pdf::loadView('exports.invoice', $data);
        $pdf->setPaper('a4');

        $filename = $this->getFilename($invoice);
        $path = $this->getStoragePath($invoice);
        $fullPath = "{$path}/{$filename}";

        // Ensure directory exists
        Storage::disk($this->getDisk())->makeDirectory($path);

        // Save PDF
        Storage::disk($this->getDisk())->put($fullPath, $pdf->output());

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $fullPath]);

        Log::info('Invoice PDF generated', [
            'invoice_id' => $invoice->id,
            'path' => $fullPath,
        ]);

        return $fullPath;
    }

    /**
     * Regenerate PDF for an invoice.
     */
    public function regenerate(Invoice $invoice): string
    {
        // Delete old PDF if exists
        $this->delete($invoice);

        return $this->generate($invoice);
    }

    /**
     * Delete PDF for an invoice.
     */
    public function delete(Invoice $invoice): bool
    {
        if (!$invoice->pdf_path) {
            return true;
        }

        $deleted = Storage::disk($this->getDisk())->delete($invoice->pdf_path);

        if ($deleted) {
            $invoice->update(['pdf_path' => null]);
        }

        return $deleted;
    }

    /**
     * Get PDF content as binary string.
     */
    public function getContent(Invoice $invoice): ?string
    {
        if (!$invoice->pdf_path) {
            $this->generate($invoice);
            $invoice->refresh();
        }

        if (!Storage::disk($this->getDisk())->exists($invoice->pdf_path)) {
            $this->generate($invoice);
            $invoice->refresh();
        }

        return Storage::disk($this->getDisk())->get($invoice->pdf_path);
    }

    /**
     * Stream PDF for inline display.
     */
    public function stream(Invoice $invoice): StreamedResponse
    {
        $content = $this->getContent($invoice);

        return response()->stream(
            fn () => print($content),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$this->getFilename($invoice)}\"",
            ]
        );
    }

    /**
     * Download PDF.
     */
    public function download(Invoice $invoice): StreamedResponse
    {
        $content = $this->getContent($invoice);

        return response()->stream(
            fn () => print($content),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$this->getFilename($invoice)}\"",
            ]
        );
    }

    /**
     * Get the storage disk.
     */
    protected function getDisk(): string
    {
        return config('invoices.pdf.storage_disk', 'local');
    }

    /**
     * Get the storage path for an invoice.
     */
    protected function getStoragePath(Invoice $invoice): string
    {
        $basePath = config('invoices.pdf.storage_path', 'invoices');
        $year = $invoice->issue_date->year;
        $type = $invoice->isTenantInvoice() ? 'tenants' : 'clubs';

        return "{$basePath}/{$type}/{$year}";
    }

    /**
     * Get the filename for an invoice PDF.
     */
    protected function getFilename(Invoice $invoice): string
    {
        return "rechnung_{$invoice->invoice_number}.pdf";
    }

    /**
     * Format invoice data for the PDF template.
     */
    protected function formatInvoiceData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'invoiceable' => $invoice->invoiceable,
            'invoiceable_type' => $invoice->invoiceable_type,
            'is_tenant' => $invoice->isTenantInvoice(),
            'is_club' => $invoice->isClubInvoice(),

            // Company info
            'company' => $this->getCompanyInfo(),

            // Bank info
            'bank' => $this->getBankInfo(),

            // Formatted amounts
            'formatted_amounts' => $invoice->formatted_amounts,

            // Line items
            'line_items' => $this->formatLineItems($invoice->line_items ?? []),

            // Billing address
            'billing_address' => $this->formatAddress($invoice->billing_address),

            // Additional data from strategy
            'extra' => $this->getExtraData($invoice),

            // Localization
            'locale' => 'de',
            'date_format' => 'd.m.Y',
        ];
    }

    /**
     * Get company info for the invoice.
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => config('invoices.company.name', app_name()),
            'address_line1' => config('invoices.company.address_line1', ''),
            'address_line2' => config('invoices.company.address_line2', ''),
            'zip' => config('invoices.company.zip', ''),
            'city' => config('invoices.company.city', ''),
            'country' => config('invoices.company.country', 'Deutschland'),
            'email' => config('invoices.company.email', ''),
            'phone' => config('invoices.company.phone', ''),
            'website' => config('invoices.company.website', ''),
            'vat_number' => config('invoices.company.vat_number', ''),
            'tax_number' => config('invoices.company.tax_number', ''),
            'logo' => config('invoices.company.logo', ''),
        ];
    }

    /**
     * Get bank info for the invoice.
     */
    protected function getBankInfo(): array
    {
        return [
            'name' => config('invoices.bank.name', ''),
            'iban' => config('invoices.bank.iban', ''),
            'bic' => config('invoices.bank.bic', ''),
            'account_holder' => config('invoices.bank.account_holder', ''),
        ];
    }

    /**
     * Format line items for display.
     */
    protected function formatLineItems(array $lineItems): array
    {
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);

        return array_map(function ($item) use ($formatter) {
            return [
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $formatter->formatCurrency((float) ($item['unit_price'] ?? 0), 'EUR'),
                'total' => $formatter->formatCurrency((float) ($item['total'] ?? 0), 'EUR'),
            ];
        }, $lineItems);
    }

    /**
     * Format billing address for display.
     */
    protected function formatAddress(?array $address): string
    {
        if (!$address) {
            return '';
        }

        $parts = array_filter([
            $address['street'] ?? null,
            trim(($address['zip'] ?? '') . ' ' . ($address['city'] ?? '')),
            $address['country'] ?? null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Get extra data from invoice strategy.
     */
    protected function getExtraData(Invoice $invoice): array
    {
        // This will be populated by the InvoiceService when generating
        // For now, return basic data
        if ($invoice->isClubInvoice()) {
            $club = $invoice->invoiceable;
            return [
                'name' => $club->name ?? '',
                'logo' => $club->logo_url ?? null,
            ];
        }

        if ($invoice->isTenantInvoice()) {
            $tenant = $invoice->invoiceable;
            return [
                'name' => $tenant->name ?? '',
                'tier' => $tenant->subscription_tier ?? 'free',
            ];
        }

        return [];
    }
}
