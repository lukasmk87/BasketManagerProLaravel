<x-mail::message>
# Rechnung {{ $invoiceNumber }}

Guten Tag {{ $billingName }},

anbei erhalten Sie Ihre Rechnung für {{ $club->name ?? 'Ihr Abonnement' }}.

## Rechnungsdetails

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Rechnungsdatum** | {{ $issueDate?->format('d.m.Y') ?? '-' }} |
| **Fälligkeitsdatum** | {{ $dueDate?->format('d.m.Y') ?? '-' }} |
| **Zahlungsziel** | {{ $paymentTermsDays }} Tage |
</x-mail::table>

## Rechnungspositionen

<x-mail::table>
| Beschreibung | Betrag |
| :----------- | -----: |
@foreach($lineItems ?? [['description' => $description, 'amount' => $netAmount]] as $item)
| {{ $item['description'] ?? $description }} | {{ number_format(($item['amount'] ?? $netAmount) / 100, 2, ',', '.') }} € |
@endforeach
| **Nettobetrag** | {{ number_format($netAmount / 100, 2, ',', '.') }} € |
| MwSt. ({{ $taxRate }}%) | {{ number_format($taxAmount / 100, 2, ',', '.') }} € |
| **Gesamtbetrag** | **{{ number_format($totalAmount / 100, 2, ',', '.') }} €** |
</x-mail::table>

## Zahlungsinformationen

<x-mail::panel>
Bitte überweisen Sie den Betrag innerhalb von {{ $paymentTermsDays }} Tagen auf folgendes Konto:

**{{ $bankDetails['account_holder'] }}**
IBAN: {{ $bankDetails['iban'] }}
BIC: {{ $bankDetails['bic'] }}
Bank: {{ $bankDetails['name'] }}

**Verwendungszweck:** {{ $invoiceNumber }}
</x-mail::panel>

Die vollständige Rechnung finden Sie im Anhang dieser E-Mail.

Bei Fragen zu dieser Rechnung stehen wir Ihnen gerne zur Verfügung.

Viele Grüße,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
