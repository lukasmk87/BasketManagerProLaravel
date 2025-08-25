# Fix für "Page not Found" bei /en/login

## Problem
Die URL `https://staging.basketmanager-pro.de/en/login` zeigt einen "Page not Found" Fehler, obwohl `/login` funktioniert.

## Ursache
Die Jetstream-Authentifizierungs-Routen wurden nur einmal global registriert, nicht für jede Locale. Das bedeutete:
- `/login` existiert ✅
- `/en/login` existiert nicht ❌

## Lösung
**Datei:** `routes/web.php` - Zeile 276-279

Die Jetstream-Routes werden jetzt für alle non-default Locales (z.B. 'en') separat geladen:

```php
// Include Jetstream Routes for this locale
if (file_exists(base_path('vendor/laravel/jetstream/routes/inertia.php'))) {
    require base_path('vendor/laravel/jetstream/routes/inertia.php');
}
```

## Nach dem Fix verfügbare Auth-Routes:
- `/login` (Deutsch - Standard)
- `/register` (Deutsch - Standard)  
- `/en/login` (Englisch)
- `/en/register` (Englisch)
- usw. für alle konfigurierten Locales

## Deployment
1. Code auf Staging-Server hochladen
2. `./deploy-staging.sh` ausführen
3. Routes prüfen mit: `php artisan route:list | grep -E "(login|register)"`

## Getestet mit:
- Laravel 11.x
- Jetstream + Inertia
- Multi-Locale Setup (de/en)

## Datum: 2025-08-25