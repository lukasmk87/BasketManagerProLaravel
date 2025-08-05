# Testbenutzer Zugangsdaten - BasketManager Pro

## Übersicht
Dieses Dokument enthält alle Zugangsdaten für die Testbenutzer im BasketManager Pro System.

## Standard Testbenutzer

### Test User (Basic)
- **Email**: `test@example.com`
- **Passwort**: `password`
- **Name**: Test User
- **Erstellt durch**: DatabaseSeeder
- **Verwendung**: Grundlegende Funktionalitätstests

## Basketball-spezifische Testbenutzer

Diese Benutzer werden automatisch in Tests über die `BasketballTestCase` Klasse erstellt und enthalten alle notwendigen Rollen und Berechtigungen.

### Admin
- **Email**: `admin@basketmanager.test`
- **Passwort**: `password`
- **Name**: Test Admin
- **Rolle**: `admin`
- **Berechtigungen**: Vollzugriff auf alle Systemfunktionen
- **Sprache**: Deutsch (`de`)

### Club Administrator
- **Email**: `clubadmin@basketmanager.test`
- **Passwort**: `password`
- **Name**: Test Club Admin
- **Rolle**: `club_admin`
- **Berechtigungen**: 
  - Vereinsverwaltung
  - Team- und Spielerverwaltung
  - Spielverwaltung
  - Statistiken einsehen
  - Notfallkontakte verwalten
- **Sprache**: Deutsch (`de`)

### Trainer
- **Email**: `trainer@basketmanager.test`
- **Passwort**: `password`
- **Name**: Test Trainer
- **Rolle**: `trainer`
- **Berechtigungen**:
  - Teams einsehen und bearbeiten
  - Spieler einsehen und bearbeiten
  - Spiele einsehen, bearbeiten und bewerten
  - Statistiken einsehen
- **Sprache**: Deutsch (`de`)

### Spieler
- **Email**: `player@basketmanager.test`
- **Passwort**: `password`
- **Name**: Test Player
- **Rolle**: `player`
- **Berechtigungen**:
  - Teams einsehen
  - Spieler einsehen
  - Spiele einsehen
  - Statistiken einsehen
- **Sprache**: Deutsch (`de`)
- **Geburtsdatum**: 20 Jahre vor heute

## Datenbank Seeds ausführen

### Standard Testbenutzer erstellen
```bash
php artisan db:seed --class=DatabaseSeeder
```

### Basketball-spezifische Testbenutzer
Diese werden automatisch in den Tests erstellt. Für manuelle Tests siehe Abschnitt "Manuelle Erstellung".

## Manuelle Erstellung der Basketball-Testbenutzer

Wenn du die Basketball-Testbenutzer außerhalb der Tests benötigst, führe folgende Befehle in `php artisan tinker` aus:

```php
// Rollen und Berechtigungen erstellen (falls noch nicht vorhanden)
$this->call(RoleAndPermissionSeeder::class);

// Admin Benutzer
$admin = User::factory()->create([
    'name' => 'Test Admin',
    'email' => 'admin@basketmanager.test',
    'password' => Hash::make('password'),
    'is_active' => true,
    'is_verified' => true,
    'language' => 'de',
]);
$admin->assignRole('admin');

// Club Admin Benutzer  
$clubAdmin = User::factory()->create([
    'name' => 'Test Club Admin',
    'email' => 'clubadmin@basketmanager.test',
    'password' => Hash::make('password'),
    'is_active' => true,
    'is_verified' => true,
    'language' => 'de',
]);
$clubAdmin->assignRole('club_admin');

// Trainer Benutzer
$trainer = User::factory()->create([
    'name' => 'Test Trainer',
    'email' => 'trainer@basketmanager.test',
    'password' => Hash::make('password'),
    'is_active' => true,
    'is_verified' => true,
    'language' => 'de',
]);
$trainer->assignRole('trainer');

// Spieler Benutzer
$player = User::factory()->create([
    'name' => 'Test Player',
    'email' => 'player@basketmanager.test',
    'password' => Hash::make('password'),
    'is_active' => true,
    'is_verified' => true,
    'language' => 'de',
    'date_of_birth' => now()->subYears(20),
]);
$player->assignRole('player');
```

## Verfügbare Rollen und Berechtigungen

### Alle verfügbaren Rollen im System:
- `super_admin` - Super Administrator mit allen Berechtigungen
- `admin` - System Administrator  
- `club_admin` - Club Administrator
- `trainer` - Trainer/Head Coach
- `assistant_coach` - Assistent Trainer
- `scorer` - Punkterichter/Statistiker
- `player` - Spieler
- `parent` - Eltern/Erziehungsberechtigte
- `team_manager` - Team Manager
- `guest` - Gast/Fan
- `referee` - Schiedsrichter

### Admin Berechtigungen (`admin`)
- Vollzugriff auf User-, Club-, Team-, Spieler-Management
- System-Administration, Backups, Integrations  
- Statistiken, Analytics, Training, Emergency
- Finanzen, GDPR, Medien, Tournaments

### Club Admin Berechtigungen (`club_admin`)
- User-Management (eingeschränkt)
- Club-Management (eigener Club)
- Team- und Spieler-Management (Club-Teams/Spieler)
- Spiele, Statistiken, Training, Emergency
- Kommunikation, Medien, Tournaments, Finanzen

### Trainer Berechtigungen (`trainer`)
- Team-Management (zugewiesene Teams)
- Spieler-Management (Team-Spieler)
- Spiele bewerten und verwalten
- Training, Emergency, Kommunikation
- Statistiken und Medien

### Spieler Berechtigungen (`player`)
- Eigene Team-Informationen anzeigen
- Mitspieler und Statistiken anzeigen
- Spiele und Training anzeigen
- Messaging-System

## Test-Datenbank Setup

### .env.testing
Erstelle eine `.env.testing` Datei für isolierte Tests:

```env
APP_NAME="BasketManager Pro Test"
APP_ENV=testing
APP_KEY=base64:YOUR_TEST_KEY_HERE
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

### PHPUnit Tests ausführen
```bash
php artisan test
```

## Wichtige Hinweise

1. **Passwort**: Alle Testbenutzer verwenden das Passwort `password`
2. **E-Mail Verifikation**: Alle Testbenutzer sind bereits verifiziert (`is_verified = true`)
3. **Sprache**: Standard-Sprache ist Deutsch (`de`)
4. **Entwicklungsumgebung**: Diese Zugangsdaten sind nur für Development/Testing gedacht
5. **Sicherheit**: Niemals in Produktionsumgebung verwenden!

## Support

Bei Problemen mit den Testbenutzern:
1. Überprüfe, ob die Datenbank-Migrations ausgeführt wurden
2. Stelle sicher, dass die Rollen und Berechtigungen korrekt erstellt wurden
3. Prüfe die Laravel-Logs für eventuelle Fehler