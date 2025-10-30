<x-mail::message>
# 📊 Subscription Analytics Report

**Tenant:** {{ $tenant->name }}
**Berichtszeitraum:** {{ $reportDate }} ({{ $reportPeriod === 'monthly' ? 'Monatlich' : 'Jährlich' }})

Ihr umfassender Überblick über die Performance Ihrer Club-Subscriptions.

## 🎯 Key Insights

@if(count($keyInsights) > 0)
@foreach($keyInsights as $insight)
@if($insight['type'] === 'positive')
✅ {{ $insight['text'] }}
@elseif($insight['type'] === 'warning')
⚠️ {{ $insight['text'] }}
@elseif($insight['type'] === 'negative')
🔴 {{ $insight['text'] }}
@endif
@endforeach
@else
Alle Metriken im normalen Bereich.
@endif

---

## 💰 Monthly Recurring Revenue (MRR)

<x-mail::panel>
### {{ number_format($totalMRR / 100, 2, ',', '.') }} EUR

**Gesamt-MRR**

@if($mrrGrowthRate > 0)
📈 +{{ number_format($mrrGrowthRate, 1) }}% Wachstum (3 Monate)
@elseif($mrrGrowthRate < 0)
📉 {{ number_format($mrrGrowthRate, 1) }}% Rückgang (3 Monate)
@else
→ Stabil (3 Monate)
@endif
</x-mail::panel>

### MRR nach Plan

<x-mail::table>
| Plan | MRR | Anteil |
| :--- | :-- | :----- |
@foreach($mrrByPlan as $plan => $mrr)
| {{ $plan }} | {{ number_format($mrr / 100, 2, ',', '.') }} EUR | {{ number_format(($mrr / $totalMRR) * 100, 1) }}% |
@endforeach
</x-mail::table>

---

## 🔄 Churn Metriken

<x-mail::table>
| Metrik | Wert | Status |
| :----- | :--- | :----- |
| **Churn-Rate** | {{ number_format($churnRate, 1) }}% | @if($churnRate > 5) 🔴 Kritisch @else ✅ OK @endif |
| **Revenue Churn** | {{ number_format($revenueChurn / 100, 2, ',', '.') }} EUR | - |
</x-mail::table>

### Hauptgründe für Kündigungen

@if(count($churnReasons) > 0)
@foreach($churnReasons as $reason)
- {{ $reason['reason'] }}: **{{ $reason['count'] }}** ({{ number_format($reason['percentage'], 1) }}%)
@endforeach
@else
Keine Kündigungen in diesem Zeitraum.
@endif

---

## 💎 Customer Lifetime Value (LTV)

<x-mail::panel>
### {{ number_format($averageLTV / 100, 2, ',', '.') }} EUR

**Durchschnittlicher LTV**
</x-mail::panel>

### LTV nach Plan

<x-mail::table>
| Plan | LTV | Kunden |
| :--- | :-- | :----- |
@foreach($ltvByPlan as $plan => $data)
| {{ $plan }} | {{ number_format($data['ltv'] / 100, 2, ',', '.') }} EUR | {{ $data['customers'] }} |
@endforeach
</x-mail::table>

---

## 🏥 Subscription Health

<x-mail::table>
| Metrik | Wert |
| :----- | :--- |
| **Aktive Abonnements** | {{ $activeSubscriptions }} |
| **Trial Conversion Rate** | {{ number_format($trialConversionRate, 1) }}% |
| **Ø Abonnement-Dauer** | {{ number_format($avgSubscriptionDuration / 30, 1) }} Monate |
</x-mail::table>

### Upgrade & Downgrade Rates

@if(isset($upgradeDowngradeRates['upgrades']) && isset($upgradeDowngradeRates['downgrades']))
- **Upgrades:** {{ $upgradeDowngradeRates['upgrades'] }} Clubs ({{ number_format($upgradeDowngradeRates['upgrade_rate'], 1) }}%)
- **Downgrades:** {{ $upgradeDowngradeRates['downgrades'] }} Clubs ({{ number_format($upgradeDowngradeRates['downgrade_rate'], 1) }}%)
@else
Keine Planänderungen in diesem Zeitraum.
@endif

---

<x-mail::button :url="$analyticsUrl">
📈 Vollständiges Dashboard öffnen
</x-mail::button>

## 💡 Empfehlungen

@if($churnRate > 5)
- **Churn reduzieren:** Implementieren Sie ein proaktives Kundenpflege-Programm
@endif

@if($trialConversionRate < 20)
- **Trial Conversion verbessern:** Optimieren Sie Ihr Onboarding und bieten Sie mehr Support während der Testphase
@endif

@if($mrrGrowthRate < 5)
- **MRR steigern:** Fokussieren Sie sich auf Upselling und Neukundengewinnung
@endif

@if($mrrGrowthRate > 10 && $churnRate < 5 && $trialConversionRate > 25)
- **Glückwunsch!** 🎉 Alle Key-Metriken im grünen Bereich. Weiter so!
@endif

<x-mail::panel>
**📅 Nächster Report:** In {{ $reportPeriod === 'monthly' ? '1 Monat' : '1 Jahr' }}

Diese Metriken werden automatisch berechnet und monatlich versendet.
</x-mail::panel>

Bei Fragen zu diesem Report oder den Metriken kontaktieren Sie bitte unser Analytics-Team.

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Analytics Team
</x-mail::message>
