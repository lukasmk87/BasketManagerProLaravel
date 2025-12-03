# Taktik-Board - Implementierungsphasen

## Status-Übersicht

| Phase | Status | Beschreibung |
|-------|--------|--------------|
| Phase 1 | ✅ Abgeschlossen | Backend-Grundlagen (Migrations, Models, Policies) |
| Phase 2 | ✅ Abgeschlossen | Services und API-Controller |
| Phase 3 | ✅ Abgeschlossen | Frontend-Basis (Konva.js, Court-Komponenten, Elemente) |
| Phase 4 | ✅ Abgeschlossen | Editor, Composables und Pages |
| Phase 5 | ✅ Abgeschlossen | Animation-Komponenten |
| Phase 6 | ✅ Abgeschlossen | Export-Funktionalität & Integrationen |

---

## Phase 5: Animation-Komponenten ✅

### Implementierte Komponenten

#### 1. `resources/js/Components/TacticBoard/Animation/TimelineControl.vue`
**Funktionen:**
- Play/Pause/Stop-Buttons mit Icons
- Progress-Bar (Range-Slider) für Scrubbing
- Geschwindigkeitsauswahl (0.5x, 1x, 1.5x, 2x)
- Zeitanzeige (aktuell / gesamt in Sekunden)
- Loop-Toggle

**Props:**
- `isPlaying`, `isPaused`, `currentTime`, `duration`
- `playbackSpeed`, `isLooping`

**Events:**
- `play`, `pause`, `stop`, `seek(time)`, `update:speed`, `update:looping`

#### 2. `resources/js/Components/TacticBoard/Animation/KeyframeMarker.vue`
**Funktionen:**
- Kreisförmiger Marker positioniert nach Zeit/Duration
- Drag & Drop zum Verschieben
- Klick zum Springen zu Keyframe
- Aktiver State (orange Highlight)
- Kontextmenü (Bearbeiten, Löschen)

**Props:**
- `keyframe`, `index`, `duration`, `isActive`

**Events:**
- `select`, `move(newTime)`, `edit`, `delete`

#### 3. `resources/js/Components/TacticBoard/TacticBoardTimeline.vue`
**Funktionen:**
- Integration von TimelineControl
- Keyframe-Marker-Liste auf Timeline-Track
- "Keyframe hinzufügen"-Button (erfasst aktuelle Positionen)
- Verbindung mit useTacticAnimation Composable
- Visuelle Timeline-Leiste mit Zeitmarkierungen

**Props:**
- `animationData` (für Import)

**Exposed Methods:**
- `exportAnimationData()` - Gibt animation_data JSON zurück

#### 4. `resources/js/Components/TacticBoard/Animation/AnimationPreview.vue`
**Funktionen:**
- Modal/Overlay mit TacticBoardViewer
- Automatische Wiedergabe bei Öffnung
- Schließen-Button (X) und ESC-Taste
- Timeline-Controls am unteren Rand
- Interpolierte Positionen aus useTacticAnimation
- Tastatursteuerung (Space, Pfeiltasten)

**Props:**
- `playData`, `animationData`, `show`

**Events:**
- `close`

### TacticBoardEditor.vue Erweiterungen
- Animation-Modus Toggle in Toolbar
- TacticBoardTimeline unterhalb des Canvas (bedingt sichtbar)
- Bei Speichern: `animation_data` mit in API-Request
- "Vorschau"-Button für AnimationPreview-Modal
- Keyframe-Erfassung: Speichert alle Element-Positionen zum aktuellen Zeitpunkt

### JSON-Struktur für Animationen
```json
{
  "version": "1.0",
  "duration": 5000,
  "keyframes": [
    {
      "time": 0,
      "elements": {
        "player_1": { "x": 250, "y": 300 },
        "player_2": { "x": 350, "y": 200 }
      },
      "events": []
    },
    {
      "time": 2500,
      "elements": {
        "player_1": { "x": 350, "y": 250 },
        "player_2": { "x": 400, "y": 150 }
      },
      "events": [{ "type": "pass", "from": "player_1", "to": "player_2" }]
    }
  ]
}
```

