# 🏀 BasketManager Pro Laravel

**Umfassende Basketball-Vereinsverwaltung mit modernster Laravel-Technologie**

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## 📋 Projektübersicht

BasketManager Pro ist eine vollständig ausgestattete Basketball-Vereinsverwaltungs-Anwendung, die mit Laravel 11+ entwickelt wurde. Das System bietet umfassende Funktionen für Vereinsverwaltung, Teammanagement, Live-Scoring, Spielerstatistiken und Emergency-Services.

### 🎯 Hauptfunktionen

- **🏀 Vereinsverwaltung**: Multi-Tenant-Architektur für mehrere Vereine
- **👥 Teammanagement**: Vollständige Team- und Spielerverwaltung
- **📊 Live-Scoring**: Real-time Spielstatistiken mit WebSocket-Broadcasting
- **📈 Analytics**: Umfassende Statistiken und ML-basierte Vorhersagen
- **🚨 Emergency System**: QR-Code-basiertes Notfallkontaktsystem
- **🔒 GDPR-Compliance**: Vollständige DSGVO-konforme Datenverwaltung
- **💳 Stripe Integration**: Multi-Tenant Subscription Management
- **📱 PWA**: Progressive Web App mit Offline-Funktionalität

---

## 🏗️ Technologie-Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP Version**: 8.3+
- **Datenbank**: MySQL 8.0+ / PostgreSQL 14+
- **Cache/Queue**: Redis 7.0+
- **Authentication**: Laravel Sanctum + Jetstream
- **Permissions**: Spatie Laravel Permission
- **Search**: Laravel Scout

### Frontend
- **Framework**: Vue.js 3.x
- **Build Tool**: Vite
- **CSS Framework**: Tailwind CSS
- **UI Components**: Headless UI
- **State Management**: Inertia.js

### DevOps & Infrastructure
- **Containerization**: Docker
- **CI/CD**: GitHub Actions (bereit)
- **Monitoring**: Laravel Telescope
- **Payment**: Stripe (Laravel Cashier)
- **Real-time**: Laravel Broadcasting + WebSockets

---

## 🚀 Installation

### Voraussetzungen

- PHP 8.3+
- Node.js 18+
- Composer
- MySQL/PostgreSQL
- Redis

### Installation Schritte

1. **Repository klonen**
   ```bash
   git clone <repository-url>
   cd BasketManagerProLaravel
   ```

2. **Dependencies installieren**
   ```bash
   composer install
   npm install
   ```

3. **Environment konfigurieren**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Datenbank einrichten**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Assets kompilieren**
   ```bash
   npm run build
   ```

6. **Laravel starten**
   ```bash
   php artisan serve
   ```

### 🐳 Docker Installation

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## 📚 Projektstruktur

### 🏆 Entwicklungsphasen

Das Projekt wurde in 5 Phasen entwickelt:

#### Phase 1: Core Foundation ✅ **ABGESCHLOSSEN**
- Laravel Foundation mit Authentication
- RBAC-System (10+ Rollen, 80+ Permissions)
- Core Models (User, Club, Team, Player)
- API V2 mit vollständigen CRUD-Operationen
- Rollenbasierte Dashboards

#### Phase 2: Game & Statistics ✅ **IMPLEMENTIERT** 
- Game Management System
- Live-Scoring mit Real-time Updates
- Statistics Engine
- Broadcasting System
- Export-Funktionen (PDF, Excel, CSV)

#### Phase 3: Advanced Features ✅ **IMPLEMENTIERT**
- Training & Drill Management
- Tournament Management
- Video Analysis mit AI-Integration
- ML-basierte Predictive Analytics
- Shot Charts & Heat Maps (vorbereitet)

#### Phase 4: Integration & Scaling ✅ **VOLLSTÄNDIG**
- Multi-Tenant Architecture
- Stripe Payment Integration
- Federation APIs (DBB, FIBA)
- Progressive Web App (PWA)
- API Documentation & SDK Generation

#### Phase 5: Emergency & Compliance ✅ **VOLLSTÄNDIG**
- Emergency Contact System mit QR-Codes
- GDPR/DSGVO Compliance Engine
- Security & Audit Framework
- Mobile Emergency Interface

---

## 🔧 Entwicklung

### Verfügbare Commands

```bash
# Development
php artisan serve                    # Laravel Server starten
php artisan queue:work              # Queue Worker starten
php artisan schedule:work           # Scheduler starten
npm run dev                         # Frontend Development

# Testing
php artisan test                    # Tests ausführen
./vendor/bin/phpunit               # PHPUnit Tests
./vendor/bin/phpstan analyse       # Static Analysis

# Code Quality
./vendor/bin/pint                  # Laravel Pint (Code Formatting)

# Datenbank
php artisan migrate:fresh --seed   # Datenbank zurücksetzen
php artisan tinker                 # Laravel Tinker Console

# API Dokumentation
php artisan generate:openapi-docs  # OpenAPI Dokumentation generieren

# Tenant Management
php artisan tenant:setup-rls       # Row Level Security einrichten
php artisan tenant:usage:reset     # Tenant Usage zurücksetzen
```

### 🧪 Testing

Das Projekt verfügt über 37+ Test-Dateien:

```bash
# Alle Tests ausführen
php artisan test

# Spezifische Test-Suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Mit Coverage
php artisan test --coverage
```

---

## 🏀 Basketball-spezifische Features

### Core Basketball Entities
- **Teams**: Vollständige Teamverwaltung mit Hierarchien
- **Players**: Detaillierte Spielerprofile mit Statistiken
- **Games**: Live-Scoring mit Real-time Broadcasting
- **Statistics**: 20+ Basketball-Metriken automatisch berechnet
- **Training**: Drill-Management und Performance-Tracking

