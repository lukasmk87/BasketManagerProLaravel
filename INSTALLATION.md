# BasketManager Pro - Installation Guide

🏀 **Willkommen zu BasketManager Pro!**

Diese Anleitung beschreibt die Installation von BasketManager Pro über den **Web-basierten Installation Wizard**.

---

## 🚀 Quick Start

### Voraussetzungen

Stellen Sie sicher, dass Ihr Server die folgenden Anforderungen erfüllt:

- **PHP** >= 8.2
- **Webserver**: Apache oder Nginx
- **Datenbank**: MySQL 8.0+ oder PostgreSQL 14+
- **PHP Extensions**:
  - BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD, cURL, Zip
- **Composer** (für Dependency Management)
- **Node.js & NPM** (für Frontend Build)

---

## 📦 Installation Schritte

### 1. Projekt herunterladen

```bash
git clone https://github.com/your-repo/basketmanager-pro.git
cd basketmanager-pro
```

### 2. Dependencies installieren

```bash
# PHP Dependencies
composer install

# Node Dependencies
npm install
```

### 3. Frontend Build

```bash
# Production Build
npm run build

# Oder für Development
npm run dev
```

### 4. Installation Wizard starten

Öffnen Sie Ihren Browser und navigieren Sie zu:

```
https://your-domain.com/install
```

**Der Installation Wizard führt Sie durch 7 einfache Schritte:**

---

## 🎯 Installation Wizard - Schritt für Schritt

### Schritt 0: Sprache wählen

Wählen Sie Ihre bevorzugte Sprache für die Installation:
- 🇩🇪 **Deutsch**
- 🇬🇧 **English**

### Schritt 1: Willkommen

Überblick über BasketManager Pro Features:
- ⚡ Live-Spielverfolgung und Statistiken
- 👥 Team- und Spielerverwaltung
- 📊 Trainingsverwaltung
- 🏆 Turnierverwaltung
- 💳 Multi-Tenant Subscription-System
- 🔒 GDPR-konforme Datenverwaltung

### Schritt 2: Server-Anforderungen prüfen

Der Wizard prüft automatisch:
- ✅ PHP Version (>= 8.2)
- ✅ PHP Extensions (12 erforderliche Extensions)
- ✅ PHP Konfiguration (Memory Limit, Upload Size)

**Falls Anforderungen nicht erfüllt sind:**
- Installieren Sie fehlende Extensions
- Passen Sie `php.ini` an (Memory Limit >= 256M, Upload Max >= 20M)
- Kontaktieren Sie Ihren Hosting-Provider

### Schritt 3: Ordner-Berechtigungen prüfen

Der Wizard prüft Schreibrechte für:
- `storage/framework/`
- `storage/logs/`
- `storage/app/`
- `bootstrap/cache/`
- `public/uploads/`

**Berechtigungen korrigieren (falls nötig):**

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public/uploads
chown -R www-data:www-data storage bootstrap/cache public/uploads
```

### Schritt 4: Umgebungskonfiguration

Konfigurieren Sie Ihre Anwendung in **4 Tabs**:

#### Tab 1: Anwendung
- **App Name**: Name Ihrer Installation
- **App URL**: Ihre Domain (z.B. `https://basketmanager.pro`)
- **Environment**: Local / Staging / Production
- **Debug Mode**: Nur für Development aktivieren

#### Tab 2: Datenbank ✅ Test-Funktion verfügbar
- **Datenbank-Typ**: MySQL / PostgreSQL / SQLite
- **Host**: `127.0.0.1` oder Ihr DB-Server
- **Port**: `3306` (MySQL) oder `5432` (PostgreSQL)
- **Datenbankname**: Name Ihrer Datenbank
- **Credentials**: Username & Password

**💡 Tipp:** Nutzen Sie den **"Datenbankverbindung testen"** Button, um Ihre Credentials zu validieren!

#### Tab 3: E-Mail (Optional)
- **Mail Driver**: SMTP / Sendmail / Mailgun / SES / Postmark
- SMTP Konfiguration (Host, Port, Username, Password)
- Absender-Email und Name

**⚠️ Kann später konfiguriert werden**

#### Tab 4: Stripe (Optional) ✅ Test-Funktion verfügbar
- **Publishable Key**: `pk_test_...` oder `pk_live_...`
- **Secret Key**: `sk_test_...` oder `sk_live_...`
- **Webhook Secret**: `whsec_...`

**💡 Tipp:** Nutzen Sie den **"Stripe-Verbindung testen"** Button, um Ihre API Keys zu validieren!

**⚠️ Kann später konfiguriert werden**

### Schritt 5: Datenbank einrichten

Klicken Sie auf **"Migrationen ausführen"** um:
- ✅ Alle Datenbanktabellen zu erstellen (116 Migrationen)
- ✅ Rollen & Berechtigungen zu seeden (11 Rollen, 136 Permissions)
- ✅ Legal Pages zu erstellen (Datenschutz, Impressum, AGB)

