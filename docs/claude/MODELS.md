# Models Referenz

> Vollständige Liste aller 78 Models in `app/Models/` (132 Migrations)

## Core Domain Models

### Users & Authentication
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `User` | Multi-Role Users mit Spatie Permission | ✓ |
| `SocialAccount` | OAuth Social Login (Google, Facebook) | - |

### Clubs & Teams
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `Club` | Multi-Tenant Clubs mit Settings und Subscriptions | ✓ |
| `BasketballTeam` | Teams mit Seasonal Rosters | ✓ |
| `Player` | Spieler mit Profilen und Statistiken | ✓ |

### Seasons & Statistics
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `Season` | Season Lifecycle (Name, Start/End, Status) | ✓ |
| `SeasonStatistic` | Season-spezifische Statistik-Aggregation | - |

### Games & Actions
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `Game` | Spiele mit Live Scoring Support | ✓ |
| `GameAction` | Einzelne Game Events (Shots, Fouls, etc.) | - |
| `GameRegistration` | Spieler-Registrierung für Spiele | - |

### Training
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `TrainingSession` | Trainingseinheiten | ✓ |
| `Drill` | Übungen | - |

### Tournaments
| Model | Beschreibung | Soft Delete |
|-------|--------------|-------------|
| `Tournament` | Turnier-Brackets und Standings | ✓ |

---

## Subscription & Analytics Models

| Model | Beschreibung |
|-------|--------------|
| `Subscription` | Tenant/Club Subscriptions (Cashier Integration) |
| `SubscriptionPlan` | Plan-Definitionen (Tenant-Level) |
| `ClubSubscriptionPlan` | Plan-Definitionen (Club-Level) |
| `SubscriptionMRRSnapshot` | Tägliches MRR Tracking für Revenue Analytics |
| `ClubSubscriptionEvent` | Subscription Lifecycle Events (created, upgraded, cancelled) |
| `ClubSubscriptionCohort` | Cohort Analytics für Retention Tracking |
| `ClubUsage` | Club Usage Metriken und Limits |
| `TenantPlanCustomization` | Tenant-spezifische Plan-Anpassungen |

---

## Club Management Models

| Model | Beschreibung |
|-------|--------------|
| `ClubInvitation` | Club-Einladungen mit QR-Codes, Expiry, Usage Limits |
| `ClubTransfer` | Club-Transfers zwischen Tenants (Status Tracking) |
| `ClubTransferLog` | Detailliertes Transfer Audit Log |
| `ClubTransferRollbackData` | Rollback-Daten für fehlgeschlagene Transfers |

---

## Player & Registration Models

| Model | Beschreibung |
|-------|--------------|
| `PlayerRegistrationInvitation` | Player Registration Invitations mit QR-Codes |

---

## CMS & Content Models

| Model | Beschreibung |
|-------|--------------|
| `LandingPageContent` | Landing Page CMS für dynamische Inhalte |
| `LegalPage` | Legal Pages (Impressum, Datenschutz, AGB, Cookie Policy) |

---

## Notification Models

| Model | Beschreibung |
|-------|--------------|
| `NotificationLog` | Notification Audit Log mit Delivery Tracking |
| `NotificationPreference` | User-spezifische Notification-Präferenzen |

---

## Infrastructure Models

| Model | Beschreibung |
|-------|--------------|
| `WebhookEvent` | Webhook Event Log für alle eingehenden Webhooks |
| `ApiUsageTracking` | Tenant API Usage Limits und Rate Limiting |

---

## Specialized Models

### Emergency & GDPR
| Model | Beschreibung |
|-------|--------------|
| `EmergencyContact` | QR-Code enabled Emergency Contacts |
| `GdprDataSubjectRequest` | GDPR Artikel 15/17 Requests |

### Booking & Facilities
| Model | Beschreibung |
|-------|--------------|
| `GymBooking` | Facility Scheduling und Booking |
| `GymHall` | Hallen und Räume |

### Federation Integration
| Model | Beschreibung |
|-------|--------------|
| `DBBIntegration` | DBB (Deutscher Basketball Bund) Data |
| `FIBAIntegration` | FIBA Data Integration |

### Video Analysis
| Model | Beschreibung |
|-------|--------------|
| `VideoFile` | Video Storage |
| `VideoAnalysisSession` | Video Analyse und Annotation |

### Machine Learning
| Model | Beschreibung |
|-------|--------------|
| `MLModel` | Model Registry mit Versioning |
| `MLPrediction` | Prediction Storage und Tracking |
| `MLExperiment` | Experiment Tracking |
| `MLTrainingData` | Training Dataset Management |
| `MLFeatureStore` | Feature Storage für ML Pipelines |

---

## Wichtige Beziehungen

```php
// Club → Teams
$club->teams()  // HasMany

// Team → Players
$team->players()  // BelongsToMany (Pivot: team_player)

// Game → Actions
$game->actions()  // HasMany

// User → Clubs
$user->clubs()  // BelongsToMany

// Club → Subscription
$club->subscription()  // HasOne (via Cashier)
```

---

## Traits in Models

```php
use SoftDeletes;           // Soft Delete Support
use HasFactory;            // Factory Support
use Notifiable;            // Notifications
use HasRoles;              // Spatie Permission (User)
use Billable;              // Laravel Cashier (Club)
use LogsActivity;          // Activity Logging
```

---

## Model-Konventionen

- **Soft Deletes**: Alle kritischen Models
- **UUIDs**: Für öffentliche IDs (optional)
- **Timestamps**: Standard Laravel (created_at, updated_at)
- **Factories**: In `database/factories/`
- **Migrations**: In `database/migrations/` (132 Dateien)
