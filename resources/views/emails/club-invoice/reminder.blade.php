<x-mail::message>
@if($reminderLevel === 1)
# Zahlungserinnerung
@elseif($reminderLevel === 2)
# ⚠️ 2. Mahnung
@else
# 🔴 Letzte Mahnung
@endif

Guten Tag {{ $billingName }},

@if($reminderLevel === 1)
wir möchten Sie freundlich daran erinnern, dass die folgende Rechnung noch offen ist:
@elseif($reminderLevel === 2)
trotz unserer ersten Erinnerung ist die folgende Rechnung weiterhin unbezahlt:
@else
**dies ist unsere letzte Mahnung.** Die folgende Rechnung ist seit {{ $daysOverdue }} Tagen überfällig:
@endif

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Offener Betrag** | **{{ number_format($totalAmount / 100, 2, ',', '.') }} €** |
| **Fälligkeitsdatum** | {{ $dueDate?->format('d.m.Y') ?? '-' }} |
| **Überfällig seit** | {{ $daysOverdue }} Tagen |
</x-mail::table>

@if($reminderLevel >= 2)
<x-mail::panel>
@if($reminderLevel === 2)
⚠️ **Wichtiger Hinweis:** Bei weiterhin ausbleibender Zahlung behalten wir uns vor, Ihr Konto zu sperren.
@else
🔴 **Dringend:** Ohne Zahlungseingang innerhalb der nächsten {{ $suspensionDays - $daysOverdue }} Tage wird Ihr Konto **gesperrt** und alle Services werden deaktiviert.
@endif
</x-mail::panel>
@endif

## Zahlungsinformationen

Bitte überweisen Sie den offenen Betrag umgehend auf folgendes Konto:

**{{ $bankDetails['account_holder'] }}**
IBAN: {{ $bankDetails['iban'] }}
BIC: {{ $bankDetails['bic'] }}
Bank: {{ $bankDetails['name'] }}

**Verwendungszweck:** {{ $invoiceNumber }}

@if($reminderLevel === 1)
Sollten Sie die Zahlung bereits veranlasst haben, betrachten Sie diese E-Mail bitte als gegenstandslos.
@else
Falls Sie Schwierigkeiten mit der Zahlung haben, kontaktieren Sie uns bitte umgehend, um eine Lösung zu finden.
@endif

Die Rechnung finden Sie im Anhang dieser E-Mail.

@if($reminderLevel >= 3)
Viele Grüße,<br>
@else
Mit freundlichen Grüßen,<br>
@endif
Ihr {{ config('app.name') }} Team
</x-mail::message>
