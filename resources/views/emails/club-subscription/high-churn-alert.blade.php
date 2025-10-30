<x-mail::message>
# ⚠️ Hohe Churn-Rate erkannt

**Tenant:** {{ $tenant->name }}

Es wurde eine kritisch hohe Churn-Rate für den Zeitraum **{{ $period }}** festgestellt, die sofortige Aufmerksamkeit erfordert.

<x-mail::panel>
# {{ number_format($churnRate, 1) }}%

**Churn-Rate** (Zielwert: < 5%)

@if($churnRate > 10)
🔴 **KRITISCH** - Sofortige Maßnahmen erforderlich!
@elseif($churnRate > 5)
🟡 **WARNUNG** - Überwacht erforderlich
@endif
</x-mail::panel>

## 📊 Churn-Metriken

<x-mail::table>
| Metrik | Wert |
| :----- | :--- |
| **Kunden zu Beginn** | {{ $customersStart }} |
| **Kunden am Ende** | {{ $customersEnd }} |
| **Abgewanderte Kunden** | {{ $churnedCustomers }} |
| **Freiwillige Kündigungen** | {{ $voluntaryChurn }} ({{ number_format(($voluntaryChurn / $churnedCustomers) * 100, 1) }}%) |
| **Unfreiwillige Kündigungen** | {{ $involuntaryChurn }} ({{ number_format(($involuntaryChurn / $churnedCustomers) * 100, 1) }}%) |
| **Umsatzauswirkung** | -{{ number_format($revenueImpact / 100, 2, ',', '.') }} EUR |
</x-mail::table>

## 🎯 At-Risk Clubs

@if(count($atRiskClubs) > 0)
Die folgenden Clubs zeigen Anzeichen für potenzielle Abwanderung:

<x-mail::table>
| Club | Risiko-Score | Letzter Login | Grund |
| :--- | :----------- | :------------ | :---- |
@foreach($atRiskClubs as $club)
| {{ $club['name'] }} | {{ $club['risk_score'] }}/100 | {{ $club['last_login'] }} | {{ $club['risk_reason'] }} |
@endforeach
</x-mail::table>
@else
Aktuell keine Clubs in der At-Risk-Liste.
@endif

## 📋 Kündigungsgründe

@if(count($churnReasons) > 0)
@foreach($churnReasons as $reason)
- **{{ $reason['reason'] }}:** {{ $reason['count'] }} Kündigungen ({{ number_format($reason['percentage'], 1) }}%)
@endforeach
@else
Keine detaillierten Kündigungsgründe verfügbar.
@endif

## ✅ Empfohlene Maßnahmen

@foreach($recommendedActions as $action)
- [ ] {{ $action }}
@endforeach

<x-mail::button :url="$analyticsUrl">
📈 Analytics Dashboard öffnen
</x-mail::button>

## Nächste Schritte

1. **Sofort:** At-Risk Clubs proaktiv kontaktieren
2. **Diese Woche:** Kundenbefragung zur Unzufriedenheit starten
3. **Monatlich:** Churn-Trends überwachen und reagieren

<x-mail::panel>
**💡 Hinweis:** Eine Churn-Rate von {{ number_format($churnRate, 1) }}% bedeutet einen jährlichen Kundenverlust von {{ number_format($churnRate * 12, 1) }}%, wenn keine Gegenmaßnahmen ergriffen werden.
</x-mail::panel>

Diese Nachricht wurde automatisch generiert basierend auf Ihren Subscription-Analytics.

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Analytics System
</x-mail::message>
