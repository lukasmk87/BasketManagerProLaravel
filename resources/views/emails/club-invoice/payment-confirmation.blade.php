<x-mail::message>
# ✅ Zahlung erhalten

Guten Tag {{ $billingName }},

vielen Dank! Wir haben Ihre Zahlung erhalten und bestätigen hiermit den Eingang.

## Zahlungsdetails

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Bezahlter Betrag** | {{ number_format($totalAmount / 100, 2, ',', '.') }} € |
| **Zahlungsdatum** | {{ $paidAt?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i') }} Uhr |
@if($paymentMethod)
| **Zahlungsart** | {{ $paymentMethod }} |
@endif
@if($transactionId)
| **Transaktions-ID** | {{ $transactionId }} |
@endif
</x-mail::table>

<x-mail::panel>
✓ Ihre Rechnung ist vollständig bezahlt
✓ Ihr Abonnement bleibt aktiv
✓ Alle Features stehen Ihnen weiterhin zur Verfügung
</x-mail::panel>

Wir bedanken uns für Ihr Vertrauen und freuen uns auf die weitere Zusammenarbeit!

Bei Fragen stehen wir Ihnen jederzeit gerne zur Verfügung.

Viele Grüße,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
