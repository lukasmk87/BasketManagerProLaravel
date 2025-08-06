# BasketManager Pro - Staging Deployment Fix

## Problem
Dashboard auf https://staging.basketmanager-pro.de/dashboard zeigt Fehler:
```
Error: Class name must be a valid object or a string
```

## Ursache
1. **Route-Cache Problem**: Veraltete Route-Caches auf Staging-Server
2. **Composer Autoloader**: Nicht optimiert für Production 
3. **Telescope Konflikt**: Laravel Telescope versucht in fehlende DB-Tabellen zu schreiben

## Lösung

### Option 1: Automatisches Deployment-Script ausführen

Auf dem Staging-Server:
```bash
cd /path/to/basketmanager-pro-laravel
./deploy-staging.sh
```

### Option 2: Manuelle Commands

Falls das Script nicht funktioniert, führen Sie diese Commands manuell aus:

#### 1. Cache Management
```bash
# Alle Laravel-Caches löschen
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
```

#### 2. Composer Optimierung
```bash
# Autoloader für Production optimieren
composer dump-autoload --optimize --classmap-authoritative
```

#### 3. Production-Optimierung
```bash
# Caches für Production neu erstellen
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

#### 4. Environment-Variablen prüfen
Stellen Sie sicher, dass auf dem Staging-Server folgende Environment-Variablen gesetzt sind:

```bash
APP_ENV=staging
APP_DEBUG=false
TELESCOPE_ENABLED=false
```

#### 5. Migrations (falls nötig)
```bash
php artisan migrate --force
```

## Erwartetes Ergebnis

Nach der Ausführung sollte das Dashboard ordnungsgemäß laden und rollenbasierte Inhalte anzeigen.

## Telescope-Konfiguration

Telescope wurde so konfiguriert, dass es:
- Nur in `local` und `development` Umgebungen aktiviert wird
- In Production/Staging automatisch deaktiviert ist
- Keine DB-Fehler mehr verursacht

## Zukünftige Deployments

Für zukünftige Deployments auf Staging:
1. Code auf Server hochladen/pullen
2. `./deploy-staging.sh` ausführen
3. Dashboard testen

## Troubleshooting

Falls Probleme weiterhin bestehen:

1. **Laravel Logs prüfen**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Route-Status überprüfen**:
   ```bash
   php artisan route:list | grep dashboard
   ```

3. **Composer-Dependencies prüfen**:
   ```bash
   composer show laravel/telescope
   ```

4. **Environment prüfen**:
   ```bash
   php artisan env
   php artisan about
   ```

## Kontakt

Bei weiteren Problemen das Development-Team kontaktieren.