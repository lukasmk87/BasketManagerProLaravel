<x-mail::message>
# 🔴 Dringend: Kontosperrung droht

Guten Tag {{ $billingName }},

**Ihr Konto wird in {{ $daysUntilSuspension }} Tagen gesperrt**, wenn die offene Rechnung nicht beglichen wird.

## Offene Rechnung

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Offener Betrag** | **{{ number_format($totalAmount / 100, 2, ',', '.') }} €** |
| **Fälligkeitsdatum** | {{ $dueDate?->format('d.m.Y') ?? '-' }} |
| **Überfällig seit** | {{ $daysOverdue }} Tagen |
| **Sperrung am** | {{ $suspensionDate?->format('d.m.Y') ?? '-' }} |
</x-mail::table>

<x-mail::panel>
⚠️ **Was passiert bei einer Kontosperrung?**

- Alle Benutzer verlieren den Zugang zum System
- Live-Scoring und Statistiken sind nicht mehr verfügbar
- Trainingsplanungen können nicht mehr erstellt werden
- Team- und Spielerverwaltung wird deaktiviert
</x-mail::panel>

## Sofort bezahlen

Um die Sperrung zu verhindern, überweisen Sie bitte den offenen Betrag umgehend:

**{{ $bankDetails['account_holder'] }}**
IBAN: {{ $bankDetails['iban'] }}
BIC: {{ $bankDetails['bic'] }}
Bank: {{ $bankDetails['name'] }}

**Verwendungszweck:** {{ $invoiceNumber }}

<x-mail::button color="primary" :url="config('app.url')">
Zur Zahlungsübersicht
</x-mail::button>

Bitte kontaktieren Sie uns umgehend, falls Sie Schwierigkeiten mit der Zahlung haben. Wir finden gemeinsam eine Lösung.

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Team

---

*Diese E-Mail wurde automatisch versendet. Bei Fragen wenden Sie sich bitte an {{ config('invoices.email.from') ?? config('mail.from.address') }}.*
</x-mail::message>
