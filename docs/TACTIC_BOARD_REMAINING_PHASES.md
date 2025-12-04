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
| Phase 7 | ✅ Abgeschlossen | Frontend-Erweiterungen (Drill, Session, Game Integration) |
| Phase 8.1 | ✅ Abgeschlossen | Freihand-Zeichnung |
| Phase 8.2 | ✅ Abgeschlossen | Form-Werkzeuge (Kreis, Rechteck, Pfeil) |
| Phase 8.3 | ✅ Abgeschlossen | Radiergummi |
| Phase 8.4 | ✅ Abgeschlossen | Linien-Styling Panel |
| Phase 9.1 | ✅ Abgeschlossen | Zoom & Pan System |
| Phase 9.2 | ✅ Abgeschlossen | Raster-System mit Snap-to-Grid |
| Phase 10.1-10.2 | ✅ Abgeschlossen | Team-Farben & Ball-Element |
| Phase 11.1-11.3 | ✅ Abgeschlossen | Ball-Animation, Easing, GIF-Export |
| Phase 12.1 | ✅ Abgeschlossen | Tastaturkürzel-System |
| Phase 12.2-12.3 | ✅ Abgeschlossen | Ebenen-Verwaltung & Ausrichten |
| Phase 13 | ⏳ Ausstehend | Templates & Bibliothek |
| Phase 14 | ⏳ Optional | Kollaboration (Echtzeit, Kommentare, Share-Links) |

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

## Phase 7: Frontend-Erweiterungen ✅

### Implementierte Komponenten

#### 1. `resources/js/Components/TacticBoard/PlaySelector.vue`
**Funktionen:**
- Modal-Dialog zum Hinzufügen von Plays
- Suche nach Name/Beschreibung
- Filter nach Kategorie
- HTML5 Drag & Drop zum Reordnen
- TacticBoardViewer-Vorschau (Thumbnail)
- Inline-Bearbeitung für Notes

**Props:**
- `plays`, `initialSelectedPlays`, `showPreview`

**Events:**
- `@update:selectedPlays` - Array der ausgewählten Plays

#### 2. `resources/js/Components/TacticBoard/PlaybookSelector.vue`
**Funktionen:**
- Modal-Dialog zum Hinzufügen von Playbooks
- Suche nach Name
- Accordion-Ansicht für Playbook → Plays Hierarchie
- TacticBoardViewer-Vorschau für einzelne Plays

**Props:**
- `playbooks`, `selectedPlaybooks`

**Events:**
- `@attach`, `@detach` - Playbook-ID

### Erweiterte Seiten

#### 3. Drill Edit-Seite
**Datei:** `resources/js/Pages/Training/EditDrill.vue`
- Neuer Abschnitt "Verknüpfte Spielzüge" mit PlaySelector
- Automatisches Laden und Synchronisieren der Plays via API

#### 4. TrainingSession Create/Edit-Seiten
**Dateien:**
- `resources/js/Pages/Training/CreateSession.vue`
- `resources/js/Pages/Training/EditSession.vue`
- PlaySelector nach DrillSelector eingebunden
- Plays werden mit Session verknüpft

#### 5. Game Show-Seite
**Datei:** `resources/js/Pages/Games/Show.vue`
- Neuer Abschnitt "Spielvorbereitung"
- PlaybookSelector zum Hinzufügen von Playbooks
- Expandierbare Playbook-Ansicht mit Play-Vorschauen
- TacticBoardViewer für Spielzug-Anzeige

### Backend-Anpassungen

#### TrainingController
**Datei:** `app/Http/Controllers/TrainingController.php`
- `createSession()`: `availablePlays` in Props
- `editSession()`: `availablePlays` in Props
- `editDrill()`: `availablePlays` in Props

#### GameController
**Datei:** `app/Http/Controllers/GameController.php`
- `show()`: `availablePlaybooks` in Props (mit eager-loaded Plays)

---

---

## Phase 8: Erweiterte Zeichenwerkzeuge

### 8.1 Freihand-Zeichnung ✅

**Implementierte Komponenten:**
- `resources/js/Components/TacticBoard/Elements/FreehandPath.vue`
  - v-line Rendering mit Control-Points
  - Punkt-Simplification-Algorithmus (Douglas-Peucker-Variante)
  - Linien-Styling Support (solid, dashed, dotted)
  - Selektion mit visueller Hervorhebung

