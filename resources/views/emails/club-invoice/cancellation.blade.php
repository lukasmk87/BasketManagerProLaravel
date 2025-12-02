<x-mail::message>
# Rechnung storniert

Guten Tag {{ $billingName }},

hiermit bestätigen wir die Stornierung der folgenden Rechnung:

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Rechnungsnummer** | {{ $invoiceNumber }} |
| **Ursprünglicher Betrag** | {{ number_format($totalAmount / 100, 2, ',', '.') }} € |
| **Rechnungsdatum** | {{ $issueDate?->format('d.m.Y') ?? '-' }} |
| **Storniert am** | {{ $cancelledAt->format('d.m.Y') }} |
</x-mail::table>

@if($cancellationReason)
<x-mail::panel>
**Stornierungsgrund:** {{ $cancellationReason }}
</x-mail::panel>
@endif

Diese Rechnung ist damit ungültig und muss nicht bezahlt werden. Sollten Sie bereits eine Zahlung geleistet haben, wird der Betrag erstattet.

Bei Fragen zur Stornierung stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
