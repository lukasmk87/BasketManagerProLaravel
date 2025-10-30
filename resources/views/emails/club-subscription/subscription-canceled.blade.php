<x-mail::message>
# Abonnement gekündigt

Ihr Abonnement für **{{ $club->name }}** wurde gekündigt.

<x-mail::panel>
**Kündigungsgrund:** {{ $cancellationReasonTranslated }}

**Betroffener Plan:** {{ $planName }}
</x-mail::panel>

@if(!$immediatelyCanceled && $accessUntil)
## Ihr Zugriff bleibt aktiv

Gute Nachrichten: Sie können {{ config('app.name') }} noch bis zum **{{ $accessUntil->format('d.m.Y H:i') }} Uhr** nutzen.

Das sind noch **{{ $daysRemaining }} Tage** voller Zugriff auf alle Features!
@else
## Sofortige Kündigung

Ihr Zugriff auf {{ config('app.name') }} wurde sofort beendet.
@endif

## Was Sie jetzt tun können

@if(!$immediatelyCanceled)
### 📥 Daten exportieren

Sichern Sie Ihre Daten, bevor Ihr Zugriff endet:

<x-mail::button :url="$exportDataUrl">
Daten jetzt exportieren
</x-mail::button>
@endif

### 💭 Ihre Meinung ist uns wichtig

Helfen Sie uns, {{ config('app.name') }} zu verbessern:

<x-mail::button :url="$feedbackUrl" color="secondary">
Feedback zur Kündigung geben
</x-mail::button>

### 🔄 Haben Sie es sich anders überlegt?

Sie können Ihr Abonnement jederzeit wieder aktivieren:

<x-mail::button :url="$resubscribeUrl" color="success">
Abonnement reaktivieren
</x-mail::button>

## Das sagen unsere Kunden

<x-mail::panel>
"Seit wir {{ config('app.name') }} nutzen, läuft unser Team-Management viel effizienter!"
— Basketball-Club aus München

"Die Live-Scoring-Funktion hat unsere Spieltage revolutioniert."
— Jugendtrainer aus Berlin
</x-mail::panel>

@if(!$immediatelyCanceled && $accessUntil)
**Erinnerung:** Ihr Zugriff endet am {{ $accessUntil->format('d.m.Y H:i') }} Uhr.
Danach werden Ihre Daten gemäß unserer Datenschutzrichtlinie archiviert.
@endif

Wir bedauern, Sie gehen zu sehen, und hoffen, Sie bald wieder begrüßen zu dürfen!

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Team
</x-mail::message>
