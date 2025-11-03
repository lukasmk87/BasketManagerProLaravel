# Testing Guide - BasketManager Pro

Dieses Projekt verwendet zwei PHPUnit-Konfigurationen für unterschiedliche Testing-Szenarien.

## 📋 Test-Konfigurationen

### 1. SQLite (Standard - Lokale Entwicklung)
**Datei:** `phpunit.xml`
**Verwendung:** Schnelle Unit- und Feature-Tests während der Entwicklung

```bash
# Standard-Tests ausführen (SQLite)
composer test

# Oder direkt mit PHPUnit
php artisan test

# Spezifische Test-Suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Einzelner Test
php artisan test --filter=PlayerRegistrationServiceTest
```

**Vorteile:**
- ⚡ Sehr schnell (In-Memory-Datenbank)
- 🔄 Automatisches Setup/Teardown
- 💻 Keine externe Datenbank erforderlich

**Einschränkungen:**
- ⚠️ Keine Database Views
- ⚠️ Keine Stored Procedures
- ⚠️ Limitiertes Foreign Key Handling
- ⚠️ Einige Migrations werden übersprungen (siehe unten)

### 2. MySQL (CI/CD & Integration Testing)
**Datei:** `phpunit.mysql.xml`
**Verwendung:** Vollständige Integration Tests mit Production-ähnlicher Umgebung

```bash
# MySQL-Tests ausführen
php artisan test --configuration phpunit.mysql.xml

# Mit Composer (erstellen Sie einen Alias in composer.json)
composer test:mysql
```

**Vorteile:**
- ✅ 100% Production Parity
- ✅ Alle MySQL-Features (Views, Stored Procedures)
- ✅ Realistische Performance-Messungen
- ✅ Für CI/CD Pipelines geeignet

**Voraussetzungen:**
- MySQL 8.0+ Server läuft
- Test-Datenbank muss erstellt werden (siehe Setup unten)

## 🛠️ MySQL Test-Datenbank Setup

### Lokales Setup

```bash
# 1. MySQL starten (falls noch nicht läuft)
sudo systemctl start mysql
# Oder bei Docker:
docker-compose up -d mysql

# 2. Test-Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS basketmanager_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Benutzer-Rechte setzen (optional)
mysql -u root -p -e "GRANT ALL PRIVILEGES ON basketmanager_test.* TO 'root'@'localhost';"

# 4. Tests ausführen
php artisan test --configuration phpunit.mysql.xml
```

### GitHub Actions / CI/CD

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: basketmanager_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Tests (MySQL)
        run: php artisan test --configuration phpunit.mysql.xml
        env:
          DB_PASSWORD: password
```

## 📝 Test-Struktur

### Test-Typen

1. **Unit Tests** (`tests/Unit/`)
   - Services (z.B. `PlayerRegistrationServiceTest.php`)
   - Policies (z.B. `PlayerRegistrationInvitationPolicyTest.php`)
   - Requests (z.B. `SubmitPlayerRegistrationRequestTest.php`)

2. **Feature Tests** (`tests/Feature/`)
   - Controller Integration Tests
   - End-to-End Workflows
   - API Endpoint Tests

### BasketballTestCase

Alle Basketball-spezifischen Tests sollten von `BasketballTestCase` erben:

```php
<?php

namespace Tests\Feature;

use Tests\BasketballTestCase;

class MyBasketballTest extends BasketballTestCase
{
    public function test_something()
    {
        // Verwende vorkonfigurierte Test-Daten
        $this->actingAsClubAdmin();
        $response = $this->get("/teams/{$this->testTeam->id}");
        $response->assertStatus(200);
    }
}
```

**Vorkonfigurierte Test-Entitäten:**
- `$this->adminUser` - System Admin
- `$this->clubAdminUser` - Club Administrator
- `$this->trainerUser` - Trainer/Coach
- `$this->playerUser` - Spieler
- `$this->testClub` - Test Basketball Club
- `$this->testTeam` - Test Team
- `$this->testPlayer` - Test Spieler

## 🔧 SQLite-Kompatibilität

### Angepasste Migrationen

Die folgenden Migrationen wurden für SQLite-Kompatibilität angepasst:

1. **`add_performance_optimizations.php`**
   - Views und Stored Procedures nur für MySQL
   - Indexes werden für beide Datenbanken erstellt

2. **`remove_team_id_from_players_table.php`**
   - Wird komplett übersprungen für SQLite (Foreign Key Drop Limitierung)

3. **`remove_league_division_from_clubs_table.php`**
   - Wird komplett übersprungen für SQLite (Index Drop Limitierung)

4. **`add_deleted_at_to_subscription_plans_table.php`**
   - Prüft ob Spalte existiert vor dem Hinzufügen

### Wenn SQLite-Tests fehlschlagen

Falls Sie auf SQLite-Inkompatibilitäten stoßen:

```bash
# Option 1: Verwenden Sie die MySQL-Konfiguration
php artisan test --configuration phpunit.mysql.xml

# Option 2: Skippen Sie spezifische Tests
php artisan test --exclude-group=mysql-only

# Option 3: Nur Unit-Tests (ohne Datenbank)
php artisan test --testsuite=Unit --filter="Request|Policy"
```

## 🎯 Test-Coverage

```bash
# Coverage Report generieren (benötigt Xdebug)
php artisan test --coverage

# HTML Coverage Report
php artisan test --coverage-html coverage

