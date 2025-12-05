@component('mail::message')
# Rechnung {{ $invoice->invoice_number }}

Guten Tag {{ $invoice->billing_name }},

anbei erhalten Sie Ihre Rechnung {{ $invoice->invoice_number }} vom {{ $invoice->issue_date->format('d.m.Y') }}.

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice->invoice_number }}
- Rechnungsbetrag: {{ $formatted_amounts['gross'] }}
- Fällig bis: {{ $invoice->due_date->format('d.m.Y') }}

@if($invoice->payment_method === 'bank_transfer')
**Zahlungsinformationen:**
- Bank: {{ $bank['name'] }}
- IBAN: {{ $bank['iban'] }}
- BIC: {{ $bank['bic'] }}
- Verwendungszweck: {{ $invoice->invoice_number }}
@else
Die Zahlung erfolgt automatisch über Ihre hinterlegte Zahlungsmethode.
@endif

@component('mail::button', ['url' => $invoice->stripe_hosted_invoice_url ?? '#'])
Rechnung anzeigen
@endcomponent

Bei Fragen zur Rechnung stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen,<br>
{{ $company['name'] }}

---
<small>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.</small>
@endcomponent
