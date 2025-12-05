@component('mail::message')
# Stornierung Ihrer Rechnung

Guten Tag {{ $invoice->billing_name }},

hiermit bestätigen wir die Stornierung der folgenden Rechnung:

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice->invoice_number }}
- Ursprünglicher Betrag: {{ $formatted_amounts['gross'] }}
- Storniert am: {{ now()->format('d.m.Y') }}

@if($cancellation_reason)
**Stornierungsgrund:**
{{ str_replace('Stornierungsgrund: ', '', $cancellation_reason) }}
@endif

@component('mail::panel')
Diese Rechnung ist nicht mehr gültig und muss nicht bezahlt werden.
@endcomponent

Sollte die Zahlung bereits erfolgt sein, wird der Betrag automatisch erstattet.

Bei Fragen kontaktieren Sie uns bitte unter {{ $company['email'] }}.

Mit freundlichen Grüßen,<br>
{{ $company['name'] }}
@endcomponent