**Live-Console zeigt Fortschritt in Echtzeit!**

### Schritt 6: Super Admin erstellen

Erstellen Sie Ihren ersten Administrator-Account:

- **Organisationsname**: Name Ihres Clubs/Ihrer Organisation
- **Admin-Name**: Ihr vollständiger Name
- **Admin-E-Mail**: Ihre E-Mail-Adresse
- **Passwort**: Sicheres Passwort (Strength Meter zeigt Sicherheit)
- **Subscription-Tier**: Wählen Sie Ihren Plan:
  - 🆓 **Free**: 10 Users, 5 Teams, 5GB
  - 💼 **Basic**: 50 Users, 20 Teams, 50GB (€29/mo)
  - 🚀 **Professional**: 200 Users, 50 Teams, 200GB (€99/mo)
  - 🏢 **Enterprise**: Unlimited (Custom Pricing)

### Schritt 7: Installation abgeschlossen! 🎉

**Ihre Zugangsdaten werden angezeigt - SPEICHERN SIE DIESE!**

**Wichtige Hinweise:**
- ✅ Ändern Sie Ihr Passwort nach dem ersten Login
- ✅ Speichern Sie Ihre Zugangsdaten sicher
- ✅ Erstellen Sie regelmäßige Backups

**Nächste Schritte:**
1. 👥 Erstes Team erstellen
2. 🏀 Spieler hinzufügen
3. ⚙️ System konfigurieren

---

## 🔒 Sicherheit nach Installation

### Installation Lock (Triple Security)

Nach erfolgreicher Installation wird der Wizard automatisch gesperrt durch:
1. ✅ `storage/installed` Marker-Datei
2. ✅ `APP_INSTALLED=true` in `.env`
3. ✅ Middleware blockiert `/install` Routes

### Neuinstallation (nur Development!)

Falls Sie neu installieren möchten:

```bash
# Unlock Installation
php artisan install:unlock --force

# Optional: Datenbank zurücksetzen
php artisan migrate:fresh
php artisan db:seed
```

**⚠️ WARNUNG: Dies löscht KEINE Daten! Nur Installation-Lock wird entfernt.**

---

## 🐛 Troubleshooting

### Problem: "500 Internal Server Error" nach Installation

**Lösung:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Problem: "Permission denied" Fehler

**Lösung:**
```bash
sudo chmod -R 755 storage bootstrap/cache public/uploads
sudo chown -R www-data:www-data storage bootstrap/cache public/uploads
```

### Problem: "APP_KEY not set"

**Lösung:**
```bash
php artisan key:generate
```

### Problem: Datenbank-Verbindungsfehler

**Lösung:**
1. Prüfen Sie DB-Credentials in `.env`
2. Stellen Sie sicher, dass die Datenbank existiert
3. Prüfen Sie, ob MySQL/PostgreSQL läuft
4. Nutzen Sie den Test-Button im Wizard

### Problem: Stripe-Verbindung fehlgeschlagen

**Lösung:**
1. Prüfen Sie, dass Test Keys (pk_test_, sk_test_) oder Live Keys (pk_live_, sk_live_) korrekt verwendet werden
2. Mischen Sie nicht Test und Live Keys
3. Nutzen Sie den Test-Button im Wizard

---

## 🔧 Manuelle Installation (Alternative)

Falls Sie den Web-Wizard nicht nutzen möchten:

```bash
# 1. .env konfigurieren
cp .env.example .env
php artisan key:generate

# 2. .env editieren (DB, Mail, Stripe)
nano .env

# 3. Migrationen ausführen
php artisan migrate --force

# 4. Seeders ausführen
php artisan db:seed --class=RoleAndPermissionSeeder --force
php artisan db:seed --class=LegalPagesSeeder --force

# 5. Super Admin manuell erstellen (via tinker)
php artisan tinker
>>> $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')]);
>>> $user->assignRole('super_admin');

# 6. Installation als abgeschlossen markieren
echo "$(date)" > storage/installed
```

---

## 📚 Weitere Dokumentation

- **Architecture Guide**: `docs/INSTALLATION_WIZARD_ARCHITECTURE.md`
- **API Reference**: `docs/SUBSCRIPTION_API_REFERENCE.md`
- **Deployment Guide**: `docs/SUBSCRIPTION_DEPLOYMENT_GUIDE.md`
- **Testing Guide**: `docs/SUBSCRIPTION_TESTING.md`
- **Permission Matrix**: `BERECHTIGUNGS_MATRIX.md`
- **Role Documentation**: `ROLLEN_DOKUMENTATION_README.md`

---

## 💬 Support

Bei Problemen oder Fragen:

- **GitHub Issues**: https://github.com/your-repo/basketmanager-pro/issues
- **Email**: support@basketmanager.pro
- **Documentation**: https://docs.basketmanager.pro

---

**🎉 Viel Erfolg mit BasketManager Pro!**
