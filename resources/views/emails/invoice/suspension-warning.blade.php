@component('mail::message')
# Wichtig: Drohende Sperrung Ihres Accounts

Guten Tag {{ $invoice->billing_name }},

trotz mehrfacher Erinnerungen ist die folgende Rechnung weiterhin unbezahlt:

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice->invoice_number }}
- Rechnungsbetrag: {{ $formatted_amounts['gross'] }}
- Fällig seit: {{ $invoice->due_date->format('d.m.Y') }} ({{ $days_overdue }} Tage überfällig)

@component('mail::panel')
**Achtung: Ihr Account wird in {{ $days_until_suspension }} Tagen gesperrt, wenn die Zahlung nicht eingeht.**
@endcomponent

Um eine Sperrung zu vermeiden, überweisen Sie bitte umgehend den offenen Betrag:

**Zahlungsinformationen:**
- Bank: {{ $bank['name'] }}
- IBAN: {{ $bank['iban'] }}
- BIC: {{ $bank['bic'] }}
- Verwendungszweck: {{ $invoice->invoice_number }}

@component('mail::button', ['url' => $invoice->stripe_hosted_invoice_url ?? '#', 'color' => 'red'])
Jetzt bezahlen
@endcomponent

Bei Zahlungsschwierigkeiten kontaktieren Sie uns bitte umgehend unter:
- E-Mail: {{ $company['email'] }}
@if($company['phone'])
- Telefon: {{ $company['phone'] }}
@endif

Mit freundlichen Grüßen,<br>
{{ $company['name'] }}
@endcomponent
