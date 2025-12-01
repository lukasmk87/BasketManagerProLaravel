# Middleware Referenz

> Vollständige Liste aller 20 Custom Middleware in `app/Http/Middleware/`

## Multi-Tenancy

| Middleware | Beschreibung |
|------------|--------------|
| `ResolveTenantMiddleware` | Domain/Subdomain/Slug-basierte Tenant-Auflösung |
| `ConfigureTenantStripe` | Dynamische Stripe-Konfiguration pro Tenant |

---

## Security & Access Control

| Middleware | Beschreibung |
|------------|--------------|
| `EnforceFeatureGates` | Subscription Tier-basierter Feature-Zugang |
| `CheckFeatureFlag` | Feature Flag-basierter Route-Schutz (Rollout Strategies) |
| `EnforceClubLimits` | Usage Limit Enforcement (Users, Teams, Storage) |
| `EnterpriseRateLimitMiddleware` | Advanced Tenant-aware Rate Limiting |
| `TenantRateLimitMiddleware` | Tenant-basiertes Rate Limiting |
| `SecurityHeadersMiddleware` | Security Headers (CSP, HSTS, X-Frame-Options) |
| `EnhancedCsrfProtection` | Erweiterter CSRF-Schutz |
| `AdminMiddleware` | Admin-only Access (Super Admin/Admin/Subscription Manager) |

---

## Installation

| Middleware | Beschreibung |
|------------|--------------|
| `InstallationSessionMiddleware` | File-basiertes Session Management während Installation |
| `PreventInstalledAccess` | Verhindert Zugang zum Installation Wizard nach Abschluss |
| `RedirectIfNotInstalled` | Redirect zum Installation Wizard wenn nicht installiert |

---

## Performance

| Middleware | Beschreibung |
|------------|--------------|
| `DatabasePerformanceMiddleware` | Query Performance Monitoring |
| `ApiResponseCompressionMiddleware` | Response Compression |

---

## API

| Middleware | Beschreibung |
|------------|--------------|
| `ApiVersioningMiddleware` | API Version Handling (v1, v2, v4) |
| `ForceJsonResponse` | Erzwingt JSON Responses für API Routes |

---

## Localization

| Middleware | Beschreibung |
|------------|--------------|
| `LocalizationMiddleware` | Sprach-Auflösung und URL-Präfix Handling |

---

## Middleware-Verwendung

### In Routes
```php
// Einzelne Middleware
Route::middleware('auth:sanctum')->group(...);

// Mehrere Middleware
Route::middleware(['auth:sanctum', 'enforce.feature.gates'])->group(...);

// Custom Middleware
Route::middleware('check.feature.flag:advanced_stats')->get(...);
```

### Middleware Aliases (bootstrap/app.php)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tenant' => ResolveTenantMiddleware::class,
        'feature.gate' => EnforceFeatureGates::class,
        'admin' => AdminMiddleware::class,
    ]);
})
```

---

## Wichtige Middleware-Gruppen

### API Routes
```php
['api', 'auth:sanctum', 'tenant']
```

### Web Routes
```php
['web', 'localize', 'tenant']
```

### Admin Routes
```php
['web', 'auth', 'admin']
```

### Installation Routes
```php
['web', 'installation.session']
```
