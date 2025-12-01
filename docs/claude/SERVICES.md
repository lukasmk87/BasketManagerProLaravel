# Services Referenz

> Vollständige Liste aller 75 Services in `app/Services/`

## Domain Services

### Core Basketball Entities
| Service | Beschreibung |
|---------|--------------|
| `ClubService` | Club-Management und -Operationen |
| `TeamService` | Team-Verwaltung und Roster-Management |
| `PlayerService` | Spieler-Profile und Statistiken |
| `SeasonService` | Season Lifecycle (Erstellung, Aktivierung, Abschluss) |
| `StatisticsService` | Basketball-Statistiken (FG%, 3P%, FT%, PER, TS%) |

### Live Scoring & Games
| Service | Beschreibung |
|---------|--------------|
| `LiveScoringService` | Echtzeit-Spielstand mit Broadcasting |
| `GameService` | Spiel-Management und Actions |

### Training
| Service | Beschreibung |
|---------|--------------|
| `TrainingService` | Trainingseinheiten und Übungen |
| `DrillService` | Drill-Verwaltung |

### Tournaments
| Service | Beschreibung |
|---------|--------------|
| `TournamentService` | Turnier-Management |
| `TournamentProgressionService` | Turnier-Fortschritt und Brackets |
| `TournamentAnalyticsService` | Turnier-Statistiken |
| `BracketGeneratorService` | Bracket-Generierung |

### Invitations & Registration
| Service | Beschreibung |
|---------|--------------|
| `ClubInvitationService` | Club-Einladungen mit QR-Codes |
| `PlayerRegistrationService` | Spieler-Registrierung mit QR-Codes |
| `ClubTransferService` | Club-Transfer zwischen Tenants mit Rollback |

---

## Infrastructure Services

### Multi-Tenancy
| Service | Beschreibung |
|---------|--------------|
| `TenantService` | Multi-Tenant Scope Management |
| `TenantLimitsService` | Subscription Tier Limits (4 Tiers) |
| `ClubUsageTrackingService` | Club Usage (Teams, Players, Storage) |
| `LimitEnforcementService` | Limit Enforcement für Tenants/Clubs |

### Feature Management
| Service | Beschreibung |
|---------|--------------|
| `FeatureGateService` | Subscription-basierte Feature-Kontrolle |
| `FeatureFlagService` | Feature Flags (Percentage, Whitelist, Gradual Rollout) |

### Security
| Service | Beschreibung |
|---------|--------------|
| `SecurityMonitoringService` | Security Event Tracking |
| `TwoFactorAuthService` | 2FA Implementation |

### Compliance
| Service | Beschreibung |
|---------|--------------|
| `GDPRComplianceService` | GDPR Artikel 15/17/20/30 |
| `EmergencyAccessService` | QR-Code basierter Notfallzugang |

### Subscription Health
| Service | Beschreibung |
|---------|--------------|
| `SubscriptionHealthMonitorService` | Health Monitoring (6 Metriken, Alerts, Scores) |

---

## Integration Services

### Stripe/ (14 Services)
| Service | Beschreibung |
|---------|--------------|
| `CheckoutService` | Allgemeine Zahlungsabwicklung |
| `ClubSubscriptionCheckoutService` | Club-spezifische Checkout Sessions |
| `ClubSubscriptionService` | Club Plan Management (assign, cancel, swap, sync) |
| `ClubStripeCustomerService` | Club Stripe Customer Management |
| `ClubInvoiceService` | Rechnungsverwaltung und PDF-Download |
| `ClubPaymentMethodService` | Payment Methods (Card, SEPA, Sofort, Giropay, EPS, iDEAL) |
| `SubscriptionAnalyticsService` | MRR/ARR Tracking, Churn Analysis, Cohort Analytics |
| `StripeSubscriptionService` | Subscription Management |
| `StripePaymentService` | Payment Handling |
| `PaymentMethodService` | Payment Method Handling |
| `CashierTenantManager` | Multi-Tenant Cashier Integration |
| `StripeClientManager` | Stripe Client Konfiguration |
| `WebhookEventProcessor` | Webhook Event Handling (16+ Events) |
| `ClubSubscriptionNotificationService` | Subscription Notifications |

### Federation/ (2 Services)
| Service | Beschreibung |
|---------|--------------|
| `DBBApiService` | DBB (Deutscher Basketball Bund) API |
| `FIBAApiService` | FIBA API Integration |

### Install/ (5 Services)
| Service | Beschreibung |
|---------|--------------|
| `InstallationService` | Installation Wizard Orchestration |
| `EnvironmentManager` | .env File Management |
| `RequirementChecker` | Server Requirements (PHP, Extensions) |
| `PermissionChecker` | File/Directory Permissions |
| `StripeValidator` | Stripe API Key Validation |

### OpenApi/ (5 Services)
| Service | Beschreibung |
|---------|--------------|
| `OpenApiDocumentationService` | OpenAPI 3.0 Spec Generation |
| `javascriptSDKGenerator` | JavaScript SDK Generator |
| `phpSDKGenerator` | PHP SDK Generator (PSR-7) |
| `pythonSDKGenerator` | Python SDK Generator |
| `SDKGeneratorInterface` | SDK Generator Interface |

### ML/ (4 Services)
| Service | Beschreibung |
|---------|--------------|
| `MLTrainingService` | ML Model Training Orchestration |
| `MLPredictionService` | Real-time Predictions |
| `InjuryRiskPredictionService` | Verletzungsrisiko basierend auf Workload |
| `PlayerPerformancePredictionService` | Spieler-Performance Forecasting |

### Api/ (1 Service)
| Service | Beschreibung |
|---------|--------------|
| `RouteVersionResolver` | API Versioning Logic |

---

## Performance Services

| Service | Beschreibung |
|---------|--------------|
| `BasketballCacheService` | Basketball-spezifische Caching-Strategien |
| `DatabasePerformanceMonitor` | Query Performance Tracking |
| `ApiResponseOptimizationService` | API Response Optimization |
| `EnterpriseRateLimitService` | Tenant-aware Rate Limiting |
| `QueryOptimizationService` | Database Query Optimization |
| `MemoryOptimizationService` | Memory Usage Optimization |

---

## Specialized Services

| Service | Beschreibung |
|---------|--------------|
| `AIVideoAnalysisService` | AI-powered Video Analysis |
| `VideoProcessingService` | Video Processing und Storage |
| `BookingService` | Facility Booking |
| `GymScheduleService` | Gym Scheduling |
| `ICalImportService` | Calendar Import |
| `LocalizationService` | Multi-Language Support |
| `UserService` | User Management |
| `UserImportService` | Bulk User Import (CSV/Excel) |
| `RedisAvailabilityService` | Redis Availability und Fallback |
| `PWAService` | Progressive Web App Features |
| `PushNotificationService` | WebPush Notifications |
| `QRCodeService` | QR Code Generation |
| `LandingPageService` | Landing Page CMS |

---

## Service-Nutzung

```php
// Service via Dependency Injection
public function __construct(
    private ClubService $clubService,
    private TenantService $tenantService
) {}

// Service via App Container
$service = app(ClubService::class);
```
