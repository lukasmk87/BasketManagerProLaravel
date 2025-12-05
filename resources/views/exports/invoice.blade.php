<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 20mm;
        }

        /* Header */
        .header {
            margin-bottom: 15mm;
        }

        .company-info {
            font-size: 8pt;
            color: #666;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3mm;
            margin-bottom: 5mm;
        }

        .logo-section {
            text-align: right;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #2563eb;
        }

        /* Recipient */
        .recipient {
            margin-bottom: 15mm;
        }

        .recipient-address {
            line-height: 1.6;
        }

        /* Invoice Info */
        .invoice-info {
            margin-bottom: 10mm;
        }

        .invoice-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5mm;
        }

        .invoice-details {
            width: 100%;
        }

        .invoice-details td {
            padding: 2mm 0;
        }

        .invoice-details .label {
            width: 40%;
            color: #666;
        }

        .invoice-details .value {
            font-weight: 500;
        }

        /* Line Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }

        .items-table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 3mm;
            font-size: 9pt;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table td {
            padding: 3mm;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td.right {
            text-align: right;
        }

        /* Totals */
        .totals {
            width: 50%;
            margin-left: 50%;
            margin-bottom: 15mm;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 2mm 0;
        }

        .totals .label {
            text-align: left;
        }

        .totals .value {
            text-align: right;
            font-weight: 500;
        }

        .totals .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 12pt;
            padding-top: 3mm;
        }

        /* Payment Info */
        .payment-info {
            background-color: #f8fafc;
            padding: 5mm;
            border-radius: 3mm;
            margin-bottom: 10mm;
        }

        .payment-info h3 {
            font-size: 11pt;
            margin-bottom: 3mm;
            color: #1f2937;
        }

        .payment-info table {
            width: 100%;
        }

        .payment-info td {
            padding: 1mm 0;
        }

        .payment-info .label {
            width: 30%;
            color: #666;
        }

        .payment-reference {
            font-family: monospace;
            background-color: #fff;
            padding: 2mm 3mm;
            border: 1px solid #e5e7eb;
            display: inline-block;
            margin-top: 2mm;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 15mm;
            left: 20mm;
            right: 20mm;
            font-size: 8pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 5mm;
        }

        .footer-columns {
            display: table;
            width: 100%;
        }

        .footer-column {
            display: table-cell;
            width: 33.33%;
            text-align: center;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 1mm 3mm;
            border-radius: 2mm;
            font-size: 8pt;
            font-weight: bold;
        }

        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Legal Text */
        .legal-text {
            font-size: 8pt;
            color: #666;
            margin-top: 10mm;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                {{ $company['name'] }} | {{ $company['address_line1'] }} | {{ $company['zip'] }} {{ $company['city'] }}
            </div>
            <div class="logo-section">
                <div class="company-name">{{ $company['name'] }}</div>
                <div style="font-size: 8pt; color: #666;">
                    @if($company['address_line1']){{ $company['address_line1'] }}<br>@endif
                    @if($company['zip'] || $company['city']){{ $company['zip'] }} {{ $company['city'] }}<br>@endif
                    @if($company['email']){{ $company['email'] }}<br>@endif
                    @if($company['phone']){{ $company['phone'] }}@endif
                </div>
            </div>
        </div>

        <!-- Recipient -->
        <div class="recipient">
            <div class="recipient-address">
                <strong>{{ $invoice->billing_name }}</strong><br>
                @if($invoice->billing_address)
                    @if(!empty($invoice->billing_address['street'])){{ $invoice->billing_address['street'] }}<br>@endif
                    @if(!empty($invoice->billing_address['zip']) || !empty($invoice->billing_address['city']))
                        {{ $invoice->billing_address['zip'] ?? '' }} {{ $invoice->billing_address['city'] ?? '' }}<br>
                    @endif
                    @if(!empty($invoice->billing_address['country']) && $invoice->billing_address['country'] !== 'Deutschland')
                        {{ $invoice->billing_address['country'] }}
                    @endif
                @endif
                @if($invoice->vat_number)
                    <br>USt-IdNr.: {{ $invoice->vat_number }}
                @endif
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="invoice-title">
                Rechnung
                @if($invoice->status === 'paid')
                    <span class="status-badge status-paid">BEZAHLT</span>
                @elseif($invoice->status === 'cancelled')
                    <span class="status-badge status-cancelled">STORNIERT</span>
                @endif
            </div>

            <table class="invoice-details">
                <tr>
                    <td class="label">Rechnungsnummer:</td>
                    <td class="value">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="label">Rechnungsdatum:</td>
                    <td class="value">{{ $formatted['issue_date'] }}</td>
                </tr>
                <tr>
                    <td class="label">Fälligkeitsdatum:</td>
                    <td class="value">{{ $formatted['due_date'] }}</td>
                </tr>
                @if($invoice->billing_period)
                <tr>
                    <td class="label">Leistungszeitraum:</td>
                    <td class="value">{{ $invoice->billing_period }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Kundennummer:</td>
                    <td class="value">{{ $invoice->invoiceable_id ?? $invoice->club_id ?? '-' }}</td>
                </tr>
                @if(isset($is_tenant) && $is_tenant)
                <tr>
                    <td class="label">Kundentyp:</td>
                    <td class="value">Tenant</td>
                </tr>
                @elseif(isset($is_club) && $is_club)
                <tr>
                    <td class="label">Kundentyp:</td>
                    <td class="value">Club</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Beschreibung</th>
                    <th class="right" style="width: 15%;">Menge</th>
                    <th class="right" style="width: 17.5%;">Einzelpreis</th>
                    <th class="right" style="width: 17.5%;">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @if($invoice->line_items && count($invoice->line_items) > 0)
                    @foreach($formatted['line_items_formatted'] as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="right">{{ $item['quantity'] }}</td>
                        <td class="right">{{ $item['unit_price'] }}</td>
                        <td class="right">{{ $item['total'] }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $invoice->description ?: 'Subscription' }}</td>
                        <td class="right">1</td>
                        <td class="right">{{ $formatted['net_amount'] }}</td>
                        <td class="right">{{ $formatted['net_amount'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Nettobetrag:</td>
                    <td class="value">{{ $formatted['net_amount'] }}</td>
                </tr>
                @if(!$invoice->is_small_business)
                <tr>
                    <td class="label">MwSt. ({{ $formatted['tax_rate'] }}):</td>
                    <td class="value">{{ $formatted['tax_amount'] }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Gesamtbetrag:</td>
                    <td class="value">{{ $formatted['gross_amount'] }}</td>
                </tr>
                @else
                <tr class="total-row">
                    <td class="label">Gesamtbetrag:</td>
                    <td class="value">{{ $formatted['net_amount'] }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if($invoice->is_small_business)
        <!-- Kleinunternehmer-Hinweis -->
        <div style="margin-top: 10mm; padding: 4mm; background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 2mm;">
            <p style="font-size: 9pt; color: #92400e; margin: 0;">
                <strong>Hinweis:</strong> Gemäß §19 UStG wird keine Umsatzsteuer berechnet.
            </p>
        </div>
        @endif

        <!-- Payment Info -->
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <div class="payment-info">
            <h3>Zahlungsinformationen</h3>
            <table>
                <tr>
                    <td class="label">Bank:</td>
                    <td>{{ $bank['name'] }}</td>
                </tr>
                <tr>
                    <td class="label">IBAN:</td>
                    <td><strong>{{ $bank['iban'] }}</strong></td>
                </tr>
                <tr>
                    <td class="label">BIC:</td>
                    <td>{{ $bank['bic'] }}</td>
                </tr>
                <tr>
                    <td class="label">Kontoinhaber:</td>
                    <td>{{ $bank['account_holder'] ?: $company['name'] }}</td>
                </tr>
            </table>

            <div style="margin-top: 3mm;">
                <strong>Verwendungszweck:</strong>
                <div class="payment-reference">{{ $invoice->payment_reference ?: $invoice->invoice_number }}</div>
            </div>

            <p style="margin-top: 3mm; font-size: 9pt;">
                Bitte überweisen Sie den Gesamtbetrag bis zum <strong>{{ $formatted['due_date'] }}</strong> unter Angabe des Verwendungszwecks.
            </p>
        </div>
        @endif

        @if($invoice->status === 'paid')
        <div class="payment-info" style="background-color: #dcfce7;">
            <h3 style="color: #166534;">Zahlung erhalten</h3>
            <p>
                Diese Rechnung wurde am {{ $invoice->paid_at ? $invoice->paid_at->format('d.m.Y') : '-' }} beglichen.
                @if($invoice->payment_reference)
                    <br>Referenz: {{ $invoice->payment_reference }}
                @endif
            </p>
        </div>
        @endif

        <!-- Legal Text -->
        <div class="legal-text">
            @if(!$invoice->is_small_business && $company['vat_number'])
                <p>USt-IdNr.: {{ $company['vat_number'] }}</p>
            @endif
            @if($company['tax_number'])
                <p>Steuernummer: {{ $company['tax_number'] }}</p>
            @endif
            <p style="margin-top: 2mm;">
                Es gelten unsere Allgemeinen Geschäftsbedingungen.
            </p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-columns">
            <div class="footer-column">
                <strong>{{ $company['name'] }}</strong><br>
                {{ $company['address_line1'] }}<br>
                {{ $company['zip'] }} {{ $company['city'] }}
            </div>
            <div class="footer-column">
                @if($company['register_court'])
                    {{ $company['register_court'] }}<br>
                    {{ $company['register_number'] }}<br>
                @endif
                @if($company['managing_director'])
                    Geschäftsführer: {{ $company['managing_director'] }}
                @endif
            </div>
            <div class="footer-column">
                @if($company['email'])E-Mail: {{ $company['email'] }}<br>@endif
                @if($company['phone'])Tel: {{ $company['phone'] }}<br>@endif
                @if($company['website']){{ $company['website'] }}@endif
            </div>
        </div>
    </div>
</body>
</html>
