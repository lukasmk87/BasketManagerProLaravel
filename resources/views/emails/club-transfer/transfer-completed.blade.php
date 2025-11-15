<x-mail::message>
# 🎉 Club-Transfer erfolgreich abgeschlossen

Hallo {{ $admin->name }},

der Transfer des Clubs **{{ $club->name }}** wurde erfolgreich abgeschlossen.

<x-mail::panel>
**Transfer-Details:**

- **Club:** {{ $club->name }}
- **Von Tenant:** {{ $sourceTenant->name }}
- **Nach Tenant:** {{ $targetTenant->name }}
- **Initiiert von:** {{ $initiatedBy->name }}
- **Abgeschlossen am:** {{ $transfer->completed_at->format('d.m.Y H:i') }} Uhr
- **Dauer:** {{ $duration }}
</x-mail::panel>

## 📊 Übertragene Daten

<x-mail::table>
| Element | Anzahl |
| :------ | -----: |
| Teams | {{ $metadata['data_to_transfer']['teams'] ?? 0 }} |
| Gym Halls | {{ $metadata['data_to_transfer']['gym_halls'] ?? 0 }} |
| Media-Dateien | {{ $metadata['data_to_transfer']['media_files'] ?? 0 }} |
| Media-Größe | {{ $metadata['data_to_transfer']['media_size_mb'] ?? 0 }} MB |
</x-mail::table>

## 🗑️ Entfernte Daten

<x-mail::table>
| Element | Anzahl |
| :------ | -----: |
| User-Memberships | {{ $metadata['data_to_remove']['user_memberships'] ?? 0 }} |
| Stripe-Subscription | {{ $metadata['data_to_remove']['stripe_subscription'] === 'yes' ? 'Ja' : 'Nein' }} |
</x-mail::table>

@if($canRollback)
## 🔄 Rollback-Information

✓ Der Transfer kann noch bis **{{ $rollbackExpiresAt->format('d.m.Y H:i') }} Uhr** rückgängig gemacht werden (24-Stunden-Fenster).

Sie können den Transfer in der Admin-Oberfläche mit einem Klick zurücksetzen.
@endif

<x-mail::button :url="route('admin.club-transfers.show', $transfer->id)" color="success">
Transfer-Details anzeigen
</x-mail::button>

## 💡 Was kommt als Nächstes?

- Der Club ist jetzt Teil des Tenants **{{ $targetTenant->name }}**
- Alle Teams und Gym-Hallen wurden mit übertragen
- User-Zuordnungen müssen im neuen Tenant neu eingerichtet werden
- Falls eine Stripe-Subscription aktiv war, wurde diese gekündigt

Vielen Dank für die Nutzung von {{ config('app.name') }}!

Viele Grüße,<br>
Ihr {{ config('app.name') }} Team

---

*Diese E-Mail wurde automatisch generiert. Bei Fragen wenden Sie sich bitte an Ihren System-Administrator.*
</x-mail::message>
