<x-mail::message>
# 🎉 Willkommen bei {{ config('app.name') }}!

Herzlich willkommen, **{{ $club->name }}**! Wir freuen uns sehr, Sie als neues Mitglied begrüßen zu dürfen.

@if($isTrialActive)
<x-mail::panel>
**🎁 Ihre kostenlose Testphase ist aktiv!**

Sie haben {{ $trialDaysRemaining }} Tage Zeit, {{ config('app.name') }} ohne Einschränkungen zu testen.
Testphase endet am: **{{ $trialEndsAt->format('d.m.Y') }}**

Nach Ablauf der Testphase wird Ihre Zahlung automatisch verarbeitet.
</x-mail::panel>
@endif

## Ihr {{ $planName }}-Plan

<x-mail::table>
| Feature | Limit |
| :------ | :---- |
| **Preis** | {{ number_format($planPrice / 100, 2, ',', '.') }} {{ $planCurrency }} / {{ $billingInterval === 'monthly' ? 'Monat' : 'Jahr' }} |
| **Teams** | {{ $planLimits['max_teams'] === -1 ? 'Unbegrenzt' : $planLimits['max_teams'] }} |
| **Spieler** | {{ $planLimits['max_players'] === -1 ? 'Unbegrenzt' : $planLimits['max_players'] }} |
| **Spiele** | {{ $planLimits['max_games'] === -1 ? 'Unbegrenzt' : $planLimits['max_games'] }} |
| **Trainings** | {{ $planLimits['max_training_sessions'] === -1 ? 'Unbegrenzt' : $planLimits['max_training_sessions'] }} |
</x-mail::table>

@if(count($planFeatures) > 0)
### Enthaltene Features

@foreach($planFeatures as $feature)
✓ {{ $feature }}
@endforeach
@endif

## 🚀 Los geht's in 4 Schritten

@foreach($gettingStartedSteps as $step)
### {{ $step['icon'] }} {{ $step['title'] }}

{{ $step['description'] }}

<x-mail::button :url="$step['url']">
{{ $step['title'] }}
</x-mail::button>

@endforeach

## Wichtige Links

<x-mail::table>
| Ressource | Beschreibung |
| :-------- | :----------- |
| [Dashboard]({{ $dashboardUrl }}) | Ihr Hauptdashboard mit Übersicht |
| [Billing Portal]({{ $billingPortalUrl }}) | Rechnungen und Zahlungsmethoden verwalten |
| [Support]({{ $supportUrl }}) | Hilfe bei Fragen oder Problemen |
</x-mail::table>

<x-mail::panel>
**💡 Tipp:** Laden Sie Ihre Trainer und Team-Manager ein, damit sie sofort mit der Arbeit beginnen können!
</x-mail::panel>

@if(!$isTrialActive && $nextBillingDate)
Ihre nächste Abrechnung erfolgt am **{{ $nextBillingDate->format('d.m.Y') }}**.
@endif

Wir wünschen Ihnen viel Erfolg mit {{ config('app.name') }}!

Bei Fragen sind wir jederzeit für Sie da.

Sportliche Grüße,<br>
Ihr {{ config('app.name') }} Team 🏀
</x-mail::message>
