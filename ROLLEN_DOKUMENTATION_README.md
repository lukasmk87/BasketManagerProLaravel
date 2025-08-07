# 📚 Rollen-Verteilung Dokumentation - README

## 🎯 Übersicht

Diese Dokumentations-Suite bietet eine vollständige Analyse und Visualisierung des Rollen- und Berechtigungssystems im **BasketManager Pro Laravel-System**.

---

## 📄 Verfügbare Dokumente

### 1. 📊 [ROLLEN_VERTEILUNG.md](./ROLLEN_VERTEILUNG.md)
**Hauptdokumentation** mit vollständiger Übersicht der Rollen-Architektur.

**Inhalt:**
- **11 System-Rollen** mit detaillierter Beschreibung
- **Dashboard-Zuordnung** für rollenbasiertes UI
- **Policy-Logik** und Autorisierungsregeln
- **136 Berechtigungen** in 14 Kategorien
- **Testbenutzer-Accounts** für alle Rollen
- **Best Practices** und Wartungshinweise

**Zielgruppe:** Entwickler, System-Administratoren, Projekt-Manager

---

### 2. 🔐 [BERECHTIGUNGS_MATRIX.md](./BERECHTIGUNGS_MATRIX.md)
**Detaillierte Permissions-Matrix** mit allen Rollen und ihren spezifischen Berechtigungen.

**Inhalt:**
- **Vollständige Matrix** aller 136 Permissions
- **Rollen vs. Berechtigungen** Vergleichstabelle
- **14 Kategorien** (User Management, Club Management, etc.)
- **Statistiken** und Permission-Verteilung
- **Kritische Permissions** und Sicherheitshinweise

**Zielgruppe:** Security-Teams, Compliance-Verantwortliche, Backend-Entwickler

---

### 3. 📈 [ROLLEN_HIERARCHIE_DIAGRAMM.md](./ROLLEN_HIERARCHIE_DIAGRAMM.md)
**Visuelle Darstellungen** mit Mermaid-Diagrammen zur Rollen-Hierarchie.

**Inhalt:**
- **Hierarchie-Diagramm** mit allen 11 Rollen
- **Organisationsstruktur** nach System-Ebenen
- **Dashboard-Zuordnung** Visualisierung
- **Berechtigungs-Pyramide** mit Permission-Levels
- **Datenzugriff-Scope** Diagramm
- **Permission-Vererbung** Flowchart

**Zielgruppe:** Alle Stakeholder, Präsentationen, Dokumentation

---

## 🏆 System-Rollen im Überblick

| Rolle | Code | Permissions | Dashboard | Hauptzweck |
|-------|------|-------------|-----------|------------|
| 🔴 **Super Admin** | `super_admin` | 136 (100%) | System-Administration | System-kritische Operationen |
| 🔴 **Admin** | `admin` | 135 (99.3%) | System-Administration | Systemweite Verwaltung |
| 🔵 **Club Admin** | `club_admin` | 65 (47.8%) | Club-Verwaltung | Club-spezifische Verwaltung |
| 🟢 **Trainer** | `trainer` | 45 (33.1%) | Trainer-Dashboard | Team- und Spielerverwaltung |
| 🟢 **Assistant Coach** | `assistant_coach` | 25 (18.4%) | Trainer-Dashboard | Unterstützende Trainer-Rolle |
| 🟡 **Team Manager** | `team_manager` | 20 (14.7%) | Basic Dashboard | Team-Organisation |
| 🟡 **Scorer** | `scorer` | 8 (5.9%) | Basic Dashboard | Spiel-Bewertung und Statistiken |
| 🟡 **Referee** | `referee` | 6 (4.4%) | Basic Dashboard | Schiedsrichter-Funktionen |
| 🟠 **Player** | `player` | 12 (8.8%) | Spieler-Dashboard | Spieler-spezifische Ansichten |
| 🟣 **Parent** | `parent` | 8 (5.9%) | Basic Dashboard | Eltern-Zugang für Minderjährige |
| ⚪ **Guest** | `guest` | 3 (2.2%) | Basic Dashboard | Öffentliche Informationen |

---

## 🔧 Schnellstart für Entwickler

### 1. Testbenutzer verwenden
```bash
# E-Mail: admin@basketmanager.test, Passwort: password
# E-Mail: clubadmin@basketmanager.test, Passwort: password
# E-Mail: trainer@basketmanager.test, Passwort: password
# E-Mail: player@basketmanager.test, Passwort: password
```