**Erweiterungen in useTacticBoard.js:**
- `freehandPaths` ref für Freihand-Pfade
- `startFreehandDrawing()`, `continueFreehandDrawing()`, `finishFreehandDrawing()` Methoden
- `simplifyPoints()` für Performance-Optimierung

### 8.2 Form-Werkzeuge ✅

**Implementierte Komponenten:**

#### CircleShape.vue
- v-ellipse mit radiusX/radiusY
- Resize-Handles bei Selektion
- Drag & Drop Unterstützung
- Anpassbare Füllung und Stroke

#### RectangleShape.vue
- v-rect mit Width/Height
- Rotation-Handle
- Transparenz für Zonen-Hervorhebung
- Selektion mit Resize-Handles

#### ArrowShape.vue
- v-arrow für eigenständige Pfeile
- Control-Points für Start/Ende
- Anpassbare Pfeilspitzen

**Erweiterungen in useTacticBoard.js:**
- `circles`, `rectangles`, `arrows` refs
- `addCircle()`, `addRectangle()`, `addArrow()` Methoden
- Position- und Size-Update-Handler

### 8.3 Radiergummi ✅

**Implementierte Funktionen:**

#### Eraser-Tool
- Klick auf Element löscht es sofort
- Ziehen über Elemente löscht alle berührten Elemente (kontinuierlich)
- Löscht alle Element-Typen: Spieler, Ball, Linien, Formen, Text, Annotationen
- Visueller Cursor-Feedback (roter Backspace-Cursor)

**Keyboard-Shortcut:**
- `E` aktiviert den Radiergummi

**Erweiterungen in useTacticBoard.js:**
- `isErasing` ref für Eraser-Modus-State
- `getElementAtPosition(x, y, tolerance)` - Hit-Detection für alle Element-Typen
- `startErasing()`, `eraseAtPosition(x, y)`, `finishErasing()` Methoden
- `pointToLineDistance()` und `isPointNearPath()` Hilfsfunktionen für Linien-Hit-Detection

**TacticBoardEditor.vue Anpassungen:**
- Eraser-Case in `handleMouseDown()`
- Kontinuierliches Löschen in `handleMouseMove()` während `isErasing`
- Eraser-Modus beenden in `handleMouseUp()`
- CSS-Cursor für Eraser-Modus

**TacticBoardToolbar.vue:**
- Neues Tool `eraser` mit `BackspaceIcon`

### 8.4 Linien-Styling ✅

**Neue Komponente:**
- `resources/js/Components/TacticBoard/Panels/LineStylePanel.vue`
  - Stroke-Width Slider (1-10px)
  - Line-Style Buttons (solid, dashed, dotted)
  - Color-Picker mit Presets

**Modifizierte Komponenten:**
- MovementPath.vue - lineStyle prop
- PassLine.vue - lineStyle prop (default: dashed)
- FreehandPath.vue - lineStyle prop

---

## Phase 9: Canvas-Kontrollen

### 9.1 Zoom & Pan ✅

**Neues Composable:**
- `resources/js/composables/useTacticZoom.js`

**Features:**
- Scale-Range: 0.5x - 3.0x
- Zoom-Step: 0.1
- Mausrad-Zoom (zoomt Richtung Mauszeiger)
- Touch-Pinch-Zoom Unterstützung
- Pan-Funktionalität

**Toolbar-Integration:**
- Zoom-Out Button (-)
- Zoom-Prozent Anzeige
- Zoom-In Button (+)
- Reset-Button (1:1)

### 9.2 Raster-System ✅

**Neue Komponente:**
- `resources/js/Components/TacticBoard/Court/GridOverlay.vue`
  - Dynamische Grid-Linien (vertikal/horizontal)
  - Major-Grid-Linien alle 5 Einheiten
  - Konfigurierbare Größe (10, 20, 25, 50 px)
  - Subtile Transparenz für nicht-störende Anzeige

**Snap-to-Grid Funktion:**
- `snapToGrid(value)` in useTacticBoard.js
- Automatisches Snapping beim Platzieren/Verschieben
- Freehand-Zeichnung ohne Snapping (für natürliches Zeichnen)

