# 🏀 BasketManager Pro Laravel - Installation Guide

Eine umfassende Anleitung für die Installation und Konfiguration von BasketManager Pro, einer Laravel-basierten Basketball-Vereinsverwaltungs-Anwendung.

## 📋 Inhaltsverzeichnis

1. [System-Anforderungen](#system-anforderungen)
2. [Basis-Installation](#basis-installation)
3. [Datenbank Setup](#datenbank-setup)
4. [Frontend Build](#frontend-build)
5. [Laravel Services Konfiguration](#laravel-services-konfiguration)
6. [Development Tools](#development-tools)
7. [Production Setup](#production-setup)
8. [Basketball-spezifische Features](#basketball-spezifische-features)
9. [Testing Setup](#testing-setup)
10. [Troubleshooting](#troubleshooting)

---

## 🔧 System-Anforderungen

### Minimale Anforderungen

- **PHP:** 8.2 oder höher
- **Node.js:** 18.0 oder höher
- **NPM:** 9.0 oder höher (alternativ Yarn)
- **Composer:** 2.0 oder höher
- **Git:** Für Repository-Management

### Datenbank
- **MySQL:** 8.0+ (empfohlen)
- **PostgreSQL:** 14+ (alternative)
- **SQLite:** Für Development/Testing

### Zusätzliche Services
- **Redis:** 7.0+ (für Cache/Queue/Sessions)
- **Meilisearch/Algolia:** Für Laravel Scout (optional)

### PHP Extensions
```bash
# Erforderliche PHP Extensions prüfen
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|zip|gd|curl|exif)"
```

---

## 🚀 Basis-Installation

### 1. Repository klonen

```bash
# HTTPS
git clone https://github.com/[username]/BasketManagerProLaravel.git
cd BasketManagerProLaravel

# SSH (wenn konfiguriert)
git clone git@github.com:[username]/BasketManagerProLaravel.git
cd BasketManagerProLaravel
```

### 2. Environment Setup

```bash
# .env Datei erstellen
cp .env.example .env

# Laravel App Key generieren (wird automatisch bei composer install gemacht)
php artisan key:generate
```

### 3. Composer Dependencies installieren

```bash
# Production Dependencies
composer install --optimize-autoloader

# Development Dependencies (nur für Development)
composer install
```

### 4. NPM Dependencies installieren

```bash
# Mit NPM
npm install

# Oder mit Yarn
yarn install
```

---

## 🗄️ Datenbank Setup

### 1. Datenbank erstellen

#### MySQL/MariaDB
```sql
CREATE DATABASE basketmanager_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'basketmanager'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON basketmanager_pro.* TO 'basketmanager'@'localhost';
FLUSH PRIVILEGES;
```

#### PostgreSQL
```sql
CREATE DATABASE basketmanager_pro;
CREATE USER basketmanager WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE basketmanager_pro TO basketmanager;
```

### 2. .env Konfiguration

```env
# Basis App Configuration
APP_NAME="BasketManager Pro"
APP_ENV=local  # production für Live-System
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=true  # false für Production
APP_TIMEZONE=Europe/Berlin
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=basketmanager_pro
DB_USERNAME=basketmanager
DB_PASSWORD=secure_password

# Redis Configuration (für Cache/Queue/Sessions)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Configuration
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@basketmanager.local"
MAIL_FROM_NAME="${APP_NAME}"

# Broadcasting (für Real-time Features)
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Laravel Scout (für Search)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=null

# File Storage
FILESYSTEM_DISK=local  # s3 für Production mit AWS

# Basketball-spezifische Settings
BASKETBALL_SEASON_START_MONTH=9
BASKETBALL_DEFAULT_ROSTER_SIZE=15
EMERGENCY_QR_ENCRYPTION_KEY=generate-secure-key-here
```

### 3. Datenbank Migrationen ausführen

```bash
# Datenbank Tabellen erstellen
php artisan migrate

# Mit Seed-Daten (für Development)
php artisan migrate --seed

# Oder separat seeden
php artisan db:seed
```

### 4. Storage Links erstellen

```bash
# Public Storage Link erstellen
php artisan storage:link
```

---

## 🎨 Frontend Build

### 1. Asset Compilation

#### Development
```bash
# Development Build mit Hot Reload
npm run dev

# Einmaliger Development Build
npm run build
```

#### Production
```bash
# Production Build (optimiert/minifiziert)
npm run build
```

### 2. Chart.js Installation (falls nicht automatisch installiert)

```bash
# Chart.js für Basketball-Statistiken
npm install chart.js
```

### 3. Vite Konfiguration prüfen

Die `vite.config.js` sollte bereits korrekt konfiguriert sein:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

---

## ⚙️ Laravel Services Konfiguration

### 1. Laravel Telescope (Development)

```bash
# Telescope installieren (nur Development)
php artisan telescope:install
php artisan migrate
```

### 2. Spatie Packages konfigurieren

```bash
# Spatie Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Spatie Activity Log
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"

# Spatie Media Library
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"

# Migrationen ausführen
php artisan migrate
```

### 3. Laravel Jetstream Setup

```bash
# Jetstream Views publizieren (optional, für Anpassungen)
php artisan vendor:publish --tag=jetstream-views
```

### 4. Queue Workers konfigurieren

#### Development
```bash
# Queue Worker starten
php artisan queue:work

# Mit Supervisor für Production
sudo nano /etc/supervisor/conf.d/basketmanager-queue.conf
```

#### Supervisor Configuration (Production)
```ini
[program:basketmanager-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue.log
stopwaitsecs=3600
```

---

## 🛠️ Development Tools

### 1. Development Server starten

#### Einzelne Commands
```bash
# Laravel Development Server
php artisan serve

# Frontend Build Watch
npm run dev

# Queue Worker
php artisan queue:work

# Log Viewer
php artisan pail
```

#### Alle Services gleichzeitig (empfohlen)
```bash
# Composer Script verwenden (alle Services parallel)
composer run dev

# Manuell mit concurrently
npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
  "php artisan serve" \
  "php artisan queue:listen --tries=1" \
  "php artisan pail --timeout=0" \
  "npm run dev" \
  --names=server,queue,logs,vite
```

### 2. Debug Tools aktivieren

```env
# .env für Development
APP_DEBUG=true
LOG_LEVEL=debug

# Laravel Debugbar aktivieren
DEBUGBAR_ENABLED=true

# Telescope aktivieren
TELESCOPE_ENABLED=true
```

### 3. Testing Commands

```bash
# Tests ausführen
composer run test

# Oder manuell
php artisan test

# Mit Coverage
php artisan test --coverage
```

---

## 🌐 Production Setup

### 1. Environment Optimierung

```env
# .env für Production
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# Cache aktivieren
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting für Production
BROADCAST_CONNECTION=pusher
```

### 2. Laravel Optimierungen

```bash
# Config Cache
php artisan config:cache

# Route Cache
php artisan route:cache

# View Cache
php artisan view:cache

# Event Cache
php artisan event:cache

# Autoloader optimieren
composer install --optimize-autoloader --no-dev

# Assets kompilieren
npm run build
```

### 3. File Permissions

```bash
# Storage und Bootstrap Cache Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Oder für spezifische Setups
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name basketmanager.yourdomain.com;
    root /path/to/basketmanager/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. SSL Setup mit Certbot

```bash
# Certbot installieren
sudo apt install certbot python3-certbot-nginx

# SSL Zertifikat generieren
sudo certbot --nginx -d basketmanager.yourdomain.com
```

---

## 🏀 Basketball-spezifische Features

### 1. Permissions Setup

```bash
# Basketball-spezifische Permissions erstellen
php artisan db:seed --class=BasketballPermissionsSeeder
```

### 2. Default Data Setup

```bash
# Basketball Positionen, Standard-Einstellungen
php artisan db:seed --class=BasketballDefaultDataSeeder
```

### 3. Emergency QR System

```env
# Emergency System Configuration
EMERGENCY_QR_ENCRYPTION_KEY=your-32-character-encryption-key
EMERGENCY_QR_EXPIRY_HOURS=24
EMERGENCY_CONTACT_EMAIL=emergency@yourclub.com
```

### 4. Media Library konfigurieren

```bash
# Basketball-spezifische Media Collections
php artisan basketmanager:setup-media-collections
```

### 5. Activity Logging

```bash
# Activity Log für Basketball-Events konfigurieren
php artisan basketmanager:setup-activity-logging
```

---

## 🧪 Testing Setup

### 1. Test Datenbank

```env
# .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Oder separate Test-Datenbank
DB_CONNECTION=mysql
DB_DATABASE=basketmanager_pro_test
```

### 2. Tests ausführen

```bash
# Alle Tests
php artisan test

# Spezifische Test-Suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Mit Coverage
php artisan test --coverage --min=80

# Parallel Testing
php artisan test --parallel
```

### 3. Basketball-spezifische Tests

```bash
# Service Tests
php artisan test tests/Unit/Services/

# Dashboard Tests
php artisan test tests/Feature/DashboardTest.php

# API Tests
php artisan test tests/Feature/Api/
```

---

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. "Key not found" Fehler
```bash
php artisan key:generate
php artisan config:cache
```

#### 2. Storage Permission Fehler
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. NPM Build Fehler
```bash
# Node Modules neu installieren
rm -rf node_modules package-lock.json
npm install
npm run build
```

#### 4. Database Connection Fehler
- .env Datei prüfen
- Datenbank Service läuft: `sudo systemctl status mysql`
- Credentials testen: `mysql -u basketmanager -p basketmanager_pro`

#### 5. Queue Jobs laufen nicht
```bash
# Queue Connection prüfen
php artisan queue:monitor
php artisan queue:restart
```

#### 6. Vite/Assets laden nicht
```bash
# Development
npm run dev

# Production - Assets neu kompilieren
npm run build
php artisan view:clear
```

### Performance Debugging

```bash
# Cache leeren
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Debug Mode aktivieren
APP_DEBUG=true in .env

# Telescope für Performance Monitoring
php artisan telescope:install
```

### Log Dateien prüfen

```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Nginx Logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM Logs
sudo tail -f /var/log/php8.2-fpm.log
```

---

## 📚 Nützliche Commands

### Laravel Artisan

```bash
# Alle verfügbaren Commands anzeigen
php artisan list

# Maintenance Mode
php artisan down --retry=60
php artisan up

# Clear Commands
php artisan optimize:clear  # Alle Caches löschen

# Database
php artisan migrate:status
php artisan db:show

# Basketball-spezifische Commands (falls implementiert)
php artisan basketmanager:create-season
php artisan basketmanager:calculate-statistics
php artisan basketmanager:generate-reports
```

### Composer Scripts

```bash
# Development Server mit allen Services
composer run dev

# Tests ausführen
composer run test

# Production Build
composer run build  # (wenn implementiert)
```

---

## 🔐 Sicherheits-Checkliste

### Development
- [ ] `.env` nie in Git committen
- [ ] Debug Mode nur in Development
- [ ] Telescope nur in Development aktiviert

### Production
- [ ] `APP_DEBUG=false`
- [ ] Sichere Datenbank Credentials
- [ ] SSL/TLS aktiviert
- [ ] File Permissions korrekt gesetzt
- [ ] Queue Worker über Supervisor
- [ ] Regular Backups konfiguriert
- [ ] Log Rotation eingerichtet

---

## 📞 Support & Dokumentation

- **Laravel Dokumentation:** https://laravel.com/docs
- **Vue 3 Dokumentation:** https://vuejs.org/guide/
- **Inertia.js Dokumentation:** https://inertiajs.com/
- **Tailwind CSS Dokumentation:** https://tailwindcss.com/docs

---

## 🔄 Updates & Maintenance

### Regular Updates
```bash
# Composer Dependencies
composer update

# NPM Dependencies
npm update

# Laravel Framework
composer update laravel/framework
```

### Basketball-spezifische Maintenance
```bash
# Statistiken neu berechnen
php artisan basketmanager:recalculate-stats

# Saisonwechsel
php artisan basketmanager:new-season

# Cleanup alte Daten
php artisan basketmanager:cleanup --older-than=2years
```

---

**Erstellt für BasketManager Pro Laravel v1.0**  
*Letzte Aktualisierung: Januar 2025*