---

## Phase 6: Export-Funktionalität & Integrationen ✅

### Teil A: Export-Funktionalität ✅

#### PDF-Templates (optimiert)

**`resources/views/exports/play-pdf.blade.php`**
- Professionelles Header-Layout mit Play-Metadaten
- Status-Badges (Entwurf, Veröffentlicht, Archiviert)
- Thumbnail zentriert mit korrektem Seitenverhältnis
- Beschreibung und Tags
- Animationsinfo (Anzahl Keyframes, Dauer)
- Fußzeile mit Export-Datum

**`resources/views/exports/playbook-pdf.blade.php`**
- Cover-Seite mit Statistiken
- Inhaltsverzeichnis mit Kategorien
- Jeder Play auf separater Seite
- Playbook-Notizen pro Play
- Konsistentes Branding

#### API-Endpunkte
```
POST /api/plays/{play}/export/png
GET  /api/plays/{play}/export/pdf
GET  /api/playbooks/{playbook}/export/pdf
```

---

### Teil B: Integrationen ✅

#### 1. Drill-Integration

**Backend (`app/Http/Controllers/Api/DrillController.php`):**
```php
public function getPlays(Drill $drill)
public function attachPlay(Request $request, Drill $drill)
public function detachPlay(Drill $drill, Play $play)
public function reorderPlays(Request $request, Drill $drill)
```

**API-Routes (`routes/api_training.php`):**
```
GET    /api/drills/{drill}/plays           → getPlays
POST   /api/drills/{drill}/plays           → attachPlay
DELETE /api/drills/{drill}/plays/{play}    → detachPlay
PUT    /api/drills/{drill}/plays/reorder   → reorderPlays
```

#### 2. TrainingSession-Integration

**Backend (`app/Http/Controllers/Api/TrainingSessionController.php`):**
```php
public function getPlays(TrainingSession $session)
public function attachPlay(Request $request, TrainingSession $session)
public function detachPlay(TrainingSession $session, Play $play)
public function reorderPlays(Request $request, TrainingSession $session)
public function updatePlayNotes(Request $request, TrainingSession $session, Play $play)
```

**API-Routes (`routes/api_training.php`):**
```
GET    /api/training-sessions/{session}/plays             → getPlays
POST   /api/training-sessions/{session}/plays             → attachPlay
DELETE /api/training-sessions/{session}/plays/{play}      → detachPlay
PUT    /api/training-sessions/{session}/plays/reorder     → reorderPlays
PUT    /api/training-sessions/{session}/plays/{play}/notes → updatePlayNotes
```

#### 3. Game-Integration (Spielvorbereitung)

**Backend (`app/Http/Controllers/Api/GameController.php`):**
```php
public function getPlaybooks(Game $game)
public function attachPlaybook(Request $request, Game $game)
public function detachPlaybook(Game $game, Playbook $playbook)
public function getAllPlays(Game $game)
```

**API-Routes (`routes/api_game_registrations.php`):**
```
GET    /api/games/{game}/playbooks              → getPlaybooks
POST   /api/games/{game}/playbooks              → attachPlaybook
DELETE /api/games/{game}/playbooks/{playbook}   → detachPlaybook
GET    /api/games/{game}/plays                  → getAllPlays
```

---

## Implementierte Dateien (Vollständige Liste)

### Phase 5 (Animation)
| Datei | Status |
|-------|--------|
| `resources/js/Components/TacticBoard/Animation/TimelineControl.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Animation/KeyframeMarker.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Animation/AnimationPreview.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/TacticBoardTimeline.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/TacticBoardEditor.vue` | ✅ Erweitert |

### Phase 6A (Export)
| Datei | Status |
|-------|--------|
| `resources/views/exports/play-pdf.blade.php` | ✅ Optimiert |
| `resources/views/exports/playbook-pdf.blade.php` | ✅ Optimiert |