# Dann öffnen:
open coverage/index.html
```

## 📊 Performance

**Typische Testzeiten:**

| Konfiguration | Test-Suite | Dauer | Tests |
|--------------|-----------|-------|-------|
| SQLite       | Alle      | ~2 Min | 392   |
| SQLite       | Unit      | ~30 Sek | 78   |
| MySQL        | Alle      | ~5 Min | 392   |
| MySQL        | Unit      | ~1 Min | 78   |

## 🆘 Troubleshooting

### "SQLSTATE[HY000]: General error: 1 table X has no column Y"

**Problem:** Factory oder Seeder verwendet eine nicht existierende Spalte.

**Lösung:**
```bash
# 1. Migrationen überprüfen
php artisan migrate:status

# 2. Datenbank frisch aufbauen
php artisan migrate:fresh

# 3. Spezifische Spalte in Migration suchen
grep -r "column_name" database/migrations/
```

### "This database driver does not support dropping foreign keys by name"

**Problem:** SQLite-Limitierung bei Foreign Key Drops.

**Lösung:** Diese Migration ist bereits für SQLite angepasst. Falls weitere auftreten:
- Fügen Sie Driver-Check hinzu: `if (DB::connection()->getDriverName() === 'mysql')`
- Oder verwenden Sie MySQL-Tests

### Composer Scripts

Fügen Sie diese Scripts zu `composer.json` hinzu:

```json
{
    "scripts": {
        "test": [
            "@php artisan config:clear --ansi",
            "@php artisan test"
        ],
        "test:mysql": [
            "@php artisan config:clear --ansi",
            "@php artisan test --configuration phpunit.mysql.xml"
        ],
        "test:unit": [
            "@php artisan test --testsuite=Unit"
        ],
        "test:feature": [
            "@php artisan test --testsuite=Feature"
        ],
        "test:coverage": [
            "@php artisan test --coverage"
        ]
    }
}
```

## 📚 Weitere Ressourcen

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Spatie Permission Testing](https://spatie.be/docs/laravel-permission/v6/basic-usage/testing)
- Projekt-spezifische Dokumentation: `TEST_USERS.md`

## ✅ Best Practices

1. **Verwenden Sie SQLite für schnelle lokale Tests**
   ```bash
   composer test
   ```

2. **Verwenden Sie MySQL vor wichtigen Commits**
   ```bash
   composer test:mysql
   ```

3. **CI/CD sollte immer MySQL verwenden**
   - Sichert Production Parity
   - Testet alle Database Features

4. **Mocken Sie externe Services in Tests**
   - QRCodeService (bereits in PlayerRegistrationServiceTest gemockt)
   - Payment Services (Stripe)
   - External APIs (DBB, FIBA)

5. **Nutzen Sie BasketballTestCase für Basketball-Tests**
   - Spart Setup-Zeit
   - Konsistente Test-Daten
   - Helper-Methoden

6. **Halten Sie Tests schnell**
   - Minimale Datenbank-Seeds
   - Mockery für externe Dependencies
   - Factory States für verschiedene Szenarien

---

## Subscription & Billing Tests

### Environment Setup

Für Subscription-Tests benötigen Sie **Stripe Test Mode Keys** in `.env.testing`:

```env
# Stripe Test Mode Keys
STRIPE_KEY=pk_test_51...
STRIPE_SECRET=sk_test_51...
STRIPE_WEBHOOK_SECRET=whsec_test_...
STRIPE_WEBHOOK_SECRET_CLUB=whsec_test_...  # Optional: Separate webhook for clubs
```

**Wichtig:** Verwenden Sie **niemals** Production Keys (`pk_live_`, `sk_live_`) in Tests!

### Test Categories

Das Projekt verfügt über **40 Subscription-Tests** in 4 Kategorien:

1. **Integration Tests (23 Tests)** - Stripe Webhook-Events
   ```bash
   php artisan test tests/Integration/ClubSubscriptionWebhookTest.php
   ```

2. **E2E Tests (17 Tests)** - Kompletter Checkout-Flow
   ```bash
   php artisan test tests/Feature/ClubCheckoutE2ETest.php
   ```

3. **Unit Tests** - Service & Mail Testing
   ```bash
   php artisan test --filter=ClubSubscription --testsuite=Unit
   ```

4. **Feature Tests** - Lifecycle & Flow Testing
   ```bash
   php artisan test --filter=ClubSubscription --testsuite=Feature
   ```

### Stripe Test Cards

Die folgenden Test-Karten werden in E2E Tests verwendet:

| Kartennummer | Szenario |
|--------------|----------|
| `4242 4242 4242 4242` | ✅ Success |
| `4000 0000 0000 0002` | ❌ Declined |
| `4000 0000 0000 9995` | ❌ Insufficient Funds |
| `4000 0027 6000 3184` | 🔐 3D Secure Required |
| `4000 0082 6000 0000` | 🇩🇪 SEPA Direct Debit (Germany) |

### Webhook Testing

Alle 11 kritischen Webhook-Events sind getestet:
- `checkout.session.completed`
- `customer.subscription.created/updated/deleted`
- `invoice.payment_succeeded/failed`
- `invoice.created/finalized/payment_action_required`
- `payment_method.attached/detached`

### Running Subscription Tests

```bash
# Alle Subscription-Tests
php artisan test --filter=ClubSubscription

# Mit Coverage
php artisan test --filter=ClubSubscription --coverage

# Nur Webhooks
php artisan test tests/Integration/ClubSubscriptionWebhookTest.php

# Nur Checkout
php artisan test tests/Feature/ClubCheckoutE2ETest.php
```

### Detaillierte Dokumentation

Siehe **[Subscription Testing Guide](docs/SUBSCRIPTION_TESTING.md)** für:
- Komplette Test-Übersicht
- Mock Strategies
- Troubleshooting
- Best Practices
- Test Statistics
