# Spieler-Registrierung via QR-Code/Link - Implementierungsplan

**Feature**: Trainer erstellen QR-Codes/Links für neue Spieler-Registrierungen, Club-Admins ordnen Spieler den Teams zu

**Status**: 🔴 Nicht begonnen
**Priorität**: Hoch
**Geschätzte Zeit**: 3-4 Tage
**Erstellt**: 2025-10-20

---

## 📋 Inhaltsverzeichnis

1. [Feature-Übersicht](#feature-übersicht)
2. [Technische Architektur](#technische-architektur)
3. [Implementierungs-Checkliste](#implementierungs-checkliste)
4. [Detaillierte Schritte](#detaillierte-schritte)
5. [Datenbank-Schema](#datenbank-schema)
6. [API-Endpunkte](#api-endpunkte)
7. [Sicherheit & Validierung](#sicherheit--validierung)
8. [Testing-Strategie](#testing-strategie)
9. [Deployment-Überlegungen](#deployment-überlegungen)

---

## 🎯 Feature-Übersicht

### Problemstellung
Neue Spieler, die noch keinen Account haben, können sich derzeit nicht selbst registrieren. Trainer müssen manuell jeden Spieler anlegen, was zeitaufwändig ist.

### Lösung
Ein Self-Service-Registrierungssystem mit folgenden Komponenten:

#### 🟢 Phase 1: Einladungs-Erstellung (Trainer)
- Trainer erstellen zeitlich begrenzte Registrierungs-Links
- Automatische QR-Code-Generierung
- Download in verschiedenen Formaten (PNG, SVG, PDF)
- Tracking: Wie viele Spieler haben sich registriert

#### 🟡 Phase 2: Selbst-Registrierung (Neue Spieler)
- Öffentlich zugängliches Registrierungsformular (kein Login erforderlich)
- Spieler füllen grundlegende Daten aus (Name, Geburtsdatum, Kontaktdaten)
- Account wird automatisch erstellt mit Status "pending_assignment"
- E-Mail-Verifizierung (optional)

#### 🔵 Phase 3: Team-Zuordnung (Club Admin)
- Dashboard mit allen "pending" Spielern
- Club Admin ordnet Spieler den Teams zu
- Optional: Trainer-Vorschlag wird angezeigt
- Nach Zuordnung: Spieler werden aktiv und können sich einloggen

---

## 🏗️ Technische Architektur

### Komponenten-Übersicht

```
┌─────────────────────────────────────────────────────────────┐
│                    TRAINER INTERFACE                          │
│  - Einladung erstellen                                        │
│  - QR-Code generieren                                         │
│  - Registrierungen verwalten                                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      v
┌─────────────────────────────────────────────────────────────┐
│            PlayerRegistrationInvitation Model                 │
│  - Token-Generierung                                          │
│  - Ablaufdatum                                                │
│  - Nutzungs-Tracking                                          │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      v
┌─────────────────────────────────────────────────────────────┐
│                ÖFFENTLICHER REGISTRIERUNGS-LINK               │
│  /register/player/{token}                                     │
│  - Kein Auth erforderlich                                     │
│  - Rate-Limited                                               │
│  - CAPTCHA-geschützt (optional)                               │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      v
┌─────────────────────────────────────────────────────────────┐
│              PlayerRegistrationService                        │
│  - User erstellen (status: pending)                           │
│  - Player-Profil erstellen (pending_assignment: true)         │
│  - Benachrichtigungen senden                                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      v
┌─────────────────────────────────────────────────────────────┐
│              CLUB ADMIN DASHBOARD                             │
│  - Pending Players anzeigen                                   │
│  - Team-Zuordnung (Dropdown/Drag&Drop)                        │
│  - Spieler aktivieren                                         │
└─────────────────────────────────────────────────────────────┘
```

### Bestehende Komponenten, die genutzt werden

✅ **QRCodeService** (`app/Services/QRCodeService.php`)
- Bereits vorhanden für Emergency Access
- Wird erweitert um `generatePlayerRegistrationQR()`

✅ **Permission System** (Spatie Laravel Permission)
- Trainer: `create players` permission
- Club Admin: `manage team rosters` permission

✅ **Player Model** (`app/Models/Player.php`)
- Wird erweitert um `pending_team_assignment` Status

✅ **User Model** mit Jetstream
- Multi-Rollen-System bereits vorhanden

---

## ✅ Implementierungs-Checkliste

### Phase 1: Datenbank & Models (Tag 1)

- [ ] **1.1** Migration: `player_registration_invitations` Tabelle erstellen
  - [ ] Basis-Felder (id, uuid, invitation_token)
  - [ ] Beziehungen (club_id, created_by_user_id, target_team_id)
  - [ ] QR-Code-Daten (qr_code_path, qr_code_metadata)
  - [ ] Konfiguration (expires_at, max_registrations, is_active)
  - [ ] Timestamps und Soft Deletes

- [ ] **1.2** Model: `PlayerRegistrationInvitation` erstellen
  - [ ] Fillable fields definieren
  - [ ] Casts (JSON, dates) hinzufügen
  - [ ] Relationships (club, creator, targetTeam)
  - [ ] Scopes (active, expired, byClub)
  - [ ] Accessors (daysUntilExpiry, registrationUrl)
  - [ ] Token-Generation in boot() Method

- [ ] **1.3** Migration: `players` Tabelle erweitern
  - [ ] `pending_team_assignment` (boolean, default: false)
  - [ ] `registered_via_invitation_id` (foreign key, nullable)
  - [ ] `registration_completed_at` (timestamp, nullable)

- [ ] **1.4** Migration: `users` Tabelle erweitern
  - [ ] `account_status` (enum: 'pending', 'active', 'suspended')
  - [ ] `pending_verification` (boolean, default: false)

### Phase 2: Service Layer (Tag 1-2)

- [ ] **2.1** Service: `PlayerRegistrationService` erstellen
  - [ ] `createInvitation($userId, $clubId, $options)` - Einladung erstellen
  - [ ] `validateToken($token)` - Token-Validierung (Ablauf, Limit)
  - [ ] `registerPlayer($token, $playerData)` - Spieler registrieren
  - [ ] `assignPlayerToTeam($playerId, $teamId, $assignedBy)` - Team zuordnen
  - [ ] `getInvitationStats($invitationId)` - Statistiken abrufen
  - [ ] `deactivateInvitation($invitationId)` - Einladung deaktivieren
  - [ ] `getPendingPlayers($clubId)` - Alle pending Players eines Clubs

- [ ] **2.2** Service: QRCodeService erweitern
  - [ ] `generatePlayerRegistrationQR($invitation, $options)` - QR-Code generieren
  - [ ] Format-Optionen (PNG, SVG, PDF)
  - [ ] Größen-Optionen (Standard, Druck, Web)
  - [ ] Optional: Club-Logo einbetten

- [ ] **2.3** Notification: E-Mail-Benachrichtigungen
  - [ ] `PlayerRegisteredNotification` - An Trainer senden
  - [ ] `PlayerAssignedNotification` - An Spieler senden
  - [ ] `RegistrationWelcomeNotification` - An neuen Spieler

### Phase 3: Controller & Routes (Tag 2)

- [ ] **3.1** Controller: `PlayerRegistrationController` erstellen

  **Trainer-Bereich (Auth + Permission):**
  - [ ] `index()` - Liste aller Einladungen
  - [ ] `create()` - Formular anzeigen
  - [ ] `store()` - Einladung speichern + QR generieren
  - [ ] `show($invitation)` - Details + Statistiken
  - [ ] `destroy($invitation)` - Einladung deaktivieren
  - [ ] `downloadQR($invitation, $format)` - QR-Code herunterladen
  - [ ] `statistics()` - Übersicht aller Registrierungen

  **Öffentlicher Bereich (Kein Auth):**
  - [ ] `showRegistrationForm($token)` - Registrierungsformular anzeigen
  - [ ] `submitRegistration($token, Request)` - Registrierung verarbeiten
  - [ ] `verifyEmail($token, $verificationToken)` - E-Mail bestätigen (optional)

- [ ] **3.2** Controller: `PendingPlayersController` erstellen (Club Admin)
  - [ ] `index()` - Liste aller pending Players
  - [ ] `assign(Request)` - Spieler Team zuordnen
  - [ ] `bulkAssign(Request)` - Mehrere Spieler gleichzeitig zuordnen
  - [ ] `reject($playerId)` - Registrierung ablehnen

- [ ] **3.3** Routes: `routes/player_registration.php` erstellen

  ```php
  // Trainer Routes (geschützt)
  Route::prefix('trainer/player-invitations')
    ->middleware(['auth', 'verified', 'role:trainer|club_admin'])
    ->name('trainer.invitations.')
    ->group(function () {
      Route::get('/', [PlayerRegistrationController::class, 'index'])->name('index');
      Route::get('/create', [PlayerRegistrationController::class, 'create'])->name('create');
      Route::post('/', [PlayerRegistrationController::class, 'store'])->name('store');
      Route::get('/{invitation}', [PlayerRegistrationController::class, 'show'])->name('show');
      Route::delete('/{invitation}', [PlayerRegistrationController::class, 'destroy'])->name('destroy');
      Route::get('/{invitation}/qr/{format}', [PlayerRegistrationController::class, 'downloadQR'])->name('download-qr');
    });

  // Öffentliche Routes (kein Auth)
  Route::prefix('register/player')
    ->middleware(['throttle:player-registration,5,1'])
    ->name('public.player.')
    ->group(function () {
      Route::get('/{token}', [PlayerRegistrationController::class, 'showRegistrationForm'])->name('register');
      Route::post('/{token}', [PlayerRegistrationController::class, 'submitRegistration'])->name('submit');
    });

  // Club Admin Routes
  Route::prefix('club-admin/pending-players')
    ->middleware(['auth', 'verified', 'role:club_admin'])
    ->name('club-admin.pending.')
    ->group(function () {
      Route::get('/', [PendingPlayersController::class, 'index'])->name('index');
      Route::post('/assign', [PendingPlayersController::class, 'assign'])->name('assign');
      Route::post('/bulk-assign', [PendingPlayersController::class, 'bulkAssign'])->name('bulk-assign');
      Route::delete('/{player}', [PendingPlayersController::class, 'reject'])->name('reject');
    });
  ```

### Phase 4: Policy & Permissions (Tag 2)

- [ ] **4.1** Policy: `PlayerRegistrationInvitationPolicy` erstellen
  - [ ] `viewAny($user)` - Liste anzeigen (Trainer für eigene Teams)
  - [ ] `view($user, $invitation)` - Details anzeigen
  - [ ] `create($user)` - Einladung erstellen (Trainer)
  - [ ] `update($user, $invitation)` - Einladung bearbeiten
  - [ ] `delete($user, $invitation)` - Einladung löschen
  - [ ] Scope: Trainer sehen nur Einladungen für ihre Teams
  - [ ] Scope: Club Admin sieht alle Club-Einladungen

- [ ] **4.2** Policy: `PlayerPolicy` erweitern
  - [ ] `assignToTeam($user, $player)` - Club Admin kann zuordnen
  - [ ] `viewPending($user)` - Pending Players sehen

- [ ] **4.3** Permissions: Seeder aktualisieren (`RoleAndPermissionSeeder.php`)
  - [ ] Neue Permission: `create player invitations` (Trainer, Club Admin)
  - [ ] Neue Permission: `manage player invitations` (Club Admin)
  - [ ] Neue Permission: `assign pending players` (Club Admin)

### Phase 5: Frontend (Vue/Inertia) (Tag 3)

- [ ] **5.1** Trainer-Interface: Einladungen verwalten
  - [ ] `Pages/Trainer/PlayerInvitations/Index.vue`
    - [ ] Liste aller Einladungen (Tabelle)
    - [ ] Filter: Aktiv/Abgelaufen/Alle
    - [ ] Sortierung nach Erstellungsdatum
    - [ ] Quick Actions: QR anzeigen, Link kopieren, Deaktivieren
  - [ ] `Pages/Trainer/PlayerInvitations/Create.vue`
    - [ ] Formular: Ziel-Team (Dropdown), Ablaufdatum, Max. Registrierungen
    - [ ] Vorschau: URL + QR-Code
    - [ ] Download-Buttons: PNG, SVG, PDF
  - [ ] `Pages/Trainer/PlayerInvitations/Show.vue`
    - [ ] QR-Code (groß)
    - [ ] Link zum Kopieren
    - [ ] Statistiken: Registrierte Spieler, Verbleibende Zeit
    - [ ] Liste der registrierten Spieler

- [ ] **5.2** Öffentliches Interface: Registrierung
  - [ ] `Pages/Public/PlayerRegistration.vue`
    - [ ] Club-Name + Logo anzeigen
    - [ ] Formular: Vor-/Nachname, Geburtsdatum, E-Mail, Telefon
    - [ ] Optional: Position, Größe, Erfahrung
    - [ ] Datenschutz-Checkbox (GDPR)
    - [ ] CAPTCHA (optional)
    - [ ] Submit → Erfolgsmeldung
  - [ ] `Pages/Public/RegistrationSuccess.vue`
    - [ ] Erfolgsmeldung
    - [ ] Nächste Schritte erklären
    - [ ] Hinweis: E-Mail-Verifizierung (falls aktiviert)

- [ ] **5.3** Club Admin Interface: Pending Players
  - [ ] `Pages/ClubAdmin/PendingPlayers/Index.vue`
    - [ ] Tabelle: Name, Registriert am, Vorgeschlagenes Team
    - [ ] Pro Zeile: Team-Dropdown + "Zuordnen"-Button
    - [ ] Bulk-Aktion: Mehrere Spieler gleichzeitig zuordnen
    - [ ] Ablehnen-Button (mit Bestätigung)
  - [ ] `Components/PendingPlayerCard.vue`
    - [ ] Spieler-Informationen (Name, Alter, Position)
    - [ ] Team-Auswahl-Dropdown
    - [ ] Actions: Zuordnen, Details, Ablehnen

### Phase 6: Validation & Security (Tag 3)

- [ ] **6.1** Form Requests erstellen
  - [ ] `StorePlayerRegistrationInvitationRequest`
    - [ ] Validation: club_id, target_team_id, expires_at, max_registrations
  - [ ] `SubmitPlayerRegistrationRequest`
    - [ ] Validation: first_name, last_name, birth_date, email, phone
    - [ ] Custom Rules: Alter (min. 6 Jahre), eindeutige E-Mail
  - [ ] `AssignPlayerToTeamRequest`
    - [ ] Validation: player_id, team_id
    - [ ] Authorization: Team gehört zum Club

- [ ] **6.2** Security-Maßnahmen
  - [ ] Rate Limiting konfigurieren
    - [ ] `player-registration`: 5 Versuche pro Minute
    - [ ] `invitation-creation`: 10 pro Stunde (Trainer)
  - [ ] Token-Sicherheit
    - [ ] 32 Zeichen, kryptografisch sicher
    - [ ] Automatischer Ablauf nach X Tagen
  - [ ] CAPTCHA integrieren (optional)
    - [ ] Google reCAPTCHA v3
    - [ ] Nur bei öffentlicher Registrierung

- [ ] **6.3** GDPR-Compliance
  - [ ] Datenschutz-Hinweis im Formular
  - [ ] Consent-Tracking speichern
  - [ ] Recht auf Vergessen: Pending Players können gelöscht werden
  - [ ] Activity Log: Alle Registrierungen protokollieren

### Phase 7: Testing (Tag 4)

- [ ] **7.1** Unit Tests
  - [ ] `PlayerRegistrationServiceTest`
    - [ ] Token-Generierung
    - [ ] Token-Validierung (gültig, abgelaufen, Limit erreicht)
    - [ ] Spieler-Registrierung
    - [ ] Team-Zuordnung
  - [ ] `QRCodeServiceTest`
    - [ ] QR-Generierung für Player Registration

- [ ] **7.2** Feature Tests
  - [ ] `PlayerRegistrationInvitationTest`
    - [ ] Trainer kann Einladung erstellen
    - [ ] Nicht-Trainer können keine Einladung erstellen
    - [ ] Einladung wird mit QR-Code gespeichert
  - [ ] `PublicPlayerRegistrationTest`
    - [ ] Öffentliches Formular ist erreichbar
    - [ ] Gültiger Token: Registrierung erfolgreich
    - [ ] Abgelaufener Token: Fehler
    - [ ] Limit erreicht: Fehler
  - [ ] `PendingPlayerAssignmentTest`
    - [ ] Club Admin kann Spieler zuordnen
    - [ ] Nach Zuordnung: Status wird "active"
    - [ ] Benachrichtigung wird gesendet

- [ ] **7.3** Browser Tests (optional)
  - [ ] E2E-Test: Kompletter Flow (Einladung → Registrierung → Zuordnung)

### Phase 8: Documentation & Deployment (Tag 4)

- [ ] **8.1** Dokumentation
  - [ ] API-Dokumentation (OpenAPI)
  - [ ] User Guide: Wie Trainer Einladungen erstellen
  - [ ] Admin Guide: Wie pending Players zuordnen
  - [ ] README aktualisieren

- [ ] **8.2** Database Seeding
  - [ ] Seeder: Beispiel-Einladungen erstellen
  - [ ] Seeder: Pending Players für Testing

- [ ] **8.3** Deployment
  - [ ] Migrations ausführen
  - [ ] Permissions seeden
  - [ ] QR-Code-Storage konfigurieren
  - [ ] Rate Limiter testen
  - [ ] Monitoring: New Relic/Sentry Alerts

---

## 🗄️ Datenbank-Schema

### Tabelle: `player_registration_invitations`

```sql
CREATE TABLE player_registration_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    invitation_token VARCHAR(32) NOT NULL UNIQUE,

    -- Beziehungen
    club_id BIGINT UNSIGNED NOT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    target_team_id BIGINT UNSIGNED NULL COMMENT 'Vorgeschlagenes Team',

    -- QR-Code
    qr_code_path VARCHAR(255) NULL,
    qr_code_metadata JSON NULL COMMENT 'Format, Größe, etc.',

    -- Konfiguration
    expires_at TIMESTAMP NOT NULL,
    max_registrations INT UNSIGNED DEFAULT 50,
    current_registrations INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    -- Einstellungen
    settings JSON NULL COMMENT 'Zusätzliche Konfiguration',

    -- Audit
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    -- Indizes
    INDEX idx_club_id (club_id),
    INDEX idx_created_by (created_by_user_id),
    INDEX idx_token (invitation_token),
    INDEX idx_expires_at (expires_at),

    -- Foreign Keys
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_team_id) REFERENCES teams(id) ON DELETE SET NULL
);
```

### Erweiterung: `players` Tabelle

```sql
ALTER TABLE players
ADD COLUMN pending_team_assignment BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN registered_via_invitation_id BIGINT UNSIGNED NULL AFTER pending_team_assignment,
ADD COLUMN registration_completed_at TIMESTAMP NULL AFTER registered_via_invitation_id,
ADD FOREIGN KEY (registered_via_invitation_id)
    REFERENCES player_registration_invitations(id) ON DELETE SET NULL;
```

### Erweiterung: `users` Tabelle

```sql
ALTER TABLE users
ADD COLUMN account_status ENUM('pending', 'active', 'suspended') DEFAULT 'pending' AFTER status,
ADD COLUMN pending_verification BOOLEAN DEFAULT FALSE AFTER account_status;
```

---

## 🔌 API-Endpunkte

### Trainer-Bereich (Auth + Permission)

| Method | Endpoint | Controller | Beschreibung |
|--------|----------|------------|--------------|
| GET | `/trainer/player-invitations` | `index()` | Liste aller Einladungen |
| GET | `/trainer/player-invitations/create` | `create()` | Formular anzeigen |
| POST | `/trainer/player-invitations` | `store()` | Einladung erstellen |
| GET | `/trainer/player-invitations/{id}` | `show()` | Details anzeigen |
| DELETE | `/trainer/player-invitations/{id}` | `destroy()` | Einladung deaktivieren |
| GET | `/trainer/player-invitations/{id}/qr/{format}` | `downloadQR()` | QR-Code herunterladen |

### Öffentlicher Bereich (Kein Auth)

| Method | Endpoint | Controller | Beschreibung |
|--------|----------|------------|--------------|
| GET | `/register/player/{token}` | `showRegistrationForm()` | Registrierungsformular |
| POST | `/register/player/{token}` | `submitRegistration()` | Registrierung absenden |

### Club Admin Bereich (Auth + Role)

| Method | Endpoint | Controller | Beschreibung |
|--------|----------|------------|--------------|
| GET | `/club-admin/pending-players` | `index()` | Pending Players anzeigen |
| POST | `/club-admin/pending-players/assign` | `assign()` | Spieler Team zuordnen |
| POST | `/club-admin/pending-players/bulk-assign` | `bulkAssign()` | Mehrere zuordnen |
| DELETE | `/club-admin/pending-players/{id}` | `reject()` | Registrierung ablehnen |

---

## 🔒 Sicherheit & Validierung

### Rate Limiting

```php
// config/rate-limiter.php

RateLimiter::for('player-registration', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('invitation-creation', function (Request $request) {
    return Limit::perHour(10)->by($request->user()->id);
});
```

### Token-Sicherheit

- **Länge**: 32 Zeichen (alphanumerisch)
- **Generierung**: `Str::random(32)` oder besser `bin2hex(random_bytes(16))`
- **Ablauf**: Konfigurierbar (Standard: 30 Tage)
- **Einmalig**: Token ist eindeutig in der Datenbank

### Validation Rules

#### Einladung erstellen
```php
[
    'club_id' => 'required|exists:clubs,id',
    'target_team_id' => 'nullable|exists:teams,id',
    'expires_at' => 'required|date|after:now',
    'max_registrations' => 'required|integer|min:1|max:500',
]
```

#### Spieler registrieren
```php
[
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'birth_date' => 'required|date|before:today|after:1920-01-01',
    'email' => 'required|email|unique:users,email',
    'phone' => 'required|string|regex:/^[0-9\s\-\+\(\)]+$/',
    'gdpr_consent' => 'required|accepted',
]
```

### CAPTCHA (Optional)

```php
// Google reCAPTCHA v3 Integration
[
    'g-recaptcha-response' => 'required|recaptcha',
]
```

---

## 🧪 Testing-Strategie

### Unit Tests (12 Tests)

```php
// tests/Unit/Services/PlayerRegistrationServiceTest.php

test('can create invitation with valid data')
test('generates unique token')
test('validates expired token')
test('validates registration limit reached')
test('registers player successfully')
test('assigns player to team')
test('sends notification on registration')
test('deactivates invitation')
```

### Feature Tests (15 Tests)

```php
// tests/Feature/PlayerRegistrationInvitationTest.php

test('trainer can create invitation')
test('non-trainer cannot create invitation')
test('invitation includes qr code')
test('can download qr code in different formats')
test('can deactivate invitation')

// tests/Feature/PublicPlayerRegistrationTest.php

test('public can access registration form with valid token')
test('cannot access with expired token')
test('cannot access with invalid token')
test('can submit registration with valid data')
test('cannot submit with invalid email')
test('registration limit is enforced')

// tests/Feature/PendingPlayerAssignmentTest.php

test('club admin can view pending players')
test('club admin can assign player to team')
test('player becomes active after assignment')
test('notification is sent after assignment')
```

### Browser Tests (1 E2E)

```php
// tests/Browser/PlayerRegistrationFlowTest.php

test('complete registration flow', function () {
    // 1. Trainer erstellt Einladung
    // 2. Öffentlicher Nutzer registriert sich
    // 3. Club Admin ordnet Spieler zu
    // 4. Spieler kann sich einloggen
});
```

---

## 🚀 Deployment-Überlegungen

### Pre-Deployment Checklist

- [ ] Alle Migrations testen (lokal + staging)
- [ ] Permissions in Seeder hinzufügen
- [ ] QR-Code-Storage-Path konfigurieren (`.env`)
- [ ] Rate Limiter testen
- [ ] E-Mail-Templates testen
- [ ] CAPTCHA-Keys konfigurieren (falls verwendet)

### Environment Variables

```env
# Player Registration Settings
PLAYER_REGISTRATION_TOKEN_LENGTH=32
PLAYER_REGISTRATION_EXPIRES_DAYS=30
PLAYER_REGISTRATION_MAX_PER_INVITATION=50
PLAYER_REGISTRATION_REQUIRE_EMAIL_VERIFICATION=false

# QR Code Storage
QR_CODE_STORAGE_DISK=public
QR_CODE_STORAGE_PATH=qr-codes/player-registrations

# Rate Limiting
RATE_LIMIT_PLAYER_REGISTRATION=5
RATE_LIMIT_INVITATION_CREATION=10

# CAPTCHA (optional)
RECAPTCHA_SITE_KEY=your-site-key
RECAPTCHA_SECRET_KEY=your-secret-key
```

### Storage Setup

```bash
# Erstelle Storage-Ordner für QR-Codes
mkdir -p storage/app/public/qr-codes/player-registrations
php artisan storage:link

# Setze Permissions
chmod -R 775 storage/app/public/qr-codes
```

### Migration Rollback Plan

Falls Probleme auftreten:

```bash
# Rollback der letzten Migration
php artisan migrate:rollback --step=1

# Oder spezifische Migration
php artisan migrate:rollback --path=/database/migrations/2025_10_20_000001_create_player_registration_invitations_table.php
```

---

## 📊 Metriken & Monitoring

### Key Performance Indicators (KPIs)

- **Registrierungsrate**: Wie viele Spieler registrieren sich pro Einladung?
- **Conversion-Zeit**: Zeit zwischen Registrierung und Team-Zuordnung
- **Token-Auslastung**: Wie viele Tokens werden tatsächlich genutzt?
- **Fehlerrate**: Gescheiterte Registrierungen

### Monitoring-Events

```php
// Events für Analytics/Monitoring

event(new InvitationCreated($invitation));
event(new PlayerRegistered($player, $invitation));
event(new PlayerAssignedToTeam($player, $team, $assignedBy));
event(new InvitationExpired($invitation));
```

### Logging

```php
// Wichtige Log-Punkte

Log::info('Player registration invitation created', [
    'invitation_id' => $invitation->id,
    'created_by' => $userId,
    'club_id' => $clubId,
]);

Log::info('New player registered via invitation', [
    'player_id' => $player->id,
    'invitation_id' => $invitationId,
    'ip_address' => $request->ip(),
]);

Log::info('Player assigned to team', [
    'player_id' => $playerId,
    'team_id' => $teamId,
    'assigned_by' => $userId,
]);
```

---

## 🎨 UI/UX Überlegungen

### Trainer-Interface

**Dashboard-Widget**: "Aktive Registrierungs-Links"
- Anzahl aktiver Einladungen
- Anzahl registrierter Spieler (heute/diese Woche)
- Quick-Link: "Neue Einladung erstellen"

**Einladungs-Liste**: Übersichtliche Tabelle
- Spalten: Team, Erstellt am, Läuft ab, Registrierungen (X/Max), Status
- Filter: Aktiv, Abgelaufen, Alle
- Actions: QR anzeigen, Link kopieren, Deaktivieren

**QR-Code-Ansicht**: Modal mit Vorschau
- Großer QR-Code (scanbar)
- Link zum Kopieren (mit Copy-Button)
- Download-Optionen: PNG (klein, groß), SVG, PDF
- Social-Share-Buttons (WhatsApp, E-Mail)

### Öffentliches Registrierungsformular

**Design-Prinzipien**:
- Einfach und übersichtlich
- Mobile-First (viele scannen mit dem Handy)
- Progress-Indicator (falls mehrstufig)
- Clear Call-to-Action: "Jetzt registrieren"

**Formular-Felder** (Step-by-Step):
1. **Persönliche Daten**: Vorname, Nachname, Geburtsdatum
2. **Kontaktdaten**: E-Mail, Telefon
3. **Basketball-Info**: Position (optional), Erfahrung, Größe
4. **Datenschutz**: GDPR-Consent (Checkbox + Link zur Datenschutzerklärung)

**Erfolgsseite**:
- Grünes Häkchen-Icon
- "Registrierung erfolgreich!"
- Text: "Der Club-Administrator wird deine Registrierung prüfen und dich einem Team zuordnen."
- Nächste Schritte erklären
- "Du erhältst eine E-Mail, sobald du aktiviert wurdest"

### Club Admin Interface

**Pending Players Dashboard**:
- Badge mit Anzahl pending Players (z.B. in Sidebar)
- Tabelle: Name, Alter, Registriert am, Vorgeschlagenes Team
- Bulk-Actions: Mehrere Spieler gleichzeitig zuordnen
- Filter: Nach Einladung, Nach Registrierungsdatum

**Team-Zuordnung**:
- Dropdown mit allen Teams des Clubs
- Optional: Drag & Drop Interface (Spieler zu Team ziehen)
- Bestätigungs-Modal: "Spieler [Name] zu Team [Team-Name] zuordnen?"

---

## 🔄 Erweiterungsmöglichkeiten (Future)

### Phase 2 Enhancements

- [ ] **Multi-Step-Registrierung**: Medizinische Daten, Notfallkontakte
- [ ] **Elternzustimmung**: Falls Spieler minderjährig
- [ ] **Document Upload**: Foto, Ausweiskopie, ärztliche Bescheinigung
- [ ] **Payment Integration**: Registrierungsgebühr direkt zahlen
- [ ] **SMS-Benachrichtigungen**: Zusätzlich zu E-Mail
- [ ] **Automatische Team-Zuordnung**: Basierend auf Alter, Position, Skill Level
- [ ] **Warteliste**: Falls Team voll ist
- [ ] **Registrierungs-Formular-Builder**: Club Admin kann Felder anpassen
- [ ] **Multi-Language**: Registrierungsformular in mehreren Sprachen
- [ ] **Analytics Dashboard**: Detaillierte Statistiken für Trainer/Club Admin

### Integration mit bestehenden Features

- [ ] **Training Sessions**: Neue Spieler automatisch zu Probetraining einladen
- [ ] **Emergency Contacts**: QR-Code-System erweitern
- [ ] **Subscription Limits**: Registrierungs-Links basierend auf Abo-Plan limitieren
- [ ] **Federation APIs**: Registrierte Spieler automatisch bei DBB/FIBA anmelden

---

## 📝 Notizen & Offene Fragen

### Offene Entscheidungen

1. **E-Mail-Verifizierung**:
   - [ ] Erforderlich oder optional?
   - [ ] Wenn erforderlich: Spieler-Status erst nach Verifizierung auf "pending"?

2. **CAPTCHA**:
   - [ ] Google reCAPTCHA v3 (unsichtbar) oder v2 (Checkbox)?
   - [ ] Nur bei auffälligem Verhalten aktivieren?

3. **Token-Ablauf**:
   - [ ] Standard 30 Tage oder anpassbar pro Einladung?
   - [ ] Automatische Erinnerung an Trainer, wenn Token bald abläuft?

4. **Registrierungs-Limit**:
   - [ ] Pro Einladung oder global pro Club?
   - [ ] Was passiert, wenn Limit erreicht: Warten auf Freigabe oder neuer Token?

5. **Team-Zuordnung**:
   - [ ] Nur Club Admin oder auch Trainer?
   - [ ] Automatische Zuordnung zu vorgeschlagenem Team (Option)?

6. **Daten bei Ablehnung**:
   - [ ] Spieler-Daten sofort löschen oder aufbewahren (GDPR)?
   - [ ] Benachrichtigung an abgelehnten Spieler?

### Technische Überlegungen

- **Performance**: Bei vielen Registrierungen → Queue für E-Mails nutzen
- **Skalierung**: Redis-Cache für Token-Validierung
- **Security**: Rate Limiting pro IP und pro Token
- **Backup**: Regelmäßige DB-Backups vor Produktions-Rollout

---

## 📚 Referenzen & Ressourcen

### Bestehende Implementierungen

- **Emergency Access System** (`routes/emergency.php`): Ähnlicher öffentlicher Zugriff ohne Auth
- **QRCodeService** (`app/Services/QRCodeService.php`): QR-Code-Generierung
- **Jetstream TeamInvitation**: E-Mail-basierte Einladungen (als Referenz)

### Externe Libraries

- **SimpleSoftwareIO QR Code**: `composer require simplesoftwareio/simple-qrcode`
- **Laravel Notifications**: Built-in E-Mail-System
- **Google reCAPTCHA**: `composer require google/recaptcha` (optional)

### Dokumentation

- [Laravel Authorization](https://laravel.com/docs/12.x/authorization)
- [Laravel Notifications](https://laravel.com/docs/12.x/notifications)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6)
- [Inertia.js Forms](https://inertiajs.com/forms)

---

## ✅ Sign-Off Checkliste

Vor dem Merge in `main`:

- [ ] Alle Unit Tests bestanden
- [ ] Alle Feature Tests bestanden
- [ ] Code Review durch Senior Developer
- [ ] Permissions im Seeder hinzugefügt
- [ ] Migrations erfolgreich auf Staging ausgeführt
- [ ] QR-Code-Generierung funktioniert
- [ ] E-Mail-Benachrichtigungen getestet
- [ ] Rate Limiting verifiziert
- [ ] GDPR-Compliance geprüft
- [ ] Dokumentation aktualisiert (README, API Docs)
- [ ] User Guide für Trainer erstellt
- [ ] Monitoring/Logging konfiguriert

---

**Letzte Aktualisierung**: 2025-10-20
**Maintainer**: BasketManager Pro Dev Team
**Status**: 🔴 In Planung

---

## 🚦 Nächste Schritte

1. **Review diesen Plan** mit dem Team
2. **Entscheidungen treffen** zu offenen Fragen (E-Mail-Verifizierung, CAPTCHA, etc.)
3. **Priorisierung** festlegen (MVP vs. Nice-to-Have)
4. **Zeitplan** erstellen (Sprint-Planung)
5. **Los geht's!** 🚀