### Phase 6B (Integrationen)
| Datei | Status |
|-------|--------|
| `app/Http/Controllers/Api/DrillController.php` | ✅ Erweitert |
| `app/Http/Controllers/Api/TrainingSessionController.php` | ✅ Erweitert |
| `app/Http/Controllers/Api/GameController.php` | ✅ Erweitert |
| `routes/api_training.php` | ✅ Routes hinzugefügt |
| `routes/api_game_registrations.php` | ✅ Routes hinzugefügt |

---

## Vorhandene Basis-Implementierung

### Backend (Phase 1 & 2)
- `app/Models/Play.php` - Eloquent Model mit Beziehungen
- `app/Models/Playbook.php` - Playbook Model
- `app/Policies/PlayPolicy.php` - Authorization
- `app/Policies/PlaybookPolicy.php` - Authorization
- `app/Services/TacticBoard/PlayService.php` - Business Logic
- `app/Services/TacticBoard/PlaybookService.php` - Business Logic
- `app/Services/TacticBoard/PlayExportService.php` - Export-Funktionalität
- `app/Http/Controllers/Api/PlayController.php` - REST API
- `app/Http/Controllers/Api/PlaybookController.php` - REST API
- `routes/api_tactics.php` - Tactic Board Routes

### Frontend (Phase 3 & 4)
- `resources/js/Components/TacticBoard/Courts/` - Court-Komponenten
- `resources/js/Components/TacticBoard/Elements/` - Spieler, Ball, Pfeile
- `resources/js/Components/TacticBoard/TacticBoardEditor.vue` - Haupt-Editor
- `resources/js/Components/TacticBoard/TacticBoardViewer.vue` - Read-Only-Ansicht
- `resources/js/composables/useTacticBoard.js` - Board-State-Management
- `resources/js/composables/useTacticAnimation.js` - Animation-Logik
- `resources/js/composables/useTacticHistory.js` - Undo/Redo
- `resources/js/composables/useTacticExport.js` - Export-Funktionen
- `resources/js/Pages/TacticBoard/` - Inertia Pages

### Datenbank
- `database/migrations/*_create_plays_table.php`
- `database/migrations/*_create_playbooks_table.php`
- `database/migrations/*_create_playbook_plays_table.php` - Pivot
- `database/migrations/*_create_drill_plays_table.php` - Pivot
- `database/migrations/*_create_game_playbooks_table.php` - Pivot
- `database/migrations/*_create_training_session_plays_table.php` - Pivot

---

## Testplan

### Unit Tests
```php
tests/Unit/Services/PlayServiceTest.php
tests/Unit/Services/PlaybookServiceTest.php
tests/Unit/Services/PlayExportServiceTest.php
```

### Feature Tests
```php
tests/Feature/Api/PlayControllerTest.php
tests/Feature/Api/PlaybookControllerTest.php
tests/Feature/TacticBoardWebTest.php
```

### Manuelle Tests
1. ✅ Play erstellen mit allen Element-Typen
2. ✅ Play bearbeiten und speichern
3. ✅ Playbook erstellen und Plays hinzufügen
4. ✅ PNG-Export testen
5. ✅ PDF-Export testen
6. ✅ Animation erstellen und abspielen
7. ✅ Play mit Drill verknüpfen
8. ✅ Playbook mit Game verknüpfen

---

## Nächste Schritte (Optional)

### Frontend-Erweiterungen
- Drill Edit-Seite: Abschnitt "Verknüpfte Spielzüge"
- TrainingSession Edit-Seite: Plays pro Drill-Block anzeigen
- Game Show-Seite: "Spielvorbereitung"-Tab mit Playbook-Auswahl

### Mögliche Verbesserungen
- GIF-Export für Animationen
- Echtzeit-Kollaboration (WebSockets)
- Play-Vorlagen-Bibliothek
- KI-gestützte Play-Empfehlungen
