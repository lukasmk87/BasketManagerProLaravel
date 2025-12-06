<x-mail::message>
# Neue Enterprise-Anfrage eingegangen

Eine neue Enterprise-Anfrage wurde über die Website gestellt.

## Organisationsdaten

<x-mail::table>
| Feld | Wert |
| :--- | :--- |
| **Organisation** | {{ $lead->organization_name }} |
| **Typ** | {{ $organizationType }} |
| **Ansprechpartner** | {{ $lead->contact_name }} |
| **E-Mail** | {{ $lead->email }} |
@if($lead->phone)
| **Telefon** | {{ $lead->phone }} |
@endif
@if($clubCount)
| **Anzahl Vereine** | {{ $clubCount }} |
@endif
@if($teamCount)
| **Anzahl Teams** | {{ $teamCount }} |
@endif
| **Newsletter** | {{ $lead->newsletter_optin ? 'Ja' : 'Nein' }} |
| **Eingegangen am** | {{ $lead->created_at->format('d.m.Y H:i') }} Uhr |
</x-mail::table>

@if($lead->message)
## Nachricht

<x-mail::panel>
{{ $lead->message }}
</x-mail::panel>
@endif

<x-mail::button url="{{ config('app.url') }}/admin/enterprise-leads/{{ $lead->id }}">
Lead im Admin-Panel ansehen
</x-mail::button>

---

**Empfehlung:** Kontaktieren Sie den Interessenten innerhalb von 24 Stunden.

Sportliche Grüße,<br>
{{ config('app.name') }} System
</x-mail::message>
