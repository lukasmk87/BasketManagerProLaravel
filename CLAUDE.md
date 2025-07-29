# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BasketManager Pro ist eine Laravel-basierte Basketball-Vereinsverwaltungs-Anwendung. Das Projekt befindet sich aktuell in der Planungsphase mit umfassenden PRDs (Product Requirements Documents) im `ToDo/` Verzeichnis.

## Architecture

- **Framework**: Laravel 11.x (geplant)
- **PHP Version**: 8.3+
- **Database**: MySQL 8.0+ / PostgreSQL 14+
- **Cache/Queue**: Redis 7.0+
- **Search**: Laravel Scout mit Meilisearch/Algolia
- **Real-time**: Laravel Broadcasting mit WebSockets
- **Authentication**: Laravel Sanctum + Jetstream

## Key Laravel Packages (Planned)

- `laravel/sanctum` - API Authentication
- `laravel/jetstream` - Authentication scaffolding
- `laravel/horizon` - Queue monitoring
- `laravel/scout` - Full-text search
- `spatie/laravel-permission` - Role-based access control
- `spatie/laravel-activitylog` - Activity logging
- `spatie/laravel-media-library` - Media management

## Development Commands

**Note**: Das Laravel-Projekt ist noch nicht initialisiert. Nach der Laravel-Installation werden folgende Standard-Commands verfügbar sein:

```bash
# Project setup (when Laravel is installed)
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# Development
php artisan serve
php artisan queue:work
php artisan schedule:work

# Code quality
php artisan test
./vendor/bin/phpunit
./vendor/bin/phpstan analyse
./vendor/bin/pint  # Laravel code formatting

# Database
php artisan migrate:fresh --seed
php artisan tinker
```

## Core Domain Models (Planned)

Based on PRDs, the system will include:

- **User Management**: Multi-role system (Admin, Trainer, Spieler, Eltern)
- **Club Management**: Vereine mit Hierarchie und Settings
- **Team Management**: Teams mit Saison-Zuordnung
- **Player Management**: Spieler mit detaillierten Profilen und Statistiken
- **Game Management**: Spiele mit Live-Scoring und Statistiken
- **Training Management**: Trainingseinheiten und Drill-Management
- **Emergency System**: Notfallkontakte-System integration

## Database Design Principles

- Eloquent Models mit expliziten Relationships
- Soft Deletes für wichtige Entitäten
- UUID Primary Keys für öffentliche APIs
- Audit Trail für alle kritischen Änderungen
- Multi-tenancy Support (Club-based)

## API Design

- RESTful API mit Laravel API Resources
- Sanctum Token-based Authentication
- Rate Limiting und Throttling
- Comprehensive API Documentation mit Laravel Scribe
- Real-time Updates via WebSocket Broadcasting

## Development Phases

Das Projekt ist in 5 Phasen unterteilt:

1. **Phase 1**: Core Foundation (Laravel Setup, Auth, Basic Models)
2. **Phase 2**: Game Statistics & Live Scoring
3. **Phase 3**: Advanced Features (Analytics, Media, Training)
4. **Phase 4**: Integration & Scaling (APIs, Mobile, Performance)
5. **Phase 5**: Emergency & Compliance Systems

## File Structure (When Implemented)

```
app/
├── Models/
│   ├── User.php
│   ├── Club.php
│   ├── Team.php
│   ├── Player.php
│   └── Game.php
├── Http/
│   ├── Controllers/Api/
│   ├── Controllers/Web/
│   ├── Resources/
│   └── Requests/
├── Services/
├── Repositories/
└── Observers/
```

## Testing Strategy

- Feature Tests für alle API Endpoints
- Unit Tests für Business Logic
- Browser Tests mit Laravel Dusk
- Real-time Feature Testing
- Performance Testing für Live-Scoring

## Security Considerations

- Laravel Sanctum für API Authentication
- Spatie Permission für RBAC
- 2FA Implementation
- Rate Limiting
- Input Validation mit Form Requests
- SQL Injection Prevention (Eloquent ORM)
- XSS Protection (Blade Templates)

## Internationalization

- Primäre Sprache: Deutsch
- Laravel Localization Support
- Database-stored translations für dynamische Inhalte