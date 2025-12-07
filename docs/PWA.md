# PWA - Progressive Web App

> BasketManager Pro PWA-Dokumentation
> Letzte Aktualisierung: Dezember 2024

---

## Übersicht

BasketManager Pro ist als Progressive Web App (PWA) implementiert und bietet:

- **Installierbarkeit** - App auf dem Home-Bildschirm hinzufügen
- **Offline-Funktionalität** - Arbeiten ohne Internetverbindung
- **Push Notifications** - Echtzeit-Benachrichtigungen
- **Background Sync** - Automatische Datensynchronisation
- **Emergency Access** - Offline-Notfallzugang

---

## Architektur

### Dateien

| Datei | Beschreibung |
|-------|--------------|
| `public/manifest.json` | PWA Manifest |
| `public/sw.js` | Haupt-Service Worker |
| `public/sw-gym.js` | Gym-spezifischer Service Worker |
| `resources/js/emergency-sw.js` | Emergency Service Worker |
| `resources/js/pwa.js` | PWA JavaScript Klasse |
| `resources/js/Composables/usePWA.js` | Vue 3 Composable |
| `app/Services/PWAService.php` | Backend PWA Service |
| `app/Http/Controllers/PWAController.php` | PWA Controller |
| `routes/pwa.php` | PWA Routen |

---

## Installation

### Meta-Tags

Die PWA Meta-Tags befinden sich in `resources/views/app.blade.php`:

```html
<!-- PWA Meta Tags -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2d3748">

<!-- iOS/Apple PWA Support -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="BasketManager">
<link rel="apple-touch-icon" href="/images/logo-192.png">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/images/logo-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/images/logo-16.png">

<!-- Microsoft Tiles -->
<meta name="msapplication-TileColor" content="#2d3748">
<meta name="msapplication-TileImage" content="/images/logo-144.png">
```

### Manifest.json

Das Manifest definiert App-Eigenschaften:

```json
{
  "name": "BasketManager Pro",
  "short_name": "BasketManager",
  "description": "Professional Basketball Club Management System",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#1a202c",
  "theme_color": "#2d3748",
  "orientation": "portrait-primary",
  "lang": "de-DE"
}
```

**Features:**
- 8 Icon-Größen (72px - 512px)
- 4 Screenshots für App Store Präsentation
- 7 App Shortcuts (Long-Press auf Icon)
- Protocol Handler (`web+basketmanager://`)
- Edge Side Panel Support

---

## Artisan Commands

### Icons Generieren

```bash
# Alle PWA-Assets generieren
php artisan pwa:generate-icons

# Existierende überschreiben
php artisan pwa:generate-icons --force
```

**Generierte Dateien:**

| Verzeichnis | Dateien | Beschreibung |
|-------------|---------|--------------|
| `public/images/` | `logo-{16,32,72,96,128,144,152,192,384,512}.png` | App Icons |
| `public/images/shortcuts/` | 7 PNG-Dateien | Shortcut Icons |
| `public/images/screenshots/` | 4 PNG-Dateien | Store Screenshots |
| `public/` | `favicon.ico` | Browser Favicon |
| `public/images/` | `logo.svg` | Master SVG |

---

## Service Worker

### Caching-Strategien

| Ressource | Strategie | Beschreibung |
|-----------|-----------|--------------|
| App Shell | Cache-First | Dashboard, Teams, Players, Games, Training |
| Static Assets | Cache-First | CSS, JS, Fonts |
| API Calls | Network-First | Basketball-Daten mit Cache-Fallback |
| Images | Cache-First | PNG, JPG, SVG, WebP |
| Documents | Network-First | PDF, Excel Dokumente |

### Cache Keys

```javascript
const CACHE_NAME = 'basketmanager-pro-v1.0.0';

// Basketball-spezifische Caches
const BASKETBALL_CACHE_KEYS = {
    GAME_STATS: 'basketball-game-stats',
    PLAYER_PROFILES: 'basketball-players',
    TEAM_ROSTERS: 'basketball-teams',
    TRAINING_DRILLS: 'basketball-training',
    MATCH_SCHEDULES: 'basketball-schedule'
};
```

### Background Sync Tags

```javascript
// Automatische Synchronisation bei Reconnect
'game-stats-sync'      // Spielstatistiken
'player-data-sync'     // Spielerdaten
'training-data-sync'   // Trainingsdaten
```

---

## Offline-Funktionalität

### Offline Page

Route: `/offline`

Zeigt bei fehlender Verbindung:
- Basketball-Animation
- Verfügbare Offline-Funktionen
- Connection Status
- Auto-Reconnect nach 30 Sekunden

### Offline Data Queue

```javascript
// Daten werden in LocalStorage gespeichert
localStorage.setItem('basketmanager_offline_queue', JSON.stringify(queue));

// Sync-Typen
const syncTypes = {
    game_stats: '/api/sync/game-stats',
    player_data: '/api/sync/player-data',
    training_data: '/api/sync/training-data',
    federation_sync: '/federation/sync'
};
```

### Vue Composable

