# 🏀 Season Management System - BasketManager Pro

## Überblick

Das Season Management System ermöglicht die vollständige Verwaltung von Basketball-Saisons mit automatischem Team-Rollover, Kader-Übertragung und Statistik-Archivierung.

## Features

✅ **Vollständiger Saison-Lifecycle**
- Erstellung neuer Saisons mit Metadaten
- Aktivierung und Abschluss von Saisons
- Automatischer Saison-Wechsel mit einem Klick

✅ **Team-Management**
- Automatische Kopie aller Teams in die neue Saison
- Neue Team-Instanzen pro Saison (saubere Trennung)
- Beibehaltung von Team-Konfigurationen

✅ **Kader-Rollover**
- Automatische Übertragung aktiver Spieler
- Jersey-Nummern und Positionen werden übernommen
- Alte Kader werden archiviert (nicht gelöscht)

✅ **Statistik-Snapshots**
- Automatische Archivierung aller Spieler-Statistiken beim Saison-Abschluss
- Historische Stats bleiben erhalten
- Per-Season Analyse möglich

---

## Datenmodell

### Season Model
```php
Season {
    id: bigint
    club_id: bigint
    name: string (z.B. "2024/25")
    start_date: date
    end_date: date
    status: enum('draft', 'active', 'completed')
    is_current: boolean
    description: text
    settings: json
}
```

**Status-Übergänge:**
- `draft` → `active` (via `activate()`)
- `active` → `completed` (via `complete()`)

### Season Statistics Model
```php
SeasonStatistic {
    player_id: bigint
    season_id: bigint
    team_id: bigint
    games_played: int
    points: int
    field_goal_percentage: decimal
    // ... alle Basketball-Statistiken
    snapshot_date: timestamp
}
```

---

## API Endpoints

### Base URL
```
/api/club/{club}/seasons
```

### 1. Liste aller Saisons
```http
GET /api/club/{club}/seasons
```

**Query Parameters:**
- `include_completed` (boolean): Include abgeschlossene Saisons (default: true)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "2024/25",
            "status": "active",
            "is_current": true,
            "start_date": "2024-08-01",
            "end_date": "2025-07-31"
        }
    ]
}
```

### 2. Aktuelle Saison abrufen
```http
GET /api/club/{club}/seasons/current
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "2024/25",
        "status": "active",
        "teams": [...],
        "games": [...]
    }
}
```

### 3. Neue Saison erstellen
```http
POST /api/club/{club}/seasons
```

**Payload:**
```json
{
    "name": "2025/26",
    "start_date": "2025-08-01",
    "end_date": "2026-07-31",
    "description": "Saison 2025/26",
    "settings": {}
}
```

### 4. Neue Saison starten (Vollständiger Saisonwechsel)
```http
POST /api/club/{club}/seasons/start-new
```

**Payload:**
```json
{
    "name": "2025/26",
    "start_date": "2025-08-01",
    "end_date": "2026-07-31",
    "rollover_teams": true,
    "rollover_rosters": true
}
```

**Was passiert:**
1. ✅ Vorherige Saison wird abgeschlossen
2. ✅ Statistik-Snapshots werden erstellt
3. ✅ Neue Saison wird erstellt
4. ✅ Alle Teams werden kopiert
5. ✅ Kader werden automatisch übertragen
6. ✅ Neue Saison wird aktiviert

### 5. Saison abschließen
```http
POST /api/club/{club}/seasons/{season}/complete
```

**Payload:**
```json
{
    "create_snapshots": true
}
```

### 6. Saison aktivieren
```http
POST /api/club/{club}/seasons/{season}/activate
```

### 7. Saison aktualisieren
```http
PUT /api/club/{club}/seasons/{season}
```

### 8. Saison löschen
```http
DELETE /api/club/{club}/seasons/{season}
```

---

## Service Layer

### SeasonService

#### `createNewSeason()`
Erstellt eine neue Saison mit Validierung.

```php
$season = $seasonService->createNewSeason(
    club: $club,
    name: '2025/26',
    startDate: Carbon::parse('2025-08-01'),
    endDate: Carbon::parse('2026-07-31'),
    description: 'Saison 2025/26'
);
```

#### `startNewSeasonForClub()`
Vollständiger Saisonwechsel in einem Schritt.

```php
$newSeason = $seasonService->startNewSeasonForClub(
    club: $club,
    newSeasonName: '2025/26',
    startDate: Carbon::parse('2025-08-01'),
    endDate: Carbon::parse('2026-07-31'),
    previousSeason: null, // Optional, wird automatisch gefunden
    rolloverTeams: true,
    rolloverRosters: true
);
```

#### `completeSeason()`
Schließt eine Saison ab und erstellt Snapshots.

```php
$seasonService->completeSeason(
    season: $season,
    createSnapshots: true
);
```

#### `rolloverTeams()`
Kopiert alle Teams in eine neue Saison.

```php
$copiedTeams = $seasonService->rolloverTeams(
    oldSeason: $oldSeason,
    newSeason: $newSeason,
    rolloverRosters: true
);
```

#### `rolloverRoster()`
Überträgt Kader von einem Team zum anderen.

```php
$transferredCount = $seasonService->rolloverRoster(
    oldTeam: $oldTeam,
    newTeam: $newTeam
);
```

---

## Workflow: Neuer Saisonstart

### Manueller Ablauf

1. **Saison erstellen**
```bash
POST /api/club/1/seasons
{
    "name": "2025/26",
    "start_date": "2025-08-01",
    "end_date": "2026-07-31"
}
```

2. **Alte Saison abschließen**
```bash
POST /api/club/1/seasons/1/complete
{
    "create_snapshots": true
}
```

3. **Teams manuell kopieren** (optional)
```php
$seasonService->rolloverTeams($oldSeason, $newSeason);
```

4. **Neue Saison aktivieren**
```bash
POST /api/club/1/seasons/2/activate
```

### Automatischer Ablauf (Empfohlen)

**Ein einziger API-Call:**
```bash
POST /api/club/1/seasons/start-new
{
    "name": "2025/26",
    "start_date": "2025-08-01",
    "end_date": "2026-07-31",
    "rollover_teams": true,
    "rollover_rosters": true
}
```

✅ Erledigt alle Schritte automatisch in einer Transaktion!

---

## Datenbankstruktur

### Migrations
1. `2025_11_19_083233_create_seasons_table.php` - Season-Tabelle
2. `2025_11_19_083323_create_season_statistics_table.php` - Statistik-Snapshots
3. `2025_11_19_083451_add_season_id_to_teams_table.php` - Teams → Season Relationship
4. `2025_11_19_083507_add_season_id_to_games_table.php` - Games → Season Relationship
5. `2025_11_19_083626_migrate_existing_season_data_to_seasons_table.php` - Datenmigration

### Relationships
- `Club` **hasMany** `Season`
- `Season` **hasMany** `BasketballTeam`
- `Season` **hasMany** `Game`
- `Season` **hasMany** `SeasonStatistic`
- `BasketballTeam` **belongsTo** `Season`
- `Game` **belongsTo** `Season`
- `Player` **hasMany** `SeasonStatistic`

---

## Frontend Integration (Vue.js/Inertia)

### Beispiel: Saison-Wechsel-Button

```vue
<template>
  <button @click="startNewSeason" :disabled="loading">
    Neue Saison starten
  </button>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const loading = ref(false);