### Basketball Rules Integration
- Positionsvalidierung (Point Guard, Shooting Guard, etc.)
- Jersey-Nummer-Validierung
- Spielzeit-Management (4x10 Min, 2x20 Min)
- Basketball-spezifische Statistiken (FG%, 3P%, FT%, etc.)

---

## 🔐 Sicherheit & Compliance

### GDPR/DSGVO Compliance
- **Artikel 15**: Auskunftsrecht (Data Export)
- **Artikel 17**: Recht auf Löschung (Right to be Forgotten)
- **Artikel 20**: Datenportabilität (Machine-readable Export)
- **Artikel 30**: Verzeichnis von Verarbeitungstätigkeiten

### Security Features
- **Multi-Factor Authentication (2FA)**
- **Rate Limiting** mit Enterprise-Features
- **Row Level Security** für Multi-Tenancy
- **Security Event Monitoring**
- **CORS Protection**
- **SQL Injection Prevention**

### Emergency System
- **QR-Code basierter Notfall-Zugriff**
- **Offline-Emergency-Interface (PWA)**
- **Automatische Notfallkontakt-Benachrichtigung**
- **Emergency Incident Tracking**

---

## 🌐 API Dokumentation

### REST API V2
- **Base URL**: `/api/v2`
- **Authentication**: Laravel Sanctum
- **Rate Limiting**: Tenant-basiert
- **OpenAPI 3.0** Dokumentation verfügbar

### Hauptendpunkte
```
GET    /api/v2/users           # User-Management
GET    /api/v2/clubs           # Club-Management  
GET    /api/v2/teams           # Team-Management
GET    /api/v2/players         # Player-Management
POST   /api/v2/games/live      # Live-Scoring
```

### SDK Generation
Automatische SDK-Generierung für:
- **PHP SDK** (`storage/api-docs/sdk-php.stub`)
- **JavaScript SDK** (`storage/api-docs/sdk-javascript.stub`)
- **Python SDK** (`storage/api-docs/sdk-python.stub`)

---

## 💳 Subscription & Billing

### Stripe Integration
- **Multi-Tenant Subscription Management**
- **Deutsche Zahlungsmethoden** (SEPA, Sofort)
- **Automated Invoice Generation**
- **Subscription Tiers**: Free → Basic → Professional → Enterprise

### Feature Gates
```php
// Tenant-basierte Feature-Kontrolle
if (tenant()->hasFeature('live_scoring')) {
    // Live Scoring Feature verfügbar
}
```

---

## 📱 Progressive Web App (PWA)

### PWA Features
- **Offline-Funktionalität** für Emergency-Access
- **Push Notifications** für Live-Game Updates
- **Install-Prompts** für mobile Geräte
- **Service Worker** für Caching-Strategien

### Emergency Offline Mode
- **Offline Emergency Contact Access**
- **QR-Code Scanning ohne Internet**
- **Local Storage für kritische Daten**

---

## 🤝 Beitragen

### Development Workflow
1. Fork das Repository
2. Feature Branch erstellen (`git checkout -b feature/amazing-feature`)
3. Commits mit aussagekräftigen Nachrichten
4. Tests schreiben und sicherstellen, dass sie bestehen
5. Pull Request erstellen

### Code Style
- **Laravel Pint** für Code Formatting
- **PHPStan** für Static Analysis
- **Vue 3 Composition API** für Frontend
- **Deutsche Kommentare** in Business Logic

---

## 📈 Performance & Monitoring

### Database Optimization
- **Row Level Security** für Multi-Tenancy
- **Database Performance Monitoring**
- **Memory Optimization Service**
- **Query Optimization**

### Real-time Features
- **WebSocket Broadcasting** für Live-Scoring
- **Redis für Caching** und Session Management
- **Queue System** für aufwändige Operationen

---

## 🚨 Emergency Features

### QR-Code Emergency System
```
https://basketmanager.pro/emergency/{qr_code}
```

### Emergency Access
- **Offline-fähige Emergency-Contacts**
- **QR-Code basierter Zugriff ohne Login**
- **Automatic Emergency Incident Logging**
- **Multi-Language Emergency Interface**

---

## 📞 Support & Kontakt

### Dokumentation
- **API Docs**: `/api/documentation`
- **OpenAPI Spec**: `/storage/api-docs/openapi.json`
- **Admin Dashboard**: `/admin/dashboard`

### Support
- **GitHub Issues** für Bug Reports
- **Wiki** für detaillierte Dokumentation
- **Discussion Board** für Feature Requests

---

## 📄 Lizenz

Dieses Projekt ist unter der **MIT License** lizenziert. Siehe [LICENSE](LICENSE) für Details.

---

## 🎯 Roadmap

### Kurzfristig (Q1 2025)
- [ ] Shot Charts & Heat Maps UI
- [ ] Mobile App (React Native) Entwicklung
- [ ] Advanced ML Model Training
- [ ] Performance Optimierung

### Mittelfristig (Q2-Q3 2025)
- [ ] Federation API Integration (DBB, FIBA)
- [ ] Video Analysis UI Vervollständigung
- [ ] Advanced Tournament Brackets
- [ ] Social Features Integration

### Langfristig (Q4 2025+)
- [ ] Multi-Language Expansion
- [ ] International Federation Support
- [ ] Advanced AI Coaching Features
- [ ] VR/AR Integration Vorbereitung

---

**🏀 BasketManager Pro Laravel - Die Zukunft der Basketball-Vereinsverwaltung**

*Entwickelt mit ❤️ für die Basketball-Community*