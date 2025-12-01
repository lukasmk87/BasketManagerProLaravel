# Routes Referenz

> Vollständige Liste aller 29 Route-Dateien

## Core Routes

| Datei | Beschreibung |
|-------|--------------|
| `routes/web.php` | Haupt-Web-Routes mit Locale-Präfixen (`/{locale}/...`) |
| `routes/api.php` | Basis-API-Routes und Auth-Endpoints |
| `routes/console.php` | Artisan Console Commands und Scheduled Tasks |
| `routes/channels.php` | Broadcasting Channel Authorization |

---

## API Versioning

| Datei | Beschreibung | Status |
|-------|--------------|--------|
| `routes/api/v1.php` | Legacy API v1 | Deprecated (Backward Compatibility) |
| `routes/api/v2.php` | **Primary API v2** (16KB) | Production API |
| `routes/api/v4.php` | Latest API v4 (17KB) | Neueste Endpoints |
| `routes/api/v4_teams_only.php` | API v4 Teams-Only | Spezialisiert |

---

## Feature-Specific Routes

### Training & Games
| Datei | Beschreibung |
|-------|--------------|
| `routes/api_training.php` | Training und Drill Management API |
| `routes/api_tournament.php` | Tournament Management API |
| `routes/api_game_registrations.php` | Game Registration & Roster Management |

### Registration & Invitations
| Datei | Beschreibung |
|-------|--------------|
| `routes/player_registration.php` | Player Registration mit QR-Codes |
| `routes/club_invitation.php` | Club Invitation System mit QR-Codes |

### Season
| Datei | Beschreibung |
|-------|--------------|
| `routes/season.php` | Season Management API (create, activate, complete) |

### Admin Panels
| Datei | Beschreibung |
|-------|--------------|
| `routes/admin.php` | System Admin Panel (Super Admin only) |
| `routes/club_admin.php` | Club Admin Panel (Members, Teams, Financials, Reports) |

---

## Integration Routes

### Stripe/Payments
| Datei | Beschreibung | Endpoints |
|-------|--------------|-----------|
| `routes/checkout.php` | Tenant-Level Stripe Checkout | Subscription Management |
| `routes/club_checkout.php` | Club-Level Stripe Subscription | 17 Endpoints |
| `routes/webhooks.php` | Stripe Webhook Handlers | 11+ Webhook Events |

### Federation
| Datei | Beschreibung |
|-------|--------------|
| `routes/federation.php` | DBB/FIBA Federation API Routes |

---

## Specialized Routes

| Datei | Beschreibung | Auth |
|-------|--------------|------|
| `routes/emergency.php` | QR-Code Emergency Access | Keine Auth |
| `routes/gdpr.php` | GDPR Compliance (Data Export/Deletion) | Auth |
| `routes/pwa.php` | PWA Manifest und Service Worker (20KB) | Keine Auth |
| `routes/security.php` | Security und 2FA Endpoints | Auth |
| `routes/notifications.php` | Push Notification Endpoints (12KB) | Auth |
| `routes/health.php` | Subscription Health Check API | 11 Endpoints |
| `routes/install.php` | Installation Wizard (7-Step) | Session-basiert |

---

## Route-Registrierung

Routes werden in `bootstrap/app.php` oder `RouteServiceProvider` geladen:

```php
// API Routes mit Prefix
Route::prefix('api/v2')
    ->middleware(['api', 'auth:sanctum'])
    ->group(base_path('routes/api/v2.php'));

// Web Routes mit Locale
Route::prefix('{locale}')
    ->middleware(['web', 'localize'])
    ->group(base_path('routes/web.php'));
```

---

## Wichtige URL-Patterns

```
# Web (mit Locale)
/{locale}/dashboard
/{locale}/teams/{team}
/{locale}/games/{game}

# API v2
/api/v2/clubs/{club}
/api/v2/teams/{team}/players
/api/v2/games/{game}/actions

# Club Checkout
/club/{club}/subscription
/club/{club}/checkout
/club/{club}/billing/invoices

# Emergency (ohne Auth)
/emergency/{qr_code}
```
