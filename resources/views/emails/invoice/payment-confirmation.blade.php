@component('mail::message')
# Zahlungsbestätigung

Guten Tag {{ $invoice->billing_name }},

vielen Dank für Ihre Zahlung. Wir bestätigen hiermit den Eingang Ihrer Zahlung für folgende Rechnung:

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice->invoice_number }}
- Betrag: {{ $formatted_amounts['gross'] }}
- Bezahlt am: {{ $invoice->paid_at ? $invoice->paid_at->format('d.m.Y') : now()->format('d.m.Y') }}
@if($invoice->payment_reference)
- Zahlungsreferenz: {{ $invoice->payment_reference }}
@endif

@component('mail::panel')
Ihre Rechnung wurde erfolgreich beglichen. Vielen Dank!
@endcomponent

Bei Fragen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen,<br>
{{ $company['name'] }}
@endcomponent
