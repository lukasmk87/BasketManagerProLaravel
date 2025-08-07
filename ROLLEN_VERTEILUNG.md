# Rollen-Verteilung - BasketManager Pro

## 📊 Übersicht

BasketManager Pro implementiert ein umfassendes, hierarchisches Rollen- und Berechtigungssystem mit **11 verschiedenen Rollen** und **136 spezifischen Berechtigungen**, das über das Laravel Spatie Permission Package verwaltet wird.

---

## 🏆 Rollen-Hierarchie

### Hierarchische Struktur (von höchster zu niedrigster Berechtigung)

```
🔴 SUPER ADMIN (super_admin)
├── 🔴 SYSTEM ADMINISTRATOR (admin)
│   ├── 🔵 CLUB ADMINISTRATOR (club_admin)
│   │   ├── 🟢 TRAINER/HEAD COACH (trainer)
│   │   │   ├── 🟢 ASSISTANT COACH (assistant_coach)
│   │   │   └── 🟡 TEAM MANAGER (team_manager)
│   │   ├── 🟡 SCORER/STATISTICIAN (scorer)
│   │   └── 🟡 REFEREE (referee)
│   ├── 🟠 PLAYER (player)
│   ├── 🟣 PARENT/GUARDIAN (parent)
│   └── ⚪ GUEST/FAN (guest)
```

---

## 👥 Detaillierte Rollen-Beschreibung

### 🔴 Super Administrator (`super_admin`)
- **Höchste Systemrolle** mit Vollzugriff auf alle Funktionen
- **Berechtigungen**: Alle 136 Permissions
- **Besonderheiten**:
  - Kann andere Super Admins verwalten
  - Uneingeschränkter Datenzugriff
  - System-kritische Operationen (Force Delete, etc.)
- **Dashboard**: System-Administration
- **Farbe**: Rot

### 🔴 System Administrator (`admin`)
- **Systemweite Verwaltung** mit fast allen Berechtigungen
- **Berechtigungen**: 135 von 136 Permissions (außer Super Admin-spezifische)
- **Besonderheiten**:
  - Kann keine Super Admins verwalten
  - Vollzugriff auf alle Clubs, Teams und Spieler
  - System-Administration ohne kritische Operationen
- **Dashboard**: System-Administration
- **Farbe**: Rot

### 🔵 Club Administrator (`club_admin`)
- **Club-spezifische Verwaltung** mit begrenzten Systemrechten
- **Berechtigungen**: 65 spezifische Permissions
- **Scope**: Nur eigene Clubs und deren Teams/Spieler
- **Hauptfunktionen**:
  - Club-Settings und Mitgliederverwaltung
  - Team- und Spielerverwaltung (club-intern)
  - Finanzverwaltung und Berichte
  - Turniere und Events organisieren
- **Dashboard**: Club-Verwaltung
- **Farbe**: Blau

### 🟢 Trainer/Head Coach (`trainer`)
- **Team-fokussierte Rolle** mit Spieler- und Spielverwaltung
- **Berechtigungen**: 45 spezifische Permissions
- **Scope**: Nur zugewiesene Teams und deren Spieler
- **Hauptfunktionen**:
  - Team-Roster Management
  - Spielbewertung und Live-Scoring
  - Training-Sessions planen
  - Spieler-Statistiken verwalten
  - Notfall-Informationen einsehen
- **Dashboard**: Trainer-Dashboard
- **Farbe**: Grün

### 🟢 Assistant Coach (`assistant_coach`)
- **Unterstützende Trainer-Rolle** mit eingeschränkten Berechtigungen
- **Berechtigungen**: 25 spezifische Permissions
- **Scope**: Nur zugewiesene Teams (read-heavy)
- **Hauptfunktionen**:
  - Teams und Spieler einsehen
  - Spiele bewerten (Live-Scoring)
  - Training-Sessions unterstützen
  - Statistiken einsehen
- **Dashboard**: Trainer-Dashboard
- **Farbe**: Grün