const startNewSeason = async () => {
  loading.value = true;

  router.post(`/api/club/${club.id}/seasons/start-new`, {
    name: '2025/26',
    start_date: '2025-08-01',
    end_date: '2026-07-31',
    rollover_teams: true,
    rollover_rosters: true
  }, {
    onSuccess: () => {
      alert('Neue Saison erfolgreich gestartet!');
    },
    onFinish: () => {
      loading.value = false;
    }
  });
};
</script>
```

---

## Testing

### Unit Tests
```bash
php artisan test --filter=SeasonServiceTest
```

### Feature Tests
```bash
php artisan test --filter=SeasonControllerTest
```

### Beispiel Test
```php
public function test_can_start_new_season_with_rollover()
{
    $club = Club::factory()->create();
    $oldSeason = Season::factory()->create(['club_id' => $club->id]);
    $team = BasketballTeam::factory()->create(['season_id' => $oldSeason->id]);

    $response = $this->postJson("/api/club/{$club->id}/seasons/start-new", [
        'name' => '2025/26',
        'start_date' => '2025-08-01',
        'end_date' => '2026-07-31',
        'rollover_teams' => true,
        'rollover_rosters' => true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('seasons', ['name' => '2025/26']);
}
```

---

## Troubleshooting

### Problem: "Saison kann nicht aktiviert werden"
**Lösung:** Prüfen Sie, ob die Saison im Status `draft` ist und die Datumsangaben korrekt sind.

### Problem: "Teams werden nicht kopiert"
**Lösung:** Stellen Sie sicher, dass `rollover_teams: true` im Request ist.

### Problem: "Alte Statistiken verschwinden"
**Lösung:** Die Statistiken werden **nicht** gelöscht! Sie werden in `season_statistics` gesnappt. Abfrage über:
```php
$stats = SeasonStatistic::where('player_id', $player->id)
    ->where('season_id', $oldSeason->id)
    ->first();
```

---

## Best Practices

1. **Immer Snapshots erstellen**: Setzen Sie `create_snapshots: true` beim Saison-Abschluss
2. **Automatischer Rollover empfohlen**: Nutzen Sie `/start-new` statt manueller Schritte
3. **Alte Saisons nicht löschen**: Soft Deletes erlauben Wiederherstellung
4. **Testen vor Prod**: Führen Sie den Saisonwechsel zuerst in Staging aus

---

## Zukünftige Erweiterungen

- [ ] UI-Dashboard für Saison-Management
- [ ] Automatischer Saisonwechsel per Cron-Job
- [ ] Import von Saison-Plänen (iCal)
- [ ] Export von Saison-Reports (PDF/Excel)
- [ ] Mehrsprachige Saison-Namen

---

## Support

Bei Fragen oder Problemen:
- GitHub Issues: [basketmanager-pro/issues](https://github.com/...)
- E-Mail: support@basketmanager.pro
- Dokumentation: [https://docs.basketmanager.pro/season-management](https://docs.basketmanager.pro/)

---

**Version:** 1.0.0
**Autor:** BasketManager Pro Team
**Letzte Aktualisierung:** 2025-11-19
