<x-mail::message>
# ✅ Zahlung erfolgreich

Gute Nachrichten! Die Zahlung für Ihr Abonnement wurde erfolgreich verarbeitet.

## Rechnungsdetails

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Betrag** | {{ number_format($amount / 100, 2, ',', '.') }} {{ $currency }} |
| **Zahlungsdatum** | {{ $paidAt->format('d.m.Y H:i') }} Uhr |
| **Plan** | {{ $planName }} |
| **Nächste Abrechnung** | {{ $nextBillingDate?->format('d.m.Y') ?? 'Keine' }} |
</x-mail::table>

@if($pdfUrl)
<x-mail::button :url="$pdfUrl">
📄 Rechnung als PDF herunterladen
</x-mail::button>
@endif

## Was passiert jetzt?

✓ Ihr Abonnement ist aktiv und alle Features sind verfügbar
✓ Die Rechnung wurde an Ihre E-Mail-Adresse gesendet
✓ Die nächste Abrechnung erfolgt automatisch am {{ $nextBillingDate?->format('d.m.Y') ?? 'Monatsende' }}

<x-mail::panel>
**Tipp:** Sie können Ihre Zahlungsmethoden und Rechnungshistorie jederzeit im Billing-Portal verwalten.
</x-mail::panel>

@if($dashboardUrl)
<x-mail::button :url="$dashboardUrl" color="success">
🏀 Zum Dashboard
</x-mail::button>
@endif

Bei Fragen stehen wir Ihnen jederzeit zur Verfügung.

Viele Grüße,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
