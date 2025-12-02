<?php

namespace App\Services\Invoice;

use App\Models\ClubInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ClubInvoicePdfService
{
    /**
     * Generate PDF for an invoice and store it.
     */
    public function generate(ClubInvoice $invoice): string
    {
        $pdf = $this->createPdf($invoice);

        $disk = config('invoices.pdf.storage_disk', 'local');
        $path = config('invoices.pdf.storage_path', 'invoices');
        $filename = $this->getFilename($invoice);
        $fullPath = "{$path}/{$filename}";

        Storage::disk($disk)->put($fullPath, $pdf->output());

        $invoice->update(['pdf_path' => $fullPath]);

        return $fullPath;
    }

    /**
     * Regenerate PDF for an invoice.
     */
    public function regenerate(ClubInvoice $invoice): string
    {
        // Delete old PDF if exists
        if ($invoice->pdf_path) {
            $disk = config('invoices.pdf.storage_disk', 'local');
            Storage::disk($disk)->delete($invoice->pdf_path);
        }

        return $this->generate($invoice);
    }

    /**
     * Get PDF content as binary string.
     */
    public function getContent(ClubInvoice $invoice): string
    {
        if ($invoice->pdf_path) {
            $disk = config('invoices.pdf.storage_disk', 'local');
            if (Storage::disk($disk)->exists($invoice->pdf_path)) {
                return Storage::disk($disk)->get($invoice->pdf_path);
            }
        }

        // Generate on-the-fly if not stored
        return $this->createPdf($invoice)->output();
    }

    /**
     * Stream PDF to browser (inline display).
     */
    public function stream(ClubInvoice $invoice): Response
    {
        $pdf = $this->createPdf($invoice);
        $filename = $this->getFilename($invoice);

        return $pdf->stream($filename);
    }

    /**
     * Download PDF.
     */
    public function download(ClubInvoice $invoice): Response
    {
        $pdf = $this->createPdf($invoice);
        $filename = $this->getFilename($invoice);

        return $pdf->download($filename);
    }

    /**
     * Create PDF instance.
     */
    protected function createPdf(ClubInvoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['club', 'subscriptionPlan']);

        $data = [
            'invoice' => $invoice,
            'company' => config('invoices.company'),
            'bank' => config('invoices.bank'),
            'formatted' => $this->formatInvoiceData($invoice),
        ];

        $pdf = Pdf::loadView('exports.invoice', $data);

        $pdf->setPaper('A4');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf;
    }

    /**
     * Get filename for the invoice PDF.
     */
    protected function getFilename(ClubInvoice $invoice): string
    {
        return "Rechnung_{$invoice->invoice_number}.pdf";
    }

    /**
     * Format invoice data for the PDF template.
     */
    protected function formatInvoiceData(ClubInvoice $invoice): array
    {
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);

        return [
            'net_amount' => $formatter->formatCurrency((float) $invoice->net_amount, $invoice->currency),
            'tax_amount' => $formatter->formatCurrency((float) $invoice->tax_amount, $invoice->currency),
            'gross_amount' => $formatter->formatCurrency((float) $invoice->gross_amount, $invoice->currency),
            'tax_rate' => number_format((float) $invoice->tax_rate, 0, ',', '.') . '%',
            'issue_date' => $invoice->issue_date->format('d.m.Y'),
            'due_date' => $invoice->due_date->format('d.m.Y'),
            'billing_address_formatted' => $this->formatAddress($invoice->billing_address),
            'line_items_formatted' => $this->formatLineItems($invoice->line_items ?? [], $invoice->currency),
        ];
    }

    /**
     * Format address for display.
     */
    protected function formatAddress(?array $address): string
    {
        if (!$address) {
            return '';
        }

        $lines = [];

        if (!empty($address['street'])) {
            $lines[] = $address['street'];
        }

        $cityLine = trim(($address['zip'] ?? '') . ' ' . ($address['city'] ?? ''));
        if ($cityLine) {
            $lines[] = $cityLine;
        }

        if (!empty($address['country']) && $address['country'] !== 'Deutschland') {
            $lines[] = $address['country'];
        }

        return implode("\n", $lines);
    }

    /**
     * Format line items for the PDF.
     */
    protected function formatLineItems(array $lineItems, string $currency): array
    {
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);
        $formatted = [];

        foreach ($lineItems as $item) {
            $formatted[] = [
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $formatter->formatCurrency((float) ($item['unit_price'] ?? 0), $currency),
                'total' => $formatter->formatCurrency((float) ($item['total'] ?? 0), $currency),
            ];
        }

        return $formatted;
    }
}