**Toolbar-Integration:**
- Grid Toggle-Button
- Grid-Size Dropdown (bei aktiviertem Grid)

---

## Phase 10: Spieler-Erweiterungen

### 10.1-10.2 Team-Farben & Ball-Element ✅

**Neue Komponenten:**

#### TeamSettingsPanel.vue
- Offense-Team Farb-Picker mit Presets
- Defense-Team Farb-Picker mit Presets
- Ball hinzufügen/entfernen Button

#### BallElement.vue
- Realistisches Basketball-Design (orange mit Nähten)
- 3D-Effekt mit Highlight
- Drag & Drop Unterstützung
- Selektion mit Hervorhebung

**Erweiterungen in useTacticBoard.js:**
- `teamColors` ref ({ offense, defense })
- `ball` ref (einzelner Ball, nullable)
- `addBall()`, `removeBall()`, `updateBallPosition()` Methoden

**PlayerToken.vue Erweiterungen:**
- `teamColors` prop für dynamische Farben
- Farben aktualisieren sich automatisch bei Änderung

---

## Phase 11: Animation-Erweiterungen ✅

### 11.1 Ball-Animation ✅

**Problem gelöst:**
Ball existierte im Board, wurde aber NICHT in Animationen erfasst.

**Implementierte Änderungen:**
- `TacticBoardEditor.vue`: Ball zu `currentElements` hinzugefügt
- `TacticBoardTimeline.vue`: Ball in `captureCurrentElements()` erfassen
- `useTacticAnimation.js`: Ball in `createInitialKeyframe()` unterstützt
- `AnimationPreview.vue`: Ball-Rendering mit animierten Positionen

### 11.2 Easing-Funktionen ✅

**Neue Datei:** `resources/js/utils/easingFunctions.js`

**Verfügbare Easing-Kurven:**
| Funktion | Beschreibung |
|----------|--------------|
| linear | Gleichmäßige Bewegung |
| easeIn | Langsamer Start |
| easeOut | Langsames Ende |
| easeInOut | Langsamer Start und Ende |
| easeInCubic | Stärkerer langsamer Start |
| easeOutCubic | Stärkeres langsames Ende |
| bounce | Springender Effekt |
| elastic | Elastischer Effekt |
| backIn | Überschwingen am Start |
| backOut | Überschwingen am Ende |

**Erweiterungen:**
- `useTacticAnimation.js`: `lerpWithEasing()` Funktion, Easing pro Keyframe
- `KeyframeMarker.vue`: Easing-Dropdown im Kontextmenü
- Animation-Datenversion auf 1.1 erhöht

### 11.3 GIF-Export ✅

**Neue Dateien:**
- `resources/js/utils/gifExporter.js` - GifExporter-Klasse mit gif.js
- `public/js/gif.worker.js` - Web Worker für Encoding

**Features:**
- Client-seitiger GIF-Export mit Progress-Anzeige
- Server-Speicherung für späteres Teilen
- Konfigurierbare FPS und Qualität

**API-Endpunkt:**
```
POST /api/plays/{play}/export/gif
```

**Erweiterungen:**
- `useTacticExport.js`: `exportAsGif()`, `downloadGif()`, `saveGifToServer()`
- `AnimationPreview.vue`: Export-Button mit Progress-Spinner
- `PlayExportService.php`: `exportAsGif()` Methode
- `PlayController.php`: `exportGif()` Endpoint

### Implementierte Dateien (Phase 11)

| Datei | Status |
|-------|--------|
| `resources/js/utils/easingFunctions.js` | ✅ NEU |
| `resources/js/utils/gifExporter.js` | ✅ NEU |
| `public/js/gif.worker.js` | ✅ NEU |

### Modifizierte Dateien (Phase 11)