### 🟡 Team Manager (`team_manager`)
- **Organisatorische Team-Verwaltung** ohne Coaching-Aspekte
- **Berechtigungen**: 20 spezifische Permissions
- **Scope**: Zugewiesene Teams
- **Hauptfunktionen**:
  - Team-Organisation und -planung
  - Spiel-Terminierung
  - Notfall-Kontakte verwalten
  - Team-Kommunikation
- **Dashboard**: Basic Dashboard
- **Farbe**: Gelb

### 🟡 Scorer/Statistician (`scorer`)
- **Spezialisierte Rolle** für Spielbewertung und Statistiken
- **Berechtigungen**: 8 spezifische Permissions
- **Scope**: Spiele und Statistiken
- **Hauptfunktionen**:
  - Live-Scoring durchführen
  - Spiel-Statistiken verwalten
  - Spieler-Daten für Scoring einsehen
- **Dashboard**: Basic Dashboard
- **Farbe**: Gelb

### 🟡 Referee (`referee`)
- **Schiedsrichter-Rolle** für Spielleitung
- **Berechtigungen**: 6 spezifische Permissions
- **Scope**: Spiele und grundlegende Spieler-Info
- **Hauptfunktionen**:
  - Spiele leiten und bewerten
  - Spieler-Informationen einsehen
  - Basis-Statistiken verwalten
- **Dashboard**: Basic Dashboard
- **Farbe**: Gelb

### 🟠 Player (`player`)
- **Spieler-Rolle** mit Fokus auf eigene Daten und Team-Info
- **Berechtigungen**: 12 spezifische Permissions
- **Scope**: Eigenes Team und persönliche Daten
- **Hauptfunktionen**:
  - Team-Informationen einsehen
  - Eigene Statistiken verfolgen
  - Spiele und Training einsehen
  - Team-Kommunikation
- **Dashboard**: Spieler-Dashboard
- **Farbe**: Orange

### 🟣 Parent/Guardian (`parent`)
- **Eltern-Rolle** für Minderjährige Spieler
- **Berechtigungen**: 8 spezifische Permissions
- **Scope**: Kinder-relevante Daten
- **Hauptfunktionen**:
  - Kind's Team-Informationen einsehen
  - Kind's Statistiken verfolgen
  - Notfall-Kontakte verwalten
  - Team-Kommunikation
- **Dashboard**: Basic Dashboard
- **Farbe**: Lila

### ⚪ Guest/Fan (`guest`)
- **Minimale Rolle** für öffentliche Informationen
- **Berechtigungen**: 3 spezifische Permissions
- **Scope**: Öffentlich verfügbare Daten
- **Hauptfunktionen**:
  - Teams einsehen
  - Spiele verfolgen
  - Basis-Statistiken einsehen
- **Dashboard**: Basic Dashboard
- **Farbe**: Grau

---

## 🎯 Dashboard-Zuordnung

Das System verwendet **5 verschiedene Dashboard-Komponenten** basierend auf der Benutzerrolle:

| Dashboard-Typ | Verwendete Rollen | Vue-Komponente | Farb-Schema |
|---------------|-------------------|----------------|-------------|
| **System-Administration** | `super_admin`, `admin` | `AdminDashboard.vue` | Rot |
| **Club-Verwaltung** | `club_admin` | `ClubAdminDashboard.vue` | Blau |
| **Trainer-Dashboard** | `trainer`, `assistant_coach` | `TrainerDashboard.vue` | Grün |
| **Spieler-Dashboard** | `player` | `PlayerDashboard.vue` | Orange |
| **Basic Dashboard** | `team_manager`, `scorer`, `referee`, `parent`, `guest` | `BasicDashboard.vue` | Grau |

### Dashboard-Features nach Rolle

#### 🔴 System-Administration Dashboard
- **System-Übersicht**: Benutzer, Clubs, Teams, Spiele
- **Rollen-Verteilung**: Detaillierte User-Statistiken
- **System-Health**: Storage, Cache, Queue Status
- **Aktivitäts-Logs**: Vollständige System-Überwachung
- **Live-Spiele**: Aktuelle Spiele systemweit

