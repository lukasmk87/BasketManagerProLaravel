<x-mail::message>
# ⚠️ Club-Transfer fehlgeschlagen

Hallo {{ $admin->name }},

leider ist der Transfer des Clubs **{{ $club->name }}** fehlgeschlagen.

<x-mail::panel>
**Transfer-Details:**

- **Club:** {{ $club->name }}
- **Von Tenant:** {{ $sourceTenant->name }}
- **Nach Tenant:** {{ $targetTenant->name }}
- **Initiiert von:** {{ $initiatedBy->name }}
- **Fehlgeschlagen am:** {{ $transfer->failed_at->format('d.m.Y H:i') }} Uhr
</x-mail::panel>

## 🔴 Fehlerdetails

**Fehlermeldung:**
```
{{ $errorMessage ?? 'Unbekannter Fehler' }}
```

@if(isset($exception) && config('app.debug'))
**Exception-Typ:** `{{ get_class($exception) }}`

**Technische Details:**
```
{{ $exception->getMessage() }}
```

*Hinweis: Diese Details sind nur im Debug-Modus sichtbar.*
@endif

## 💡 Nächste Schritte

1. **Fehlerdetails überprüfen:** Öffnen Sie die Transfer-Details in der Admin-Oberfläche für eine vollständige Fehleranalyse
2. **Problem beheben:** Beheben Sie die Ursache des Fehlers (z.B. Kapazitätsprobleme, Berechtigungen)
3. **Transfer wiederholen:** Starten Sie den Transfer erneut über die Admin-Oberfläche

<x-mail::button :url="route('admin.club-transfers.show', $transfer->id)" color="error">
Fehlerdetails anzeigen
</x-mail::button>

## 📋 Häufige Fehlerursachen

- **Kapazitätsprobleme:** Ziel-Tenant hat maximale Club-Anzahl erreicht
- **Stripe-Fehler:** Subscription konnte nicht gekündigt werden
- **Datei-Migration:** Media-Dateien konnten nicht kopiert werden
- **Datenbank-Probleme:** Constraint-Verletzungen oder Verbindungsfehler

## 🆘 Weitere Hilfe benötigt?

Wenn Sie weitere Unterstützung benötigen, wenden Sie sich bitte an unseren Support mit der **Transfer-ID: {{ $transfer->id }}**.

Viele Grüße,<br>
Ihr {{ config('app.name') }} Team

---

*Diese E-Mail wurde automatisch generiert. Bei Fragen wenden Sie sich bitte an Ihren System-Administrator.*
</x-mail::message>
