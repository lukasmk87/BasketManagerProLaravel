<x-mail::message>
# ⚠️ Zahlung fehlgeschlagen

Ihre Zahlung für das Abonnement von **{{ $club->name }}** konnte leider nicht verarbeitet werden.

<x-mail::panel>
**Fehlergrund:** {{ $failureReasonTranslated }}

**Betroffene Rechnung:** {{ $invoiceNumber }}
**Betrag:** {{ number_format($amount / 100, 2, ',', '.') }} {{ $currency }}
**Versuchszeitpunkt:** {{ $attemptedAt->format('d.m.Y H:i') }} Uhr
</x-mail::panel>

## Was Sie jetzt tun sollten

Um Unterbrechungen Ihres Service zu vermeiden, aktualisieren Sie bitte Ihre Zahlungsmethode **innerhalb der nächsten {{ $gracePeriodDays }} Tage**.

@if($retryAttempts !== null && $retryAttempts > 0)
**Hinweis:** Dies war Versuch {{ $retryAttempts }} von 3. Nach dem letzten fehlgeschlagenen Versuch wird Ihr Abonnement automatisch gekündigt.
@endif

<x-mail::button :url="$updatePaymentMethodUrl" color="error">
💳 Zahlungsmethode aktualisieren
</x-mail::button>

## Wichtige Informationen

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Aktueller Plan** | {{ $planName }} |
| **Zugriff läuft ab am** | {{ $accessExpiresAt->format('d.m.Y H:i') }} Uhr |
| **Verbleibende Zeit** | {{ $gracePeriodDays }} Tage |
</x-mail::table>

## Häufige Lösungen

- **Unzureichende Deckung:** Stellen Sie sicher, dass Ihr Konto gedeckt ist
- **Abgelaufene Karte:** Überprüfen Sie das Ablaufdatum Ihrer Karte
- **Falsche Details:** Verifizieren Sie Ihre Rechnungsadresse und Kartennummer
- **Bank-Ablehnung:** Kontaktieren Sie Ihre Bank für Details

@if($supportUrl)
Sollten Sie Hilfe benötigen, steht unser Support-Team zur Verfügung.

<x-mail::button :url="$supportUrl">
🆘 Support kontaktieren
</x-mail::button>
@endif

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
