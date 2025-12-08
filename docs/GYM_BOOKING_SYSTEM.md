# Gym-Buchungssystem

> Automatische Hallenbuchungen aus Team-Zuordnungen mit Training-Absage-Funktion

---

## Übersicht

Das Gym-Buchungssystem ermöglicht die Verwaltung von Sporthallen-Buchungen für Basketball-Teams. Kernfeatures:

- **Automatische Buchungsgenerierung**: Bei Team-Zuordnung zu einem Zeitslot werden automatisch Buchungen bis zum Saison-Ende erstellt
- **Training absagen**: Trainer können einzelne Trainings absagen, wodurch die Zeit für andere Teams verfügbar wird
- **Buchungsanfragen**: Freigegebene Zeiten können von anderen Teams angefragt werden

---

## Architektur

### Models

| Model | Beschreibung |
|-------|--------------|
| `GymHall` | Sporthalle mit Adresse, Kapazität, Öffnungszeiten |
| `GymTimeSlot` | Wiederkehrender Zeitslot (z.B. "Montag 18:00-20:00") |
| `GymTimeSlotTeamAssignment` | Team-Zuordnung zu einem Zeitslot-Segment |
| `GymBooking` | Konkrete Buchung für ein bestimmtes Datum |
| `GymBookingRequest` | Anfrage für eine freigegebene Buchung |
| `GymCourt` | Einzelnes Spielfeld innerhalb einer Halle |

### Services

| Service | Pfad | Verantwortlichkeit |
|---------|------|-------------------|
| `GymTimeSlotAssignmentService` | `app/Services/Gym/` | Team-Zuordnungen, Buchungsgenerierung |
| `GymBookingService` | `app/Services/Gym/` | Buchungs-Lifecycle (Release, Cancel, Request) |
| `GymConflictDetector` | `app/Services/Gym/` | Konfliktprüfung bei Zuordnungen |

### Controller

| Controller | Pfad | Endpunkte |
|------------|------|-----------|
| `GymDashboardController` | `app/Http/Controllers/Gym/` | Web-Views (Dashboard, Halls, Bookings) |
| `GymBookingController` | `app/Http/Controllers/Api/` | API für Buchungs-Aktionen |
| `GymHallController` | `app/Http/Controllers/Api/` | API für Hallen-Management |

---

## Datenfluss

### 1. Team-Zuordnung erstellen

```
Frontend: Team zu Zeitslot zuordnen
    ↓
GymTimeSlotController::assignTeamToSegment()
    ↓
GymTimeSlotAssignmentService::assignTeamToSegment()
    ↓
GymTimeSlotTeamAssignment wird erstellt
    ↓
generateBookingsForAssignment() wird aufgerufen
    ↓
GymBooking Einträge für jede Woche bis Saison-Ende (Status: reserved)
```

### 2. Training absagen

```
Trainer: Training für Datum X absagen
    ↓
POST /api/v2/gym-bookings/{id}/release
    ↓
GymBookingService::releaseBooking()
    ↓
GymBooking Status: reserved → released
    ↓
Benachrichtigung an andere Teams (optional)
    ↓
Andere Teams können diese Zeit anfragen
```

### 3. Freigegebene Zeit anfragen

```
Team B: Freigegebene Zeit anfragen
    ↓
GymBookingRequest wird erstellt
    ↓
Original-Team wird benachrichtigt
    ↓
Genehmigung/Ablehnung
    ↓
Bei Genehmigung: GymBooking.team_id → Team B
```

---

## API-Endpunkte

### Training absagen (Release)

```http
POST /api/v2/gym-bookings/{gymBooking}/release
Authorization: Bearer {token}
Content-Type: application/json

{
    "reason": "Trainer krank"  // optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Training wurde abgesagt. Die Zeit ist nun für andere Teams verfügbar.",
    "data": {
        "booking_id": 123,
        "status": "released",
        "booking_date": "2025-12-15"
    }
}
```

### Buchung stornieren (Cancel)

```http
POST /api/v2/gym-bookings/{gymBooking}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
    "reason": "Feiertag"  // optional
}
```

### Buchungen für Team abrufen

```http
GET /api/v2/gym-bookings/for-team
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "booking_date": "2025-12-15",
            "start_time": "18:00",
            "end_time": "20:00",
            "status": "reserved",
            "gym_time_slot": {
                "gym_hall": {
                    "id": 1,
                    "name": "Sporthalle Mitte"
                }
            }
        }
    ]
}
```