### 2. Rollen und Permissions seeden
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

### 3. Benutzer-Rolle prüfen
```php
// In Controller/Policy
$user->hasRole('admin');
$user->can('view clubs');
$user->hasAnyRole(['trainer', 'club_admin']);
```

### 4. Dashboard-Routing verstehen
```php
// DashboardController.php - Zeile 42-48
$primaryRole = $this->getPrimaryRole($user);
$dashboardData = match ($primaryRole) {
    'admin', 'super-admin' => $this->getAdminDashboard($user),
    'club-admin' => $this->getClubAdminDashboard($user),
    // ...
};
```

---

## 🎨 Visualisierungen nutzen

### Mermaid-Diagramme anzeigen
1. **GitHub/GitLab**: Automatisches Rendering in Markdown
2. **VS Code**: Mermaid-Extension installieren
3. **Online**: [mermaid.live](https://mermaid.live/) verwenden

### Beispiel-Code aus Diagramm-Datei kopieren:
```mermaid
graph TD
    SA[🔴 Super Admin<br/>super_admin<br/>136 Permissions]
    SA --> A[🔴 System Administrator<br/>admin<br/>135 Permissions]
    A --> CA[🔵 Club Administrator<br/>club_admin<br/>65 Permissions]
    // ... weitere Hierarchie
```

---

## 🔒 Sicherheits-Highlights

### Kritische Permissions (nur Admin-Rollen)
- `impersonate users` - Nur Super Admin
- `delete users` - Admin + Super Admin
- `manage system settings` - Admin + Super Admin
- `handle data deletion requests` - GDPR-Compliance

### Scope-Beschränkungen
- **Club Admin**: Nur eigene Club-Daten
- **Trainer**: Nur zugewiesene Teams  
- **Player**: Nur eigene Daten und Team-Kontext
- **Parent**: Nur Daten der eigenen Kinder

### GDPR & Compliance
- Strikte Datenzonierung nach Rollen
- Audit-Trail für alle kritischen Aktionen
- Consent-Management für Minderjährige
- Datenexport und -löschung nur für berechtigte Rollen

---

## 📊 Key Statistics

- **11 System-Rollen** mit hierarchischer Struktur
- **136 spezifische Permissions** in 14 Kategorien
- **5 Dashboard-Varianten** für rollenbasierte UI
- **Policy-basierte Autorisierung** mit granularer Kontrolle
- **Multi-Tenant-Architektur** mit Club-Isolation
- **GDPR-konforme** Datenschutz-Implementation

---

## 🚀 Weiterentwicklung

### Neue Rolle hinzufügen
1. `RoleAndPermissionSeeder.php` erweitern
2. Dashboard-Routing in `DashboardController.php` anpassen
3. Policy-Regeln definieren
4. Vue-Components für UI erstellen

### Permission-Management
1. Granulare Permissions in Seeder hinzufügen
2. Policy-Methoden erweitern
3. Frontend-Authorization implementieren
4. API-Middleware konfigurieren

---

## 📞 Support und Wartung

### Monitoring
- Activity Logs für alle kritischen Aktionen
- Permission-Violations werden protokolliert
- Role-Changes mit vollständiger Nachverfolgung

### Performance-Optimierung
- 24h Permission-Caching aktiviert
- Scope-basierte Database-Queries
- Lazy Loading für Role-Relationships

---

## 📚 Zusätzliche Ressourcen

### Laravel Spatie Permission
- [Offizielle Dokumentation](https://spatie.be/docs/laravel-permission)
- [GitHub Repository](https://github.com/spatie/laravel-permission)

### Basketball-Domain-Wissen
- Siehe `ToDo/` Verzeichnis für umfassende PRDs
- `CLAUDE.md` für Projekt-spezifische Guidelines

### Testing
- `TEST_USERS.md` für vorgefertigte Testaccounts
- `BasketballTestCase.php` für Role-basierte Tests

---

**🏀 BasketManager Pro - Vollständige Rollen-Dokumentation**  
*Erstellt: August 2025 | Laravel 11.x | Spatie Permission Package*

---

## 🏷️ Tags & Keywords

`Laravel` `Spatie Permission` `Role-based Access Control` `RBAC` `Basketball Management` `User Roles` `Permissions Matrix` `Dashboard Routing` `Policy Authorization` `Multi-tenant` `GDPR Compliance` `Mermaid Diagrams` `Basketball Domain` `Club Management` `Team Management`