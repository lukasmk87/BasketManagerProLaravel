@component('mail::message')
# @if($reminder_level === 1)Zahlungserinnerung@else{{ $reminder_level }}. Mahnung@endif

Guten Tag {{ $invoice->billing_name }},

@if($reminder_level === 1)
wir möchten Sie freundlich daran erinnern, dass die folgende Rechnung noch nicht beglichen wurde:
@else
trotz unserer vorherigen Erinnerung(en) haben wir noch keinen Zahlungseingang für die folgende Rechnung verzeichnen können:
@endif

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice->invoice_number }}
- Rechnungsbetrag: {{ $formatted_amounts['gross'] }}
- Fällig seit: {{ $invoice->due_date->format('d.m.Y') }} ({{ $days_overdue }} Tage überfällig)

@component('mail::panel')
**Bitte überweisen Sie den offenen Betrag von {{ $formatted_amounts['gross'] }} umgehend.**
@endcomponent

**Zahlungsinformationen:**
- Bank: {{ $bank['name'] }}
- IBAN: {{ $bank['iban'] }}
- BIC: {{ $bank['bic'] }}
- Verwendungszweck: {{ $invoice->invoice_number }}

@if($reminder_level >= 2)
@component('mail::panel')
**Wichtig:** Falls die Zahlung nicht innerhalb der nächsten 7 Tage bei uns eingeht, behalten wir uns vor, den Zugang zu Ihrem Account temporär zu sperren.
@endcomponent
@endif

Sollte die Zahlung bereits erfolgt sein, bitten wir Sie, diese E-Mail zu ignorieren.

Bei Fragen kontaktieren Sie uns bitte unter {{ $company['email'] }}.

Mit freundlichen Grüßen,<br>
{{ $company['name'] }}
@endcomponent