---

## Artisan Command

### Buchungen aus Zuordnungen generieren

```bash
# Alle aktiven Zuordnungen verarbeiten
php artisan gym:generate-bookings

# Nur eine bestimmte Zuordnung
php artisan gym:generate-bookings --assignment=123

# Dry-Run (zeigt nur was passieren würde)
php artisan gym:generate-bookings --dry-run
```

**Beispiel-Ausgabe:**
```
Verarbeite 2 Zuordnung(en)...

Zuordnung #1: U14 Jungs - Montag 17:00-18:30 (Sporthalle Mitte)
  → 24 Buchungen erstellt

Zuordnung #2: Senioren - Dienstag 20:00-22:00 (Sporthalle Mitte)
  → 24 Buchungen erstellt

Gesamt: 48 Buchungen erstellt.
```

---

## Buchungs-Status

| Status | Beschreibung |
|--------|--------------|
| `reserved` | Standard-Status bei Erstellung aus Team-Zuordnung |
| `released` | Vom Team freigegeben (Training abgesagt) |
| `requested` | Von einem anderen Team angefragt |
| `confirmed` | Buchung bestätigt |
| `cancelled` | Storniert |
| `completed` | Abgeschlossen |
| `no_show` | Team nicht erschienen |

---

## Typische Workflows

### Workflow 1: Neue Saison einrichten

1. **Halle anlegen** unter `/gym-management/halls`
2. **Zeitslots erstellen** (z.B. "Montag 17:00-22:00")
3. **Teams zuordnen** via Terminplan-Modal
4. → Buchungen werden automatisch bis Saison-Ende generiert

### Workflow 2: Training absagen

1. Trainer öffnet Buchungsübersicht
2. Wählt Buchung für bestimmtes Datum
3. Klickt "Training absagen"
4. → Status wechselt zu `released`
5. → Andere Teams sehen die verfügbare Zeit

### Workflow 3: Nachträgliche Buchungen generieren

Falls eine Zuordnung erstellt wurde bevor die Saison definiert war:

```bash
php artisan gym:generate-bookings --assignment=123
```

---

## Beziehung: Assignment vs. Booking

| Aspekt | GymTimeSlotTeamAssignment | GymBooking |
|--------|---------------------------|------------|
| **Typ** | Wiederkehrende Zuordnung | Konkrete Buchung |
| **Zeitbezug** | Wochentag (z.B. "Montag") | Datum (z.B. "2025-12-15") |
| **Gültigkeit** | `valid_from` bis `valid_until` | `booking_date` |
| **Status** | `active` / `inactive` | `reserved`, `released`, etc. |
| **Absagen** | Nicht möglich | Ja, einzeln absagbar |

---

## Datenbankschema (vereinfacht)

```
gym_halls
├── id
├── club_id
├── name
└── ...

gym_time_slots
├── id
├── gym_hall_id
├── day_of_week
├── start_time
├── end_time
└── ...

gym_time_slot_team_assignments
├── id
├── gym_time_slot_id
├── team_id
├── day_of_week
├── start_time
├── end_time
├── duration_minutes
├── valid_from
├── valid_until
└── status

gym_bookings
├── id
├── gym_time_slot_id
├── team_id
├── booking_date
├── start_time
├── end_time
├── duration_minutes
├── status
├── booking_type
└── metadata (JSON: generated_from_assignment)
```

---

## Konfiguration

### Buchungsgenerierung

Die Buchungen werden standardmäßig bis zum **Saison-Ende** des Clubs generiert. Falls keine aktive Saison existiert, werden Buchungen für **12 Wochen** im Voraus erstellt.

### Berechtigungen

| Aktion | Erforderliche Rolle |
|--------|---------------------|
| Halle anlegen | `club_admin`, `tenant_admin`, `super_admin` |
| Team zuordnen | `club_admin`, `trainer` |
| Training absagen | `trainer`, `assistant_coach` des Teams |
| Buchung anfragen | Jedes Team im Club |

---

## Siehe auch

- [SUBSCRIPTION_API_REFERENCE.md](SUBSCRIPTION_API_REFERENCE.md) - Feature Gates für Gym-Management
- [BERECHTIGUNGS_MATRIX.md](../BERECHTIGUNGS_MATRIX.md) - Vollständige Berechtigungsmatrix
