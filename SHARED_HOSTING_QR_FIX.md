# QR-Code Fix für Shared Hosting Deployment

## Problem
Auf Shared Hosting-Servern ist die Imagick PHP-Extension oft nicht verfügbar, was dazu führt, dass QR-Codes für Player-Einladungen nicht erstellt werden können. Dies resultiert in 403-Fehlern beim Laden der QR-Code-Bilder.

## Lösung
Das System verwendet jetzt standardmäßig SVG-Format für QR-Codes, das ohne Imagick funktioniert und folgende Vorteile bietet:
- ✅ Keine PHP-Extension-Abhängigkeit
- ✅ Skalierbar ohne Qualitätsverlust
- ✅ Kleinere Dateigröße
- ✅ Unterstützung in allen modernen Browsern

## Deployment-Schritte für Staging-Server

### 1. Code deployen
```bash
# Auf dem lokalen System
git add .
git commit -m "Fix: QR-Code Generierung für Shared Hosting (SVG statt PNG)"
git push origin main
```

### 2. Auf dem Server: Code pullen
```bash
cd /pfad/zu/BasketManagerProLaravel
git pull origin main
```

### 3. Dependencies aktualisieren
```bash
composer install --no-dev --optimize-autoloader
```

### 4. Storage-Verzeichnisse prüfen und erstellen
```bash
# Verzeichnisse erstellen falls nicht vorhanden
mkdir -p storage/app/public/qr-codes/player-registrations

# Berechtigungen setzen (wichtig für Shared Hosting!)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod -R 775 storage/app/public/qr-codes
```

### 5. Storage Symlink prüfen/erstellen
```bash
php artisan storage:link
```

Falls der Befehl fehlschlägt (bei manchen Shared Hosting Providern), erstelle den Symlink manuell:
```bash
ln -s /vollständiger/pfad/zu/storage/app/public /vollständiger/pfad/zu/public/storage
```

### 6. QR-Codes für bestehende Invitations regenerieren
```bash
# Nur fehlende QR-Codes regenerieren (empfohlen)
php artisan invitations:regenerate-qr --missing

# Oder alle QR-Codes neu generieren
php artisan invitations:regenerate-qr --all

# Mit spezifischem Format (optional)
php artisan invitations:regenerate-qr --missing --format=svg
```

### 7. Cache leeren
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 8. Assets neu kompilieren (falls nötig)
```bash
npm ci
npm run build
```

## Fehlerbehebung

### Problem: "Permission denied" beim Erstellen von QR-Codes

**Lösung:**
```bash
# Berechtigungen für Storage-Verzeichnis setzen
chmod -R 775 storage/app/public
chmod -R 775 storage/app/public/qr-codes

# Eigentümer auf Webserver-User setzen (falls möglich)
chown -R www-data:www-data storage/app/public
```

### Problem: Symlink kann nicht erstellt werden

**Symptom:** `php artisan storage:link` funktioniert nicht

**Lösung:**
1. Bei manchen Shared Hosting Providern ist `symlink()` deaktiviert
2. Kontaktiere den Hosting-Support oder:
3. Erstelle den Symlink manuell über FTP/cPanel File Manager
4. Alternative: Kopiere die Dateien direkt nach `public/storage` (nicht empfohlen, aber funktioniert)

### Problem: QR-Code wird immer noch nicht angezeigt (403)

**Diagnose:**
```bash
# 1. Prüfe ob Datei existiert
ls -la storage/app/public/qr-codes/player-registrations/

# 2. Prüfe Symlink
ls -la public/storage

# 3. Prüfe Berechtigungen
stat storage/app/public/qr-codes/player-registrations/

# 4. Teste Zugriff direkt
curl https://staging.basketmanager-pro.de/storage/qr-codes/player-registrations/dateiname.svg
```

**Häufige Ursachen:**
- `.htaccess` blockiert Zugriff auf Storage
- Symlink zeigt auf falschen Pfad
- Dateiberechtigungen zu restriktiv (sollte 644 oder 755 sein)

### Problem: "Imagick extension not available" Warnung

**Lösung:**
Das ist normal und wird erwartet auf Shared Hosting. Die Warnung ist informativ, das System verwendet automatisch SVG als Fallback.

Falls du PNG-Support benötigst:
1. Kontaktiere deinen Hosting-Provider
2. Frage nach Installation von `php-imagick` Extension
3. Alternativ: Upgrade auf VPS/Dedicated Server mit Root-Zugriff

## Testing nach Deployment

1. **Neue Invitation erstellen:**
   - Gehe zu `/trainer/player-invitations/create`
   - Erstelle eine neue Einladung
   - Prüfe ob QR-Code korrekt angezeigt wird

2. **Bestehende Invitation prüfen:**
   - Öffne eine bestehende Invitation
   - QR-Code sollte jetzt im SVG-Format sein
   - Download-Buttons testen (PNG, SVG, PDF)

3. **Browser-Konsole prüfen:**
   - Sollte keine 403-Fehler mehr geben
   - Sollte keine Ziggy-Route-Fehler mehr geben

## Vorteile der SVG-Lösung

| Aspekt | PNG (mit Imagick) | SVG (ohne Imagick) |
|--------|-------------------|---------------------|
| Extension benötigt | ✅ Imagick | ❌ Keine |
| Dateigröße | ~15-30 KB | ~5-10 KB |
| Skalierbarkeit | Pixelbasiert | Vektorbasiert (unendlich) |
| Browser-Support | ✅ Alle | ✅ Alle modernen |
| Druckqualität | Gut bei hoher Auflösung | ✅ Perfekt bei jeder Größe |
| Shared Hosting | ⚠️ Oft nicht verfügbar | ✅ Immer verfügbar |

## Monitoring

Nach dem Deployment überwache:

1. **Laravel Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "qr\|player.*registration"
```

2. **Erfolgsmeldungen:** Sollte zeigen "QR code saved successfully"
3. **Fehlermeldungen:** Bei Problemen werden detaillierte Fehler geloggt

## Rollback (falls nötig)

Falls Probleme auftreten:
```bash
# 1. Zum vorherigen Commit zurückkehren
git revert HEAD

# 2. Dependencies neu installieren
composer install --no-dev

# 3. Cache leeren
php artisan config:clear
```

## Support

Bei Problemen:
1. Prüfe `storage/logs/laravel.log`
2. Teste mit: `php artisan invitations:regenerate-qr --missing`
3. Kontaktiere Hosting-Support bei Berechtigungsproblemen
