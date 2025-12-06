<x-mail::message>
# Vielen Dank für Ihre Anfrage!

Guten Tag {{ $lead->contact_name }},

wir haben Ihre Anfrage für die **{{ $appName }} Enterprise-Lösung** erhalten und freuen uns über Ihr Interesse.

## Ihre Angaben

<x-mail::table>
| | |
| :--- | :--- |
| **Organisation** | {{ $lead->organization_name }} |
| **Ihre E-Mail** | {{ $lead->email }} |
@if($lead->phone)
| **Telefon** | {{ $lead->phone }} |
@endif
</x-mail::table>

## Wie geht es weiter?

<x-mail::panel>
Unser Enterprise-Team wird sich **innerhalb von 24 Stunden** bei Ihnen melden, um Ihre Anforderungen zu besprechen und ein individuelles Angebot zu erstellen.
</x-mail::panel>

### Was Sie von uns erwarten können:

- Persönliche Beratung zu Ihren spezifischen Anforderungen
- Demonstration der White-Label-Funktionen
- Individuelles Preisangebot basierend auf Ihrer Vereins-/Verbandsstruktur
- Informationen zu Migration und Implementierung

## Fragen in der Zwischenzeit?

Besuchen Sie unsere [Enterprise-Seite]({{ config('app.url') }}/enterprise) für weitere Informationen oder antworten Sie einfach auf diese E-Mail.

<x-mail::button url="{{ config('app.url') }}/enterprise">
Mehr über Enterprise erfahren
</x-mail::button>

Wir freuen uns darauf, mit Ihnen zusammenzuarbeiten!

Sportliche Grüße,<br>
Ihr {{ $appName }} Enterprise-Team
</x-mail::message>