#### 🔵 Club-Verwaltung Dashboard
- **Club-Statistiken**: Mitglieder, Teams, Finanzen
- **Team-Übersicht**: Alle Club-Teams mit Details
- **Mitglieder-Aktivität**: Neue Registrierungen
- **Anstehende Spiele**: Club-spezifische Spielpläne
- **Finanzverwaltung**: Budgets und Berichte

#### 🟢 Trainer-Dashboard
- **Team-Statistiken**: Performance-Metriken
- **Kader-Übersicht**: Detaillierte Spieler-Info
- **Trainings-Planung**: Kommende Sessions
- **Spiel-Historie**: Vergangene Ergebnisse
- **Entwicklungs-Tracking**: Spieler-Fortschritt

#### 🟠 Spieler-Dashboard
- **Persönliche Statistiken**: Eigene Performance
- **Team-Roster**: Mitspieler-Übersicht
- **Spiel-Kalender**: Kommende Spiele
- **Trainings-Plan**: Anstehende Sessions
- **Entwicklungs-Ziele**: Persönliche Targets

---

## 🔐 Berechtigungs-System

Das System verwaltet **136 spezifische Berechtigungen** in **14 Hauptkategorien**:

### Kategorien-Übersicht

| Kategorie | Anzahl Permissions | Beschreibung |
|-----------|-------------------|--------------|
| **User Management** | 6 | Benutzerverwaltung und Rollen |
| **Club Management** | 7 | Vereinsverwaltung und -settings |
| **Team Management** | 8 | Team-Organisation und -verwaltung |
| **Player Management** | 9 | Spielerverwaltung und -profile |
| **Game Management** | 8 | Spielverwaltung und Live-Scoring |
| **Statistics & Analytics** | 5 | Statistiken und Berichte |
| **Training Management** | 6 | Trainings-Planung und -durchführung |
| **Emergency Contacts** | 4 | Notfall-System Integration |
| **Communication** | 3 | Messaging und Announcements |
| **System Administration** | 6 | System-Settings und Maintenance |
| **Media Management** | 3 | Medien-Bibliothek Verwaltung |
| **Tournament Management** | 5 | Turnier-Organisation |
| **Financial Management** | 3 | Finanz-Verwaltung |
| **GDPR & Compliance** | 3 | Datenschutz und Compliance |

---

## 🧪 Testbenutzer-Übersicht

Das System stellt vorgefertigte Testbenutzer für alle Hauptrollen bereit:

### Verfügbare Testaccounts

| Rolle | E-Mail | Passwort | Name | Sprache |
|-------|--------|----------|------|---------|
| **Admin** | `admin@basketmanager.test` | `password` | Test Admin | Deutsch |
| **Club Admin** | `clubadmin@basketmanager.test` | `password` | Test Club Admin | Deutsch |
| **Trainer** | `trainer@basketmanager.test` | `password` | Test Trainer | Deutsch |
| **Spieler** | `player@basketmanager.test` | `password` | Test Player | Deutsch |

### Testbenutzer erstellen
```bash
# Rollen und Berechtigungen seeden
php artisan db:seed --class=RoleAndPermissionSeeder

# Oder manuell in Tinker:
php artisan tinker
```

---

## 🔒 Policy-Logik und Autorisierung

Das System implementiert umfassende Policy-Klassen für granulare Zugriffskontrolle:

### Implementierte Policies

| Policy | Zweck | Hauptfunktionen |
|--------|-------|-----------------|
| **UserPolicy** | Benutzerzugriff | view, update, delete, impersonate, manageRoles |
| **ClubPolicy** | Club-Zugriff | view, update, manageMembers, manageSettings |
| **TeamPolicy** | Team-Zugriff | view, update, manageRoster, assignCoaches |
| **PlayerPolicy** | Spieler-Zugriff | view, update, viewMedical, editMedical |
| **GamePolicy** | Spiel-Zugriff | view, update, score, manageOfficials |

### Autorisierungs-Prinzipien