| Datei | Änderungen |
|-------|------------|
| `resources/js/Components/TacticBoard/TacticBoardEditor.vue` | Ball zu currentElements |
| `resources/js/Components/TacticBoard/TacticBoardTimeline.vue` | Ball-Erfassung, Easing-Events |
| `resources/js/composables/useTacticAnimation.js` | Ball-Support, Easing, Version 1.1 |
| `resources/js/Components/TacticBoard/Animation/AnimationPreview.vue` | Ball-Rendering, GIF-Export-Button |
| `resources/js/Components/TacticBoard/Animation/KeyframeMarker.vue` | Easing-UI |
| `resources/js/composables/useTacticExport.js` | GIF-Export-Methoden |
| `app/Services/TacticBoard/PlayExportService.php` | exportAsGif() Methode |
| `app/Http/Controllers/Api/PlayController.php` | exportGif() Endpoint |
| `routes/api_tactics.php` | GIF-Export Route |
| `package.json` | gif.js Dependency |

### Animation-Datenstruktur (v1.1)
```json
{
  "version": "1.1",
  "duration": 5000,
  "keyframes": [
    {
      "time": 0,
      "elements": {
        "player_1": { "x": 200, "y": 300 },
        "ball_abc": { "x": 210, "y": 310 }
      },
      "events": [],
      "easing": "linear"
    },
    {
      "time": 2500,
      "elements": {
        "player_1": { "x": 350, "y": 250 },
        "ball_abc": { "x": 360, "y": 260 }
      },
      "events": [],
      "easing": "easeInOut"
    }
  ]
}
```

---

## Phase 12: UX-Verbesserungen

### 12.1 Tastaturkürzel-System ✅

**Neues Composable:**
- `resources/js/composables/useTacticKeyboard.js`

**Implementierte Tastaturkürzel:**

| Taste | Aktion |
|-------|--------|
| V | Auswahl-Werkzeug |
| P | Spieler-Werkzeug |
| M | Bewegungslinie |
| A | Passlinie |
| D | Dribbellinie |
| S | Screen-Werkzeug |
| T | Text-Werkzeug |
| F | Freihand |
| C | Kreis-Werkzeug |
| R | Rechteck-Werkzeug |
| W | Pfeil-Werkzeug |
| G | Raster ein/aus |
| Delete/Backspace | Ausgewähltes löschen |
| Escape | Auswahl aufheben |
| Ctrl+Z | Rückgängig |
| Ctrl+Shift+Z | Wiederholen |
| Ctrl+Y | Wiederholen (Alternative) |
| Ctrl++/= | Zoom vergrößern |
| Ctrl+- | Zoom verkleinern |
| Ctrl+0 | Zoom zurücksetzen |

**Features:**
- Automatische Deaktivierung bei Text-Eingabe
- Input-Feld-Erkennung (INPUT, TEXTAREA, contentEditable)

### 12.2 Ebenen-Verwaltung (z-Index) ✅

**Neues Composable:**
- `resources/js/composables/useTacticLayers.js`

**Funktionen:**
- `bringToFront()` - Element an oberste Ebene
- `sendToBack()` - Element an unterste Ebene
- `bringForward()` - Element eine Ebene nach vorne
- `sendBackward()` - Element eine Ebene nach hinten

**Neue UI-Komponente:**
- `resources/js/Components/TacticBoard/Panels/LayerPanel.vue`

**Erweiterungen in useTacticBoard.js:**
- `zIndexCounter` ref für z-Index Vergabe
- `zIndex` Property bei allen Element-Typen (players, paths, shapes, annotations, freehandPaths, circles, rectangles, arrows, ball)
- `allElementsSorted` computed property für sortierte Element-Liste
- `getMaxZIndex()`, `getMinZIndex()`, `updateElementZIndex()` Hilfsmethoden