```javascript
import { usePWA } from '@/Composables/usePWA';

const {
    isInstalled,      // Ref<boolean> - App installiert?
    isOnline,         // Ref<boolean> - Online Status
    updateAvailable,  // Ref<boolean> - Update verfügbar?
    installApp,       // () => Promise - App installieren
    updateServiceWorker, // () => Promise - SW aktualisieren
    scheduleBackgroundSync, // (tag) => Promise - Sync planen
    cacheGymData,     // (data) => Promise - Gym-Daten cachen
    clearCache        // () => Promise - Cache leeren
} = usePWA();
```

---

## Push Notifications

### Konfiguration

VAPID Keys in `.env`:

```env
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
```

### Notification Types

```javascript
const notificationTypes = {
    game_start: {
        icon: '/images/notifications/game-start.png',
        vibrate: [200, 100, 200]
    },
    player_foul: {
        icon: '/images/notifications/foul.png'
    },
    training_reminder: {
        icon: '/images/notifications/training.png'
    }
};
```

### Subscribe

```javascript
// Via Vue Composable
const { subscribeToPush } = usePWA();
await subscribeToPush();

// Oder via PWA Klasse
window.basketManagerPWA.subscribeToPushNotifications();
```

---

## Emergency PWA

Spezielle PWA-Funktionen für Notfallzugang ohne Authentifizierung.

### Routen

| Route | Beschreibung |
|-------|--------------|
| `/emergency/pwa/manifest/{accessKey}` | Emergency Manifest |
| `/emergency/pwa/sw/{accessKey}.js` | Emergency Service Worker |
| `/emergency/pwa/install/{accessKey}` | Installation Prompt |
| `/emergency/pwa/offline/{accessKey}` | Offline Interface |
| `/emergency/pwa/cache/{accessKey}` | Daten cachen |

### Features

- **Offline Notfallkontakte** - 24h Cache
- **Incident Reporting** - Auch offline möglich
- **GPS-Koordinaten** - Standort teilen
- **Medizinische Daten** - Cached für Notfall
- **Emergency Numbers** - Immer verfügbar (112, etc.)

### IndexedDB Schema

```javascript
// Emergency Database
const stores = {
    incident_reports: { keyPath: 'id', autoIncrement: true },
    contact_usage: { keyPath: 'id', autoIncrement: true },
    emergency_logs: { keyPath: 'id', autoIncrement: true }
};
```

---

## API Endpoints

### Public (keine Auth)

| Method | Route | Beschreibung |
|--------|-------|--------------|
| GET | `/manifest.json` | PWA Manifest |
| GET | `/sw.js` | Service Worker |
| GET | `/offline` | Offline Page |

### Authenticated

| Method | Route | Beschreibung |
|--------|-------|--------------|
| GET | `/pwa/status` | Installation Status |
| POST | `/pwa/clear-caches` | Caches löschen |
| POST | `/pwa/update-service-worker` | SW aktualisieren |
| POST | `/pwa/queue-offline-data` | Offline Queue |
| POST | `/pwa/subscribe-push` | Push Subscription |

### Sync Endpoints (auth + feature:live_scoring)

| Method | Route | Beschreibung |
|--------|-------|--------------|
| POST | `/api/sync/game-stats` | Spielstatistiken sync |
| POST | `/api/sync/player-data` | Spielerdaten sync |
| POST | `/api/sync/training-data` | Trainingsdaten sync |

---

## Multi-Tenant Support

Der PWAService generiert tenant-spezifische Konfigurationen:

```php
$config = [
    'cache_strategy' => 'balanced',
    'offline_timeout' => 10000,
    'sync_interval' => 300000,
    'max_cache_size' => 50 * 1024 * 1024, // 50MB
    'basketball_features' => [
        'live_scoring',
        'player_tracking',
        'advanced_stats',
        'video_analysis'
    ],
    'federation_sync' => [
        'dbb_enabled' => $tenant->hasFeature('dbb_integration'),
        'fiba_enabled' => $tenant->hasFeature('fiba_integration')
    ]
];
```

---

## Entwicklung

### Service Worker aktualisieren

1. Bearbeite `public/sw.js`
2. Erhöhe die Version in `CACHE_NAME`
3. Browser erkennt Update automatisch

### Icons neu generieren

```bash
# Nach Logo-Änderung
php artisan pwa:generate-icons --force
```

### Debugging

```javascript
// Service Worker Logs in Browser Console
console.log('[SW] Install event');
console.log('[SW] Fetch event:', request.url);

// PWA Status prüfen
console.log(window.basketManagerPWA.isInstalled);
console.log(window.basketManagerPWA.offlineQueue);
```

### Lighthouse Audit

1. Chrome DevTools öffnen
2. Lighthouse Tab
3. "Progressive Web App" Category
4. "Analyze page load"

---

## Checkliste für Production

- [ ] HTTPS aktiviert (Pflicht für PWA)
- [ ] Manifest.json erreichbar
- [ ] Service Worker registriert
- [ ] Icons in allen Größen vorhanden
- [ ] Offline Page funktioniert
- [ ] Push Notifications konfiguriert (VAPID Keys)
- [ ] Background Sync getestet
- [ ] Lighthouse PWA Score > 90

---

## Weiterführende Dokumentation

- [Web.dev PWA](https://web.dev/progressive-web-apps/)
- [MDN Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Workbox (Google)](https://developers.google.com/web/tools/workbox)