1. **Hierarchische Berechtigung**: Höhere Rollen erben niedrigere Berechtigungen
2. **Scope-basierte Zugriffe**: Jede Rolle hat definierten Datenzugriff-Bereich
3. **Selbst-Management**: Benutzer können eigene Daten immer bearbeiten
4. **GDPR-Compliance**: Strenge Regelung für sensible Daten
5. **Team-/Club-Kontext**: Zugriffe sind an Mitgliedschaften gebunden

### Besondere Sicherheitsregeln

- **Super Admins** können nicht impersonated oder gelöscht werden
- **Benutzer können sich nicht selbst löschen** oder Rollen zuweisen
- **Club Admins** haben nur Zugriff auf eigene Club-Daten
- **Trainer** können nur zugewiesene Teams verwalten
- **Medizinische Daten** haben extra Schutzebenen

---

## 🛠️ Technische Implementation

### Laravel Spatie Permission Package
- **Models**: `Role`, `Permission`, `User` (mit `HasRoles` Trait)
- **Middleware**: Automatische Gate-Registration
- **Caching**: 24-Stunden Permission-Cache
- **Database Tables**: `roles`, `permissions`, `model_has_roles`, etc.

### Rollen-Priorisierung für Dashboard-Routing
```php
$rolePriority = [
    'super-admin', 'admin', 'club-admin', 'trainer', 
    'head-coach', 'assistant-coach', 'player', 'member'
];
```

### Scope-Queries in User Model
- `scopeActive()` - Nur aktive Benutzer
- `scopeCoaches()` - Nur Trainer-Rollen
- `scopePlayers()` - Nur aktive Spieler
- `scopeByLocale()` - Nach Sprache filtern

---

## 📈 Statistiken und Metriken

### Rollen-Verteilung (Design-Annahmen)
- **Spieler**: ~70% aller Benutzer
- **Trainer/Coaches**: ~15% aller Benutzer
- **Club Admins**: ~10% aller Benutzer
- **System Admins**: ~5% aller Benutzer

### Performance-Optimierungen
- **Permission Caching** für 24 Stunden
- **Scope-basierte Queries** zur Datenreduktion
- **Lazy Loading** für Relationship-Daten
- **Database Indexing** auf Role/Permission Joins

---

## 🔄 Weiterentwicklung und Wartung

### Neue Rollen hinzufügen
1. **RoleAndPermissionSeeder** erweitern
2. **Dashboard-Routing** in `DashboardController` aktualisieren
3. **Policy-Regeln** für neue Rolle definieren
4. **Vue-Components** für rollenspezifisches UI

### Berechtigungen verwalten
1. **Granulare Permissions** in Seeder hinzufügen
2. **Policy-Methoden** entsprechend erweitern
3. **Frontend-Authorization** in Vue-Components
4. **API-Endpoints** mit Middleware schützen

### Monitoring und Logs
- **Activity Logs** für alle kritischen Aktionen
- **Role Changes** werden vollständig protokolliert
- **Permission Violations** werden geloggt
- **Performance Monitoring** für Authorization-Queries

---

## 🎯 Best Practices

### Sicherheit
- **Principle of Least Privilege** - Minimale notwendige Berechtigungen
- **Regular Role Reviews** - Periodische Überprüfung der Rollenzuweisungen
- **Audit Trails** - Vollständige Nachverfolgung von Änderungen
- **Data Isolation** - Strikte Trennung nach Scope

### Performance
- **Permission Caching** nutzen
- **Scope-Queries** für große Datenmengen
- **Lazy Loading** von Relationships
- **Database Optimization** für Role-Queries

### Benutzerfreundlichkeit
- **Klare Rollenbeschreibungen** für Administratoren
- **Intuitive Dashboard-Navigation** pro Rolle
- **Konsistente UI-Patterns** zwischen Rollen
- **Hilfe-Texte** für komplexe Berechtigungen

---

*Letzte Aktualisierung: August 2025*
*BasketManager Pro - Rollen und Berechtigungssystem*