**Keyboard Shortcuts:**
| Taste | Aktion |
|-------|--------|
| Ctrl+Shift+] | Ganz nach vorne |
| Ctrl+Shift+[ | Ganz nach hinten |
| Ctrl+] | Eine Ebene vor |
| Ctrl+[ | Eine Ebene zurück |

**Export-Format Update:**
- Version 1.1 → 1.2
- Alle Elemente enthalten jetzt `zIndex` Property
- Backward-Kompatibilität: Bei Import ohne zIndex wird sequentieller zIndex basierend auf Array-Position zugewiesen

### 12.3 Ausrichten & Verteilen ✅

**Neues Composable:**
- `resources/js/composables/useTacticAlignment.js`

**Alignment-Funktionen (mind. 2 Elemente):**
- `alignLeft()` - Links ausrichten
- `alignCenter()` - Horizontal zentrieren
- `alignRight()` - Rechts ausrichten
- `alignTop()` - Oben ausrichten
- `alignMiddle()` - Vertikal zentrieren
- `alignBottom()` - Unten ausrichten

**Distribution-Funktionen (mind. 3 Elemente):**
- `distributeHorizontally()` - Gleichmäßiger horizontaler Abstand
- `distributeVertically()` - Gleichmäßiger vertikaler Abstand

**Neue UI-Komponente:**
- `resources/js/Components/TacticBoard/Panels/AlignmentPanel.vue`

**Mehrfachauswahl-System:**
- `selectedIds` ref (Set) für mehrere ausgewählte Element-IDs
- `selectedTypes` ref (Map) für Element-Typ pro ID
- `selectedCount` computed property
- `selectElement(id, type)` - Einzelauswahl
- `toggleSelection(id, type)` - Zur Auswahl hinzufügen/entfernen (Shift+Click)
- `isSelected(id)` - Prüft ob Element ausgewählt
- `selectAll()` - Alle Elemente auswählen

**Keyboard Shortcuts:**
| Taste | Aktion |
|-------|--------|
| Ctrl+A | Alle auswählen |

**TacticBoardEditor.vue Erweiterungen:**
- Shift+Click Handler für alle Element-Typen
- LayerPanel und AlignmentPanel Integration
- Toolbar-Buttons für Panel-Toggles

---

## Implementierte Dateien (Vollständige Liste - Phase 8-12.3)

### Phase 8 (Zeichenwerkzeuge)
| Datei | Status |
|-------|--------|
| `resources/js/Components/TacticBoard/Elements/FreehandPath.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Elements/CircleShape.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Elements/RectangleShape.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Elements/ArrowShape.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Panels/LineStylePanel.vue` | ✅ Erstellt |

### Phase 9 (Canvas-Kontrollen)
| Datei | Status |
|-------|--------|
| `resources/js/composables/useTacticZoom.js` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Court/GridOverlay.vue` | ✅ Erstellt |

### Phase 10 (Spieler-Erweiterungen)
| Datei | Status |
|-------|--------|
| `resources/js/Components/TacticBoard/Elements/BallElement.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Panels/TeamSettingsPanel.vue` | ✅ Erstellt |

### Phase 12 (UX-Verbesserungen)
| Datei | Status |
|-------|--------|
| `resources/js/composables/useTacticKeyboard.js` | ✅ Erstellt |
| `resources/js/composables/useTacticLayers.js` | ✅ Erstellt |
| `resources/js/composables/useTacticAlignment.js` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Panels/LayerPanel.vue` | ✅ Erstellt |
| `resources/js/Components/TacticBoard/Panels/AlignmentPanel.vue` | ✅ Erstellt |

### Modifizierte Dateien
| Datei | Änderungen |
|-------|------------|
| `resources/js/composables/useTacticBoard.js` | Neue Element-Typen, Grid, Ball, Team-Farben, Eraser-Methoden, zIndex, Mehrfachauswahl |
| `resources/js/Components/TacticBoard/TacticBoardEditor.vue` | Zoom/Pan, neue Tools, Panels, Keyboard, Eraser-Modus, Layer/Alignment Panels, Shift+Click |
| `resources/js/Components/TacticBoard/TacticBoardToolbar.vue` | Neue Werkzeuge, Zoom, Grid-Toggle, Eraser-Tool, Layer/Alignment Buttons |
| `resources/js/composables/useTacticKeyboard.js` | Eraser-Shortcut (E), Layer-Shortcuts, Ctrl+A |
| `resources/js/Components/TacticBoard/Elements/PlayerToken.vue` | teamColors prop |
| `resources/js/Components/TacticBoard/Elements/MovementPath.vue` | lineStyle prop |
| `resources/js/Components/TacticBoard/Elements/PassLine.vue` | lineStyle prop |

---

## Nächste Schritte

### Ausstehende Phasen
1. **Phase 13**: Templates & Bibliothek

### Optionale Erweiterungen (Phase 14)
- Echtzeit-Kollaboration (WebSockets)
- Kommentar-System
- Share-Links für öffentlichen Zugang
