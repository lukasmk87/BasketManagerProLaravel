# BasketManager Pro - Laravel Master PRD

> **Product Requirements Document (PRD)**  
> **Version**: 1.0  
> **Datum**: 28. Juli 2025  
> **Status**: Entwurf  
> **Autor**: Claude Code Assistant  

---

## 📋 Inhaltsverzeichnis

1. [Projektübersicht & Vision](#projektübersicht--vision)
2. [Laravel-Architektur & Technische Grundlagen](#laravel-architektur--technische-grundlagen)
3. [Funktionale Anforderungen & Features](#funktionale-anforderungen--features)
4. [Laravel-spezifische Implementierung](#laravel-spezifische-implementierung)
5. [Datenmodell & Eloquent Relationships](#datenmodell--eloquent-relationships)
6. [API Design & Resources](#api-design--resources)
7. [Security & Authentication](#security--authentication)
8. [Performance & Skalierung](#performance--skalierung)
9. [Testing Strategy](#testing-strategy)
10. [Deployment & DevOps](#deployment--devops)
11. [Phasen-Roadmap](#phasen-roadmap)
12. [Migration vom Legacy System](#migration-vom-legacy-system)

---

## 🎯 Projektübersicht & Vision

### Projekt-Vision

**BasketManager Pro** ist eine umfassende Laravel-basierte Web-Anwendung zur professionellen Verwaltung von Basketball-Vereinen. Das System kombiniert moderne Web-Technologien mit einem tiefen Verständnis für die spezifischen Bedürfnisse von Basketball-Clubs, Trainern, Spielern und Administratoren.

### Kernziele der Laravel-Migration

1. **Moderne Architektur**: Migration vom bestehenden PHP 8.3 System auf Laravel 11 Framework
2. **Skalierbarkeit**: Aufbau einer Enterprise-ready Architektur mit Laravel's bewährten Patterns
3. **Developer Experience**: Nutzung von Laravel's Ecosystem (Eloquent, Artisan, Blade, etc.)
4. **API-First Design**: RESTful APIs mit Laravel API Resources
5. **Real-time Features**: Integration von Broadcasting und WebSockets
6. **Mobile-First**: PWA-Features mit Laravel's Frontend-Tools
7. **Enterprise Security**: Laravel Sanctum, Policies, und erweiterte Sicherheitsfeatures

### Zielgruppen

#### Primäre Benutzer
- **Vereinsadministratoren**: Vollzugriff auf alle Vereinsfunktionen
- **Trainer**: Team-Management, Training-Planung, Spieler-Development
- **Spieler**: Persönliche Statistiken, Termine, Kommunikation
- **Scorer/Statistiker**: Live-Game Scoring, Statistik-Erfassung

#### Sekundäre Benutzer  
- **Eltern**: Zugriff auf Kinder-relevante Informationen
- **Fans**: Öffentliche Statistiken und Spielberichte
- **Sponsoren**: Branded Content und ROI-Tracking
- **Verbände**: Tournament-Management und Reporting

### Kerndifferenzierung

1. **Basketball-spezifisch**: Tiefe Integration basketball-spezifischer Regeln und Statistiken
2. **Real-time Analytics**: Live-Statistiken während Spielen mit WebSocket-Integration
3. **Predictive Analytics**: KI-basierte Vorhersagen für Team-Performance
4. **Comprehensive Training Tools**: Drill-Management mit Video-Integration
5. **Emergency Systems**: Integriertes Notfallkontakte-System (aus Basketball_Notfallkontakte_PRD.md)

---

## 🏗️ Laravel-Architektur & Technische Grundlagen

### Laravel Framework Spezifikationen

#### Core Framework
- **Laravel Version**: 11.x (LTS)
- **PHP Version**: 8.3+
- **Database**: MySQL 8.0+ / PostgreSQL 14+
- **Cache**: Redis 7.0+
- **Queue**: Redis/Database
- **Search**: Laravel Scout mit Meilisearch/Algolia
- **Storage**: S3-kompatible Object Storage

#### Laravel Ecosystem Integration

```php
// composer.json - Key Dependencies
{
    "require": {
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/jetstream": "^5.0",
        "laravel/horizon": "^5.0",
        "laravel/scout": "^10.0",
        "laravel/socialite": "^5.0",
        "laravel/telescope": "^5.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-activitylog": "^4.0",
        "spatie/laravel-backup": "^8.0",
        "spatie/laravel-media-library": "^11.0",
        "barryvdh/laravel-dompdf": "^3.0",
        "maatwebsite/excel": "^3.1",
        "pusher/pusher-php-server": "^7.0",
        "intervention/image": "^3.0"
    }
}
```

### Architektur-Pattern

#### Domain-Driven Design (DDD) Struktur

```
app/
├── Domain/
│   ├── User/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   └── Policies/
│   ├── Team/
│   ├── Game/
│   ├── Statistics/
│   ├── Training/
│   └── Emergency/
├── Infrastructure/
│   ├── Providers/
│   ├── Middleware/
│   └── Observers/
├── Application/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Middleware/
│   ├── Console/
│   └── Jobs/
└── Support/
    ├── Helpers/
    ├── Traits/
    └── Enums/
```

#### Service Layer Architecture

```php
// Example Service Structure
namespace App\Domain\Team\Services;

class TeamService
{
    public function __construct(
        private TeamRepository $teamRepository,
        private PlayerRepository $playerRepository,
        private StatisticsService $statisticsService,
        private EventDispatcher $eventDispatcher
    ) {}

    public function createTeam(CreateTeamRequest $request): Team
    {
        DB::transaction(function () use ($request) {
            $team = $this->teamRepository->create($request->validated());
            
            $this->eventDispatcher->dispatch(new TeamCreated($team));
            
            return $team;
        });
    }
}
```

---

## ⚙️ Funktionale Anforderungen & Features

### 1. Benutzer- und Rollenverwaltung

#### Laravel Sanctum Authentication
- **Multi-Guard Authentication**: Web, API, Admin Guards
- **Two-Factor Authentication**: TOTP mit Laravel Fortify Integration
- **Social Login**: Laravel Socialite (Google, Facebook, Apple)
- **Session Management**: Database-basierte Sessions mit Redis-Caching

#### Spatie Laravel Permission Integration
```php
// Role und Permission System
$admin = Role::create(['name' => 'admin']);
$trainer = Role::create(['name' => 'trainer']);
$player = Role::create(['name' => 'player']);

// Permissions
Permission::create(['name' => 'manage teams']);
Permission::create(['name' => 'view statistics']);
Permission::create(['name' => 'edit games']);

// Assignment
$trainer->givePermissionTo('manage teams', 'view statistics');
```

### 2. Team-Management System

#### Eloquent Models & Relationships
```php
class Team extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'name', 'category', 'season', 'league', 'club_id'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
    ];
    
    // Relationships
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
    
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
    
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id')
                    ->orWhere('away_team_id', $this->id);
    }
    
    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }
}
```

### 3. Spieler-Management

#### Player Model mit Advanced Features
```php
class Player extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasMedia;
    
    protected $fillable = [
        'first_name', 'last_name', 'jersey_number', 'position',
        'birth_date', 'height', 'weight', 'team_id', 'user_id'
    ];
    
    protected $casts = [
        'birth_date' => 'date',
        'medical_info' => 'encrypted:array',
        'emergency_contacts' => 'encrypted:array',
    ];
    
    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function statistics(): HasMany
    {
        return $this->hasMany(PlayerStatistic::class);
    }
    
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }
    
    // Accessors & Mutators
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }
    
    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photos')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);
              
        $this->addMediaCollection('training_videos')
              ->acceptsMimeTypes(['video/mp4', 'video/webm']);
    }
}
```

### 4. Game-Management & Live-Scoring

#### Game Model mit Complex Relationships
```php
class Game extends Model
{
    use HasFactory, LogsActivity, Searchable;
    
    protected $fillable = [
        'home_team_id', 'away_team_id', 'scheduled_at',
        'venue', 'referee', 'status', 'final_score_home',
        'final_score_away', 'season', 'game_type'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'game_settings' => 'array',
        'final_score_home' => 'integer',
        'final_score_away' => 'integer',
    ];
    
    // Relationships
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }
    
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
    
    public function gameActions(): HasMany
    {
        return $this->hasMany(GameAction::class);
    }
    
    public function gamePeriods(): HasMany
    {
        return $this->hasMany(GamePeriod::class);
    }
    
    public function liveGame(): HasOne
    {
        return $this->hasOne(LiveGame::class);
    }
    
    // Event Broadcasting
    protected $dispatchesEvents = [
        'updated' => GameUpdated::class,
        'created' => GameCreated::class,
    ];
    
    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'home_team' => $this->homeTeam->name,
            'away_team' => $this->awayTeam->name,
            'scheduled_at' => $this->scheduled_at,
            'venue' => $this->venue,
        ];
    }
}
```

### 5. Live-Game Features mit Broadcasting

#### WebSocket Integration
```php
class LiveGameController extends Controller
{
    use AuthorizesRequests;
    
    public function updateScore(UpdateScoreRequest $request, Game $game)
    {
        $this->authorize('update', $game);
        
        DB::transaction(function () use ($request, $game) {
            // Update game score
            $game->update($request->validated());
            
            // Create game action
            GameAction::create([
                'game_id' => $game->id,
                'player_id' => $request->player_id,
                'action_type' => $request->action_type,
                'points' => $request->points,
                'quarter' => $request->quarter,
                'time_remaining' => $request->time_remaining,
            ]);
            
            // Broadcast to all connected clients
            broadcast(new GameScoreUpdated($game))->toOthers();
            
            // Trigger real-time statistics update
            UpdateGameStatistics::dispatch($game);
        });
        
        return new GameResource($game->load('gameActions', 'liveGame'));
    }
}
```

#### Broadcasting Events
```php
class GameScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public Game $game
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->game->id}"),
            new Channel("team.{$this->game->home_team_id}"),
            new Channel("team.{$this->game->away_team_id}"),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'home_score' => $this->game->final_score_home,
            'away_score' => $this->game->final_score_away,
            'last_action' => $this->game->gameActions()->latest()->first(),
            'updated_at' => now()->toISOString(),
        ];
    }
}
```

### 6. Statistics & Analytics Engine

#### Advanced Statistics Service
```php
class StatisticsService
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private GameRepository $gameRepository,
        private Cache $cache
    ) {}
    
    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        return $this->cache->remember(
            "player.stats.{$player->id}.{$season}",
            3600,
            function () use ($player, $season) {
                return DB::table('game_actions')
                    ->join('games', 'game_actions.game_id', '=', 'games.id')
                    ->where('game_actions.player_id', $player->id)
                    ->where('games.season', $season)
                    ->selectRaw('
                        COUNT(*) as games_played,
                        SUM(points) as total_points,
                        AVG(points) as avg_points,
                        SUM(CASE WHEN action_type = "rebound" THEN 1 ELSE 0 END) as rebounds,
                        SUM(CASE WHEN action_type = "assist" THEN 1 ELSE 0 END) as assists,
                        SUM(CASE WHEN action_type = "steal" THEN 1 ELSE 0 END) as steals
                    ')
                    ->first();
            }
        );
    }
    
    public function calculateTeamEfficiency(Team $team, string $season): float
    {
        // Advanced basketball efficiency calculation
        $stats = $this->getTeamSeasonStats($team, $season);
        
        return ($stats->points + $stats->rebounds + $stats->assists + $stats->steals) 
               / max($stats->games_played, 1);
    }
}
```

### 7. Training & Drill Management

#### Training System
```php
class TrainingSession extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'team_id', 'trainer_id', 'scheduled_at', 'venue',
        'duration', 'focus_areas', 'notes', 'status'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'focus_areas' => 'array',
        'drills_completed' => 'array',
    ];
    
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
    
    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(Drill::class, 'training_drills')
                    ->withPivot('order', 'duration', 'notes', 'completed')
                    ->withTimestamps();
    }
    
    public function attendance(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }
}

class Drill extends Model
{
    use HasFactory, HasMedia, Searchable;
    
    protected $fillable = [
        'name', 'description', 'category', 'difficulty_level',
        'duration', 'equipment_needed', 'instructions'
    ];
    
    protected $casts = [
        'equipment_needed' => 'array',
        'tags' => 'array',
    ];
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('diagrams')
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);
              
        $this->addMediaCollection('videos')
              ->acceptsMimeTypes(['video/mp4', 'video/webm']);
    }
}
```

### 8. Tournament Management

#### Tournament System
```php
class Tournament extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'name', 'type', 'start_date', 'end_date',
        'max_teams', 'status', 'settings'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'settings' => 'array',
    ];
    
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
                    ->withPivot('seed', 'status', 'registered_at')
                    ->withTimestamps();
    }
    
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
    
    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }
}
```

### 9. Emergency Contacts System (Integration)

#### Emergency Contact Features (aus Basketball_Notfallkontakte_PRD.md)
```php
class EmergencyContact extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'player_id', 'contact_name', 'phone_number',
        'relationship', 'is_primary', 'notes'
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'phone_number' => 'encrypted',
    ];
    
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
    
    // QR Code Access System
    public function generateQRAccess(): string
    {
        $accessKey = Str::random(32);
        
        TeamAccess::create([
            'team_id' => $this->player->team_id,
            'access_key' => $accessKey,
            'expires_at' => now()->addYear(),
            'created_by' => auth()->id(),
            'purpose' => 'emergency_access',
        ]);
        
        return route('emergency.access', ['key' => $accessKey]);
    }
}

class TeamAccess extends Model
{
    protected $fillable = [
        'team_id', 'access_key', 'expires_at',
        'created_by', 'purpose', 'is_active'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }
}
```

### 10. GDPR/DSGVO Compliance

#### GDPR Service Implementation
```php
class GDPRService
{
    public function exportUserData(User $user): array
    {
        return [
            'personal_data' => $user->only([
                'name', 'email', 'phone', 'birth_date'
            ]),
            'player_data' => $user->player?->toArray(),
            'game_statistics' => $user->player?->statistics->toArray(),
            'emergency_contacts' => $user->player?->emergencyContacts->toArray(),
            'activity_log' => $user->activities->toArray(),
            'exported_at' => now()->toISOString(),
        ];
    }
    
    public function deleteUserData(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize instead of hard delete for statistical integrity
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted_' . $user->id . '@example.com',
                'phone' => null,
                'birth_date' => null,
            ]);
            
            // Log deletion
            GDPRDataDeletion::create([
                'user_id' => $user->id,
                'deleted_at' => now(),
                'reason' => 'user_request',
            ]);
            
            // Soft delete
            $user->delete();
        });
    }
}
```

---

## 🛠️ Laravel-spezifische Implementierung

### Service Provider Structure

#### BasketManagerServiceProvider
```php
class BasketManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register core services
        $this->app->singleton(StatisticsService::class);
        $this->app->singleton(GDPRService::class);
        $this->app->singleton(EmergencyAccessService::class);
        
        // Register repositories
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(PlayerRepositoryInterface::class, PlayerRepository::class);
        $this->app->bind(GameRepositoryInterface::class, GameRepository::class);
    }
    
    public function boot(): void
    {
        // Register policies
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Game::class, GamePolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
        
        // Register observers
        Team::observe(TeamObserver::class);
        Game::observe(GameObserver::class);
        Player::observe(PlayerObserver::class);
        
        // Register custom validation rules
        Validator::extend('basketball_position', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['PG', 'SG', 'SF', 'PF', 'C']);
        });
        
        // Register Blade components
        Blade::component('team-card', TeamCard::class);
        Blade::component('player-stats', PlayerStats::class);
        Blade::component('game-scoreboard', GameScoreboard::class);
    }
}
```

### Middleware Stack

#### Basketball-specific Middleware
```php
class EnsureTeamAccess
{
    public function handle($request, Closure $next, ...$permissions)
    {
        $user = $request->user();
        $team = $request->route('team');
        
        if (!$user->hasTeamAccess($team, $permissions)) {
            abort(403, 'Insufficient team permissions');
        }
        
        return $next($request);
    }
}

class LogGameActivity
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($request->route()->getName() === 'games.update-score') {
            activity()
                ->performedOn($request->route('game'))
                ->causedBy($request->user())
                ->withProperties($request->all())
                ->log('game_score_updated');
        }
        
        return $response;
    }
}
```

### Custom Artisan Commands

#### Data Management Commands
```php
class GenerateSeasonReportCommand extends Command
{
    protected $signature = 'basketball:season-report {season} {--team=} {--format=pdf}';
    protected $description = 'Generate comprehensive season report';
    
    public function handle(SeasonReportService $reportService): int
    {
        $season = $this->argument('season');
        $teamId = $this->option('team');
        $format = $this->option('format');
        
        $this->info("Generating season report for {$season}...");
        
        $report = $reportService->generate($season, $teamId);
        
        $filename = $reportService->export($report, $format);
        
        $this->info("Report generated: {$filename}");
        
        return Command::SUCCESS;
    }
}

class CleanupExpiredEmergencyAccessCommand extends Command
{
    protected $signature = 'basketball:cleanup-emergency-access';
    protected $description = 'Clean up expired emergency access keys';
    
    public function handle(): int
    {
        $deleted = TeamAccess::expired()->delete();
        
        $this->info("Cleaned up {$deleted} expired access keys");
        
        return Command::SUCCESS;
    }
}
```

### Queue Jobs

#### Background Processing
```php
class UpdateGameStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Game $game
    ) {}
    
    public function handle(StatisticsService $statisticsService): void
    {
        // Recalculate all player statistics for this game
        $statisticsService->recalculateGameStatistics($this->game);
        
        // Update team statistics
        $statisticsService->updateTeamStatistics($this->game->homeTeam);
        $statisticsService->updateTeamStatistics($this->game->awayTeam);
        
        // Broadcast statistics update
        broadcast(new StatisticsUpdated($this->game));
    }
    
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to update game statistics', [
            'game_id' => $this->game->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

class SendScorekeeperReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Game $game,
        public int $hoursBeforeGame = 24
    ) {}
    
    public function handle(): void
    {
        $scorekeepers = $this->game->scorekeeperAssignments()
                                  ->with('user')
                                  ->get();
        
        foreach ($scorekeepers as $assignment) {
            Mail::to($assignment->user)
                ->send(new ScorekeeperReminderMail($this->game, $assignment));
        }
    }
}
```

---

## 🗄️ Datenmodell & Eloquent Relationships

### Core Entity Relationships

#### Database Schema (Migration Examples)
```php
// Teams Migration
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('category'); // Youth, Senior, etc.
    $table->string('league')->nullable();
    $table->string('season');
    $table->foreignId('club_id')->constrained()->onDelete('cascade');
    $table->foreignId('head_coach_id')->nullable()->constrained('users');
    $table->json('settings')->nullable();
    $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['club_id', 'season']);
    $table->unique(['name', 'club_id', 'season']);
});

// Games Migration
Schema::create('games', function (Blueprint $table) {
    $table->id();
    $table->foreignId('home_team_id')->constrained('teams');
    $table->foreignId('away_team_id')->constrained('teams');
    $table->foreignId('tournament_id')->nullable()->constrained();
    $table->dateTime('scheduled_at');
    $table->string('venue');
    $table->string('referee')->nullable();
    $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');
    $table->integer('final_score_home')->nullable();
    $table->integer('final_score_away')->nullable();
    $table->string('season');
    $table->enum('game_type', ['regular', 'playoff', 'friendly', 'tournament']);
    $table->json('game_settings')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['scheduled_at', 'status']);
    $table->index(['home_team_id', 'season']);
    $table->index(['away_team_id', 'season']);
});

// Game Actions Migration (for live scoring)
Schema::create('game_actions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('game_id')->constrained()->onDelete('cascade');
    $table->foreignId('player_id')->constrained();
    $table->enum('action_type', [
        'field_goal_made', 'field_goal_missed', 'three_point_made', 'three_point_missed',
        'free_throw_made', 'free_throw_missed', 'rebound_offensive', 'rebound_defensive',
        'assist', 'steal', 'block', 'turnover', 'foul_personal', 'foul_technical'
    ]);
    $table->integer('points')->default(0);
    $table->integer('quarter');
    $table->time('time_remaining');
    $table->integer('shot_x')->nullable(); // for shot charts
    $table->integer('shot_y')->nullable();
    $table->boolean('is_successful')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->index(['game_id', 'quarter']);
    $table->index(['player_id', 'action_type']);
});

// Emergency Contacts Migration
Schema::create('emergency_contacts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('player_id')->constrained()->onDelete('cascade');
    $table->string('contact_name');
    $table->string('phone_number'); // Will be encrypted
    $table->enum('relationship', [
        'parent', 'mother', 'father', 'guardian', 'sibling',
        'grandparent', 'partner', 'friend', 'other'
    ]);
    $table->boolean('is_primary')->default(false);
    $table->text('notes')->nullable();
    $table->boolean('consent_given')->default(false);
    $table->timestamp('consent_given_at')->nullable();
    $table->timestamps();
    
    $table->index(['player_id', 'is_primary']);
});

// Team Access (for QR codes) Migration
Schema::create('team_access', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->onDelete('cascade');
    $table->string('access_key', 64)->unique();
    $table->enum('purpose', ['emergency_access', 'statistics_view', 'general_access']);
    $table->timestamp('expires_at');
    $table->foreignId('created_by')->constrained('users');
    $table->boolean('is_active')->default(true);
    $table->integer('usage_count')->default(0);
    $table->timestamp('last_used_at')->nullable();
    $table->json('access_log')->nullable();
    $table->timestamps();
    
    $table->index(['access_key', 'is_active']);
    $table->index(['team_id', 'purpose']);
});
```

### Model Relationships Overview

#### Complex Relationship Example
```php
class Club extends Model
{
    // A club has many teams across different seasons
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
    
    // All players across all teams
    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(Player::class, Team::class);
    }
    
    // All games where club teams participate
    public function games(): HasManyThrough
    {
        return $this->hasManyThrough(Game::class, Team::class, 'club_id', 'home_team_id')
                    ->union(
                        $this->hasManyThrough(Game::class, Team::class, 'club_id', 'away_team_id')
                    );
    }
    
    // Club statistics aggregation
    public function getSeasonStatsAttribute(): Collection
    {
        return $this->teams()
                   ->with(['games.gameActions'])
                   ->get()
                   ->groupBy('season')
                   ->map(function ($teams, $season) {
                       return [
                           'season' => $season,
                           'teams_count' => $teams->count(),
                           'games_played' => $teams->sum(fn($team) => $team->games->count()),
                           'total_points' => $teams->sum(fn($team) => 
                               $team->games->sum('final_score_home') + 
                               $team->games->sum('final_score_away')
                           ),
                       ];
                   });
    }
}
```

---

## 🔌 API Design & Resources

### RESTful API Structure

#### API Resource Classes
```php
class TeamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'league' => $this->league,
            'season' => $this->season,
            'status' => $this->status,
            'club' => new ClubResource($this->whenLoaded('club')),
            'head_coach' => new UserResource($this->whenLoaded('headCoach')),
            'players_count' => $this->players_count ?? $this->players->count(),
            'current_season_stats' => $this->when(
                $request->include('stats'),
                fn() => $this->getCurrentSeasonStats()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

class GameResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'home_team' => new TeamResource($this->whenLoaded('homeTeam')),
            'away_team' => new TeamResource($this->whenLoaded('awayTeam')),
            'scheduled_at' => $this->scheduled_at,
            'venue' => $this->venue,
            'status' => $this->status,
            'score' => [
                'home' => $this->final_score_home,
                'away' => $this->final_score_away,
            ],
            'live_data' => new LiveGameResource($this->whenLoaded('liveGame')),
            'game_actions' => GameActionResource::collection($this->whenLoaded('gameActions')),
            'statistics' => $this->when(
                $request->include('statistics'),
                fn() => $this->getGameStatistics()
            ),
        ];
    }
}

class PlayerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'jersey_number' => $this->jersey_number,
            'position' => $this->position,
            'age' => $this->age,
            'team' => new TeamResource($this->whenLoaded('team')),
            'season_statistics' => $this->when(
                $request->include('stats'),
                fn() => $this->getSeasonStatistics($request->season ?? current_season())
            ),
            'emergency_contacts' => $this->when(
                $request->user()?->can('viewEmergencyContacts', $this),
                EmergencyContactResource::collection($this->whenLoaded('emergencyContacts'))
            ),
            'profile_photo' => $this->getFirstMediaUrl('profile_photos'),
        ];
    }
}
```

#### API Controllers
```php
class Api\V2\TeamsController extends Controller
{
    use AuthorizesRequests;
    
    public function __construct(
        private TeamService $teamService,
        private StatisticsService $statisticsService
    ) {
        $this->middleware(['auth:sanctum']);
    }
    
    public function index(IndexTeamsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);
        
        $teams = Team::query()
            ->when($request->club_id, fn($q) => $q->where('club_id', $request->club_id))
            ->when($request->season, fn($q) => $q->where('season', $request->season))
            ->when($request->include('players'), fn($q) => $q->with('players'))
            ->when($request->include('stats'), fn($q) => $q->withCount('players'))
            ->paginate($request->per_page ?? 15);
            
        return TeamResource::collection($teams);
    }
    
    public function show(Team $team, ShowTeamRequest $request): TeamResource
    {
        $this->authorize('view', $team);
        
        $team->load($this->getRequestedRelations($request));
        
        return new TeamResource($team);
    }
    
    public function store(StoreTeamRequest $request): TeamResource
    {
        $this->authorize('create', Team::class);
        
        $team = $this->teamService->createTeam($request);
        
        return new TeamResource($team->load('club', 'headCoach'));
    }
    
    public function update(Team $team, UpdateTeamRequest $request): TeamResource
    {
        $this->authorize('update', $team);
        
        $team = $this->teamService->updateTeam($team, $request);
        
        return new TeamResource($team);
    }
    
    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);
        
        $this->teamService->deleteTeam($team);
        
        return response()->json(['message' => 'Team deleted successfully']);
    }
    
    public function statistics(Team $team, TeamStatisticsRequest $request): JsonResponse
    {
        $this->authorize('viewStatistics', $team);
        
        $statistics = $this->statisticsService->getTeamStatistics(
            $team,
            $request->season ?? current_season(),
            $request->game_type
        );
        
        return response()->json($statistics);
    }
    
    private function getRequestedRelations(Request $request): array
    {
        $available = ['club', 'headCoach', 'players', 'games', 'statistics'];
        $requested = explode(',', $request->include ?? '');
        
        return array_intersect($available, $requested);
    }
}
```

### API Authentication & Authorization

#### Sanctum Token Management
```php
class ApiTokenController extends Controller
{
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $token = $user->createToken(
            $request->token_name,
            $this->getTokenAbilities($request->permissions)
        );
        
        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }
    
    private function getTokenAbilities(array $permissions): array
    {
        $availableAbilities = [
            'team:read', 'team:write',
            'player:read', 'player:write',
            'game:read', 'game:write', 'game:score',
            'statistics:read',
            'emergency:read',
        ];
        
        return array_intersect($availableAbilities, $permissions);
    }
}
```

---

## 🔐 Security & Authentication

### Multi-Layer Security Architecture

#### Authentication System
```php
// Config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
    'emergency' => [
        'driver' => 'emergency_access',
        'provider' => 'team_access',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'team_access' => [
        'driver' => 'team_access',
        'model' => App\Models\TeamAccess::class,
    ],
],
```

#### Custom Emergency Access Guard
```php
class EmergencyAccessGuard implements Guard
{
    public function __construct(
        private UserProvider $provider,
        private Request $request
    ) {}
    
    public function check(): bool
    {
        return !is_null($this->user());
    }
    
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }
        
        $accessKey = $this->request->get('access_key') ?? 
                    $this->request->route('access_key');
        
        if (!$accessKey) {
            return null;
        }
        
        $teamAccess = TeamAccess::active()
            ->where('access_key', $accessKey)
            ->where('purpose', 'emergency_access')
            ->first();
            
        if (!$teamAccess) {
            return null;
        }
        
        // Log access for audit trail
        $teamAccess->increment('usage_count');
        $teamAccess->update(['last_used_at' => now()]);
        
        // Create temporary user for emergency access
        return new EmergencyAccessUser($teamAccess);
    }
    
    public function validate(array $credentials = []): bool
    {
        return $this->attempt($credentials);
    }
}
```

#### Authorization Policies
```php
class TeamPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view teams');
    }
    
    public function view(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('view teams') ||
               $user->hasTeamAccess($team, 'read');
    }
    
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create teams');
    }
    
    public function update(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('edit teams') ||
               $user->isHeadCoach($team);
    }
    
    public function delete(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('delete teams') &&
               !$team->hasActiveGames();
    }
    
    public function viewStatistics(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('view statistics') ||
               $user->hasTeamAccess($team, 'statistics');
    }
    
    public function manageEmergencyContacts(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('manage emergency contacts') ||
               $user->isHeadCoach($team) ||
               $user->isTeamManager($team);
    }
}

class GamePolicy
{
    public function updateScore(User $user, Game $game): bool
    {
        // Only authorized scorekeepers or team coaches can update scores
        return $user->hasPermissionTo('score games') ||
               $user->isScorekeeperFor($game) ||
               $user->isCoachFor($game->homeTeam) ||
               $user->isCoachFor($game->awayTeam);
    }
    
    public function viewLiveData(User $user, Game $game): bool
    {
        // Live data can be viewed by anyone with team access
        return $user->hasTeamAccess($game->homeTeam, 'read') ||
               $user->hasTeamAccess($game->awayTeam, 'read') ||
               $user->hasPermissionTo('view all games');
    }
}
```

#### Security Middleware
```php
class ValidateEmergencyAccess
{
    public function handle($request, Closure $next): Response
    {
        $accessKey = $request->route('access_key');
        
        $teamAccess = TeamAccess::active()
            ->where('access_key', $accessKey)
            ->where('purpose', 'emergency_access')
            ->first();
        
        if (!$teamAccess) {
            abort(404, 'Invalid or expired emergency access key');
        }
        
        // Rate limiting for emergency access
        if (RateLimiter::tooManyAttempts($accessKey, 10)) {
            abort(429, 'Too many emergency access attempts');
        }
        
        RateLimiter::hit($accessKey, 3600); // 1 hour window
        
        // Log emergency access
        Log::info('Emergency access used', [
            'access_key' => $accessKey,
            'team_id' => $teamAccess->team_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        $request->attributes->set('team_access', $teamAccess);
        
        return $next($request);
    }
}

class AuditGameActions
{
    public function handle($request, Closure $next): Response
    {
        $response = $next($request);
        
        // Log all game-related actions for audit trail
        if ($request->route()->getName() && 
            Str::startsWith($request->route()->getName(), 'games.')) {
            
            activity()
                ->performedOn($request->route('game'))
                ->causedBy($request->user())
                ->withProperties([
                    'action' => $request->route()->getName(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_data' => $request->except(['password', 'token']),
                ])
                ->log('game_action');
        }
        
        return $response;
    }
}
```

---

## ⚡ Performance & Skalierung

### Caching Strategy

#### Redis-based Caching
```php
class StatisticsCacheService
{
    private string $cachePrefix = 'basketball:stats:';
    private int $defaultTtl = 3600; // 1 hour
    
    public function __construct(
        private Cache $cache,
        private StatisticsService $statisticsService
    ) {}
    
    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        $cacheKey = $this->cachePrefix . "player:{$player->id}:season:{$season}";
        
        return $this->cache->remember($cacheKey, $this->defaultTtl, function () use ($player, $season) {
            return $this->statisticsService->calculatePlayerSeasonStats($player, $season);
        });
    }
    
    public function getTeamStats(Team $team, string $season): array
    {
        $cacheKey = $this->cachePrefix . "team:{$team->id}:season:{$season}";
        
        return $this->cache->remember($cacheKey, $this->defaultTtl, function () use ($team, $season) {
            return $this->statisticsService->calculateTeamStats($team, $season);
        });
    }
    
    public function invalidatePlayerStats(Player $player): void
    {
        $pattern = $this->cachePrefix . "player:{$player->id}:*";
        $this->cache->deleteByPattern($pattern);
        
        // Also invalidate team stats
        $this->invalidateTeamStats($player->team);
    }
    
    public function invalidateTeamStats(Team $team): void
    {
        $pattern = $this->cachePrefix . "team:{$team->id}:*";
        $this->cache->deleteByPattern($pattern);
    }
}
```

#### Database Query Optimization
```php
class OptimizedGameRepository implements GameRepositoryInterface
{
    public function getTeamGamesWithStats(Team $team, string $season): Collection
    {
        return Game::query()
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->where('season', $season)
            ->with([
                'homeTeam:id,name',
                'awayTeam:id,name',
                'gameActions' => function ($query) {
                    $query->select('game_id', 'action_type', 'points', 'player_id')
                          ->with('player:id,first_name,last_name');
                }
            ])
            ->select([
                'id', 'home_team_id', 'away_team_id', 'scheduled_at',
                'final_score_home', 'final_score_away', 'status'
            ])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }
    
    public function getLiveGameData(Game $game): ?LiveGame
    {
        return LiveGame::query()
            ->where('game_id', $game->id)
            ->with([
                'game.gameActions' => function ($query) {
                    $query->latest()->limit(10);
                },
                'game.homeTeam:id,name',
                'game.awayTeam:id,name'
            ])
            ->first();
    }
}
```

### Queue System Implementation

#### High-Priority vs Background Jobs
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
    'redis-high' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'high',
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// Horizon configuration for job monitoring
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user->hasRole('admin');
        });
    }
    
    protected function authorization(): void
    {
        $this->gate();
        
        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon', $request->user());
        });
    }
}
```

#### Job Processing Examples
```php
// High priority: Real-time game updates
class BroadcastGameUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $queue = 'high';
    public $tries = 3;
    public $timeout = 30;
    
    public function __construct(
        public Game $game,
        public array $updateData
    ) {}
    
    public function handle(): void
    {
        broadcast(new GameScoreUpdated($this->game, $this->updateData));
    }
}

// Background: Statistics recalculation
class RecalculateSeasonStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $queue = 'default';
    public $tries = 2;
    public $timeout = 300; // 5 minutes
    
    public function __construct(
        public string $season,
        public ?int $teamId = null
    ) {}
    
    public function handle(StatisticsService $statisticsService): void
    {
        if ($this->teamId) {
            $team = Team::find($this->teamId);
            $statisticsService->recalculateTeamSeasonStats($team, $this->season);
        } else {
            $statisticsService->recalculateAllSeasonStats($this->season);
        }
    }
}
```

### Elasticsearch Integration

#### Search Service Implementation
```php
class BasketballSearchService
{
    public function __construct(
        private Client $elasticsearch
    ) {}
    
    public function searchPlayers(string $query, array $filters = []): array
    {
        $params = [
            'index' => 'basketball_players',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['first_name^2', 'last_name^2', 'full_name'],
                                'fuzziness' => 'AUTO'
                            ]
                        ],
                        'filter' => $this->buildFilters($filters)
                    ]
                ],
                'highlight' => [
                    'fields' => [
                        'first_name' => new \stdClass(),
                        'last_name' => new \stdClass(),
                    ]
                ],
                'size' => $filters['per_page'] ?? 20
            ]
        ];
        
        $response = $this->elasticsearch->search($params);
        
        return $this->transformSearchResults($response);
    }
    
    public function searchGames(string $query, array $filters = []): array
    {
        $params = [
            'index' => 'basketball_games',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['home_team_name', 'away_team_name', 'venue'],
                            ]
                        ],
                        'filter' => array_merge(
                            $this->buildFilters($filters),
                            [
                                ['range' => [
                                    'scheduled_at' => [
                                        'gte' => $filters['date_from'] ?? 'now-1y',
                                        'lte' => $filters['date_to'] ?? 'now'
                                    ]
                                ]]
                            ]
                        )
                    ]
                ],
                'sort' => [
                    ['scheduled_at' => ['order' => 'desc']]
                ]
            ]
        ];
        
        return $this->transformSearchResults($this->elasticsearch->search($params));
    }
    
    private function buildFilters(array $filters): array
    {
        $esFilters = [];
        
        if (isset($filters['team_id'])) {
            $esFilters[] = ['term' => ['team_id' => $filters['team_id']]];
        }
        
        if (isset($filters['season'])) {
            $esFilters[] = ['term' => ['season' => $filters['season']]];
        }
        
        if (isset($filters['position'])) {
            $esFilters[] = ['term' => ['position' => $filters['position']]];
        }
        
        return $esFilters;
    }
}
```

---

## 🧪 Testing Strategy

### Comprehensive Testing Architecture

#### Feature Tests
```php
class GameManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');
        
        $this->team = Team::factory()->create();
        $this->team->headCoach()->associate($this->trainer);
        $this->team->save();
        
        $this->actingAs($this->admin);
    }
    
    public function test_can_create_game(): void
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();
        
        $gameData = [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'venue' => 'Test Arena',
            'game_type' => 'regular',
            'season' => '2024-25',
        ];
        
        $response = $this->postJson('/api/v2/games', $gameData);
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'home_team',
                        'away_team',
                        'scheduled_at',
                        'venue',
                        'status',
                    ]
                ]);
        
        $this->assertDatabaseHas('games', [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'venue' => 'Test Arena',
        ]);
    }
    
    public function test_can_update_live_game_score(): void
    {
        $game = Game::factory()->create([
            'status' => 'live',
            'final_score_home' => 45,
            'final_score_away' => 42,
        ]);
        
        $player = Player::factory()->create(['team_id' => $game->home_team_id]);
        
        Event::fake([GameScoreUpdated::class]);
        
        $scoreUpdate = [
            'player_id' => $player->id,
            'action_type' => 'field_goal_made',
            'points' => 2,
            'quarter' => 2,
            'time_remaining' => '08:30',
            'final_score_home' => 47,
            'final_score_away' => 42,
        ];
        
        $response = $this->patchJson("/api/v2/games/{$game->id}/score", $scoreUpdate);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'final_score_home' => 47,
            'final_score_away' => 42,
        ]);
        
        $this->assertDatabaseHas('game_actions', [
            'game_id' => $game->id,
            'player_id' => $player->id,
            'action_type' => 'field_goal_made',
            'points' => 2,
        ]);
        
        Event::assertDispatched(GameScoreUpdated::class);
    }
    
    public function test_unauthorized_user_cannot_update_game_score(): void
    {
        $game = Game::factory()->create(['status' => 'live']);
        $unauthorizedUser = User::factory()->create();
        
        $this->actingAs($unauthorizedUser);
        
        $response = $this->patchJson("/api/v2/games/{$game->id}/score", [
            'final_score_home' => 50,
        ]);
        
        $response->assertStatus(403);
    }
}
```

#### Unit Tests
```php
class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private StatisticsService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatisticsService::class);
    }
    
    public function test_calculates_player_season_stats_correctly(): void
    {
        $player = Player::factory()->create();
        $team = $player->team;
        $season = '2024-25';
        
        // Create test games and actions
        $game1 = Game::factory()->create([
            'home_team_id' => $team->id,
            'season' => $season,
            'status' => 'finished',
        ]);
        
        $game2 = Game::factory()->create([
            'away_team_id' => $team->id,
            'season' => $season,
            'status' => 'finished',
        ]);
        
        // Create game actions
        GameAction::factory()->create([
            'game_id' => $game1->id,
            'player_id' => $player->id,
            'action_type' => 'field_goal_made',
            'points' => 2,
        ]);
        
        GameAction::factory()->create([
            'game_id' => $game1->id,
            'player_id' => $player->id,
            'action_type' => 'three_point_made',
            'points' => 3,
        ]);
        
        GameAction::factory()->create([
            'game_id' => $game2->id,
            'player_id' => $player->id,
            'action_type' => 'free_throw_made',
            'points' => 1,
        ]);
        
        $stats = $this->service->getPlayerSeasonStats($player, $season);
        
        $this->assertEquals(2, $stats['games_played']);
        $this->assertEquals(6, $stats['total_points']);
        $this->assertEquals(3.0, $stats['avg_points']);
        $this->assertEquals(1, $stats['field_goals_made']);
        $this->assertEquals(1, $stats['three_points_made']);
        $this->assertEquals(1, $stats['free_throws_made']);
    }
    
    public function test_team_efficiency_calculation(): void
    {
        $team = Team::factory()->create();
        $season = '2024-25';
        
        // Mock team stats
        $this->mock(StatisticsService::class, function ($mock) use ($team, $season) {
            $mock->shouldReceive('getTeamSeasonStats')
                 ->with($team, $season)
                 ->andReturn((object) [
                     'points' => 100,
                     'rebounds' => 50,
                     'assists' => 30,
                     'steals' => 20,
                     'games_played' => 10,
                 ]);
        });
        
        $efficiency = $this->service->calculateTeamEfficiency($team, $season);
        
        $this->assertEquals(20.0, $efficiency); // (100+50+30+20)/10
    }
}
```

#### Browser Tests (Laravel Dusk)
```php
class LiveGameScoringTest extends DuskTestCase
{
    public function test_scorer_can_update_game_in_real_time(): void
    {
        $scorer = User::factory()->create();
        $scorer->assignRole('scorer');
        
        $game = Game::factory()->create(['status' => 'live']);
        $player = Player::factory()->create(['team_id' => $game->home_team_id]);
        
        // Assign scorer to game
        ScorekeeperAssignment::factory()->create([
            'game_id' => $game->id,
            'user_id' => $scorer->id,
        ]);
        
        $this->browse(function (Browser $browser) use ($scorer, $game, $player) {
            $browser->loginAs($scorer)
                    ->visit("/games/{$game->id}/live-scoring")
                    ->waitFor('@live-scoring-interface')
                    ->select('@player-select', $player->id)
                    ->select('@action-type', 'field_goal_made')
                    ->type('@points', '2')
                    ->select('@quarter', '1')
                    ->type('@time-remaining', '10:30')
                    ->click('@add-action-btn')
                    ->waitFor('@action-added-notification')
                    ->assertSee('Action added successfully');
            
            // Verify score updated in UI
            $browser->waitUntilMissing('@loading-indicator')
                    ->assertSee($game->final_score_home + 2)
                    ->assertSee($player->full_name);
        });
        
        // Verify database was updated
        $this->assertDatabaseHas('game_actions', [
            'game_id' => $game->id,
            'player_id' => $player->id,
            'action_type' => 'field_goal_made',
            'points' => 2,
        ]);
    }
}
```

#### Emergency Access Testing
```php
class EmergencyAccessTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_valid_emergency_access_key_grants_access(): void
    {
        $team = Team::factory()->create();
        $players = Player::factory()->count(3)->create(['team_id' => $team->id]);
        
        foreach ($players as $player) {
            EmergencyContact::factory()->count(2)->create(['player_id' => $player->id]);
        }
        
        $teamAccess = TeamAccess::factory()->create([
            'team_id' => $team->id,
            'purpose' => 'emergency_access',
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);
        
        $response = $this->get("/emergency/{$teamAccess->access_key}");
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.access')
                ->assertViewHas('team', $team)
                ->assertViewHas('players');
        
        // Verify usage was logged
        $teamAccess->refresh();
        $this->assertEquals(1, $teamAccess->usage_count);
        $this->assertNotNull($teamAccess->last_used_at);
    }
    
    public function test_expired_emergency_access_key_denies_access(): void
    {
        $teamAccess = TeamAccess::factory()->create([
            'purpose' => 'emergency_access',
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);
        
        $response = $this->get("/emergency/{$teamAccess->access_key}");
        
        $response->assertStatus(404);
    }
    
    public function test_emergency_access_rate_limiting(): void
    {
        $teamAccess = TeamAccess::factory()->create([
            'purpose' => 'emergency_access',
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ]);
        
        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            $this->get("/emergency/{$teamAccess->access_key}")
                 ->assertStatus(200);
        }
        
        // 11th request should be rate limited
        $response = $this->get("/emergency/{$teamAccess->access_key}");
        $response->assertStatus(429);
    }
}
```

---

## 🚀 Deployment & DevOps

### Laravel Forge Integration

#### Server Configuration
```yaml
# forge-deployment.yml
name: BasketManager Pro Deployment
server_type: app
size: 2gb
region: fra1
provider: digitalocean

php_version: "8.3"
database: mysql8
nginx: true
redis: true
supervisor: true

ssl_certificate: letsencrypt
domain: basketmanager-pro.example.com

environment_variables:
  APP_ENV: production
  APP_DEBUG: false
  DB_CONNECTION: mysql
  BROADCAST_DRIVER: pusher
  CACHE_DRIVER: redis
  QUEUE_CONNECTION: redis
  SESSION_DRIVER: redis
  
scheduled_jobs:
  - command: "php artisan basketball:cleanup-emergency-access"
    frequency: daily
    hour: 2
    minute: 0
  
  - command: "php artisan basketball:generate-daily-reports"
    frequency: daily
    hour: 6
    minute: 0
  
  - command: "php artisan horizon:snapshot"
    frequency: every_five_minutes

daemon_processes:
  - command: "php artisan horizon"
    user: forge
    numprocs: 1
    redirect_stderr: true
    stdout_logfile: /home/forge/basketmanager-pro.example.com/storage/logs/horizon.log
```

#### Deployment Script
```bash
#!/bin/bash

set -e

echo "🏀 Starting BasketManager Pro Deployment"

# Navigate to site directory
cd /home/forge/basketmanager-pro.example.com

# Put application in maintenance mode
php artisan down --message="Deploying new version..." --allow=your.ip.address

# Pull latest changes
git pull origin main

# Install/update composer dependencies
composer install --no-dev --optimize-autoloader

# Clear various caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configurations for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Restart Horizon for queue processing
php artisan horizon:terminate

# Restart PHP-FPM
sudo service php8.3-fpm restart

# Take application out of maintenance mode
php artisan up

echo "✅ Deployment completed successfully"

# Run post-deployment health checks
php artisan basketball:health-check

# Warm up caches
php artisan basketball:warm-cache

echo "🎉 BasketManager Pro is ready!"
```

### Docker Configuration

#### Multi-stage Dockerfile
```dockerfile
# Build stage
FROM php:8.3-fpm-alpine AS build

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zip \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    mysql-client \
    redis

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    xml \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Production stage
FROM php:8.3-fpm-alpine AS production

# Install runtime dependencies
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    oniguruma \
    libxml2 \
    mysql-client \
    redis \
    nginx \
    supervisor

# Install PHP extensions (same as build stage)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    xml \
    zip

RUN pecl install redis && docker-php-ext-enable redis

# Copy application from build stage
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/php.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/run/nginx /var/run/supervisord

WORKDIR /var/www/html

# Expose ports
EXPOSE 80 6001

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

#### Docker Compose for Development
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      target: build
    volumes:
      - .:/var/www/html
      - ./docker/php.ini:/usr/local/etc/php/php.ini
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - PUSHER_HOST=soketi
    depends_on:
      - mysql
      - redis
      - soketi
    
  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx-dev.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: basketmanager_pro
      MYSQL_USER: basketmanager
      MYSQL_PASSWORD: password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
  
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
  
  soketi:
    image: quay.io/soketi/soketi:latest-16-alpine
    environment:
      SOKETI_DEBUG: 1
      SOKETI_DEFAULT_APP_ID: basketmanager-app
      SOKETI_DEFAULT_APP_KEY: basketmanager-key
      SOKETI_DEFAULT_APP_SECRET: basketmanager-secret
    ports:
      - "6001:6001"
  
  horizon:
    build:
      context: .
      dockerfile: Dockerfile
      target: build
    command: php artisan horizon
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis

volumes:
  mysql_data:
  redis_data:
```

---

## 📊 Phasen-Roadmap

### Phase 1: Core Foundation (Monate 1-3)
- **Laravel 11 Setup & Authentication**
- **Basis-Models & Relationships**  
- **User, Role, Team, Player Management**
- **Basic Dashboard & Navigation**
- **Core API Endpoints**

### Phase 2: Game & Statistics (Monate 4-6)
- **Game Management System**
- **Live-Scoring Features**
- **Statistics Engine**
- **Real-time Broadcasting**
- **Basic Reporting**

### Phase 3: Advanced Features (Monate 7-9)

Phase 3 erweitert BasketManager Pro um hochentwickelte Features, die das System zu einer vollständigen Basketball-Analytics-Plattform machen. Diese Phase konzentriert sich auf KI-gestützte Analysen, Video-Integration und erweiterte Trainingstools.

#### 🏋️ Training & Drill Management System

**Kernziele:**
- Umfassendes Training- und Drill-Management mit Performance-Tracking
- Drill-Bibliothek mit 100+ vorkonfigurierten Übungen
- Video-Integration für Trainingsanalysen
- Automatische Trainingsplanung und -bewertung

**Laravel Implementation:**

```php
// Training Session Model
class TrainingSession extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'team_id', 'trainer_id', 'assistant_trainer_id', 'title',
        'description', 'scheduled_at', 'venue', 'session_type',
        'focus_areas', 'intensity_level', 'status'
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'focus_areas' => 'array',
        'goals_achieved' => 'array',
    ];
    
    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(Drill::class, 'training_drills')
                    ->withPivot(['order_in_session', 'planned_duration', 
                               'actual_duration', 'performance_notes'])
                    ->withTimestamps();
    }
}

// Drill Model mit Media Integration
class Drill extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Searchable;
    
    protected $fillable = [
        'name', 'description', 'category', 'difficulty_level',
        'min_players', 'max_players', 'estimated_duration'
    ];
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('diagrams')
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);
              
        $this->addMediaCollection('videos')
              ->acceptsMimeTypes(['video/mp4', 'video/webm']);
    }
}
```

**Training Service:**
- Automatische Trainingsplanung basierend auf Team-Analyse
- Performance-Tracking für individuelle Spieler
- Drill-Empfehlungssystem mit KI-Unterstützung
- Integration mit Wearable-Devices für Belastungsmonitoring

#### 🏆 Tournament Management System

**Kernziele:**
- Vollständige Turnierorganisation mit automatischen Brackets
- Support für alle gängigen Turnierformate (Single/Double Elimination, Round Robin)
- Live-Tournament Updates mit Broadcasting
- Automatische Seeding und Bracket-Generation

**Laravel Implementation:**

```php
// Tournament Model
class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'name', 'type', 'category', 'start_date', 'end_date',
        'max_teams', 'entry_fee', 'status', 'settings'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'settings' => 'array',
        'prizes' => 'array',
    ];
    
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
                    ->withPivot(['seed', 'status', 'final_position'])
                    ->withTimestamps();
    }
    
    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }
}

// Tournament Service für Bracket-Management
class TournamentService
{
    public function generateBrackets(Tournament $tournament): void
    {
        $teams = $tournament->approvedTeams()->get();
        
        switch ($tournament->type) {
            case 'single_elimination':
                $this->generateSingleEliminationBracket($tournament, $teams);
                break;
            case 'round_robin':
                $this->generateRoundRobinSchedule($tournament, $teams);
                break;
        }
    }
    
    public function updateBracketResults(Game $game): void
    {
        // Automatische Bracket-Updates nach Spielergebnissen
        $bracket = $game->tournamentBracket;
        $winner = $game->getWinner();
        
        $bracket->update([
            'winner_team_id' => $winner->id,
            'status' => 'completed'
        ]);
        
        // Advance winner to next round
        if ($bracket->winner_advances_to) {
            $nextBracket = $bracket->nextBracket;
            $this->advanceTeamToBracket($winner, $nextBracket);
        }
    }
}
```

**Features:**
- Automatische Bracket-Generierung mit konfigurierbaren Regeln
- Live-Tournament Dashboard mit Real-time Updates
- Prize-Management und Award-System
- Export-Funktionen für Tournament-Reports

#### 📹 Video Analysis Integration

**Kernziele:**
- KI-gestützte Video-Analyse mit Frame-Level-Annotations
- Automatische Spielererkennung und Tracking
- Shot-Analyse und Bewegungspattern-Erkennung
- Integration mit Training-System für Performance-Feedback

**Laravel Implementation:**

```php
// Video Analysis Service
class VideoAnalysisService
{
    public function __construct(
        private AIVideoProcessor $aiProcessor,
        private MediaLibrary $mediaLibrary
    ) {}
    
    public function analyzeGameVideo(Game $game, Media $video): VideoAnalysis
    {
        return DB::transaction(function () use ($game, $video) {
            // Start AI processing job
            ProcessGameVideoJob::dispatch($game, $video);
            
            return VideoAnalysis::create([
                'game_id' => $game->id,
                'video_media_id' => $video->id,
                'status' => 'processing',
                'analysis_type' => 'game_analysis'
            ]);
        });
    }
    
    public function extractPlayerActions(VideoAnalysis $analysis): Collection
    {
        $frames = $this->aiProcessor->extractFrames($analysis->video);
        $actions = collect();
        
        foreach ($frames as $frame) {
            $detectedActions = $this->aiProcessor->detectBasketballActions($frame);
            $actions->push($detectedActions);
        }
        
        return $actions;
    }
}

// Video Analysis Model
class VideoAnalysis extends Model
{
    protected $fillable = [
        'game_id', 'training_session_id', 'video_media_id',
        'status', 'analysis_results', 'ai_confidence_score'
    ];
    
    protected $casts = [
        'analysis_results' => 'array',
        'player_positions' => 'array',
        'detected_actions' => 'array',
    ];
    
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
    
    public function annotations(): HasMany
    {
        return $this->hasMany(VideoAnnotation::class);
    }
}
```

**KI-Features:**
- Automatische Spielererkennung mit Computer Vision
- Shot-Tracking und Erfolgsquoten-Analyse
- Bewegungspattern-Analyse für taktische Insights
- Highlight-Generation für wichtige Spielmomente

#### 🤖 Predictive Analytics Engine

**Kernziele:**
- Machine Learning für Performance-Vorhersagen (>85% Accuracy)
- Verletzungsrisiko-Analyse basierend auf Belastungsdaten
- Spielausgang-Prognosen mit statistischen Modellen
- Spieler-Development-Tracking mit KI-Insights

**Laravel Implementation:**

```php
// Predictive Analytics Service
class PredictiveAnalyticsService
{
    public function __construct(
        private MLModelService $mlService,
        private StatisticsService $statsService
    ) {}
    
    public function predictGameOutcome(Game $game): GamePrediction
    {
        $homeTeamStats = $this->statsService->getTeamPerformanceMetrics($game->homeTeam);
        $awayTeamStats = $this->statsService->getTeamPerformanceMetrics($game->awayTeam);
        
        $prediction = $this->mlService->predict('game_outcome', [
            'home_team_metrics' => $homeTeamStats,
            'away_team_metrics' => $awayTeamStats,
            'historical_matchups' => $this->getHistoricalMatchups($game),
            'venue_advantage' => $this->calculateVenueAdvantage($game)
        ]);
        
        return GamePrediction::create([
            'game_id' => $game->id,
            'predicted_winner' => $prediction['winner'],
            'confidence_score' => $prediction['confidence'],
            'predicted_score_home' => $prediction['score_home'],
            'predicted_score_away' => $prediction['score_away'],
            'model_version' => $prediction['model_version']
        ]);
    }
    
    public function analyzeInjuryRisk(Player $player): InjuryRiskAssessment
    {
        $playerData = [
            'recent_workload' => $this->getRecentWorkload($player),
            'injury_history' => $player->injuries()->recent()->get(),
            'performance_decline' => $this->detectPerformanceDecline($player),
            'age_factor' => $this->calculateAgeFactor($player)
        ];
        
        $riskScore = $this->mlService->predict('injury_risk', $playerData);
        
        return InjuryRiskAssessment::create([
            'player_id' => $player->id,
            'risk_score' => $riskScore['risk_level'],
            'risk_factors' => $riskScore['factors'],
            'recommendations' => $riskScore['recommendations'],
            'valid_until' => now()->addWeeks(2)
        ]);
    }
}

// ML Model Integration
class MLModelService
{
    public function predict(string $modelType, array $inputData): array
    {
        switch ($modelType) {
            case 'game_outcome':
                return $this->predictGameOutcome($inputData);
            case 'player_performance':
                return $this->predictPlayerPerformance($inputData);
            case 'injury_risk':
                return $this->assessInjuryRisk($inputData);
        }
    }
    
    private function predictGameOutcome(array $data): array
    {
        // Integration mit Python ML-Modellen via API
        $response = Http::post(config('ml.prediction_api'), [
            'model' => 'game_prediction_v2',
            'data' => $data
        ]);
        
        return $response->json();
    }
}
```

**ML-Modelle:**
- **Game Outcome Prediction**: Random Forest Modell mit 87% Accuracy
- **Player Performance Forecasting**: LSTM für Zeitreihen-Analyse
- **Injury Risk Assessment**: Gradient Boosting für Risiko-Klassifikation
- **Tactical Pattern Recognition**: Deep Learning für Spielzug-Analyse

#### 📊 Advanced Visualization & Analytics

**Kernziele:**
- Interactive Shot Charts mit Real-time Updates
- Heat Maps für Spieler-Bewegungen und Performance
- Performance Dashboard mit 50+ Metriken
- Responsive Design für Mobile-Trainer-Tools

**Laravel Implementation:**

```php
// Shot Chart Service
class ShotChartService
{
    public function generateShotChart(Player $player, ?string $season = null): array
    {
        $shots = GameAction::where('player_id', $player->id)
            ->whereIn('action_type', ['field_goal_made', 'field_goal_missed', 
                                     'three_point_made', 'three_point_missed'])
            ->when($season, fn($q) => $q->whereHas('game', 
                fn($q) => $q->where('season', $season)))
            ->with('game')
            ->get();
        
        return [
            'shot_zones' => $this->analyzeShotZones($shots),
            'accuracy_map' => $this->generateAccuracyMap($shots),
            'heat_map_data' => $this->generateHeatMapData($shots),
            'performance_trends' => $this->analyzePerformanceTrends($shots)
        ];
    }
    
    public function generateTeamHeatMap(Team $team, string $season): array
    {
        $games = $team->games()->where('season', $season)->get();
        $positions = collect();
        
        foreach ($games as $game) {
            $gamePositions = $this->extractPlayerPositions($game);
            $positions = $positions->merge($gamePositions);
        }
        
        return $this->createHeatMapVisualization($positions);
    }
}

// Performance Analytics Controller
class AnalyticsController extends Controller
{
    public function playerDashboard(Player $player): JsonResponse
    {
        $analytics = [
            'current_season_stats' => $this->statsService->getPlayerSeasonStats($player),
            'performance_trends' => $this->getPerformanceTrends($player),
            'shot_chart' => $this->shotChartService->generateShotChart($player),
            'efficiency_metrics' => $this->calculateEfficiencyMetrics($player),
            'injury_risk' => $this->predictiveAnalytics->analyzeInjuryRisk($player),
            'development_insights' => $this->getDevelopmentInsights($player)
        ];
        
        return response()->json($analytics);
    }
    
    public function teamAnalytics(Team $team, string $season): JsonResponse
    {
        return response()->json([
            'team_stats' => $this->statsService->getTeamSeasonStats($team, $season),
            'player_contributions' => $this->analyzePlayerContributions($team, $season),
            'tactical_analysis' => $this->tacticalAnalyzer->analyzeTeamPatterns($team),
            'upcoming_game_predictions' => $this->getUpcomingPredictions($team),
            'training_recommendations' => $this->getTrainingRecommendations($team)
        ]);
    }
}
```

#### 🎯 Advanced Tactical Analysis

**Features:**
- **Interactive Tactic Designer**: Drag-and-drop Spielzug-Editor
- **Pattern Recognition**: KI-basierte Erkennung von Spielmustern
- **Opponent Analysis**: Automatische Gegner-Analyse aus Video-Material
- **Game Plan Generator**: KI-gestützte Taktik-Empfehlungen

#### ⚡ Performance & Real-time Features

**Queue Jobs für Background Processing:**

```php
// Background ML Processing
class ProcessPredictiveAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $queue = 'ml-processing';
    public $timeout = 600; // 10 minutes for ML operations
    
    public function handle(PredictiveAnalyticsService $service): void
    {
        $this->processInjuryRiskAssessments();
        $this->updateGamePredictions();
        $this->generatePerformanceInsights();
    }
}

// Real-time Broadcasting
class TrainingSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function broadcastOn(): array
    {
        return [
            new Channel("training.{$this->session->id}"),
            new Channel("team.{$this->session->team_id}")
        ];
    }
}
```

#### 🧪 Testing Strategy für Phase 3

**Comprehensive Testing Approach:**

```php
// Feature Tests für Training System
class TrainingManagementTest extends TestCase
{
    public function test_trainer_can_create_training_session_with_drills(): void
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');
        $team = Team::factory()->create();
        
        $sessionData = [
            'team_id' => $team->id,
            'title' => 'Intensive Training',
            'scheduled_at' => now()->addDays(2),
            'drills' => [
                ['drill_id' => 1, 'duration' => 15, 'order' => 1],
                ['drill_id' => 2, 'duration' => 20, 'order' => 2]
            ]
        ];
        
        $response = $this->actingAs($trainer)
                         ->postJson('/api/v2/training-sessions', $sessionData);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('training_sessions', [
            'team_id' => $team->id,
            'title' => 'Intensive Training'
        ]);
    }
}

// ML Model Tests
class PredictiveAnalyticsTest extends TestCase
{
    public function test_game_prediction_accuracy(): void
    {
        $game = Game::factory()->create();
        $prediction = $this->analyticsService->predictGameOutcome($game);
        
        $this->assertInstanceOf(GamePrediction::class, $prediction);
        $this->assertGreaterThan(0.7, $prediction->confidence_score);
        $this->assertContains($prediction->predicted_winner, 
                            [$game->home_team_id, $game->away_team_id]);
    }
}
```

#### 📈 Success Metrics für Phase 3

- ✅ Training-System mit 100+ vorkonfigurierten Drills
- ✅ Tournament-Management für alle gängigen Formate  
- ✅ Video-Processing mit AI-Annotation (<2min per Video)
- ✅ ML-Modelle mit >85% Prediction-Accuracy
- ✅ Interactive Shot Charts mit Real-time Updates
- ✅ Performance Dashboard mit 50+ Metriken
- ✅ Mobile-optimierte Trainer-Tools
- ✅ API Response Times <200ms für Analytics Endpoints
- ✅ 99.9% Uptime für Real-time Features

### Phase 4: Integration & Scaling (Monate 10-12)

> **Product Requirements Document (PRD)**  
> **Version**: 1.0  
> **Datum**: 29. Juli 2025  
> **Status**: Entwurf  
> **Autor**: Claude Code Assistant  
> **Phase**: 4 von 5 (Integration & Scaling)  
> **Zeitraum**: Monate 10-12  

---

## 📋 Inhaltsverzeichnis - Phase 4

1. [Executive Summary](#phase-4-executive-summary)
2. [Projektübersicht & Kontext](#phase-4-projektübersicht--kontext)
3. [Funktionale Anforderungen](#phase-4-funktionale-anforderungen)
4. [Technische Spezifikationen](#phase-4-technische-spezifikationen)
5. [Architektur & Design](#phase-4-architektur--design)
6. [Implementierungsplan](#phase-4-implementierungsplan)
7. [Testing & Quality Assurance](#phase-4-testing--quality-assurance)
8. [Deployment & DevOps](#phase-4-deployment--devops)
9. [Risikomanagement](#phase-4-risikomanagement)
10. [Success Metrics & KPIs](#phase-4-success-metrics--kpis)
11. [Ressourcenplanung](#phase-4-ressourcenplanung)
12. [Zeitplan & Meilensteine](#phase-4-zeitplan--meilensteine)

---

## 🎯 Phase 4: Executive Summary

### Projekt-Mission

**Phase 4** markiert den kritischen Übergang von BasketManager Pro zu einer **Enterprise-ready, skalierbaren SaaS-Plattform** für Basketball-Vereine. Diese Phase verwandelt das System von einer funktionalen Anwendung zu einer vollständig integrierten, Multi-Tenant-Lösung mit umfassenden External Services und Progressive Web App Capabilities.

### Kernziele der Phase 4

1. **API Finalization & Documentation**
   - Vollständige OpenAPI 3.0 Dokumentation mit automatischer Generierung
   - Versionierte API-Architektur mit Backward Compatibility
   - Enterprise-grade Rate Limiting und Webhook-System
   - API Gateway Integration für Load Balancing

2. **External Integrations**
   - Basketball Federation APIs (DBB, FIBA) für offizielle Daten
   - Social Media Integration für Marketing und Fan-Engagement
   - Payment Gateway Integration für Membership und Tournament Fees
   - Cloud Services für Scalability und globale Content Delivery

3. **Progressive Web App (PWA) Features**
   - Service Worker für Offline-Funktionalität und intelligentes Caching
   - Push Notifications für Real-time Updates und Engagement
   - App Shell Architecture für native App-ähnliche Performance
   - Background Sync für seamless Offline-to-Online Transitions

4. **Performance Optimization**
   - Database Query Optimization mit Advanced Indexing und Partitioning
   - CDN Integration für globale Content Delivery (<100ms Ladezeiten)
   - Image Optimization mit WebP-Konvertierung und Lazy Loading
   - Memory Usage Optimization (<512MB pro Request)

5. **Multi-tenant Architecture**
   - Single Database mit Row Level Security für Tenant Isolation
   - Domain-based Tenant Resolution und Custom Branding
   - Subscription Management mit Usage-based Billing
   - Tenant-specific Feature Flags und Customization

### Business Impact & ROI

**Direkte Revenue-Generierung:**
- **SaaS Subscription Model**: €50-500/Monat pro Verein je nach Tier
- **Transaction Fees**: 2.5% auf alle Payment-Transaktionen
- **Premium Features**: Add-on Services für Advanced Analytics
- **Marketplace Commission**: 15% auf Third-party Integrations

**Projected Annual Revenue (Year 1):**
- **100 Vereine** × **€200 Average MRR** = **€240.000** ARR
- **Payment Volume**: €500.000 × **2.5%** = **€12.500**
- **Total Projected Revenue**: **€252.500**

**Cost Savings durch Automation:**
- **90% Reduktion** in Manual Support durch Self-Service APIs
- **75% weniger** Infrastructure-Kosten durch Multi-tenancy
- **85% Effizienzsteigerung** bei Deployment und Maintenance

### Strategische Vorteile

1. **Market Leadership**: Erste vollständig integrierte Basketball-Management-Plattform in DACH
2. **Scalability**: Support für 1.000+ Vereine ohne Performance-Degradation
3. **Integration Ecosystem**: Zentrale Plattform für alle Basketball-related Services
4. **Global Expansion**: Multi-language und Multi-federation Support
5. **Competitive Moat**: Umfassende Integration macht Wechsel zu Konkurrenz schwierig

### Technische Highlights

- **Laravel 11** mit Domain-Driven Design Architecture
- **Multi-tenant SaaS**: Single Codebase, Multiple Clients
- **Real-time Capabilities**: WebSocket Integration mit Broadcasting
- **Enterprise Security**: OAuth 2.0, GDPR Compliance, Audit Logging
- **High Performance**: <100ms API Response Times, 99.9% Uptime
- **Progressive Enhancement**: PWA mit Native App Experience

### Erfolgskriterien

- ✅ **API Performance**: 95% der Requests <100ms Response Time
- ✅ **Integration Success**: >99% Uptime für alle External Services
- ✅ **PWA Adoption**: >60% Mobile Users installieren die App
- ✅ **Multi-tenant Efficiency**: Tenant Onboarding <5 Minuten
- ✅ **Revenue Target**: €200.000 ARR bis Ende Phase 4
- ✅ **Customer Satisfaction**: NPS Score >70

---

## 🏀 Phase 4: Projektübersicht & Kontext

### Ausgangslage

Nach erfolgreichem Abschluss der Phasen 1-3 verfügt BasketManager Pro über:

**Phase 1 (Core Foundation)**: Solide Laravel 11 Basis mit User/Team/Player Management
**Phase 2 (Game & Statistics)**: Live-Scoring System mit Real-time Broadcasting  
**Phase 3 (Advanced Features)**: KI-gestützte Analytics, Training Management, Tournament System

### Herausforderungen der aktuellen Architektur

1. **Single-Tenant Limitation**: Jeder Verein benötigt separate Installation
2. **Integration Gaps**: Manuelle Dateneingabe ohne externe APIs
3. **Performance Bottlenecks**: Nicht optimiert für >100 concurrent Users
4. **Limited Mobile Experience**: Responsive Design aber keine Native App Features
5. **Manual Scaling**: Infrastructure-Management erfordert technische Expertise

### Vision für Phase 4

**Transformation zu einer Enterprise SaaS-Plattform**, die:

- **Vereine befähigt** durch Self-Service Onboarding und Management
- **Daten automatisiert** durch umfassende External Integrations
- **Performance maximiert** durch intelligente Caching und CDN
- **Mobile-first** Experience mit PWA-Features bietet
- **Skaliert mühelos** durch Multi-tenant Architektur

### Zielgruppen-Expansion

**Primäre Zielgruppen (Phase 4):**
- **Vereine** (50-5.000 Mitglieder): Self-Service SaaS Platform
- **Verbände** (Bezirk/Land/National): Multi-Club Management
- **Service Provider**: White-Label-Lösungen für Basketball-Dienstleister
- **Internationale Märkte**: FIBA-konforme Systeme für EU-Expansion

**Sekundäre Zielgruppen:**
- **Software-Integratoren**: API-first für Third-party Entwickler
- **Analytics-Partner**: Data-as-a-Service für Basketball Intelligence
- **Media Companies**: Content-Syndication für Basketball-Berichterstattung

### Competitive Landscape

**Direkte Konkurrenten:**
- **TeamSnap**: US-fokussiert, wenig Basketball-spezifisch
- **SportsEngine**: Microsoft-owned, complex Enterprise Focus
- **LeagueApps**: Gute UI/UX aber limitierte Basketball Features

**BasketManager Pro Differenzierung:**
1. **Basketball-spezifisch**: Deep Domain Knowledge der Sportart
2. **DACH-optimiert**: GDPR, Deutsche Vereinsstrukturen, DBB Integration
3. **Vollständig integriert**: Training + Games + Analytics + Emergency
4. **Open Integration**: API-first für Third-party Erweiterungen
5. **Community-driven**: Feedback-Loop mit Trainern und Vereinen

---

## ⚙️ Phase 4: Funktionale Anforderungen

### 1. API Finalization & Documentation

#### 1.1 OpenAPI 3.0 Dokumentation System

**Ziel**: Vollständig automatisierte, immer aktuelle API-Dokumentation für Entwickler und Integratoren.

**Kernfunktionalitäten:**

- **Automatische Schema-Generierung** aus Laravel Models und Form Requests
- **Interactive API Explorer** mit Try-it-out Funktionalität
- **Code-Generierung** für PHP, JavaScript, Python, Java SDKs
- **Postman Collection Export** für einfaches Testing
- **Versionierung** mit Changelog und Migration Guides

**Laravel Implementation:**

```php
// OpenAPI Documentation Generator
class OpenApiDocumentationService
{
    public function generateDocumentation(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'BasketManager Pro API',
                'version' => config('api.version', '4.0.0'),
                'description' => 'Enterprise Basketball Club Management API'
            ],
            'servers' => $this->getApiServers(),
            'paths' => $this->extractPathsFromRoutes(),
            'components' => [
                'schemas' => $this->generateSchemas(),
                'securitySchemes' => $this->getSecuritySchemes()
            ]
        ];
    }
    
    private function extractPathsFromRoutes(): array
    {
        $paths = [];
        $routes = Route::getRoutes();
        
        foreach ($routes as $route) {
            if (Str::startsWith($route->uri, 'api/')) {
                $paths['/' . $route->uri] = $this->extractRouteDocumentation($route);
            }
        }
        
        return $paths;
    }
    
    private function generateSchemas(): array
    {
        $schemas = [];
        $models = $this->getApiModels();
        
        foreach ($models as $model) {
            $schemas[$model] = $this->generateModelSchema($model);
        }
        
        return $schemas;
    }
    
    private function extractRouteDocumentation(Route $route): array
    {
        $controller = $route->getController();
        $method = $route->getActionMethod();
        
        // Extract documentation from controller method docblocks
        $reflection = new ReflectionMethod($controller, $method);
        $docComment = $this->parseDocComment($reflection->getDocComment());
        
        return [
            strtolower($route->methods()[0]) => [
                'summary' => $docComment['summary'] ?? 'API endpoint',
                'description' => $docComment['description'] ?? '',
                'parameters' => $this->extractParameters($route, $reflection),
                'requestBody' => $this->extractRequestBody($reflection),
                'responses' => $this->extractResponses($reflection),
                'security' => $this->extractSecurity($route),
                'tags' => [$this->extractTag($route->uri)]
            ]
        ];
    }
    
    private function generateModelSchema(string $modelClass): array
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        $hidden = $model->getHidden();
        
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];
        
        foreach ($fillable as $field) {
            if (in_array($field, $hidden)) {
                continue; // Skip hidden fields in API documentation
            }
            
            $schema['properties'][$field] = $this->getFieldSchema($field, $casts[$field] ?? 'string');
            
            // Check if field is required based on validation rules
            if ($this->isRequiredField($modelClass, $field)) {
                $schema['required'][] = $field;
            }
        }
        
        // Add computed attributes and relationships
        $schema['properties'] = array_merge(
            $schema['properties'],
            $this->extractComputedAttributes($model),
            $this->extractRelationships($model)
        );
        
        return $schema;
    }
    
    private function getFieldSchema(string $field, string $cast): array
    {
        return match($cast) {
            'integer', 'int' => ['type' => 'integer', 'format' => 'int64'],
            'float', 'double', 'real' => ['type' => 'number', 'format' => 'float'],
            'boolean', 'bool' => ['type' => 'boolean'],
            'array', 'json' => ['type' => 'array', 'items' => ['type' => 'object']],
            'datetime', 'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'encrypted' => ['type' => 'string', 'description' => 'Encrypted field - write-only'],
            default => ['type' => 'string']
        };
    }
    
    public function generateApiGatewayConfig(): array
    {
        return [
            'rate_limiting' => [
                'default' => '1000/hour',
                'burst' => '100/minute',
                'by_tier' => [
                    'free' => '100/hour',
                    'basic' => '1000/hour', 
                    'professional' => '10000/hour',
                    'enterprise' => '100000/hour'
                ]
            ],
            'caching' => [
                'enabled' => true,
                'ttl' => 300, // 5 minutes
                'vary_by' => ['Accept-Version', 'Authorization'],
                'exclude_paths' => ['/api/v*/auth/*', '/api/v*/webhooks/*']
            ],
            'monitoring' => [
                'log_requests' => true,
                'track_performance' => true,
                'alert_on_errors' => true,
                'metrics' => ['response_time', 'error_rate', 'throughput']
            ]
        ];
    }
}
```

#### 1.2 API Versioning & Backward Compatibility

**Ziel**: Nahtlose API-Evolution ohne Breaking Changes für bestehende Integrationen.

**Versioning Strategy:**

- **Semantic Versioning**: Major.Minor.Patch (4.0.0)
- **Header-based Versioning**: `Accept-Version: 4.0`
- **URL-based Fallback**: `/api/v4/teams`
- **Graceful Deprecation**: 12-Monate Notice für Breaking Changes

**Laravel Implementation:**

```php
// API Versioning Middleware
class ApiVersionMiddleware
{
    public function handle($request, Closure $next, ...$versions)
    {
        $requestedVersion = $request->header('Accept-Version', config('api.default_version'));
        
        if (!in_array($requestedVersion, $versions)) {
            return response()->json([
                'error' => 'Unsupported API version',
                'supported_versions' => $versions,
                'requested_version' => $requestedVersion
            ], 400);
        }
        
        // Set version context for controllers
        app()->instance('api.version', $requestedVersion);
        
        return $next($request);
    }
}

// Backward Compatibility Service
class BackwardCompatibilityService
{
    public function transformRequest(Request $request, string $fromVersion, string $toVersion): Request
    {
        $transformations = config("api.transformations.{$fromVersion}_to_{$toVersion}");
        
        $transformedData = $request->all();
        
        foreach ($transformations['request'] ?? [] as $field => $transformation) {
            if (isset($transformedData[$field])) {
                $transformedData = $this->applyTransformation($transformedData, $field, $transformation);
            }
        }
        
        return new Request($transformedData);
    
    private function applyTransformation(array $data, string $field, array $transformation): array
    {
        switch ($transformation['type']) {
            case 'rename':
                $data[$transformation['to']] = $data[$field];
                unset($data[$field]);
                break;
                
            case 'type_cast':
                $data[$field] = $this->castValue($data[$field], $transformation['to_type']);
                break;
                
            case 'nested_transform':
                if (is_array($data[$field])) {
                    $data[$field] = $this->transformNestedData($data[$field], $transformation['rules']);
                }
                break;
                
            case 'remove':
                unset($data[$field]);
                break;
        }
        
        return $data;
    }
    
    public function transformResponse(array $response, string $fromVersion, string $toVersion): array
    {
        $transformations = config("api.transformations.{$fromVersion}_to_{$toVersion}");
        
        foreach ($transformations['response'] ?? [] as $field => $transformation) {
            if (isset($response[$field])) {
                $response = $this->applyTransformation($response, $field, $transformation);
            }
        }
        
        return $response;
    }
}

// API Gateway Integration für Load Balancing
class ApiGatewayService
{
    public function __construct(
        private LoadBalancerService $loadBalancer,
        private CacheManager $cache,
        private MetricsCollector $metrics
    ) {}
    
    public function routeRequest(Request $request): Response
    {
        $version = $request->header('Accept-Version', config('api.default_version'));
        $route = $request->getPathInfo();
        
        // Check cached response first
        $cacheKey = $this->generateCacheKey($request);
        if ($cachedResponse = $this->cache->get($cacheKey)) {
            $this->metrics->increment('api.cache.hit');
            return $cachedResponse;
        }
        
        // Route to appropriate backend
        $backend = $this->loadBalancer->selectBackend($route, $version);
        $response = $this->forwardRequest($request, $backend);
        
        // Cache successful responses
        if ($response->isSuccessful() && $this->shouldCache($request)) {
            $this->cache->put($cacheKey, $response, config('api.cache_ttl', 300));
        }
        
        $this->metrics->recordResponseTime($request->getPathInfo(), $response->getTime());
        
        return $response;
    }
    
    private function generateCacheKey(Request $request): string
    {
        $key = sprintf(
            'api:%s:%s:%s',
            $request->method(),
            $request->getPathInfo(),
            md5($request->getQueryString() ?? '')
        );
        
        // Include version and user context in cache key
        $context = [
            'version' => $request->header('Accept-Version'),
            'user_id' => $request->user()?->id,
            'tenant_id' => $request->user()?->tenant_id
        ];
        
        return $key . ':' . md5(json_encode($context));
    }
}
```

#### 1.3 Enterprise Rate Limiting

**Ziel**: Fair Usage Policy und DoS-Schutz mit differenzierten Limits für verschiedene Subscription Tiers.

**Rate Limiting Matrix:**

| Tier | Requests/Hour | Burst Limit | Concurrent Connections |
|------|---------------|-------------|----------------------|
| Free | 1.000 | 100/min | 5 |
| Basic | 5.000 | 500/min | 25 |
| Professional | 25.000 | 2.500/min | 100 |
| Enterprise | 100.000 | 10.000/min | 500 |

**Laravel Implementation:**

```php
// Enterprise Rate Limiting Service
class EnterpriseRateLimitService
{
    public function checkRateLimit(Request $request): RateLimitResult
    {
        $user = $request->user();
        $limits = $this->getLimitsForUser($user);
        
        // Multiple Rate Limit Windows
        $checks = [
            'per_second' => $this->checkWindow($user, 1, $limits['per_second']),
            'per_minute' => $this->checkWindow($user, 60, $limits['per_minute']),
            'per_hour' => $this->checkWindow($user, 3600, $limits['per_hour']),
            'per_day' => $this->checkWindow($user, 86400, $limits['per_day'])
        ];
        
        return new RateLimitResult($checks);
    }
    
    private function getLimitsForUser(?User $user): array
    {
        if (!$user) {
            return config('api.rate_limits.anonymous');
        }
        
        $tenant = $user->tenant;
        return config("api.rate_limits.{$tenant->subscription_tier}");
    }
    
    private function checkWindow(User $user, int $windowSeconds, int $limit): array
    {
        $key = "rate_limit:{$user->id}:{$windowSeconds}";
        $current = Redis::get($key) ?: 0;
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'current' => $current,
                'reset_at' => now()->addSeconds($windowSeconds),
                'retry_after' => $windowSeconds
            ];
        }
        
        // Increment counter with sliding window
        $pipeline = Redis::pipeline();
        $pipeline->incr($key);
        $pipeline->expire($key, $windowSeconds);
        $pipeline->execute();
        
        return [
            'allowed' => true,
            'limit' => $limit,
            'current' => $current + 1,
            'remaining' => $limit - ($current + 1),
            'reset_at' => now()->addSeconds($windowSeconds)
        ];
    }
    
    public function handleRateLimitExceeded(Request $request, RateLimitResult $result): JsonResponse
    {
        // Log rate limit violation for security monitoring  
        Log::warning('Rate limit exceeded', [
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'endpoint' => $request->getPathInfo(),
            'limit_info' => $result->toArray()
        ]);
        
        // Temporary IP blocking for aggressive rate limiting violations
        if ($this->isAggressiveViolation($request, $result)) {
            $this->temporarilyBlockIP($request->ip());
        }
        
        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please slow down.',
            'retry_after' => $result->getRetryAfter(),
            'limit' => $result->getLimit(),
            'reset_at' => $result->getResetAt()->toISOString()
        ], 429);
    }
    
    private function isAggressiveViolation(Request $request, RateLimitResult $result): bool
    {
        // Define aggressive violation as >150% of limit in short timeframe
        return $result->getCurrentUsage() > ($result->getLimit() * 1.5);
    }
    
    public function getAdaptiveRateLimit(User $user, string $endpoint): array
    {
        // Implement adaptive rate limiting based on user behavior
        $baseLimit = $this->getLimitsForUser($user);
        $userPattern = $this->analyzeUserPattern($user, $endpoint);
        
        // Adjust limits based on user behavior
        if ($userPattern['good_citizen']) {
            $baseLimit['per_hour'] = (int) ($baseLimit['per_hour'] * 1.2);
        } elseif ($userPattern['suspicious']) {
            $baseLimit['per_hour'] = (int) ($baseLimit['per_hour'] * 0.5);
        }
        
        return $baseLimit;
    }
}

// Advanced Rate Limiting Middleware
class SmartRateLimitMiddleware
{
    public function handle($request, Closure $next, ...$parameters)
    {
        $rateLimiter = app(EnterpriseRateLimitService::class);
        $result = $rateLimiter->checkRateLimit($request);
        
        if (!$result->isAllowed()) {
            return $rateLimiter->handleRateLimitExceeded($request, $result);
        }
        
        $response = $next($request);
        
        // Add rate limit headers to response
        return $response->withHeaders([
            'X-RateLimit-Limit' => $result->getLimit(),
            'X-RateLimit-Remaining' => $result->getRemaining(),
            'X-RateLimit-Reset' => $result->getResetAt()->timestamp,
            'X-RateLimit-Used' => $result->getCurrentUsage()
        ]);
    }
}
```

#### 1.4 Webhook System

**Ziel**: Real-time Event Notifications für externe Systeme und Third-party Integrationen.

**Supported Events:**

- `game.started`, `game.score_updated`, `game.finished`
- `player.created`, `player.updated`, `player.transferred`
- `team.created`, `team.updated`, `team.season_ended`
- `tournament.created`, `tournament.bracket_updated`
- `training.scheduled`, `training.completed`

**Laravel Implementation:**

```php
// Webhook Delivery Service
class WebhookDeliveryService
{
    public function deliverWebhook(WebhookSubscription $subscription, Event $event): void
    {
        $payload = [
            'id' => Str::uuid(),
            'event' => $event->getName(),
            'data' => $event->getPayload(),
            'timestamp' => now()->toISOString(),
            'api_version' => '4.0'
        ];
        
        $signature = $this->generateSignature($payload, $subscription->secret);
        
        Http::timeout(30)
            ->withHeaders([
                'X-Webhook-Event' => $event->getName(),
                'X-Webhook-Signature-256' => $signature,
                'User-Agent' => 'BasketManager-Webhook/4.0'
            ])
            ->retry(3, 100)
            ->post($subscription->url, $payload);
    }
    
    private function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}

// Webhook Job with Advanced Retry Logic
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 5;
    public $backoff = [60, 300, 900, 3600, 7200]; // Progressive backoff
    
    public function handle(WebhookDeliveryService $service): void
    {
        $response = Http::post($this->subscription->url, $this->payload);
        
        if (!$response->successful()) {
            $this->subscription->increment('failed_deliveries');
            
            if ($this->subscription->failed_deliveries > 10) {
                $this->subscription->update(['is_active' => false]);
                Log::warning('Webhook subscription disabled due to failures', [
                    'subscription_id' => $this->subscription->id
                ]);
            }
            
            throw new WebhookDeliveryException("HTTP {$response->status()}");
        }
        
        $this->subscription->update([
            'successful_deliveries' => $this->subscription->successful_deliveries + 1,
            'last_successful_delivery' => now(),
            'failed_deliveries' => 0 // Reset failed counter on success
        ]);
    }
    
    public function failed(Throwable $exception): void
    {
        Log::error('Webhook delivery failed', [
            'subscription_id' => $this->subscription->id,
            'url' => $this->subscription->url,
            'attempt' => $this->attempts(),
            'exception' => $exception->getMessage(),
            'payload' => $this->payload
        ]);
        
        // Update subscription with failure info
        $this->subscription->increment('failed_deliveries');
        $this->subscription->update(['last_failure_at' => now()]);
        
        // Auto-disable after too many failures
        if ($this->subscription->failed_deliveries >= 25) {
            $this->subscription->update([
                'is_active' => false,
                'disabled_reason' => 'Too many delivery failures',
                'disabled_at' => now()
            ]);
            
            // Notify subscription owner
            NotifyWebhookSubscriptionDisabled::dispatch($this->subscription);
        }
    }
}

// Intelligent Webhook Delivery with Circuit Breaker
class IntelligentWebhookDeliveryService extends WebhookDeliveryService
{
    private CircuitBreakerService $circuitBreaker;
    
    public function deliverWebhook(WebhookSubscription $subscription, Event $event): void
    {
        $circuitKey = "webhook_circuit:{$subscription->id}";
        
        // Check circuit breaker state
        if ($this->circuitBreaker->isOpen($circuitKey)) {
            Log::info('Webhook delivery skipped - circuit breaker open', [
                'subscription_id' => $subscription->id
            ]);
            return;
        }
        
        try {
            $this->attemptDelivery($subscription, $event);
            $this->circuitBreaker->recordSuccess($circuitKey);
        } catch (WebhookDeliveryException $e) {
            $this->circuitBreaker->recordFailure($circuitKey);
            throw $e;
        }
    }
    
    private function attemptDelivery(WebhookSubscription $subscription, Event $event): void
    {
        $payload = $this->buildPayload($event, $subscription);
        $signature = $this->generateSignature($payload, $subscription->secret);
        
        $response = Http::timeout($subscription->timeout ?? 30)
            ->withHeaders($this->buildHeaders($event, $signature))
            ->retry(3, function ($exception, $request) {
                // Custom retry logic - retry on 5xx but not 4xx
                if ($exception instanceof RequestException) {
                    $statusCode = $exception->response?->status();
                    return $statusCode >= 500;
                }
                return true;
            }, 100)
            ->post($subscription->url, $payload);
            
        if (!$response->successful()) {
            throw new WebhookDeliveryException(
                "HTTP {$response->status()}: {$response->body()}"
            );
        }
        
        $this->recordSuccessfulDelivery($subscription, $response);
    }
    
    private function buildPayload(Event $event, WebhookSubscription $subscription): array
    {
        $payload = [
            'id' => Str::uuid(),
            'event' => $event->getName(),
            'data' => $event->getPayload(),
            'timestamp' => now()->toISOString(),
            'api_version' => config('api.version', '4.0'),
            'subscription_id' => $subscription->id
        ];
        
        // Add event-specific metadata
        if ($event instanceof GameEvent) {
            $payload['meta'] = [
                'game_id' => $event->game->id,
                'teams' => [
                    'home' => $event->game->homeTeam->name,
                    'away' => $event->game->awayTeam->name
                ]
            ];
        }
        
        return $payload;
    }
    
    private function buildHeaders(Event $event, string $signature): array
    {
        return [
            'Content-Type' => 'application/json',
            'User-Agent' => 'BasketManager-Webhook/4.0',
            'X-Webhook-Event' => $event->getName(),
            'X-Webhook-Signature-256' => $signature,
            'X-Webhook-Delivery' => Str::uuid(),
            'X-Webhook-Timestamp' => now()->timestamp
        ];
    }
}

// Webhook Subscription Management
class WebhookSubscriptionService
{
    public function createSubscription(array $data): WebhookSubscription
    {
        $subscription = WebhookSubscription::create([
            'tenant_id' => $data['tenant_id'],
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => $data['secret'] ?? Str::random(64),
            'is_active' => true,
            'timeout' => $data['timeout'] ?? 30,
            'retry_config' => $data['retry_config'] ?? [
                'max_attempts' => 5,
                'backoff_strategy' => 'exponential'
            ]
        ]);
        
        // Verify webhook endpoint
        $this->verifyWebhookEndpoint($subscription);
        
        return $subscription;
    }
    
    private function verifyWebhookEndpoint(WebhookSubscription $subscription): void
    {
        $verificationPayload = [
            'type' => 'webhook.verification',
            'challenge' => Str::random(32),
            'timestamp' => now()->toISOString()
        ];
        
        $signature = hash_hmac('sha256', json_encode($verificationPayload), $subscription->secret);
        
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Event' => 'webhook.verification',
                    'X-Webhook-Signature-256' => $signature
                ])
                ->post($subscription->url, $verificationPayload);
                
            if ($response->successful() && $response->json('challenge') === $verificationPayload['challenge']) {
                $subscription->update(['verified_at' => now()]);
            } else {
                $subscription->update(['verification_failed_at' => now()]);
            }
        } catch (Exception $e) {
            Log::warning('Webhook verification failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            $subscription->update(['verification_failed_at' => now()]);
        }
    }
}
```

### 2. External Integrations

#### 2.1 Basketball Federation APIs

**Ziel**: Automatische Synchronisation mit offiziellen Basketball-Verbänden für nahtlose Datenintegration.

**Unterstützte Verbände:**

- **DBB (Deutscher Basketball Bund)**: Offizielle Spiele, Ergebnisse, Tabellen
- **FIBA Europe**: Internationale Turniere und Rankings
- **Landesverbände**: Regionale Liga-Daten und Schiedsrichter-Zuordnungen

**Laravel Implementation:**

```php
// DBB Integration Service
class DBBIntegrationService implements FederationIntegrationInterface
{
    public function syncOfficialGameData(Game $game): FederationSyncResult
    {
        $dbbResponse = $this->fetchGameFromDBB($game->external_game_id);
        
        if ($dbbResponse->successful()) {
            $officialData = $dbbResponse->json();
            
            return DB::transaction(function () use ($game, $officialData) {
                // Update Game with Official Data
                $game->update([
                    'final_score_home' => $officialData['score']['home'],
                    'final_score_away' => $officialData['score']['away'],
                    'status' => $this->mapDBBStatus($officialData['status']),
                    'officials' => $officialData['officials'],
                    'last_dbb_sync' => now()
                ]);
                
                // Sync Player Statistics
                $this->syncPlayerStatistics($game, $officialData['player_stats']);
                
                // Validate Data Consistency
                $this->validateAgainstLocalData($game, $officialData);
                
                return FederationSyncResult::success($officialData);
            });
        }
        
        return FederationSyncResult::failure($dbbResponse->body());
    }
    
    public function submitGameResults(Game $game): bool
    {
        if (!$game->isOfficialGame()) {
            return false;
        }
        
        $gameData = [
            'game_id' => $game->external_game_id,
            'home_team' => $game->homeTeam->external_id,
            'away_team' => $game->awayTeam->external_id,
            'final_score' => [
                'home' => $game->final_score_home,
                'away' => $game->final_score_away
            ],
            'period_scores' => $game->period_scores,
            'player_statistics' => $this->formatPlayerStats($game),
            'game_events' => $this->formatGameEvents($game),
            'officials' => $game->officials,
            'venue' => $game->venue,
            'completed_at' => $game->completed_at->toISOString()
        ];
        
        $response = Http::withToken(config('services.dbb.api_key'))
            ->post("https://api.basketball-bund.de/v2/games/{$game->external_game_id}/results", $gameData);
            
        if ($response->successful()) {
            $game->update(['dbb_submission_status' => 'submitted']);
            return true;
        }
        
        Log::error('DBB submission failed', [
            'game_id' => $game->id,
            'error' => $response->body()
        ]);
        
        return false;
    }
}
```

#### 2.2 Social Media Integration

**Ziel**: Automatisierte Content-Distribution für Marketing und Fan-Engagement.

**Unterstützte Plattformen:**

- **Facebook**: Game Highlights, Team Updates, Event Promotion
- **Instagram**: Visual Content, Stories, Live Game Updates
- **Twitter**: Real-time Score Updates, Breaking News
- **TikTok**: Short-form Video Content, Player Highlights
- **YouTube**: Full Game Recordings, Training Videos

**Laravel Implementation:**

```php
// Advanced Social Media Service
class SocialMediaAutomationService
{
    public function postGameHighlight(Game $game, Media $highlightVideo): SocialMediaResult
    {
        $results = [];
        $socialSettings = $game->homeTeam->social_media_settings;
        
        // Facebook Video Post
        if ($socialSettings['facebook_enabled']) {
            $results['facebook'] = $this->postToFacebook($game, $highlightVideo);
        }
        
        // Instagram Reel
        if ($socialSettings['instagram_enabled']) {
            $results['instagram'] = $this->postToInstagram($game, $highlightVideo);
        }
        
        // Twitter with GIF
        if ($socialSettings['twitter_enabled']) {
            $gifPreview = $this->generateGifPreview($highlightVideo);
            $results['twitter'] = $this->postToTwitter($game, $gifPreview);
        }
        
        // TikTok Short Video
        if ($socialSettings['tiktok_enabled']) {
            $shortClip = $this->generateTikTokClip($highlightVideo);  
            $results['tiktok'] = $this->postToTikTok($game, $shortClip);
        }
        
        // YouTube Shorts
        if ($socialSettings['youtube_enabled']) {
            $youtubeShort = $this->generateYouTubeShort($highlightVideo);
            $results['youtube'] = $this->postToYouTube($game, $youtubeShort);
        }
        
        return new SocialMediaResult($results);
    }
    
    private function postToTikTok(Game $game, Media $shortClip): TikTokPostResult
    {
        $tiktokApi = new TikTokBusinessApi([
            'app_id' => config('services.tiktok.app_id'),
            'app_secret' => config('services.tiktok.app_secret')
        ]);
        
        $hashtags = $this->generateTikTokHashtags($game);
        $description = $this->generateTikTokDescription($game) . ' ' . implode(' ', $hashtags);
        
        try {
            // Upload video to TikTok
            $uploadResponse = $tiktokApi->uploadVideo([
                'video_file' => $shortClip->getPath(),
                'description' => Str::limit($description, 150),
                'privacy_level' => 'PUBLIC_FEED',
                'disable_duet' => false,
                'disable_comment' => false,
                'disable_stitch' => false,
                'brand_content_toggle' => true
            ], $game->homeTeam->tiktok_access_token);
            
            return TikTokPostResult::success($uploadResponse['video_id']);
            
        } catch (TikTokApiException $e) {
            Log::error('TikTok posting failed', [
                'game_id' => $game->id,
                'error' => $e->getMessage()
            ]);
            return TikTokPostResult::failure($e->getMessage());
        }
    }
    
    private function postToYouTube(Game $game, Media $video): YouTubePostResult
    {
        $youtube = new Google_Service_YouTube(new Google_Client([
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'access_token' => $game->homeTeam->youtube_access_token
        ]));
        
        $videoMetadata = new Google_Service_YouTube_Video();
        $videoMetadata->setSnippet(new Google_Service_YouTube_VideoSnippet([
            'title' => $this->generateYouTubeTitle($game),
            'description' => $this->generateYouTubeDescription($game),
            'tags' => $this->generateYouTubeTags($game),
            'categoryId' => '17', // Sports category
            'defaultLanguage' => 'de',
            'defaultAudioLanguage' => 'de'
        ]));
        
        $videoMetadata->setStatus(new Google_Service_YouTube_VideoStatus([
            'privacyStatus' => $game->homeTeam->social_settings['youtube_privacy'] ?? 'public',
            'embeddable' => true,
            'license' => 'youtube'
        ]));
        
        try {
            $response = $youtube->videos->insert('snippet,status', $videoMetadata, [
                'data' => file_get_contents($video->getPath()),
                'mimeType' => $video->mime_type,
                'uploadType' => 'multipart'
            ]);
            
            return YouTubePostResult::success($response->getId());
            
        } catch (Google_Service_Exception $e) {
            Log::error('YouTube posting failed', [
                'game_id' => $game->id,
                'error' => $e->getMessage()
            ]);
            return YouTubePostResult::failure($e->getMessage());
        }
    }
    
    private function generateTikTokClip(Media $originalVideo): Media
    {
        // Create 15-60 second clip optimized for TikTok (9:16 aspect ratio)
        return ProcessVideoJob::dispatchSync($originalVideo, [
            'duration' => rand(15, 60),
            'aspect_ratio' => '9:16',
            'resolution' => '1080x1920',
            'format' => 'mp4',
            'add_captions' => true,
            'add_music' => config('services.tiktok.default_music_enabled', false),
            'filters' => ['brightness', 'contrast', 'saturation'],
            'thumbnail_time' => 'auto' // Auto-select best frame
        ]);
    }
    
    private function generateYouTubeShort(Media $originalVideo): Media
    {
        // Create YouTube Shorts (max 60 seconds, 9:16 aspect ratio)
        return ProcessVideoJob::dispatchSync($originalVideo, [
            'duration' => min(60, $originalVideo->duration),
            'aspect_ratio' => '9:16',
            'resolution' => '1080x1920',
            'format' => 'mp4',
            'add_intro_outro' => true,
            'add_team_branding' => true,
            'optimize_for_mobile' => true
        ]);
    }
    
    private function generateTikTokHashtags(Game $game): array
    {
        $baseHashtags = ['#basketball', '#sport', '#highlights'];
        $teamHashtags = [
            '#' . Str::slug($game->homeTeam->name),
            '#' . Str::slug($game->awayTeam->name)
        ];
        $contextualHashtags = [];
        
        // Add contextual hashtags based on game context
        if ($game->game_type === 'playoff') {
            $contextualHashtags[] = '#playoffs';
        }
        if ($game->isLocalDerby()) {
            $contextualHashtags[] = '#localderby';
        }
        
        return array_merge($baseHashtags, $teamHashtags, $contextualHashtags);
    }
    
    private function postToFacebook(Game $game, Media $video): FacebookPostResult
    {
        $facebook = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v18.0'
        ]);
        
        $message = $this->generateGameMessage($game);
        
        try {
            $response = $facebook->post(
                "/{$game->homeTeam->facebook_page_id}/videos",
                [
                    'source' => $facebook->videoToUpload($video->getPath()),
                    'description' => $message,
                    'published' => true
                ],
                $game->homeTeam->facebook_access_token
            );
            
            return FacebookPostResult::success($response->getGraphNode()['id']);
        } catch (FacebookResponseException $e) {
            return FacebookPostResult::failure($e->getMessage());
        }
    }
    
    public function scheduleAutomatedPosts(Team $team): void
    {
        // Schedule game day posts
        $upcomingGames = $team->games()
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<', now()->addDays(7))
            ->get();
            
        foreach ($upcomingGames as $game) {
            // Pre-game announcement (24h before)
            ScheduledSocialMediaPost::create([
                'team_id' => $team->id,
                'game_id' => $game->id,
                'platform' => 'all',
                'post_type' => 'pre_game_announcement',
                'scheduled_at' => $game->scheduled_at->subDay(),
                'content' => $this->generatePreGameContent($game)
            ]);
            
            // Game day reminder (2h before)
            ScheduledSocialMediaPost::create([
                'team_id' => $team->id,
                'game_id' => $game->id,
                'platform' => 'twitter,facebook',
                'post_type' => 'game_day_reminder',
                'scheduled_at' => $game->scheduled_at->subHours(2),
                'content' => $this->generateGameDayContent($game)
            ]);
        }
    }
}
```

#### 2.3 Payment Gateway Integration

**Ziel**: Nahtlose Payment-Abwicklung für Vereinsbeiträge, Tournament-Gebühren und Merchandise.

**Unterstützte Payment-Methoden:**

- **Stripe**: Kreditkarten, SEPA Lastschrift, Apple Pay, Google Pay
- **PayPal**: PayPal-Wallet, PayPal Credit
- **Klarna**: Buy Now Pay Later, Installments
- **SEPA Direct Debit**: Für wiederkehrende Vereinsbeiträge
- **Sofort/Giropay**: Deutsche Bank-to-Bank Transfers

**Laravel Implementation:**

```php
// Multi-Gateway Payment Service
class MultiGatewayPaymentService
{
    public function processPayment(PaymentRequest $request): PaymentResult
    {
        $gateway = $this->selectOptimalGateway($request);
        
        return match($gateway) {
            'stripe' => $this->processStripePayment($request),
            'paypal' => $this->processPayPalPayment($request),
            'klarna' => $this->processKlarnaPayment($request),
            'sepa' => $this->processSEPAPayment($request),
            default => throw new UnsupportedPaymentMethodException($gateway)
        };
    }
    
    private function selectOptimalGateway(PaymentRequest $request): string
    {
        // Dynamic Gateway Selection based on:
        // - User's country/locale
        // - Payment amount and type
        // - Historical success rates
        // - Cost optimization
        
        $selectionCriteria = [
            'country' => $request->getBillingCountry(),
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'payment_type' => $request->getPaymentType(), // one-time, recurring, installment
            'user_preference' => $request->getUser()?->preferred_payment_method
        ];
        
        return $this->gatewaySelector->selectBestGateway($selectionCriteria);
    }
    
    public function processStripePayment(PaymentRequest $request): PaymentResult
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $request->getAmountInCents(),
                'currency' => strtolower($request->getCurrency()),
                'customer' => $this->getOrCreateStripeCustomer($request->getUser()),
                'payment_method_types' => ['card', 'sepa_debit', 'giropay'],
                'metadata' => [
                    'team_id' => $request->getTeam()?->id,
                    'tournament_id' => $request->getTournament()?->id,
                    'payment_type' => $request->getPaymentType()
                ],
                'automatic_payment_methods' => ['enabled' => true]
            ]);
            
            // Store Payment Record
            $payment = Payment::create([
                'user_id' => $request->getUser()->id,
                'team_id' => $request->getTeam()?->id,
                'amount' => $request->getAmount(),
                'currency' => $request->getCurrency(),
                'gateway' => 'stripe',
                'gateway_payment_id' => $paymentIntent->id,
                'status' => 'pending',
                'metadata' => $request->getMetadata()
            ]);
            
            return PaymentResult::success([
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'next_action' => $paymentIntent->next_action
            ]);
            
        } catch (StripeException $e) {
            Log::error('Stripe payment failed', [
                'user_id' => $request->getUser()->id,
                'amount' => $request->getAmount(),
                'error' => $e->getMessage()
            ]);
            
            return PaymentResult::failure($e->getMessage());
        }
    }
    
    public function processRecurringPayment(Team $team, float $amount, string $description): PaymentResult
    {
        // For subscription payments
        $subscription = $team->subscription;
        
        if (!$subscription || !$subscription->payment_method_id) {
            return PaymentResult::failure('No payment method on file');
        }
        
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100,
                'currency' => $subscription->currency,
                'customer' => $team->stripe_customer_id,
                'payment_method' => $subscription->payment_method_id,
                'confirm' => true,
                'description' => $description,
                'metadata' => [
                    'team_id' => $team->id,
                    'subscription_id' => $subscription->id,
                    'payment_type' => 'recurring'
                ]
            ]);
            
            return PaymentResult::success(['payment_intent_id' => $paymentIntent->id]);
            
        } catch (StripeException $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }
    
    public function processPayPalPayment(PaymentRequest $request): PaymentResult
    {
        $paypal = new PayPalHttpClient(new SandboxEnvironment(
            config('services.paypal.client_id'),
            config('services.paypal.client_secret')
        ));
        
        $orderRequest = new OrdersCreateRequest();
        $orderRequest->prefer('return=representation');
        $orderRequest->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $request->getCurrency(),
                    'value' => number_format($request->getAmount(), 2, '.', '')
                ],
                'description' => $request->getDescription(),
                'custom_id' => "team_{$request->getTeam()?->id}"
            ]],
            'application_context' => [
                'brand_name' => 'BasketManager Pro',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => route('payments.paypal.success'),
                'cancel_url' => route('payments.paypal.cancel')
            ]
        ];
        
        try {
            $response = $paypal->execute($orderRequest);
            
            $payment = Payment::create([
                'user_id' => $request->getUser()->id,
                'team_id' => $request->getTeam()?->id,
                'amount' => $request->getAmount(),
                'currency' => $request->getCurrency(),
                'gateway' => 'paypal',
                'gateway_payment_id' => $response->result->id,
                'status' => 'pending',
                'metadata' => $request->getMetadata()
            ]);
            
            $approvalLink = collect($response->result->links)
                ->firstWhere('rel', 'approve')
                ->href;
            
            return PaymentResult::success([
                'payment_id' => $payment->id,
                'approval_url' => $approvalLink,
                'paypal_order_id' => $response->result->id
            ]);
            
        } catch (HttpException $e) {
            Log::error('PayPal payment failed', [
                'user_id' => $request->getUser()->id,
                'error' => $e->getMessage()
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }
    
    private function selectOptimalGateway(PaymentRequest $request): string
    {
        $selector = app(PaymentGatewaySelector::class);
        return $selector->selectBestGateway($request);
    }
}

// Intelligent Payment Gateway Selector
class PaymentGatewaySelector
{
    public function __construct(
        private PaymentAnalyticsService $analytics,
        private GatewayPerformanceTracker $performanceTracker
    ) {}
    
    public function selectBestGateway(PaymentRequest $request): string
    {
        $criteria = $this->buildSelectionCriteria($request);
        $availableGateways = $this->getAvailableGateways($criteria);
        
        // Score each gateway
        $scores = [];
        foreach ($availableGateways as $gateway) {
            $scores[$gateway] = $this->calculateGatewayScore($gateway, $criteria);
        }
        
        // Select highest scoring gateway
        arsort($scores);
        $selectedGateway = array_key_first($scores);
        
        // Log selection for analytics
        $this->logGatewaySelection($selectedGateway, $criteria, $scores);
        
        return $selectedGateway;
    }
    
    private function buildSelectionCriteria(PaymentRequest $request): array
    {
        return [
            'country' => $request->getBillingCountry(),
            'amount' => $request->getAmount(),
            'currency' => $request->getCurrency(),
            'payment_type' => $request->getPaymentType(),
            'user_preference' => $request->getUser()?->preferred_payment_method,
            'device_type' => $request->getDeviceType(),
            'time_of_day' => now()->hour,
            'user_tier' => $request->getUser()?->subscription_tier,
            'historical_success' => $this->getUserPaymentHistory($request->getUser())
        ];
    }
    
    private function calculateGatewayScore(string $gateway, array $criteria): float
    {
        $baseScore = 50;
        
        // Success rate (40% weight)
        $successRate = $this->performanceTracker->getSuccessRate($gateway, $criteria);
        $successScore = $successRate * 40;
        
        // Cost optimization (25% weight)
        $cost = $this->getGatewayCost($gateway, $criteria['amount']);
        $costScore = max(0, 25 - ($cost * 100)); // Lower cost = higher score
        
        // Speed (20% weight)
        $avgProcessingTime = $this->performanceTracker->getAverageProcessingTime($gateway);
        $speedScore = max(0, 20 - ($avgProcessingTime / 1000)); // Faster = higher score
        
        // User preference (10% weight)
        $preferenceScore = ($criteria['user_preference'] === $gateway) ? 10 : 0;
        
        // Regional optimization (5% weight)
        $regionalScore = $this->getRegionalScore($gateway, $criteria['country']);
        
        return $baseScore + $successScore + $costScore + $speedScore + $preferenceScore + $regionalScore;
    }
    
    private function getGatewayCost(string $gateway, float $amount): float
    {
        $costs = config("payments.gateway_costs.{$gateway}");
        
        return ($costs['fixed'] ?? 0) + ($amount * ($costs['percentage'] ?? 0));
    }
    
    public function handlePaymentFailure(Payment $payment, string $reason): void
    {
        // Update payment status
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now()
        ]);
        
        // Try alternative gateway if available
        if ($payment->retry_count < 2 && $this->shouldRetryWithDifferentGateway($reason)) {
            $alternativeGateway = $this->getAlternativeGateway($payment->gateway, $payment);
            
            if ($alternativeGateway) {
                RetryPaymentJob::dispatch($payment, $alternativeGateway)
                    ->delay(now()->addMinutes(5));
            }
        }
        
        // Notify user of payment failure
        PaymentFailedNotification::dispatch($payment);
        
        // Update gateway performance metrics
        $this->performanceTracker->recordFailure($payment->gateway, $reason);
    }
    
    private function shouldRetryWithDifferentGateway(string $reason): bool
    {
        $retryableReasons = [
            'gateway_timeout',
            'temporary_unavailable',
            'rate_limit_exceeded',
            'processing_error'
        ];
        
        return in_array($reason, $retryableReasons);
    }
    
    private function getAlternativeGateway(string $failedGateway, Payment $payment): ?string
    {
        $alternatives = [
            'stripe' => ['paypal', 'klarna'],
            'paypal' => ['stripe', 'sofort'],
            'klarna' => ['stripe', 'paypal'],
            'sofort' => ['stripe', 'giropay']
        ];
        
        $possibleAlternatives = $alternatives[$failedGateway] ?? [];
        
        // Filter by availability and user context
        return collect($possibleAlternatives)
            ->filter(fn($gateway) => $this->isGatewayAvailable($gateway, $payment))
            ->first();
    }
}

// Payment Fraud Detection Service
class PaymentFraudDetectionService
{
    public function analyzeTransaction(PaymentRequest $request): FraudAnalysisResult
    {
        $riskScore = 0;
        $riskFactors = [];
        
        // IP address analysis
        $ipRisk = $this->analyzeIPAddress($request->getIPAddress());
        $riskScore += $ipRisk['score'];
        if ($ipRisk['score'] > 20) {
            $riskFactors[] = $ipRisk['reason'];
        }
        
        // User behavior analysis
        $behaviorRisk = $this->analyzeUserBehavior($request->getUser());
        $riskScore += $behaviorRisk['score'];
        if ($behaviorRisk['score'] > 15) {
            $riskFactors[] = $behaviorRisk['reason'];
        }
        
        // Amount analysis
        $amountRisk = $this->analyzeAmount($request->getAmount(), $request->getUser());
        $riskScore += $amountRisk['score'];
        if ($amountRisk['score'] > 10) {
            $riskFactors[] = $amountRisk['reason'];
        }
        
        // Device fingerprinting
        $deviceRisk = $this->analyzeDevice($request->getDeviceFingerprint());
        $riskScore += $deviceRisk['score'];
        if ($deviceRisk['score'] > 15) {
            $riskFactors[] = $deviceRisk['reason'];
        }
        
        return new FraudAnalysisResult([
            'risk_score' => $riskScore,
            'risk_level' => $this->calculateRiskLevel($riskScore),
            'risk_factors' => $riskFactors,
            'action' => $this->determineAction($riskScore),
            'additional_verification_required' => $riskScore > 50
        ]);
    }
    
    private function calculateRiskLevel(int $score): string
    {
        return match(true) {
            $score < 20 => 'low',
            $score < 50 => 'medium',
            $score < 80 => 'high',
            default => 'very_high'
        };
    }
    
    private function determineAction(int $score): string
    {
        return match(true) {
            $score < 30 => 'approve',
            $score < 60 => 'review',
            default => 'decline'
        };
    }
}
```

#### 2.4 Cloud Services Integration

**Ziel**: Skalierbare, globale Infrastructure für Media Storage, CDN und Advanced Services.

**Cloud Provider Strategy:**

- **Primary**: AWS (S3, CloudFront, Lambda, SES)
- **Backup**: Google Cloud (Cloud Storage, Cloud Functions)
- **CDN**: Cloudflare für globale Performance
- **Media Processing**: AWS MediaConvert für Video-Transcoding

**Laravel Implementation:**

```php
// Cloud Storage Service
class CloudStorageService
{
    public function storeGameVideo(Game $game, UploadedFile $video): Media
    {
        // Store original in S3
        $s3Path = $this->storeInS3($video, "games/{$game->id}/videos");
        
        // Create media record
        $media = $game->addMediaFromDisk($s3Path, 's3')
                     ->toMediaCollection('game_videos');
        
        // Queue for processing
        ProcessVideoJob::dispatch($media, [
            'resolutions' => ['1080p', '720p', '480p'],
            'formats' => ['mp4', 'webm'],
            'generate_thumbnails' => true,
            'create_preview_gif' => true
        ]);
        
        return $media;
    }
    
    private function storeInS3(UploadedFile $file, string $prefix): string
    {
        $filename = $this->generateSecureFilename($file);
        $path = "{$prefix}/{$filename}";
        
        $s3Path = Storage::disk('s3')->putFileAs(
            dirname($path),
            $file,
            basename($path),
            [
                'visibility' => 'private',
                'metadata' => [
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType()
                ]
            ]
        );
        
        return $s3Path;
    }
    
    public function generateCDNUrl(Media $media, array $transformations = []): string
    {
        $baseUrl = config('services.cloudflare.cdn_url');
        $path = $media->getPath();
        
        // Apply Cloudflare Image Transformations
        if (!empty($transformations) && $media->mime_type && str_starts_with($media->mime_type, 'image/')) {
            $transforms = [];
            
            if (isset($transformations['width'])) {
                $transforms[] = "w={$transformations['width']}";
            }
            if (isset($transformations['height'])) {
                $transforms[] = "h={$transformations['height']}";
            }
            if (isset($transformations['quality'])) {
                $transforms[] = "q={$transformations['quality']}";
            }
            if (isset($transformations['format'])) {
                $transforms[] = "f={$transformations['format']}";
            }
            
            $transformString = implode(',', $transforms);
            return "{$baseUrl}/cdn-cgi/image/{$transformString}/{$path}";
        }
        
        return "{$baseUrl}/{$path}";
    }
    
    public function processVideoWithAI(Media $video): array
    {
        // Use AWS Rekognition for video analysis
        $rekognition = new RekognitionClient([
            'region' => config('services.aws.region', 'eu-central-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret')
            ]
        ]);
        
        try {
            // Start video analysis job
            $result = $rekognition->startLabelDetection([
                'Video' => [
                    'S3Object' => [
                        'Bucket' => config('services.aws.s3.bucket'),
                        'Name' => $video->getPath()
                    ]
                ],
                'MinConfidence' => 75,
                'JobTag' => "basketball_analysis_{$video->id}"
            ]);
            
            // Queue job to check results
            ProcessVideoAnalysisJob::dispatch($video, $result['JobId'])
                ->delay(now()->addMinutes(2));
                
            return [
                'job_id' => $result['JobId'],
                'status' => 'processing',
                'estimated_completion' => now()->addMinutes(5)
            ];
            
        } catch (AwsException $e) {
            Log::error('AWS Rekognition failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);
            throw new VideoProcessingException('AI analysis failed: ' . $e->getMessage());
        }
    }
    
    public function generateThumbnails(Media $video, array $timestamps = []): Collection
    {
        $lambda = new LambdaClient([
            'region' => config('services.aws.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret')
            ]
        ]);
        
        // Use AWS Lambda for thumbnail generation
        $payload = [
            'source_bucket' => config('services.aws.s3.bucket'),
            'source_key' => $video->getPath(),
            'output_bucket' => config('services.aws.s3.thumbnails_bucket'),
            'timestamps' => $timestamps ?: [5, 15, 30, 60], // Default timestamps
            'dimensions' => [
                ['width' => 320, 'height' => 180],   // Small
                ['width' => 640, 'height' => 360],   // Medium  
                ['width' => 1280, 'height' => 720]   // Large
            ]
        ];
        
        try {
            $result = $lambda->invoke([
                'FunctionName' => 'basketball-thumbnail-generator',
                'Payload' => json_encode($payload)
            ]);
            
            $response = json_decode($result['Payload']->getContents(), true);
            
            if ($response['statusCode'] === 200) {
                return collect($response['thumbnails'])->map(function ($thumb) use ($video) {
                    return $video->addMediaFromUrl($thumb['url'])
                                ->toMediaCollection('thumbnails');
                });
            }
            
            throw new VideoProcessingException('Thumbnail generation failed');
            
        } catch (AwsException $e) {
            Log::error('AWS Lambda thumbnail generation failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);
            throw new VideoProcessingException('Thumbnail generation failed: ' . $e->getMessage());
        }
    }
}

// Multi-Cloud Storage Strategy
class MultiCloudStorageService extends CloudStorageService
{
    private array $providers;
    
    public function __construct()
    {
        $this->providers = [
            'primary' => 'aws',
            'backup' => 'gcp',
            'cdn' => 'cloudflare'
        ];
    }
    
    public function storeWithRedundancy(UploadedFile $file, string $path): array
    {
        $results = [];
        
        // Store in primary (AWS S3)
        try {
            $results['primary'] = $this->storeInAWS($file, $path);
        } catch (Exception $e) {
            Log::error('Primary storage (AWS) failed', ['error' => $e->getMessage()]);
            $results['primary'] = null;
        }
        
        // Store in backup (Google Cloud Storage)
        try {
            $results['backup'] = $this->storeInGCP($file, $path);
        } catch (Exception $e) {
            Log::error('Backup storage (GCP) failed', ['error' => $e->getMessage()]);
            $results['backup'] = null;
        }
        
        // At least one storage must succeed
        if (!$results['primary'] && !$results['backup']) {
            throw new StorageException('All storage providers failed');
        }
        
        return $results;
    }
    
    private function storeInGCP(UploadedFile $file, string $path): string
    {
        $storage = new StorageClient([
            'projectId' => config('services.gcp.project_id'),
            'keyFile' => json_decode(config('services.gcp.key_file'), true)
        ]);
        
        $bucket = $storage->bucket(config('services.gcp.storage.bucket'));
        
        $object = $bucket->upload(
            fopen($file->getPathname(), 'r'),
            [
                'name' => $path,
                'metadata' => [
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'content_type' => $file->getMimeType()
                ]
            ]
        );
        
        return $object->name();
    }
    
    public function getOptimalDownloadUrl(string $path, array $options = []): string
    {
        $userLocation = $this->getUserLocation();
        
        // Select optimal CDN based on user location
        $cdnRegion = $this->selectOptimalCDNRegion($userLocation);
        
        return match($cdnRegion) {
            'eu' => config('services.cloudflare.eu_cdn_url') . '/' . $path,
            'us' => config('services.cloudflare.us_cdn_url') . '/' . $path,
            'asia' => config('services.cloudflare.asia_cdn_url') . '/' . $path,
            default => config('services.cloudflare.global_cdn_url') . '/' . $path
        };
    }
    
    private function selectOptimalCDNRegion(?string $userLocation): string
    {
        if (!$userLocation) {
            return 'global';
        }
        
        $regionMap = [
            'DE' => 'eu', 'FR' => 'eu', 'IT' => 'eu', 'ES' => 'eu', 'GB' => 'eu',
            'US' => 'us', 'CA' => 'us', 'MX' => 'us',
            'JP' => 'asia', 'CN' => 'asia', 'KR' => 'asia', 'AU' => 'asia'
        ];
        
        return $regionMap[$userLocation] ?? 'global';
    }
}

// Advanced Media Processing Pipeline
class MediaProcessingPipeline
{
    public function __construct(
        private CloudStorageService $storage,
        private VideoTranscodingService $transcoding,
        private AIAnalysisService $aiAnalysis
    ) {}
    
    public function processGameVideo(Game $game, UploadedFile $video): ProcessingResult
    {
        $processingId = Str::uuid();
        
        // Create processing job record
        $job = MediaProcessingJob::create([
            'id' => $processingId,
            'game_id' => $game->id,
            'original_filename' => $video->getClientOriginalName(),
            'status' => 'starting',
            'steps' => [
                'upload' => 'pending',
                'transcode' => 'pending', 
                'thumbnail_generation' => 'pending',
                'ai_analysis' => 'pending',
                'cdn_distribution' => 'pending'
            ]
        ]);
        
        // Start processing pipeline
        ProcessGameVideoJob::dispatch($job, $video);
        
        return new ProcessingResult([
            'processing_id' => $processingId,
            'estimated_completion' => now()->addMinutes(10),
            'webhook_url' => route('api.media.processing.webhook', $processingId)
        ]);
    }
    
    public function generateGameHighlights(Game $game): Collection
    {
        $gameVideos = $game->getMedia('game_videos');
        $highlights = collect();
        
        foreach ($gameVideos as $video) {
            // Use AI to identify exciting moments
            $analysis = $this->aiAnalysis->analyzeGameVideo($video);
            $excitingMoments = $analysis->getExcitingMoments();
            
            foreach ($excitingMoments as $moment) {
                $highlight = $this->transcoding->extractClip($video, [
                    'start_time' => $moment['start_time'],
                    'duration' => min(30, $moment['duration']),
                    'quality' => 'high',
                    'add_slow_motion' => $moment['type'] === 'dunk' || $moment['type'] === 'block',
                    'add_effects' => true
                ]);
                
                $highlights->push($highlight);
            }
        }
        
        return $highlights;
    }
}

// Global CDN Management
class GlobalCDNManager
{
    public function invalidateCache(array $paths): array
    {
        $results = [];
        
        // Cloudflare cache purge
        $results['cloudflare'] = $this->purgeCloudflareCache($paths);
        
        // AWS CloudFront invalidation
        $results['cloudfront'] = $this->invalidateCloudFront($paths);
        
        return $results;
    }
    
    private function purgeCloudflareCache(array $paths): bool
    {
        $cloudflare = new CloudflareAPI(
            config('services.cloudflare.email'),
            config('services.cloudflare.api_key')
        );
        
        try {
            $response = $cloudflare->zones->purge_cache(
                config('services.cloudflare.zone_id'),
                ['files' => $paths]
            );
            
            return $response->success;
            
        } catch (Exception $e) {
            Log::error('Cloudflare cache purge failed', [
                'paths' => $paths,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function invalidateCloudFront(array $paths): bool
    {
        $cloudfront = new CloudFrontClient([
            'region' => 'us-east-1', // CloudFront is global but uses us-east-1
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret')
            ]
        ]);
        
        try {
            $result = $cloudfront->createInvalidation([
                'DistributionId' => config('services.aws.cloudfront.distribution_id'),
                'InvalidationBatch' => [
                    'Paths' => [
                        'Quantity' => count($paths),
                        'Items' => $paths
                    ],
                    'CallerReference' => 'invalidation-' . time()
                ]
            ]);
            
            return isset($result['Invalidation']['Id']);
            
        } catch (AwsException $e) {
            Log::error('CloudFront invalidation failed', [
                'paths' => $paths,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
```

### 3. Progressive Web App (PWA) Features

#### 3.1 Service Worker Implementation

**Ziel**: Offline-Funktionalität und intelligentes Caching für native App-ähnliche Performance.

**Caching Strategy:**

- **App Shell**: Navigation, Layout, Critical CSS/JS (Cache First)
- **API Data**: Game Scores, Player Stats (Network First with Fallback)
- **Media**: Images, Videos (Cache First mit Lazy Loading)
- **User Content**: Forms, Notes (Background Sync bei Offline)

**Advanced Service Worker:**

```javascript
// Service Worker Implementation
const CACHE_NAME = 'basketmanager-v4.0.0';
const RUNTIME_CACHE = 'basketmanager-runtime';

// App Shell - Always cached
const APP_SHELL_URLS = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192x192.png',
    '/offline.html'
];

// Install Event - Cache App Shell
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(APP_SHELL_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate Event - Clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE)
                        .map(cacheName => caches.delete(cacheName))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch Event - Intelligent Caching Strategy
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // API Requests - Network First with Cache Fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirstStrategy(request));
    }
    // Static Assets - Cache First
    else if (request.destination === 'image' || request.destination === 'script' || request.destination === 'style') {
        event.respondWith(cacheFirstStrategy(request));
    }
    // HTML Pages - Stale While Revalidate
    else if (request.destination === 'document') {
        event.respondWith(staleWhileRevalidateStrategy(request));
    }
});

// Network First Strategy for API calls
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Offline - Data not available', { status: 503 });
    }
}

// Background Sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(handleBackgroundSync());
    } else if (event.tag === 'score-update') {
        event.waitUntil(syncOfflineScoreUpdates());
    }
});

async function handleBackgroundSync() {
    try {
        const offlineActions = await getOfflineActions();
        
        for (const action of offlineActions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method,
                    headers: action.headers,
                    body: action.body
                });
                
                if (response.ok) {
                    await removeOfflineAction(action.id);
                    
                    // Notify UI of successful sync
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'BACKGROUND_SYNC_SUCCESS',
                                action: action.type,
                                id: action.id
                            });
                        });
                    });
                }
            } catch (error) {
                console.error('Background sync failed for action:', action.id, error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Advanced Offline Strategy with Intelligent Syncing
async function syncOfflineScoreUpdates() {
    try {
        const pendingScoreUpdates = await getStoredScoreUpdates();
        
        for (const update of pendingScoreUpdates) {
            try {
                // Validate update is still relevant
                if (await isUpdateStillRelevant(update)) {
                    const response = await fetch(`/api/games/${update.gameId}/score`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${update.token}`,
                            'X-Offline-Sync': 'true'
                        },
                        body: JSON.stringify(update.data)
                    });
                    
                    if (response.ok) {
                        await removeStoredScoreUpdate(update.id);
                        
                        // Notify UI of successful sync with optimistic update reversal protection
                        self.clients.matchAll().then(clients => {
                            clients.forEach(client => {
                                client.postMessage({
                                    type: 'SCORE_UPDATE_SYNCED',
                                    gameId: update.gameId,
                                    data: update.data,
                                    timestamp: Date.now()
                                });
                            });
                        });
                    } else if (response.status === 409) {
                        // Conflict - server has newer data
                        await handleSyncConflict(update, response);
                    }
                } else {
                    // Update is no longer relevant, remove it
                    await removeStoredScoreUpdate(update.id);
                }
            } catch (error) {
                console.error('Score update sync failed:', update.id, error);
                await markUpdateAsFailed(update.id, error.message);
            }
        }
    } catch (error) {
        console.error('Score update sync process failed:', error);
    }
}

// Handle sync conflicts intelligently
async function handleSyncConflict(localUpdate, serverResponse) {
    const serverData = await serverResponse.json();
    
    // Show conflict resolution UI to user
    self.clients.matchAll().then(clients => {
        clients.forEach(client => {
            client.postMessage({
                type: 'SYNC_CONFLICT',
                localData: localUpdate.data,
                serverData: serverData,
                gameId: localUpdate.gameId,
                conflictId: localUpdate.id
            });
        });
    });
}

// Intelligent cache strategy based on user behavior
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Enhanced caching strategy
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(intelligentApiStrategy(request));
    } else if (request.destination === 'image') {
        event.respondWith(smartImageStrategy(request));
    } else if (request.destination === 'document') {
        event.respondWith(adaptiveDocumentStrategy(request));
    } else if (request.destination === 'script' || request.destination === 'style') {
        event.respondWith(assetStrategy(request));
    }
});

async function intelligentApiStrategy(request) {
    const url = new URL(request.url);
    const cache = await caches.open(RUNTIME_CACHE);
    
    // Determine caching strategy based on endpoint
    if (url.pathname.includes('/live') || url.pathname.includes('/score')) {
        // Live data - Network first with very short cache
        return networkFirstWithTimeout(request, 2000);
    } else if (url.pathname.includes('/statistics') || url.pathname.includes('/players')) {
        // Statistics - Cache first with background update
        return cacheFirstWithBackgroundUpdate(request);
    } else {
        // Default - Network first with cache fallback
        return networkFirstStrategy(request);
    }
}

async function networkFirstWithTimeout(request, timeout = 3000) {
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        const networkResponse = await fetch(request, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (networkResponse.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Timeout or network error - fall back to cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Add header to indicate stale data
            const response = cachedResponse.clone();
            response.headers.set('X-Served-From-Cache', 'true');
            return response;
        }
        
        return new Response(JSON.stringify({
            error: 'Network timeout',
            message: 'Unable to fetch fresh data',
            cached: false
        }), {
            status: 408,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

async function cacheFirstWithBackgroundUpdate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        // Serve from cache immediately
        
        // Update in background if cache is older than 5 minutes
        const cachedDate = new Date(cachedResponse.headers.get('date'));
        const isStale = (Date.now() - cachedDate.getTime()) > 300000; // 5 minutes
        
        if (isStale) {
            // Background update without waiting
            fetch(request).then(networkResponse => {
                if (networkResponse.ok) {
                    cache.put(request, networkResponse.clone());
                }
            }).catch(error => {
                console.log('Background update failed:', error);
            });
        }
        
        return cachedResponse;
    } else {
        // No cached version - fetch from network
        try {
            const networkResponse = await fetch(request);
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        } catch (error) {
            return new Response(JSON.stringify({
                error: 'Network error',
                message: 'Unable to fetch data'
            }), {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            });
        }
    }
}

// Predictive caching based on user patterns
async function predictiveCache() {
    try {
        // Get user's favorite teams and recent activity
        const userPreferences = await getUserPreferences();
        
        if (userPreferences.favoriteTeams) {
            // Pre-cache upcoming games for favorite teams
            for (const teamId of userPreferences.favoriteTeams) {
                const upcomingGamesUrl = `/api/teams/${teamId}/games?upcoming=true`;
                
                try {
                    const response = await fetch(upcomingGamesUrl);
                    if (response.ok) {
                        const cache = await caches.open(RUNTIME_CACHE);
                        cache.put(upcomingGamesUrl, response.clone());
                    }
                } catch (error) {
                    console.log('Predictive caching failed for team:', teamId);
                }
            }
        }
        
        // Pre-cache frequently accessed player statistics
        if (userPreferences.frequentlyViewedPlayers) {
            for (const playerId of userPreferences.frequentlyViewedPlayers) {
                const playerStatsUrl = `/api/players/${playerId}/statistics`;
                
                try {
                    const response = await fetch(playerStatsUrl);
                    if (response.ok) {
                        const cache = await caches.open(RUNTIME_CACHE);
                        cache.put(playerStatsUrl, response.clone());
                    }
                } catch (error) {
                    console.log('Predictive caching failed for player:', playerId);
                }
            }
        }
    } catch (error) {
        console.error('Predictive caching failed:', error);
    }
}

// Schedule predictive caching during idle time
self.addEventListener('message', event => {
    if (event.data.type === 'SCHEDULE_PREDICTIVE_CACHE') {
        // Schedule during next idle period
        setTimeout(predictiveCache, 5000);
    }
});
```

#### 3.2 Push Notifications System

**Ziel**: Real-time Engagement durch intelligente, kontextuelle Push Notifications.

**Notification Categories:**

- **Game Updates**: Score changes, game start/end, important plays
- **Team News**: Player transfers, training changes, announcements
- **Personal**: Individual stats milestones, training reminders
- **Emergency**: Urgent club communications, game cancellations
- **Marketing**: Tournament registrations, special offers (opt-in)

**Laravel Implementation:**

```php
// Smart Push Notification Service
class SmartPushNotificationService
{
    public function sendGameScoreUpdate(Game $game, GameAction $lastAction): void
    {
        $relevantUsers = $this->getGameInterestedUsers($game);
        
        foreach ($relevantUsers as $user) {
            $personalizedMessage = $this->personalizeMessage($user, $game, $lastAction);
            
            $notification = [
                'title' => '🏀 ' . $this->getGameTitle($game),
                'body' => $personalizedMessage,
                'icon' => '/images/icons/basketball-notification.png',
                'badge' => '/images/icons/badge-72x72.png',
                'tag' => "game-{$game->id}",
                'renotify' => true,
                'data' => [
                    'game_id' => $game->id,
                    'action' => 'view_live_game',
                    'url' => "/games/{$game->id}/live",
                    'timestamp' => now()->toISOString()
                ],
                'actions' => [
                    [
                        'action' => 'view',
                        'title' => 'Live verfolgen',
                        'icon' => '/images/icons/eye.png'
                    ],
                    [
                        'action' => 'share',
                        'title' => 'Teilen',
                        'icon' => '/images/icons/share.png'
                    ]
                ]
            ];
            
            // Intelligent Timing - Don't spam users
            if (!$this->shouldSendNotification($user, $game)) {
                continue;
            }
            
            SendPushNotificationJob::dispatch($user, $notification)
                ->delay($this->calculateOptimalDelay($user));
        }
    }
    
    private function personalizeMessage(User $user, Game $game, GameAction $action): string
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;
        
        // User's preferred team
        $userTeam = $user->favoriteTeam;
        $isUserTeamPlaying = $userTeam && ($userTeam->id === $homeTeam->id || $userTeam->id === $awayTeam->id);
        
        if ($isUserTeamPlaying) {
            $userTeamScore = $userTeam->id === $homeTeam->id ? $game->final_score_home : $game->final_score_away;
            $opponentTeamScore = $userTeam->id === $homeTeam->id ? $game->final_score_away : $game->final_score_home;
            $opponentTeam = $userTeam->id === $homeTeam->id ? $awayTeam : $homeTeam;
            
            if ($userTeamScore > $opponentTeamScore) {
                return "{$userTeam->name} führt {$userTeamScore}:{$opponentTeamScore} gegen {$opponentTeam->name}!";
            } else if ($userTeamScore < $opponentTeamScore) {
                return "{$userTeam->name} liegt {$userTeamScore}:{$opponentTeamScore} zurück gegen {$opponentTeam->name}";
            } else {
                return "Unentschieden! {$userTeam->name} {$userTeamScore}:{$opponentTeamScore} {$opponentTeam->name}";
            }
        }
        
        // Generic message for non-involved users
        return "{$homeTeam->name} {$game->final_score_home}:{$game->final_score_away} {$awayTeam->name}";
    }
    
    private function shouldSendNotification(User $user, Game $game): bool
    {
        $settings = $user->notification_settings;
        
        // Respect user preferences
        if (!$settings['push_enabled'] || !$settings['game_updates']) {
            return false;
        }
        
        // Rate limiting - max 1 notification per game per 5 minutes
        $lastNotification = $user->pushNotifications()
            ->where('data->game_id', $game->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
            
        if ($lastNotification) {
            return false;
        }
        
        // Time-based rules (don't disturb at night)
        $userTimezone = $user->timezone ?? 'Europe/Berlin';
        $localTime = now()->setTimezone($userTimezone);
        
        if ($localTime->hour < 7 || $localTime->hour > 22) {
            return false;
        }
        
        return true;
    }
    
    public function sendPersonalizedTrainingReminder(User $user, TrainingSession $training): void
    {
        // Analyze user's training attendance pattern
        $attendancePattern = $this->analyzeAttendancePattern($user);
        
        $notification = [
            'title' => $this->generateTrainingTitle($training, $attendancePattern),
            'body' => $this->generateTrainingMessage($user, $training, $attendancePattern),
            'icon' => '/images/icons/training-notification.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => "training-{$training->id}",
            'data' => [
                'training_id' => $training->id,
                'action' => 'view_training',
                'url' => "/training/{$training->id}",
                'can_confirm' => true
            ],
            'actions' => [
                [
                    'action' => 'confirm',
                    'title' => 'Zusagen',
                    'icon' => '/images/icons/check.png'
                ],
                [
                    'action' => 'decline',
                    'title' => 'Absagen',
                    'icon' => '/images/icons/x.png'
                ],
                [
                    'action' => 'view',
                    'title' => 'Details',
                    'icon' => '/images/icons/eye.png'
                ]
            ],
            'requireInteraction' => true,
            'silent' => false
        ];
        
        // Optimal timing based on user's typical activity
        $optimalTime = $this->calculateOptimalNotificationTime($user, 'training_reminder');
        
        SendPushNotificationJob::dispatch($user, $notification)
            ->delay($optimalTime);
    }
    
    public function sendAdaptivePlayerMilestone(Player $player, array $milestoneData): void
    {
        $interestedUsers = $this->getPlayerInterestedUsers($player);
        
        foreach ($interestedUsers as $user) {
            $relationship = $this->getUserPlayerRelationship($user, $player);
            
            $notification = [
                'title' => $this->generateMilestoneTitle($player, $milestoneData, $relationship),
                'body' => $this->generateMilestoneMessage($player, $milestoneData, $relationship),
                'icon' => '/images/icons/achievement-notification.png',
                'badge' => '/images/icons/badge-72x72.png',
                'tag' => "milestone-{$player->id}-{$milestoneData['type']}",
                'data' => [
                    'player_id' => $player->id,
                    'milestone_type' => $milestoneData['type'],
                    'action' => 'view_player_stats',
                    'url' => "/players/{$player->id}/statistics"
                ],
                'actions' => [
                    [
                        'action' => 'congratulate',
                        'title' => 'Gratulieren',
                        'icon' => '/images/icons/heart.png'
                    ],
                    [
                        'action' => 'share',
                        'title' => 'Teilen',
                        'icon' => '/images/icons/share.png'
                    ]
                ]
            ];
            
            // Send immediately for important milestones
            if ($milestoneData['importance'] === 'high') {
                SendPushNotificationJob::dispatch($user, $notification);
            } else {
                // Batch with other notifications for less important milestones
                BatchPushNotificationJob::dispatch($user, $notification)
                    ->delay(now()->addMinutes(15));
            }
        }
    }
    
    private function analyzeAttendancePattern(User $user): array
    {
        $player = $user->player;
        if (!$player) {
            return ['type' => 'unknown', 'reliability' => 0.5];
        }
        
        $recentAttendance = $player->trainingAttendance()
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();
            
        $attendanceRate = $recentAttendance->where('status', 'present')->count() / 
                         max($recentAttendance->count(), 1);
        
        $pattern = [
            'reliability' => $attendanceRate,
            'total_sessions' => $recentAttendance->count(),
            'last_attendance' => $recentAttendance->where('status', 'present')->first()?->created_at,
            'preferred_days' => $this->getPreferredTrainingDays($recentAttendance),
            'typical_response_time' => $this->getTypicalResponseTime($player)
        ];
        
        // Classify attendance pattern
        if ($attendanceRate > 0.9) {
            $pattern['type'] = 'highly_reliable';
        } elseif ($attendanceRate > 0.7) {
            $pattern['type'] = 'reliable';
        } elseif ($attendanceRate > 0.5) {
            $pattern['type'] = 'moderate';
        } else {
            $pattern['type'] = 'unreliable';
        }
        
        return $pattern;
    }
    
    private function generateTrainingMessage(User $user, TrainingSession $training, array $pattern): string
    {
        $baseMessage = "Training am {$training->scheduled_at->format('d.m.Y H:i')} - {$training->venue}";
        
        return match($pattern['type']) {
            'highly_reliable' => $baseMessage . " Wir freuen uns auf dich! 💪",
            'reliable' => $baseMessage . " Bist du dabei?",
            'moderate' => $baseMessage . " Deine Teilnahme würde uns freuen! 🏀",
            'unreliable' => $baseMessage . " Das Team braucht dich! Schaffst du es?",
            default => $baseMessage
        };
    }
    
    private function calculateOptimalNotificationTime(User $user, string $type): Carbon
    {
        $userActivity = $this->getUserActivityPattern($user);
        $notificationPrefs = $user->notification_preferences[$type] ?? [];
        
        // Default timing
        $defaultTiming = match($type) {
            'training_reminder' => now()->addHours(24),
            'game_reminder' => now()->addHours(2),
            'milestone' => now(),
            default => now()->addMinutes(5)
        };
        
        // Adjust based on user's active hours
        $optimalHour = $userActivity['most_active_hour'] ?? 18;
        $optimalTime = $defaultTiming->setHour($optimalHour);
        
        // Don't send notifications too late or too early
        if ($optimalTime->hour < 8) {
            $optimalTime->setHour(8);
        } elseif ($optimalTime->hour > 21) {
            $optimalTime->setHour(21);
        }
        
        // Avoid user's quiet hours
        $quietHours = $notificationPrefs['quiet_hours'] ?? [];
        if (in_array($optimalTime->hour, $quietHours)) {
            $optimalTime->addHours(1);
        }
        
        return $optimalTime;
    }
    
    public function handleNotificationInteraction(string $action, array $data, User $user): void
    {
        switch ($action) {
            case 'confirm':
                if (isset($data['training_id'])) {
                    $this->confirmTrainingAttendance($user, $data['training_id']);
                }
                break;
                
            case 'decline':
                if (isset($data['training_id'])) {
                    $this->declineTrainingAttendance($user, $data['training_id']);
                }
                break;
                
            case 'congratulate':
                if (isset($data['player_id'])) {
                    $this->sendCongratulations($user, $data['player_id'], $data['milestone_type']);
                }
                break;
                
            case 'share':
                $this->generateShareContent($data, $user);
                break;
        }
        
        // Track interaction for analytics
        PushNotificationInteraction::create([
            'user_id' => $user->id,
            'action' => $action,
            'data' => $data,
            'timestamp' => now()
        ]);
    }
    
    private function confirmTrainingAttendance(User $user, int $trainingId): void
    {
        $training = TrainingSession::find($trainingId);
        if (!$training || !$user->player) return;
        
        TrainingAttendance::updateOrCreate([
            'training_session_id' => $trainingId,
            'player_id' => $user->player->id
        ], [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmation_method' => 'push_notification'
        ]);
        
        // Send confirmation to coach
        $coach = $training->trainer;
        if ($coach) {
            $this->sendCoachNotification($coach, $user->player, $training, 'confirmed');
        }
    }
    
    private function sendCoachNotification(User $coach, Player $player, TrainingSession $training, string $status): void
    {
        $message = match($status) {
            'confirmed' => "{$player->full_name} hat für das Training am {$training->scheduled_at->format('d.m H:i')} zugesagt",
            'declined' => "{$player->full_name} hat für das Training am {$training->scheduled_at->format('d.m H:i')} abgesagt",
            default => "{$player->full_name} - Training {$training->scheduled_at->format('d.m H:i')}: {$status}"
        };
        
        $notification = [
            'title' => 'Training-Rückmeldung',
            'body' => $message,
            'icon' => '/images/icons/coach-notification.png',
            'data' => [
                'training_id' => $training->id,
                'player_id' => $player->id,
                'action' => 'view_training_attendance'
            ]
        ];
        
        SendPushNotificationJob::dispatch($coach, $notification);
    }
}

// Advanced Notification Analytics
class PushNotificationAnalytics
{
    public function trackDelivery(User $user, array $notification, bool $successful): void
    {
        PushNotificationLog::create([
            'user_id' => $user->id,
            'notification_type' => $notification['data']['type'] ?? 'unknown',
            'title' => $notification['title'],
            'delivered' => $successful,
            'device_type' => $this->detectDeviceType($user),
            'sent_at' => now()
        ]);
    }
    
    public function analyzeEngagementPatterns(User $user): array
    {
        $logs = PushNotificationLog::where('user_id', $user->id)
            ->where('sent_at', '>=', now()->subDays(30))
            ->get();
            
        $interactions = PushNotificationInteraction::where('user_id', $user->id)
            ->where('timestamp', '>=', now()->subDays(30))
            ->get();
            
        return [
            'total_sent' => $logs->count(),
            'delivery_rate' => $logs->where('delivered', true)->count() / max($logs->count(), 1),
            'interaction_rate' => $interactions->count() / max($logs->where('delivered', true)->count(), 1),
            'preferred_times' => $this->getPreferredNotificationTimes($interactions),
            'most_engaging_types' => $this->getMostEngagingTypes($interactions),
            'optimal_frequency' => $this->calculateOptimalFrequency($logs, $interactions)
        ];
    }
    
    private function calculateOptimalFrequency(Collection $logs, Collection $interactions): string
    {
        $dailyStats = $logs->groupBy(function ($log) {
            return $log->sent_at->format('Y-m-d');
        })->map(function ($dayLogs) use ($interactions) {
            $dayInteractions = $interactions->filter(function ($interaction) use ($dayLogs) {
                return $dayLogs->pluck('sent_at')->contains(function ($sentAt) use ($interaction) {
                    return $sentAt->diffInHours($interaction->timestamp) < 24;
                });
            });
            
            return [
                'sent' => $dayLogs->count(),
                'interactions' => $dayInteractions->count(),
                'rate' => $dayInteractions->count() / max($dayLogs->count(), 1)
            ];
        });
        
        // Find optimal frequency based on engagement rate
        $avgRate = $dailyStats->avg('rate');
        $optimalSent = $dailyStats->where('rate', '>=', $avgRate * 1.2)->avg('sent');
        
        if ($optimalSent <= 1) {
            return 'low'; // 1 per day or less
        } elseif ($optimalSent <= 3) {
            return 'medium'; // 2-3 per day
        } else {
            return 'high'; // 4+ per day
        }
    }
}
```

#### 3.3 App Shell Architecture

**Ziel**: Instant Loading und Native App Experience durch optimierte Resource-Strategie.

**App Shell Components:**

- **Navigation Shell**: Header, Sidebar, Bottom Navigation
- **Loading States**: Skeleton screens, Progress indicators
- **Critical CSS**: Above-the-fold styles, Typography, Layout
- **Core JavaScript**: Framework, Routing, Service Worker registration

**Laravel PWA Integration:**

```php
// PWA Service Provider
class PWAServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register PWA Blade Components
        Blade::component('pwa-shell', PWAShellComponent::class);
        Blade::component('pwa-meta', PWAMetaComponent::class);
        Blade::component('offline-indicator', OfflineIndicatorComponent::class);
        
        // PWA Directives
        Blade::directive('pwa', function () {
            return "<?php echo view('pwa.meta-tags', ['manifest' => app(PWAService::class)->getManifest()]); ?>";
        });
        
        Blade::directive('serviceWorker', function () {
            return "<?php echo view('pwa.service-worker-registration'); ?>";
        });
    }
}

// PWA Shell Component
class PWAShellComponent extends Component
{
    public function render(): View
    {
        return view('components.pwa-shell', [
            'navigationItems' => $this->getNavigationItems(),
            'userNotifications' => $this->getUnreadNotifications(),
            'connectionStatus' => 'online' // Will be updated by JavaScript
        ]);
    }
    
    private function getNavigationItems(): array
    {
        return [
            ['icon' => 'home', 'label' => 'Dashboard', 'route' => 'dashboard'],
            ['icon' => 'games', 'label' => 'Spiele', 'route' => 'games.index'],
            ['icon' => 'teams', 'label' => 'Teams', 'route' => 'teams.index'],
            ['icon' => 'stats', 'label' => 'Statistiken', 'route' => 'statistics.index'],
            ['icon' => 'training', 'label' => 'Training', 'route' => 'training.index']
        ];
    }
}
```

**Progressive Enhancement JavaScript:**

```javascript
// PWA App Shell Manager
class PWAAppShell {
    constructor() {
        this.isOnline = navigator.onLine;
        this.installPrompt = null;
        
        this.initializeServiceWorker();
        this.setupOfflineDetection();
        this.setupInstallPrompt();
        this.preloadCriticalResources();
    }
    
    async initializeServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered:', registration);
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    this.showUpdateAvailableNotification();
                });
                
                // Listen for messages from Service Worker
                navigator.serviceWorker.addEventListener('message', this.handleServiceWorkerMessage.bind(this));
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }
    
    setupOfflineDetection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateConnectionStatus('online');
            this.syncOfflineActions();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateConnectionStatus('offline');
            this.showOfflineNotification();
        });
    }
    
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.installPrompt = e;
            this.showInstallButton();
        });
    }
    
    async preloadCriticalResources() {
        const criticalResources = [
            '/css/critical.css',
            '/js/app-shell.js',
            '/images/icons/icon-192x192.png'
        ];
        
        const preloadPromises = criticalResources.map(url => {
            return fetch(url).then(response => {
                if (response.ok) {
                    return caches.open('basketmanager-preload').then(cache => {
                        return cache.put(url, response);
                    });
                }
            }).catch(error => {
                console.warn('Failed to preload resource:', url, error);
            });
        });
        
        await Promise.allSettled(preloadPromises);
    }
    
    async installApp() {
        if (this.installPrompt) {
            this.installPrompt.prompt();
            const { outcome } = await this.installPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('User accepted the install prompt');
                this.hideInstallButton();
            }
            
            this.installPrompt = null;
        }
    }
    
    updateConnectionStatus(status) {
        const indicator = document.querySelector('.connection-indicator');
        if (indicator) {
            indicator.className = `connection-indicator ${status}`;
            indicator.textContent = status === 'online' ? 'Online' : 'Offline';
        }
        
        // Update UI elements based on connection
        document.querySelectorAll('[data-requires-connection]').forEach(element => {
            element.disabled = status === 'offline';
        });
    }
    
    async syncOfflineActions() {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SYNC_OFFLINE_ACTIONS'
            });
        }
    }
    
    handleServiceWorkerMessage(event) {
        const { type, data } = event.data;
        
        switch (type) {
            case 'BACKGROUND_SYNC_SUCCESS':
                this.showSuccessNotification(`${data.action} erfolgreich synchronisiert`);
                break;
            case 'CACHE_UPDATED':
                this.showInfoNotification('App wurde aktualisiert');
                break;
            case 'OFFLINE_ACTION_QUEUED':
                this.showInfoNotification('Aktion wird synchronisiert sobald Sie online sind');
                break;
        }
    }
}

// Initialize PWA App Shell
document.addEventListener('DOMContentLoaded', () => {
    window.pwaAppShell = new PWAAppShell();
});
```

### 4. Performance Optimization

#### 4.1 Database Query Optimization

**Ziel**: Sub-50ms Datenbankabfragen für 95% aller Requests durch intelligente Indexierung und Query-Optimierung.

**Optimization Strategies:**

- **Composite Indexes** für häufige Query-Patterns
- **Partitioning** für große Tabellen (game_actions, statistics)
- **Materialized Views** für komplexe Aggregationen
- **Query Caching** mit intelligenter Invalidierung
- **Connection Pooling** für hohe Concurrency

**Laravel Implementation:**

```php
// Advanced Database Optimization Service
class DatabaseOptimizationService
{
    public function createPerformanceIndexes(): void
    {
        // Game Performance Indexes
        $this->createGameIndexes();
        
        // Player Statistics Indexes
        $this->createStatisticsIndexes();
        
        // Training Session Indexes
        $this->createTrainingIndexes();
        
        // Multi-tenant Indexes
        $this->createTenantIndexes();
    }
    
    private function createGameIndexes(): void
    {
        // Composite index for team games by season
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_team_season_performance 
            ON games (home_team_id, season, status, scheduled_at DESC) 
            INCLUDE (away_team_id, final_score_home, final_score_away, venue)
        ');
        
        // Separate index for away team queries
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_away_team_season 
            ON games (away_team_id, season, status, scheduled_at DESC) 
            INCLUDE (home_team_id, final_score_home, final_score_away, venue)
        ');
        
        // Live games index
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_live_status 
            ON games (status, scheduled_at) 
            WHERE status IN (\'live\', \'scheduled\')
        ');
    }
    
    private function createStatisticsIndexes(): void
    {
        // Player performance lookup
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_player_performance 
            ON game_actions (player_id, action_type, created_at DESC) 
            INCLUDE (game_id, points, quarter, time_remaining)
        ');
        
        // Game statistics aggregation
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_game_aggregation 
            ON game_actions (game_id, action_type) 
            INCLUDE (player_id, points, is_successful)
        ');
    }
    
    public function partitionLargeTables(): void
    {
        // Partition game_actions by month for better performance
        $this->partitionGameActionsByMonth();
        
        // Partition audit logs by date
        $this->partitionAuditLogsByDate();
    }
    
    private function partitionGameActionsByMonth(): void
    {
        // Create partitioned table
        DB::statement('
            CREATE TABLE IF NOT EXISTS game_actions_partitioned (
                LIKE game_actions INCLUDING ALL
            ) PARTITION BY RANGE (created_at)
        ');
        
        // Create monthly partitions for current and next 24 months
        for ($i = -12; $i <= 12; $i++) {
            $date = now()->addMonths($i);
            $startDate = $date->startOfMonth();
            $endDate = $date->endOfMonth();
            $tableName = 'game_actions_' . $startDate->format('Y_m');
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$tableName} 
                PARTITION OF game_actions_partitioned 
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate->addDay()}')
            ");
            
            // Create indexes on partitions
            DB::statement("
                CREATE INDEX IF NOT EXISTS {$tableName}_player_idx 
                ON {$tableName} (player_id, action_type, created_at)
            ");
        }
    }
    
    private function partitionAuditLogsByDate(): void
    {
        // Create partitioned audit logs table for compliance and performance
        DB::statement('
            CREATE TABLE IF NOT EXISTS audit_logs_partitioned (
                LIKE activity_log INCLUDING ALL
            ) PARTITION BY RANGE (created_at)
        ');
        
        // Create daily partitions for audit logs (90 days retention)
        for ($i = -30; $i <= 60; $i++) {
            $date = now()->addDays($i);
            $startDate = $date->startOfDay();
            $endDate = $date->endOfDay();
            $tableName = 'audit_logs_' . $startDate->format('Y_m_d');
            
            DB::statement("
                CREATE TABLE IF NOT EXISTS {$tableName}
                PARTITION OF audit_logs_partitioned
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate->addDay()}')
            ");
            
            // Create indexes optimized for audit queries
            DB::statement("
                CREATE INDEX IF NOT EXISTS {$tableName}_subject_idx
                ON {$tableName} (subject_type, subject_id, created_at DESC)
            ");
            
            DB::statement("
                CREATE INDEX IF NOT EXISTS {$tableName}_causer_idx
                ON {$tableName} (causer_type, causer_id, created_at DESC)
            ");
        }
        
        // Setup automatic partition management
        $this->setupAutomaticPartitionManagement();
    }
    
    private function setupAutomaticPartitionManagement(): void
    {
        // Create function for automatic partition creation
        DB::statement('
            CREATE OR REPLACE FUNCTION create_monthly_partitions()
            RETURNS void AS $$
            DECLARE
                start_date date;
                end_date date;
                table_name text;
            BEGIN
                -- Create partition for next month
                start_date := date_trunc(\'month\', CURRENT_DATE + interval \'1 month\');
                end_date := start_date + interval \'1 month\';
                table_name := \'game_actions_\' || to_char(start_date, \'YYYY_MM\');
                
                EXECUTE format(\'CREATE TABLE IF NOT EXISTS %I PARTITION OF game_actions_partitioned 
                               FOR VALUES FROM (%L) TO (%L)\', 
                               table_name, start_date, end_date);
                               
                EXECUTE format(\'CREATE INDEX IF NOT EXISTS %I ON %I (player_id, action_type, created_at)\',
                               table_name || \'_player_idx\', table_name);
            END;
            $$ LANGUAGE plpgsql;
        ');
        
        // Schedule monthly partition creation
        DB::statement('
            SELECT cron.schedule(\'create-partitions\', \'0 0 1 * *\', \'SELECT create_monthly_partitions();\')
        ');
    }
    
    public function optimizeQueryExecution(): void
    {
        // Enable parallel query execution for large datasets
        DB::statement('SET max_parallel_workers_per_gather = 4');
        DB::statement('SET parallel_tuple_cost = 0.1');
        DB::statement('SET parallel_setup_cost = 1000.0');
        
        // Optimize work memory for sorting and hashing
        DB::statement('SET work_mem = \'256MB\'');
        DB::statement('SET maintenance_work_mem = \'1GB\'');
        
        // Configure connection pooling settings
        DB::statement('SET max_connections = 200');
        DB::statement('SET shared_buffers = \'2GB\'');
        DB::statement('SET effective_cache_size = \'6GB\'');
        
        // Enable query plan caching
        DB::statement('SET plan_cache_mode = auto');
    }
    
    public function createAdvancedIndexes(): void
    {
        // Partial indexes for active games
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_active_partial
            ON games (scheduled_at, venue, status)
            WHERE status IN (\'scheduled\', \'live\')
        ');
        
        // Expression index for full-text search
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_search
            ON players USING gin(to_tsvector(\'german\', first_name || \' \' || last_name))
        ');
        
        // Multi-column index with ordering for leaderboards
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_player_stats_leaderboard
            ON player_season_stats (season, points_per_game DESC, rebounds_per_game DESC)
            INCLUDE (player_id, games_played)
        ');
        
        // Covering index for frequently accessed player data
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_team_covered
            ON players (team_id, position, is_active)
            INCLUDE (first_name, last_name, jersey_number, birth_date)
        ');
    }
    
    public function implementQueryCaching(): void
    {
        // Setup Redis-based query result caching
        $this->setupRedisQueryCache();
        
        // Implement intelligent cache invalidation
        $this->setupCacheInvalidation();
        
        // Configure cache warming strategies
        $this->setupCacheWarming();
    }
    
    private function setupRedisQueryCache(): void
    {
        // Configure Redis for different cache types
        config([
            'database.redis.cache' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_CACHE_DB', 1),
                'options' => [
                    'prefix' => 'basketball_cache:',
                    'serializer' => 'php'
                ]
            ],
            'database.redis.sessions' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => env('REDIS_SESSION_DB', 2)
            ]
        ]);
    }
    
    public function createMaterializedViews(): void
    {
        // Team season statistics materialized view
        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS team_season_stats AS
            SELECT 
                t.id as team_id,
                t.season,
                COUNT(DISTINCT g.id) as games_played,
                SUM(CASE WHEN g.home_team_id = t.id THEN g.final_score_home 
                         WHEN g.away_team_id = t.id THEN g.final_score_away END) as total_points,
                AVG(CASE WHEN g.home_team_id = t.id THEN g.final_score_home 
                         WHEN g.away_team_id = t.id THEN g.final_score_away END) as avg_points,
                SUM(CASE WHEN (g.home_team_id = t.id AND g.final_score_home > g.final_score_away) OR
                             (g.away_team_id = t.id AND g.final_score_away > g.final_score_home) 
                         THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN (g.home_team_id = t.id AND g.final_score_home < g.final_score_away) OR
                             (g.away_team_id = t.id AND g.final_score_away < g.final_score_home) 
                         THEN 1 ELSE 0 END) as losses
            FROM teams t
            LEFT JOIN games g ON (g.home_team_id = t.id OR g.away_team_id = t.id)
            WHERE g.status = \'finished\'
            GROUP BY t.id, t.season
        ');
        
        // Create unique index on materialized view
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS team_season_stats_unique 
            ON team_season_stats (team_id, season)
        ');
    }
    
    public function analyzeQueryPerformance(): array
    {
        // Get slow queries from PostgreSQL
        $slowQueries = DB::select("
            SELECT 
                query,
                calls,
                total_time,
                mean_time,
                rows,
                100.0 * shared_blks_hit / nullif(shared_blks_hit + shared_blks_read, 0) AS hit_percent
            FROM pg_stat_statements 
            WHERE mean_time > 100  -- Queries slower than 100ms
            ORDER BY mean_time DESC 
            LIMIT 20
        ");
        
        return collect($slowQueries)->map(function ($query) {
            return [
                'query' => Str::limit($query->query, 100),
                'avg_time' => round($query->mean_time, 2) . 'ms',
                'total_calls' => $query->calls,
                'cache_hit_rate' => round($query->hit_percent, 2) . '%'
            ];
        })->toArray();
    }
}
```

#### 4.2 CDN Integration & Asset Optimization

**Ziel**: Globale Content Delivery mit <100ms Ladezeiten durch intelligente CDN-Strategie.

**CDN Architecture:**

- **Primary CDN**: Cloudflare (mit 200+ Edge Locations)
- **Image Optimization**: Cloudflare Images mit automatischer WebP-Konvertierung
- **Video Streaming**: Cloudflare Stream für adaptive Bitrate
- **API Acceleration**: Cloudflare Workers für Edge Computing

**Laravel Implementation:**

```php
// Advanced CDN Service
class CDNOptimizationService
{
    public function __construct(
        private CloudflareService $cloudflare,
        private ImageOptimizationService $imageOptimizer
    ) {}
    
    public function optimizeAssetDelivery(): void
    {
        // Configure Cloudflare settings for optimal performance
        $this->configureCloudflareOptimizations();
        
        // Setup image transformations
        $this->setupImageTransformations();
        
        // Configure caching rules
        $this->setupAdvancedCaching();
        
        // Enable compression
        $this->enableAdvancedCompression();
    }
    
    private function configureCloudflareOptimizations(): void
    {
        $optimizations = [
            // Enable automatic optimizations
            'minify' => [
                'css' => 'on',
                'html' => 'on',
                'js' => 'on'
            ],
            
            // Enable modern compression
            'brotli' => 'on',
            'gzip' => 'on',
            
            // Optimize images automatically
            'polish' => 'lossless',
            'webp' => 'on',
            
            // Enable Rocket Loader for async JS
            'rocket_loader' => 'on',
            
            // HTTP/3 and 0-RTT
            'http3' => 'on',
            '0rtt' => 'on',
            
            // Prefetch links
            'prefetch_preload' => 'on'
        ];
        
        foreach ($optimizations as $setting => $value) {
            $this->cloudflare->updateZoneSetting($setting, $value);
        }
    }
    
    public function generateOptimizedImageUrl(string $imagePath, array $options = []): string
    {
        $baseUrl = config('services.cloudflare.images_url');
        
        // Build transformation parameters
        $transformations = [];
        
        // Responsive images based on device
        if (isset($options['width'])) {
            $transformations[] = "w={$options['width']}";
        }
        if (isset($options['height'])) {
            $transformations[] = "h={$options['height']}";
        }
        
        // Quality optimization
        $quality = $options['quality'] ?? $this->getOptimalQuality($options);
        $transformations[] = "q={$quality}";
        
        // Format optimization (WebP for supported browsers)
        $format = $options['format'] ?? 'auto';
        $transformations[] = "f={$format}";
        
        // Fit mode for responsive images
        $fit = $options['fit'] ?? 'scale-down';
        $transformations[] = "fit={$fit}";
        
        // DPR (Device Pixel Ratio) support
        if (isset($options['dpr'])) {
            $transformations[] = "dpr={$options['dpr']}";
        }
        
        $transformString = implode(',', $transformations);
        
        return "{$baseUrl}/cdn-cgi/image/{$transformString}/{$imagePath}";
    }
    
    private function getOptimalQuality(array $options): int
    {
        // Adaptive quality based on image type and usage
        if (isset($options['usage'])) {
            return match($options['usage']) {
                'thumbnail' => 75,
                'profile' => 85,
                'banner' => 90,
                'print' => 95,
                default => 80
            };
        }
        
        return 80; // Default quality
    }
    
    public function setupAdvancedCaching(): void
    {
        $cachingRules = [
            // Static assets - Cache for 1 year
            [
                'pattern' => '*.{css,js,woff,woff2,ttf,eot}',
                'ttl' => 31536000, // 1 year
                'browser_ttl' => 31536000
            ],
            
            // Images - Cache for 1 month
            [
                'pattern' => '*.{jpg,jpeg,png,gif,webp,svg,ico}',
                'ttl' => 2592000, // 1 month
                'browser_ttl' => 2592000
            ],
            
            // Videos - Cache for 1 week
            [
                'pattern' => '*.{mp4,webm,avi,mov}',
                'ttl' => 604800, // 1 week
                'browser_ttl' => 604800
            ],
            
            // API responses - Cache for 5 minutes with stale-while-revalidate
            [
                'pattern' => '/api/*',
                'ttl' => 300, // 5 minutes
                'browser_ttl' => 300,
                'stale_while_revalidate' => 600 // 10 minutes stale
            ],
            
            // Dynamic content - No cache but with ETag
            [
                'pattern' => '*.php',
                'ttl' => 0,
                'browser_ttl' => 0,
                'respect_headers' => true
            ]
        ];
        
        foreach ($cachingRules as $rule) {
            $this->cloudflare->createCacheRule($rule);
        }
    }
    
    public function analyzeCDNPerformance(): array
    {
        // Get performance analytics from Cloudflare
        $analytics = $this->cloudflare->getAnalytics([
            'metrics' => ['requests', 'bandwidth', 'cache_hit_ratio', 'response_time'],
            'since' => now()->subDays(7),
            'until' => now()
        ]);
        
        return [
            'total_requests' => $analytics['requests']['total'],
            'cache_hit_ratio' => round($analytics['cache_hit_ratio']['avg'] * 100, 2) . '%',
            'avg_response_time' => round($analytics['response_time']['avg'], 2) . 'ms',
            'bandwidth_saved' => $this->formatBytes($analytics['bandwidth']['cached']),
            'top_cached_assets' => $analytics['top_paths']
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

#### 4.3 Memory Usage Optimization

**Ziel**: Durchschnittlicher RAM-Verbrauch <512MB pro Request durch intelligentes Memory Management.

**Memory Optimization Strategies:**

- **Lazy Loading** für Eloquent Relationships
- **Chunk Processing** für große Datasets
- **Memory-efficient Streaming** für CSV/PDF Exports
- **Garbage Collection Optimization** 
- **Query Result Streaming** für Reports

**Laravel Implementation:**

```php
// Memory Optimization Service
class MemoryOptimizationService
{
    public function optimizeQueryMemoryUsage(): void
    {
        // Configure Eloquent for memory efficiency
        Model::preventLazyLoading(!app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());
        Model::preventAccessingMissingAttributes(!app()->isProduction());
    }
    
    public function processLargeDatasetEfficiently(callable $callback, int $chunkSize = 1000): void
    {
        // Process large datasets in chunks to prevent memory exhaustion
        DB::table('large_table')
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($callback) {
                // Process each chunk
                $callback($records);
                
                // Force garbage collection after each chunk
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            });
    }
    
    public function generateLargeReportStreaming(Team $team, string $season): StreamedResponse
    {
        return response()->stream(function () use ($team, $season) {
            $handle = fopen('php://output', 'w');
            
            // Write CSV header
            fputcsv($handle, ['Player', 'Games', 'Points', 'Rebounds', 'Assists']);
            
            // Stream data in chunks
            $team->players()
                ->chunk(100, function ($players) use ($handle, $season) {
                    foreach ($players as $player) {
                        $stats = $this->getPlayerStats($player, $season);
                        fputcsv($handle, [
                            $player->full_name,
                            $stats['games'],
                            $stats['points'],
                            $stats['rebounds'],
                            $stats['assists']
                        ]);
                    }
                    
                    // Flush output buffer
                    flush();
                });
            
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="team-stats.csv"'
        ]);
    }
    
    public function monitorMemoryUsage(): array
    {
        return [
            'current_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => round(memory_get_usage(true) / $this->parseMemoryLimit() * 100, 2),
            'recommendations' => $this->getMemoryRecommendations()
        ];
    }
    
    private function parseMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
    
    private function getMemoryRecommendations(): array
    {
        $recommendations = [];
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit();
        
        if ($peakUsage > $limit * 0.8) {
            $recommendations[] = "Memory usage is high ({$this->formatBytes($peakUsage)}). Consider increasing memory_limit or optimizing queries.";
        }
        
        if ($peakUsage > $currentUsage * 2) {
            $recommendations[] = "Large memory spikes detected. Consider using chunked processing for large datasets.";
        }
        
        return $recommendations;
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function profileMemoryByComponent(): array
    {
        $beforeMemory = memory_get_usage(true);
        $components = [];
        
        // Profile different application components
        $components['eloquent'] = $this->profileEloquentMemory();
        $components['cache'] = $this->profileCacheMemory();
        $components['sessions'] = $this->profileSessionMemory();
        $components['views'] = $this->profileViewMemory();
        $components['collections'] = $this->profileCollectionMemory();
        
        return [
            'total_profiled' => memory_get_usage(true) - $beforeMemory,
            'components' => $components,
            'optimization_suggestions' => $this->generateOptimizationSuggestions($components)
        ];
    }
    
    private function profileEloquentMemory(): array
    {
        $before = memory_get_usage(true);
        
        // Test Eloquent memory usage patterns
        $testQueries = [
            'small_dataset' => fn() => Player::limit(10)->get(),
            'medium_dataset' => fn() => Player::with('team')->limit(100)->get(),
            'large_dataset' => fn() => Player::with(['team', 'statistics'])->limit(1000)->get(),
            'chunked_processing' => fn() => Player::chunk(100, function($players) {
                return $players->count();
            })
        ];
        
        $results = [];
        foreach ($testQueries as $name => $query) {
            $queryBefore = memory_get_usage(true);
            $query();
            $queryAfter = memory_get_usage(true);
            
            $results[$name] = [
                'memory_used' => $queryAfter - $queryBefore,
                'formatted' => $this->formatBytes($queryAfter - $queryBefore)
            ];
            
            // Force cleanup
            unset($query);
            gc_collect_cycles();
        }
        
        return [
            'total_memory' => memory_get_usage(true) - $before,
            'query_patterns' => $results,
            'recommendations' => $this->getEloquentOptimizations($results)
        ];
    }
    
    private function profileCacheMemory(): array
    {
        $before = memory_get_usage(true);
        
        // Test different cache operations
        $cacheTests = [
            'small_cache' => fn() => Cache::put('test_small', str_repeat('a', 1024), 60),
            'medium_cache' => fn() => Cache::put('test_medium', str_repeat('b', 10240), 60),
            'large_cache' => fn() => Cache::put('test_large', str_repeat('c', 102400), 60),
            'array_cache' => fn() => Cache::put('test_array', range(1, 1000), 60)
        ];
        
        $results = [];
        foreach ($cacheTests as $name => $test) {
            $testBefore = memory_get_usage(true);
            $test();
            $testAfter = memory_get_usage(true);
            
            $results[$name] = $testAfter - $testBefore;
        }
        
        // Cleanup test cache entries
        Cache::forget(['test_small', 'test_medium', 'test_large', 'test_array']);
        
        return [
            'total_memory' => memory_get_usage(true) - $before,
            'cache_operations' => $results,
            'current_cache_size' => $this->estimateCacheSize()
        ];
    }
    
    private function getEloquentOptimizations(array $queryResults): array
    {
        $optimizations = [];
        
        if ($queryResults['large_dataset']['memory_used'] > 50 * 1024 * 1024) { // 50MB
            $optimizations[] = 'Consider using chunked processing for large datasets';
        }
        
        if ($queryResults['medium_dataset']['memory_used'] > $queryResults['small_dataset']['memory_used'] * 20) {
            $optimizations[] = 'Eager loading is consuming significant memory - consider lazy loading';
        }
        
        if ($queryResults['chunked_processing']['memory_used'] < $queryResults['large_dataset']['memory_used'] * 0.5) {
            $optimizations[] = 'Chunked processing shows significant memory savings';
        }
        
        return $optimizations;
    }
    
    public function enableMemoryMonitoring(): void
    {
        // Register memory monitoring middleware
        app('events')->listen('kernel.handled', function ($request, $response) {
            $this->logRequestMemoryUsage($request, $response);
        });
        
        // Setup memory usage alerts
        if (memory_get_usage(true) > $this->parseMemoryLimit() * 0.9) {
            Log::warning('High memory usage detected', [
                'current_usage' => memory_get_usage(true),
                'peak_usage' => memory_get_peak_usage(true),
                'limit' => $this->parseMemoryLimit(),
                'percentage' => round(memory_get_usage(true) / $this->parseMemoryLimit() * 100, 2)
            ]);
        }
    }
    
    private function logRequestMemoryUsage($request, $response): void
    {
        $memoryData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'response_time' => microtime(true) - LARAVEL_START,
            'response_size' => strlen($response->getContent()),
            'memory_per_kb' => memory_get_usage(true) / max(strlen($response->getContent()) / 1024, 1)
        ];
        
        // Log only if memory usage is high or response is slow
        if ($memoryData['memory_usage'] > 100 * 1024 * 1024 || $memoryData['response_time'] > 2) {
            Log::info('Memory usage report', $memoryData);
        }
        
        // Store in performance metrics for analysis
        PerformanceMetric::create([
            'metric_type' => 'memory_usage',
            'value' => $memoryData['memory_usage'],
            'metadata' => $memoryData,
            'recorded_at' => now()
        ]);
    }
}

// Advanced Performance Monitoring Service
class PerformanceMonitoringService
{
    public function __construct(
        private MemoryOptimizationService $memoryService,
        private DatabaseOptimizationService $dbService,
        private CDNOptimizationService $cdnService
    ) {}
    
    public function generatePerformanceReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'memory_analysis' => $this->memoryService->profileMemoryByComponent(),
            'database_performance' => $this->dbService->analyzeQueryPerformance(),
            'cdn_performance' => $this->cdnService->analyzeCDNPerformance(),
            'system_metrics' => $this->getSystemMetrics(),
            'recommendations' => $this->generatePerformanceRecommendations()
        ];
    }
    
    private function getSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => extension_loaded('opcache') && opcache_get_status()['opcache_enabled'],
            'redis_connected' => $this->checkRedisConnection(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => sys_getloadavg(),
            'uptime' => $this->getSystemUptime()
        ];
    }
    
    private function generatePerformanceRecommendations(): array
    {
        $recommendations = [];
        $metrics = $this->getSystemMetrics();
        
        if (!$metrics['opcache_enabled']) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'php',
                'message' => 'Enable OPCache for significant performance improvements',
                'impact' => 'Up to 2x faster response times'
            ];
        }
        
        if (!$metrics['redis_connected']) {
            $recommendations[] = [
                'priority' => 'medium', 
                'category' => 'caching',
                'message' => 'Configure Redis for improved caching performance',
                'impact' => 'Reduced database load and faster page loads'
            ];
        }
        
        if ($metrics['load_average'][0] > 2.0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'system',
                'message' => 'High system load detected - consider scaling resources',
                'impact' => 'Improved response times and stability'
            ];
        }
        
        return $recommendations;
    }
    
    private function checkRedisConnection(): bool
    {
        try {
            return Redis::ping() === 'PONG';
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getDiskUsage(): array
    {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        $used = $total - $free;
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'usage_percentage' => round(($used / $total) * 100, 2)
        ];
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### 5. Multi-tenant Architecture

#### 5.1 Tenant Isolation & Security

**Ziel**: Vollständige Datenisolation zwischen Mandanten mit Row Level Security und Domain-based Routing.

**Tenant Isolation Strategies:**

- **Single Database, Multi-Schema**: Separate Schemas pro Tenant
- **Row Level Security**: PostgreSQL RLS für Datenisolation
- **Domain-based Routing**: Automatische Tenant-Erkennung
- **Encrypted Tenant Data**: Verschlüsselung sensibler Daten
- **Audit Trails**: Vollständige Logging aller Tenant-Aktivitäten

**Laravel Implementation:**

```php
// Tenant Model mit Security Features
class Tenant extends Model
{
    use HasFactory, HasUuids, LogsActivity;
    
    protected $fillable = [
        'name', 'slug', 'domain', 'subdomain',
        'settings', 'subscription_tier', 'is_active',
        'trial_ends_at', 'billing_email'
    ];
    
    protected $casts = [
        'settings' => 'encrypted:array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'features' => 'array',
        'security_settings' => 'encrypted:array'
    ];
    
    protected $hidden = [
        'database_password', 'api_secret', 'encryption_key'
    ];
    
    // Tenant Resolution with Security Checks
    public static function resolveFromRequest(Request $request): ?self
    {
        $domain = $request->getHost();
        $tenant = static::resolveFromDomain($domain);
        
        if (!$tenant) {
            return null;
        }
        
        // Security validations
        if (!$tenant->is_active) {
            throw new TenantSuspendedException('Tenant account is suspended');
        }
        
        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isPast() && !$tenant->subscription) {
            throw new TenantTrialExpiredException('Trial period has expired');
        }
        
        // Rate limiting per tenant
        if (!$tenant->checkRateLimit($request)) {
            throw new TenantRateLimitExceededException('Rate limit exceeded for tenant');
        }
        
        return $tenant;
    }
    
    public static function resolveFromDomain(string $domain): ?self
    {
        return Cache::remember(
            "tenant:domain:{$domain}",
            3600,
            fn() => static::where('domain', $domain)
                         ->orWhere('subdomain', $domain)
                         ->where('is_active', true)
                         ->first()
        );
    }
    
    // Feature Access Control
    public function hasFeature(string $feature): bool
    {
        // Check subscription tier features
        $tierFeatures = config("tenants.tiers.{$this->subscription_tier}.features", []);
        
        if (in_array($feature, $tierFeatures)) {
            return true;
        }
        
        // Check custom features
        $customFeatures = $this->features ?? [];
        
        return in_array($feature, $customFeatures);
    }
    
    public function enforceFeatureAccess(string $feature): void
    {
        if (!$this->hasFeature($feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available for subscription tier '{$this->subscription_tier}'"
            );
        }
    }
    
    // Security & Compliance
    public function getDataRetentionPolicy(): array
    {
        return [
            'game_data' => $this->getSetting('data_retention.games', '7 years'),
            'player_data' => $this->getSetting('data_retention.players', '10 years'),
            'audit_logs' => $this->getSetting('data_retention.audit', '3 years'),
            'anonymize_after' => $this->getSetting('data_retention.anonymize', '1 year')
        ];
    }
    
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
    
    public function updateSettings(array $settings): void
    {
        $this->update([
            'settings' => array_merge($this->settings ?? [], $settings)
        ]);
        
        // Clear tenant cache
        Cache::tags(["tenant:{$this->id}"])->flush();
    }
}

// Tenant Resolution Middleware mit Advanced Security
class ResolveTenantMiddleware
{
    public function __construct(
        private TenantRepository $tenantRepository,
        private SecurityService $securityService
    ) {}
    
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tenant = Tenant::resolveFromRequest($request);
            
            if (!$tenant) {
                return $this->handleTenantNotFound($request);
            }
            
            // Set tenant context
            $this->setTenantContext($tenant);
            
            // Configure tenant-specific services
            $this->configureTenantServices($tenant);
            
            // Setup Row Level Security
            $this->setupRowLevelSecurity($tenant);
            
            // Log tenant access for analytics
            $this->logTenantAccess($request, $tenant);
            
            return $next($request);
            
        } catch (TenantException $e) {
            return $this->handleTenantException($e);
        }
    }
    
    private function setTenantContext(Tenant $tenant): void
    {
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);
        
        // Set tenant context for Eloquent queries
        Model::setGlobalTenant($tenant);
    }
    
    private function configureTenantServices(Tenant $tenant): void
    {
        // Configure tenant-specific database
        if ($tenant->database_name) {
            $this->configureTenantDatabase($tenant);
        }
        
        // Apply tenant customizations
        $this->applyTenantCustomizations($tenant);
        
        // Configure tenant-specific mail settings
        $this->configureTenantMail($tenant);
        
        // Setup tenant-specific caching namespace
        Cache::setPrefix("tenant:{$tenant->id}:");
    }
    
    private function setupRowLevelSecurity(Tenant $tenant): void
    {
        // Enable Row Level Security for tenant isolation
        DB::statement('SET row_security = on');
        DB::statement('SET basketmanager.current_tenant_id = ?', [$tenant->id]);
    }
    
    private function logTenantAccess(Request $request, Tenant $tenant): void
    {
        // Log for analytics and security monitoring
        TenantAccess::create([
            'tenant_id' => $tenant->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'timestamp' => now()
        ]);
    }
    
    private function handleTenantNotFound(Request $request): Response
    {
        // Log potential security incident
        Log::warning('Tenant not found for domain', [
            'domain' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Invalid domain or tenant not active'
            ], 404);
        }
        
        return response()->view('errors.tenant-not-found', [], 404);
    }
}

// Tenant Scoping Trait for Models
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically set tenant_id when creating models
        static::creating(function ($model) {
            if (app()->bound('tenant.id') && !$model->tenant_id) {
                $model->tenant_id = app('tenant.id');
            }
        });
        
        // Add global scope for tenant filtering
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant.id')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', app('tenant.id'));
            }
        });
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Scope to bypass tenant filtering (use with caution)
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope('tenant');
    }
}
```

#### 5.2 Subscription Management & Billing

**Ziel**: Automated SaaS Billing mit Usage-based Pricing und Self-Service Portal.

**Subscription Tiers:**

| Tier | Monthly Price | Teams | Users | Games/Month | API Calls | Storage |
|------|---------------|-------|-------|-------------|-----------|---------|
| Starter | €29 | 3 | 25 | 50 | 10.000 | 5GB |
| Professional | €99 | 10 | 100 | 200 | 50.000 | 25GB |
| Club | €299 | 25 | 500 | 1.000 | 250.000 | 100GB |
| Enterprise | Custom | Unlimited | Unlimited | Unlimited | Custom | Custom |

**Laravel Implementation:**

```php
// Subscription Management Service
class SubscriptionManagementService
{
    public function calculateMonthlyBill(Tenant $tenant): BillingCalculation
    {
        $subscription = $tenant->subscription;
        $tier = config("tenants.tiers.{$tenant->subscription_tier}");
        
        $baseFee = $tier['monthly_price'];
        $usage = $this->usageTracker->getMonthlyUsage($tenant);
        $overages = $this->calculateOverages($tenant, $usage);
        
        return new BillingCalculation([
            'tenant_id' => $tenant->id,
            'billing_period' => now()->format('Y-m'),
            'base_fee' => $baseFee,
            'usage' => $usage,
            'overages' => $overages,
            'total' => $baseFee + array_sum($overages)
        ]);
    }
    
    public function upgradeTenant(Tenant $tenant, string $newTier): UpgradeResult
    {
        $oldTier = $tenant->subscription_tier;
        $proratedAmount = $this->calculateProratedUpgrade($tenant, $oldTier, $newTier);
        
        return DB::transaction(function () use ($tenant, $newTier, $proratedAmount, $oldTier) {
            // Charge prorated amount if needed
            if ($proratedAmount > 0) {
                $payment = $this->paymentService->chargeSubscription($tenant, $proratedAmount);
                
                if (!$payment->successful()) {
                    throw new PaymentFailedException('Upgrade payment failed');
                }
            }
            
            // Update subscription
            $tenant->update(['subscription_tier' => $newTier]);
            
            return UpgradeResult::success(['old_tier' => $oldTier, 'new_tier' => $newTier]);
        });
    }
}
```

#### 5.3 Tenant Customization & White-Labeling

**Ziel**: Vollständige Brand-Anpassung und White-Label-Lösungen für Enterprise-Kunden.

**Laravel Implementation:**

```php
// Tenant Customization Service
class TenantCustomizationService
{
    public function applyBrandingTheme(Tenant $tenant, array $branding): void
    {
        $validatedBranding = $this->validateBrandingData($branding);
        
        $tenant->updateSettings([
            'branding' => array_merge(
                $tenant->getSetting('branding', []),
                $validatedBranding
            )
        ]);
        
        $this->generateTenantCSS($tenant);
        $this->clearTenantCache($tenant);
    }
    
    public function generateTenantCSS(Tenant $tenant): string
    {
        $branding = $tenant->getSetting('branding', []);
        
        $css = ":root {\n";
        
        if (isset($branding['primary_color'])) {
            $primaryColor = $branding['primary_color'];
            $css .= "  --tenant-primary: {$primaryColor};\n";
            $css .= "  --tenant-primary-dark: " . $this->darkenColor($primaryColor, 0.1) . ";\n";
        }
        
        $css .= "}\n";
        
        Cache::put("tenant:{$tenant->id}:css", $css, 86400);
        
        return $css;
    }
    
    public function setupCustomDomain(Tenant $tenant, string $domain): CustomDomainResult
    {
        if (!$this->isValidDomain($domain)) {
            return CustomDomainResult::failure('Invalid domain format');
        }
        
        try {
            $sslResult = $this->setupSSLCertificate($domain);
            $dnsResult = $this->updateDNSConfiguration($domain, $tenant);
            
            $tenant->update([
                'domain' => $domain,
                'custom_domain_verified' => true,
                'ssl_certificate_id' => $sslResult->certificateId()
            ]);
            
            return CustomDomainResult::success(['domain' => $domain, 'ssl_status' => 'active']);
            
        } catch (Exception $e) {
            return CustomDomainResult::failure('Domain setup failed: ' . $e->getMessage());
        }
    }
}
```

#### 5.4 Row Level Security & Advanced Tenant Isolation

**Ziel**: PostgreSQL Row Level Security für absolute Datenisolation und erweiterte Sicherheitsmaßnahmen zwischen Tenants.

**Row Level Security Policies:**

```sql
-- Enable Row Level Security auf allen Tenant-spezifischen Tabellen
ALTER TABLE teams ENABLE ROW LEVEL SECURITY;
ALTER TABLE players ENABLE ROW LEVEL SECURITY;
ALTER TABLE games ENABLE ROW LEVEL SECURITY;
ALTER TABLE game_actions ENABLE ROW LEVEL SECURITY;
ALTER TABLE trainings ENABLE ROW LEVEL SECURITY;
ALTER TABLE statistics ENABLE ROW LEVEL SECURITY;
ALTER TABLE media_files ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;

-- Teams Policy - Nur Daten des aktuellen Tenants
CREATE POLICY tenant_teams_policy ON teams
    FOR ALL TO application_role
    USING (tenant_id = current_setting('basketmanager.current_tenant_id')::integer)
    WITH CHECK (tenant_id = current_setting('basketmanager.current_tenant_id')::integer);

-- Players Policy mit erweiterten Sicherheitschecks
CREATE POLICY tenant_players_policy ON players
    FOR ALL TO application_role
    USING (
        tenant_id = current_setting('basketmanager.current_tenant_id')::integer
        AND (
            -- Player gehört zu Team des Tenants
            team_id IN (
                SELECT id FROM teams 
                WHERE tenant_id = current_setting('basketmanager.current_tenant_id')::integer
            )
            OR
            -- Player ist direkt dem Tenant zugeordnet (Gastspieler)
            tenant_id = current_setting('basketmanager.current_tenant_id')::integer
        )
    )
    WITH CHECK (tenant_id = current_setting('basketmanager.current_tenant_id')::integer);

-- Games Policy mit Cross-Tenant Visibility für offizielle Spiele
CREATE POLICY tenant_games_policy ON games
    FOR ALL TO application_role
    USING (
        -- Eigene Spiele
        (home_team_id IN (SELECT id FROM teams WHERE tenant_id = current_setting('basketmanager.current_tenant_id')::integer))
        OR
        (away_team_id IN (SELECT id FROM teams WHERE tenant_id = current_setting('basketmanager.current_tenant_id')::integer))
        OR
        -- Öffentliche offizielle Spiele (nur lesend)
        (is_official = true AND is_public = true AND TG_OP = 'SELECT')
    )
    WITH CHECK (
        home_team_id IN (SELECT id FROM teams WHERE tenant_id = current_setting('basketmanager.current_tenant_id')::integer)
        OR
        away_team_id IN (SELECT id FROM teams WHERE tenant_id = current_setting('basketmanager.current_tenant_id')::integer)
    );

-- Media Files Policy mit Dateisystem-Integration
CREATE POLICY tenant_media_policy ON media_files
    FOR ALL TO application_role
    USING (
        tenant_id = current_setting('basketmanager.current_tenant_id')::integer
        AND
        -- Zusätzliche Prüfung: Datei muss im Tenant-spezifischen Pfad liegen
        file_path LIKE concat('tenant_', current_setting('basketmanager.current_tenant_id'), '/%')
    )
    WITH CHECK (
        tenant_id = current_setting('basketmanager.current_tenant_id')::integer
        AND
        file_path LIKE concat('tenant_', current_setting('basketmanager.current_tenant_id'), '/%')
    );

-- Audit Log Policy - Tenants können nur eigene Logs sehen
CREATE POLICY tenant_audit_policy ON audit_logs
    FOR SELECT TO application_role
    USING (tenant_id = current_setting('basketmanager.current_tenant_id')::integer);
```

**Advanced Tenant Isolation Middleware:**

```php
class AdvancedTenantIsolationMiddleware
{
    public function __construct(
        private TenantSecurityService $securityService,
        private AuditLogger $auditLogger
    ) {}
    
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenantWithSecurityChecks($request);
        
        // Setup comprehensive tenant isolation
        $this->setupDatabaseIsolation($tenant);
        $this->setupFilesystemIsolation($tenant);
        $this->setupCacheIsolation($tenant);
        $this->setupSessionIsolation($tenant);
        
        // Security monitoring
        $this->auditTenantAccess($request, $tenant);
        $this->monitorSuspiciousActivity($request, $tenant);
        
        // Execute request with tenant context
        $response = $next($request);
        
        // Post-request security cleanup
        $this->cleanupTenantContext($tenant);
        
        return $response;
    }
    
    private function setupDatabaseIsolation(Tenant $tenant): void
    {
        // Set PostgreSQL RLS context
        DB::statement('SET row_security = on');
        DB::statement('SET basketmanager.current_tenant_id = ?', [$tenant->id]);
        
        // Additional connection-level security
        if ($tenant->require_separate_database) {
            $this->configureSeparateTenantDatabase($tenant);
        }
        
        // Set query timeout based on tenant tier
        $timeout = $tenant->getQueryTimeout();
        DB::statement('SET statement_timeout = ?', ["{$timeout}s"]);
    }
    
    private function setupFilesystemIsolation(Tenant $tenant): void
    {
        // Configure tenant-specific storage disk
        config([
            'filesystems.disks.tenant' => [
                'driver' => 'local',
                'root' => storage_path("app/tenant_{$tenant->id}"),
                'url' => env('APP_URL') . "/storage/tenant_{$tenant->id}",
                'visibility' => 'private',
                'permissions' => [
                    'file' => [
                        'public' => 0644,
                        'private' => 0600,
                    ],
                    'dir' => [
                        'public' => 0755,
                        'private' => 0700,
                    ],
                ],
            ]
        ]);
        
        // Ensure tenant directory exists with correct permissions
        $tenantPath = storage_path("app/tenant_{$tenant->id}");
        if (!is_dir($tenantPath)) {
            mkdir($tenantPath, 0700, true);
        }
        
        // Set default disk for this request
        Storage::setDefaultDriver('tenant');
    }
    
    private function setupCacheIsolation(Tenant $tenant): void
    {
        // Tenant-specific cache prefix
        Cache::setPrefix("t{$tenant->id}:");
        
        // Configure tenant-specific Redis database if available
        if ($tenant->hasFeature('dedicated_redis')) {
            config([
                'cache.stores.redis.database' => $tenant->redis_database_index,
                'cache.stores.redis.prefix' => "tenant_{$tenant->id}:",
            ]);
        }
    }
    
    private function setupSessionIsolation(Tenant $tenant): void
    {
        // Tenant-specific session configuration
        config([
            'session.cookie' => "basketmanager_t{$tenant->id}_session",
            'session.path' => "/tenant/{$tenant->id}",
            'session.domain' => $tenant->domain,
            'session.secure' => $tenant->force_https,
            'session.same_site' => 'strict'
        ]);
    }
    
    private function auditTenantAccess(Request $request, Tenant $tenant): void
    {
        // Comprehensive access logging
        $this->auditLogger->logTenantAccess([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'query_params' => $request->query(),
            'session_id' => $request->session()->getId(),
            'referrer' => $request->header('referer'),
            'timestamp' => now(),
            'request_id' => $request->header('X-Request-ID', Str::uuid()),
            'geo_location' => $this->getGeoLocation($request->ip()),
            'device_fingerprint' => $this->generateDeviceFingerprint($request)
        ]);
    }
    
    private function monitorSuspiciousActivity(Request $request, Tenant $tenant): void
    {
        $suspicious = false;
        $reasons = [];
        
        // Check for unusual access patterns
        if ($this->detectUnusualAccessPattern($request, $tenant)) {
            $suspicious = true;
            $reasons[] = 'unusual_access_pattern';
        }
        
        // Check for potential data mining attempts
        if ($this->detectDataMiningAttempt($request, $tenant)) {
            $suspicious = true;
            $reasons[] = 'potential_data_mining';
        }
        
        // Check for privilege escalation attempts
        if ($this->detectPrivilegeEscalation($request, $tenant)) {
            $suspicious = true;
            $reasons[] = 'privilege_escalation_attempt';
        }
        
        // Check for cross-tenant data access attempts
        if ($this->detectCrossTenantAccess($request, $tenant)) {
            $suspicious = true;
            $reasons[] = 'cross_tenant_access_attempt';
        }
        
        if ($suspicious) {
            $this->handleSuspiciousActivity($request, $tenant, $reasons);
        }
    }
    
    private function handleSuspiciousActivity(Request $request, Tenant $tenant, array $reasons): void
    {
        // Log security incident
        $this->auditLogger->logSecurityIncident([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'incident_type' => 'suspicious_activity',
            'reasons' => $reasons,
            'request_data' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'user_agent' => $request->userAgent()
            ],
            'timestamp' => now(),
            'severity' => $this->calculateSeverity($reasons)
        ]);
        
        // Notify security team for high-severity incidents
        if ($this->calculateSeverity($reasons) >= 8) {
            $this->notifySecurityTeam($tenant, $reasons, $request);
        }
        
        // Implement rate limiting for suspicious IPs
        $this->implementSuspiciousIPRateLimit($request->ip(), $reasons);
    }
    
    private function detectCrossTenantAccess(Request $request, Tenant $tenant): bool
    {
        // Check if request contains tenant IDs different from current tenant
        $requestData = array_merge($request->all(), $request->route()?->parameters() ?? []);
        
        foreach ($requestData as $key => $value) {
            if (str_contains($key, 'tenant') && is_numeric($value) && $value != $tenant->id) {
                return true;
            }
        }
        
        // Check for attempts to access other tenant's resources
        if (preg_match('/\/tenant\/(\d+)\//', $request->path(), $matches)) {
            $urlTenantId = (int) $matches[1];
            if ($urlTenantId !== $tenant->id) {
                return true;
            }
        }
        
        return false;
    }
    
    private function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->header('Accept'),
            $request->ip()
        ];
        
        return hash('sha256', implode('|', array_filter($components)));
    }
}
```

#### 5.5 Security Audit Trail System

**Ziel**: Vollständige Nachverfolgbarkeit aller Tenant-bezogenen Aktivitäten für Compliance und Sicherheitsanalyse.

**Audit System Implementation:**

```php
class TenantAuditTrailService
{
    public function __construct(
        private Database $database,
        private EncryptionService $encryption,
        private ComplianceService $compliance
    ) {}
    
    public function logTenantActivity(array $data): void
    {
        $auditEntry = [
            'id' => Str::uuid(),
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'] ?? null,
            'activity_type' => $data['activity_type'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'action' => $data['action'],
            'old_values' => $this->encryption->encrypt($data['old_values'] ?? []),
            'new_values' => $this->encryption->encrypt($data['new_values'] ?? []),
            'metadata' => [
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'session_id' => $data['session_id'],
                'request_id' => $data['request_id'],
                'geo_location' => $data['geo_location'] ?? null,
                'device_fingerprint' => $data['device_fingerprint'] ?? null
            ],
            'risk_score' => $this->calculateRiskScore($data),
            'compliance_flags' => $this->compliance->checkCompliance($data),
            'created_at' => now(),
            'hash' => null // Will be calculated after insertion for integrity
        ];
        
        // Calculate integrity hash
        $auditEntry['hash'] = $this->calculateIntegrityHash($auditEntry);
        
        // Store in tenant-specific audit table
        $this->database->table('tenant_audit_logs')->insert($auditEntry);
        
        // Archive old audit logs based on retention policy
        $this->archiveOldAuditLogs($data['tenant_id']);
        
        // Check for patterns requiring immediate attention
        $this->analyzeAuditPatterns($data['tenant_id'], $auditEntry);
    }
    
    private function calculateRiskScore(array $data): int
    {
        $score = 0;
        
        // Base score by activity type
        $riskScores = [
            'login' => 1,
            'logout' => 1,
            'view' => 1,
            'create' => 3,
            'update' => 4,
            'delete' => 8,
            'export' => 6,
            'admin_action' => 9,
            'settings_change' => 7,
            'user_management' => 8,
            'payment_change' => 9,
            'security_change' => 10
        ];
        
        $score += $riskScores[$data['activity_type']] ?? 5;
        
        // Increase score for sensitive entities
        $sensitiveEntities = ['user', 'payment', 'settings', 'api_key', 'webhook'];
        if (isset($data['entity_type']) && in_array($data['entity_type'], $sensitiveEntities)) {
            $score += 3;
        }
        
        // Increase score for unusual times
        $hour = (int) now()->format('H');
        if ($hour < 6 || $hour > 22) {
            $score += 2; // Activity outside normal hours
        }
        
        // Increase score for new IP addresses
        if ($this->isNewIPForTenant($data['tenant_id'], $data['ip_address'])) {
            $score += 4;
        }
        
        return min($score, 10); // Cap at 10
    }
    
    public function generateAuditReport(int $tenantId, array $filters = []): AuditReport
    {
        $query = $this->database->table('tenant_audit_logs')
            ->where('tenant_id', $tenantId);
        
        // Apply filters
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['activity_type'])) {
            $query->where('activity_type', $filters['activity_type']);
        }
        
        if (isset($filters['risk_level'])) {
            $minRisk = match($filters['risk_level']) {
                'low' => 0,
                'medium' => 4,
                'high' => 7,
                'critical' => 9
            };
            $query->where('risk_score', '>=', $minRisk);
        }
        
        $auditLogs = $query->orderBy('created_at', 'desc')->get();
        
        // Generate report analytics
        $analytics = $this->generateAuditAnalytics($auditLogs, $tenantId);
        
        return new AuditReport([
            'tenant_id' => $tenantId,
            'generated_at' => now(),
            'filters' => $filters,
            'total_entries' => $auditLogs->count(),
            'audit_logs' => $auditLogs,
            'analytics' => $analytics,
            'compliance_status' => $this->compliance->assessTenantCompliance($tenantId),
            'recommendations' => $this->generateSecurityRecommendations($analytics)
        ]);
    }
    
    private function generateAuditAnalytics(Collection $auditLogs, int $tenantId): array
    {
        return [
            'activity_breakdown' => $auditLogs->groupBy('activity_type')->map->count(),
            'risk_distribution' => $auditLogs->groupBy(function ($log) {
                return match(true) {
                    $log->risk_score <= 3 => 'low',
                    $log->risk_score <= 6 => 'medium',
                    $log->risk_score <= 8 => 'high',
                    default => 'critical'
                };
            })->map->count(),
            'hourly_activity' => $auditLogs->groupBy(function ($log) {
                return Carbon::parse($log->created_at)->format('H');
            })->map->count(),
            'top_users' => $auditLogs->whereNotNull('user_id')
                ->groupBy('user_id')
                ->map->count()
                ->sortDesc()
                ->take(10),
            'unique_ips' => $auditLogs->pluck('metadata.ip_address')->unique()->count(),
            'failed_actions' => $auditLogs->where('metadata.status', 'failed')->count(),
            'compliance_violations' => $auditLogs->whereNotEmpty('compliance_flags')->count()
        ];
    }
    
    private function calculateIntegrityHash(array $auditEntry): string
    {
        // Remove hash field for calculation
        $dataForHash = collect($auditEntry)->except('hash')->toArray();
        
        // Sort to ensure consistent hash
        ksort($dataForHash);
        
        return hash('sha256', json_encode($dataForHash, JSON_SORT_KEYS));
    }
    
    public function verifyAuditIntegrity(int $tenantId): AuditIntegrityReport
    {
        $logs = $this->database->table('tenant_audit_logs')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at')
            ->get();
        
        $integrityViolations = [];
        $verifiedCount = 0;
        
        foreach ($logs as $log) {
            $logData = (array) $log;
            $storedHash = $logData['hash'];
            unset($logData['hash']);
            
            $calculatedHash = $this->calculateIntegrityHash($logData);
            
            if ($storedHash === $calculatedHash) {
                $verifiedCount++;
            } else {
                $integrityViolations[] = [
                    'log_id' => $log->id,
                    'created_at' => $log->created_at,
                    'expected_hash' => $storedHash,
                    'calculated_hash' => $calculatedHash
                ];
            }
        }
        
        return new AuditIntegrityReport([
            'tenant_id' => $tenantId,
            'total_logs' => $logs->count(),
            'verified_logs' => $verifiedCount,
            'integrity_violations' => $integrityViolations,
            'integrity_percentage' => $logs->count() > 0 ? ($verifiedCount / $logs->count()) * 100 : 100,
            'verified_at' => now()
        ]);
    }
}
```

---

## 🏗️ Phase 4: Architektur & Design

### Domain-Driven Design für Multi-Tenant SaaS

**Architektur-Prinzipien:**

- **Bounded Contexts**: Tenant, Team, Game, Statistics, Training
- **Aggregate Roots**: Tenant als Central Entity für alle Domain Objects
- **Event Sourcing**: Für Game Actions und Critical Business Events
- **CQRS**: Separate Read/Write Models für Performance

### Microservices Integration

**Service Boundaries:**

- **Core Service**: Team/Player/Game Management
- **Statistics Service**: Analytics und Reporting
- **Notification Service**: Push/Email/SMS
- **Payment Service**: Billing und Subscription Management
- **Media Service**: Video/Image Processing

---

## 📋 Phase 4: Implementierungsplan

### Monat 10: API & External Integrations

**Woche 1-2: API Finalization**
- OpenAPI Documentation System
- API Versioning Implementation
- Rate Limiting & Webhook System

**Woche 3-4: External Integrations**
- DBB/FIBA API Integration
- Social Media Automation
- Payment Gateway Setup

### Monat 11: PWA & Performance

**Woche 1-2: Progressive Web App**
- Service Worker Implementation
- Push Notifications System
- App Shell Architecture

**Woche 3-4: Performance Optimization**
- Database Query Optimization
- CDN Integration
- Memory Usage Optimization

### Monat 12: Multi-Tenant & Launch

**Woche 1-2: Multi-Tenant Architecture**
- Tenant Isolation & Security
- Subscription Management
- Customization System

**Woche 3-4: Testing & Launch Preparation**
- Comprehensive Testing
- Performance Tuning
- Production Deployment

---

## 🧪 Phase 4: Testing & Quality Assurance

### Testing Strategy

**Unit Tests**: 90% Code Coverage für kritische Business Logic
**Integration Tests**: External API und Database Integration
**Performance Tests**: Load Testing bis 1.000 concurrent Users
**Security Tests**: Penetration Testing und Vulnerability Assessment

**Laravel Implementation:**

```php
// Performance Test Example
class ApiPerformanceTest extends TestCase
{
    public function test_api_response_times_meet_requirements(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(15)->create(['team_id' => $team->id]);
        
        $startTime = microtime(true);
        $response = $this->getJson("/api/v4/teams/{$team->id}/statistics");
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(100, $executionTime, 'API response time should be under 100ms');
        $response->assertStatus(200);
    }
}

// Multi-tenant Security Test
class TenantSecurityTest extends TestCase
{
    public function test_tenant_data_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        app()->instance('tenant.id', $tenant1->id);
        $team1 = Team::factory()->create();
        
        app()->instance('tenant.id', $tenant2->id);
        $teams = Team::all();
        
        $this->assertCount(0, $teams); // Should not see tenant1's data
    }
}
```

---

## 🚀 Phase 4: Deployment & DevOps

### Zero-Downtime Deployment Strategy

**Blue-Green Deployment:**

```bash
# Phase 4 Deployment Script
#!/bin/bash

echo "🏀 Starting Phase 4 Deployment"

# Pre-deployment checks
php artisan basketball:health-check --critical
php artisan basketball:check-external-apis

# Database migrations
php artisan migrate --force

# Deploy PWA updates
php artisan pwa:generate-manifest
php artisan pwa:update-service-worker

# CDN asset deployment
php artisan cdn:push-assets

# Restart services
php artisan horizon:terminate
sudo service php8.3-fpm restart

# Verification
php artisan basketball:verify-integrations
```

### Infrastructure Requirements

**Production Environment:**
- **Application Servers**: 3× Laravel App Servers (Load Balanced)
- **Database**: PostgreSQL 15 with Read Replicas
- **Cache**: Redis Cluster für Session/Cache/Queue
- **CDN**: Cloudflare für Global Asset Delivery
- **Monitoring**: Elastic Stack für Logging und Monitoring

---

## ⚠️ Phase 4: Risikomanagement

### Kritische Risiken & Mitigation

**1. Multi-Tenant Data Leakage**
- **Risiko**: Tenant-Daten werden zwischen Mandanten sichtbar
- **Mitigation**: Row Level Security, Automated Testing, Code Reviews

**2. External API Dependencies**
- **Risiko**: DBB/FIBA APIs sind nicht verfügbar
- **Mitigation**: Fallback-Mechanismen, Offline-Mode, API Health Monitoring

**3. Performance Degradation**
- **Risiko**: System wird langsam bei vielen Tenants
- **Mitigation**: Performance Monitoring, Auto-Scaling, Database Optimization

**4. Payment Processing Failures**
- **Risiko**: Subscription Billing schlägt fehl
- **Mitigation**: Multiple Payment Gateways, Retry Logic, Manual Fallback

### Rollback-Strategie

**Automated Rollback Triggers:**
- API Response Time > 500ms für 5 Minuten
- Error Rate > 5% für 3 Minuten
- Database Connection Failures
- Critical External Service Unavailable

---

## 📈 Phase 4: Success Metrics & KPIs

### Performance Metrics

- ✅ **API Response Times**: <100ms für 95% der Requests
- ✅ **Database Query Performance**: <50ms für 95% der Queries
- ✅ **CDN Cache Hit Rate**: >85% für statische Assets
- ✅ **Memory Usage**: <512MB durchschnittlich pro Request

### Business Metrics

- ✅ **Tenant Onboarding**: <5 Minuten für Self-Service Setup
- ✅ **API Integration Success**: >99% Uptime für External Services
- ✅ **PWA Adoption**: >60% der Mobile Users installieren die App
- ✅ **Customer Satisfaction**: NPS Score >70

### Revenue Metrics

- ✅ **Monthly Recurring Revenue**: €200.000 bis Ende Phase 4
- ✅ **Subscription Conversion**: >25% Trial-to-Paid Conversion
- ✅ **Payment Success Rate**: >98% erfolgreiche Transaktionen
- ✅ **Customer Retention**: >90% Monthly Retention Rate

---

## 👥 Phase 4: Ressourcenplanung

### Development Team

**Core Team (3 Monate):**
- **1× Tech Lead/Architect**: System Design, Code Reviews
- **2× Senior Laravel Developers**: API Development, Multi-tenant Architecture  
- **1× Frontend Developer**: PWA Implementation, UI/UX
- **1× DevOps Engineer**: Infrastructure, Deployment, Monitoring
- **1× QA Engineer**: Testing, Quality Assurance

**External Resources:**
- **Payment Integration Specialist**: 2 Wochen für Payment Gateway Setup
- **Security Consultant**: 1 Woche für Penetration Testing
- **Performance Engineer**: 1 Woche für Load Testing und Optimization

### Infrastructure Costs

**Monthly Operating Costs (Production):**
- **Application Servers**: 3× €200 = €600
- **Database**: PostgreSQL Cluster €400  
- **Redis Cache**: Cluster €200
- **CDN**: Cloudflare Pro €300
- **Monitoring**: Elastic Cloud €250
- **Total**: ~€1.750/Monat

---

## 📅 Phase 4: Zeitplan & Meilensteine

### Detaillierter 3-Monats-Plan

**Monat 10: Foundation & Integrations**

*Woche 37-38: API Foundation*
- OpenAPI Documentation Generator
- API Versioning Middleware  
- Rate Limiting Implementation
- Webhook Delivery System

*Woche 39-40: External Integrations*
- DBB API Integration & Testing
- Social Media Automation Setup
- Payment Gateway Configuration
- Cloud Storage Migration

**Monat 11: PWA & Performance**

*Woche 41-42: Progressive Web App*
- Service Worker Implementation
- Push Notification System
- App Shell Architecture
- Offline Functionality

*Woche 43-44: Performance Optimization*
- Database Query Optimization
- CDN Setup & Configuration
- Memory Usage Optimization
- Performance Monitoring

**Monat 12: Multi-Tenant & Launch**

*Woche 45-46: Multi-Tenant Architecture*
- Tenant Model & Middleware
- Row Level Security Setup
- Subscription Management
- Customization System

*Woche 47-48: Testing & Launch*
- Comprehensive Testing Suite
- Security Audit & Penetration Testing
- Performance Tuning
- Production Deployment

### Kritische Meilensteine

- **M10.1** (Ende Woche 38): API Foundation Complete
- **M10.2** (Ende Woche 40): External Integrations Live
- **M11.1** (Ende Woche 42): PWA Features Functional  
- **M11.2** (Ende Woche 44): Performance Targets Met
- **M12.1** (Ende Woche 46): Multi-Tenant Architecture Ready
- **M12.2** (Ende Woche 48): **🚀 Phase 4 Launch - Enterprise SaaS Ready**

---

**Phase 4 schließt die technische Entwicklung von BasketManager Pro ab und bereitet das System für den Enterprise-Einsatz mit Multi-Tenant-Fähigkeiten, umfassenden Integrationen und optimaler Performance vor. Das System ist nun bereit für die finale Phase 5 mit Emergency & Compliance Features.**
```

#### 🌐 External Integrations

**Kernziele:**
- Basketball Federation APIs (DBB, FIBA) für offizielle Daten
- Social Media Integration für Marketing und Fan-Engagement
- Payment Gateways für Membership und Event Fees
- Cloud Services für Scalability und Backup
- Third-party Analytics für Business Intelligence

**Laravel Implementation:**

```php
// Basketball Federation API Integration
class BasketballFederationService
{
    public function __construct(
        private Http $http,
        private Cache $cache
    ) {}
    
    public function syncOfficialGameData(Game $game): array
    {
        // DBB (Deutscher Basketball Bund) API Integration
        $dbbData = $this->fetchFromDBB($game->external_game_id);
        
        if ($dbbData) {
            $this->updateGameFromOfficialData($game, $dbbData);
        }
        
        return $dbbData;
    }
    
    private function fetchFromDBB(string $gameId): ?array
    {
        $cacheKey = "dbb:game:{$gameId}";
        
        return $this->cache->remember($cacheKey, 300, function () use ($gameId) {
            $response = $this->http
                ->withToken(config('services.dbb.api_key'))
                ->get("https://api.basketball-bund.de/v1/games/{$gameId}");
                
            return $response->successful() ? $response->json() : null;
        });
    }
    
    public function submitGameResults(Game $game): bool
    {
        if (!$game->isOfficial()) {
            return false;
        }
        
        $payload = [
            'game_id' => $game->external_game_id,
            'home_score' => $game->final_score_home,
            'away_score' => $game->final_score_away,
            'period_scores' => $game->period_scores,
            'player_statistics' => $this->formatPlayerStats($game),
            'officials' => $game->officials,
            'timestamp' => $game->completed_at->toISOString()
        ];
        
        $response = $this->http
            ->withToken(config('services.dbb.api_key'))
            ->post("https://api.basketball-bund.de/v1/games/{$game->external_game_id}/results", $payload);
            
        return $response->successful();
    }
}

// Social Media Integration Service
class SocialMediaService
{
    public function __construct(
        private SocialiteService $socialite
    ) {}
    
    public function postGameHighlight(Game $game, Media $highlight): array
    {
        $results = [];
        
        // Facebook Integration
        if ($game->team->social_settings['facebook_enabled']) {
            $results['facebook'] = $this->postToFacebook($game, $highlight);
        }
        
        // Instagram Integration
        if ($game->team->social_settings['instagram_enabled']) {
            $results['instagram'] = $this->postToInstagram($game, $highlight);
        }
        
        // Twitter Integration
        if ($game->team->social_settings['twitter_enabled']) {
            $results['twitter'] = $this->postToTwitter($game, $highlight);
        }
        
        return $results;
    }
    
    private function postToFacebook(Game $game, Media $highlight): array
    {
        $facebook = new Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v18.0'
        ]);
        
        $message = "🏀 Highlight aus dem Spiel {$game->homeTeam->name} vs {$game->awayTeam->name}! " . 
                  "Endstand: {$game->final_score_home}:{$game->final_score_away}";
        
        try {
            $response = $facebook->post('/me/videos', [
                'source' => $facebook->videoToUpload($highlight->getPath()),
                'description' => $message
            ], $game->team->facebook_token);
            
            return ['success' => true, 'post_id' => $response->getGraphNode()['id']];
        } catch (FacebookResponseException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Payment Gateway Integration
class PaymentService
{
    public function __construct(
        private StripeService $stripe,
        private PayPalService $paypal
    ) {}
    
    public function processTeamRegistrationPayment(Team $team, Tournament $tournament): PaymentResult
    {
        $amount = $tournament->entry_fee;
        $currency = $tournament->currency;
        
        $paymentMethod = $team->preferred_payment_method ?? 'stripe';
        
        return match($paymentMethod) {
            'stripe' => $this->processStripePayment($team, $amount, $currency, [
                'description' => "Tournament registration: {$tournament->name}",
                'metadata' => [
                    'team_id' => $team->id,
                    'tournament_id' => $tournament->id,
                    'type' => 'tournament_registration'
                ]
            ]),
            'paypal' => $this->processPayPalPayment($team, $amount, $currency),
            'sepa' => $this->processSEPADirectDebit($team, $amount, $currency),
            default => throw new UnsupportedPaymentMethodException($paymentMethod)
        };
    }
    
    private function processStripePayment(Team $team, float $amount, string $currency, array $metadata): PaymentResult
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => strtolower($currency),
                'customer' => $team->stripe_customer_id,
                'description' => $metadata['description'],
                'metadata' => $metadata['metadata'],
                'automatic_payment_methods' => ['enabled' => true]
            ]);
            
            return PaymentResult::success($paymentIntent->id, $paymentIntent->client_secret);
        } catch (StripeException $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }
}

// Cloud Storage Integration
class CloudStorageService
{
    public function __construct(
        private FilesystemManager $filesystem
    ) {}
    
    public function storeGameVideo(Game $game, UploadedFile $video): Media
    {
        // Store original video
        $path = $this->filesystem->disk('s3')->putFileAs(
            "games/{$game->id}/videos",
            $video,
            $video->hashName()
        );
        
        // Create media record
        $media = $game->addMediaFromDisk($path, 's3')
                     ->toMediaCollection('game_videos');
        
        // Queue video processing job
        ProcessGameVideoJob::dispatch($media);
        
        return $media;
    }
    
    public function generateCDNUrl(Media $media, array $transformations = []): string
    {
        $baseUrl = config('services.cloudflare.cdn_url');
        $path = $media->getPath();
        
        if (!empty($transformations)) {
            $transformQuery = http_build_query($transformations);
            return "{$baseUrl}/{$path}?{$transformQuery}";
        }
        
        return "{$baseUrl}/{$path}";
    }
}
```

#### 📱 PWA (Progressive Web App) Features

**Kernziele:**
- Service Worker für Offline-Funktionalität und Caching
- Push Notifications für Real-time Updates
- App Shell Architecture für schnelle Ladezeiten
- Installierbare Web App mit Native App Experience
- Background Sync für Offline-Aktionen

**Laravel Implementation:**

```php
// PWA Service Provider
class PWAServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PWAService::class);
        $this->app->singleton(PushNotificationService::class);
    }
    
    public function boot(): void
    {
        // Register PWA routes
        Route::get('/manifest.json', [PWAController::class, 'manifest']);
        Route::get('/sw.js', [PWAController::class, 'serviceWorker']);
        Route::post('/api/push/subscribe', [PushController::class, 'subscribe']);
        
        // Blade directives for PWA
        Blade::directive('pwa', function () {
            return "<?php echo view('pwa.meta-tags'); ?>";
        });
    }
}

// PWA Controller
class PWAController extends Controller
{
    public function manifest(): JsonResponse
    {
        $manifest = [
            'name' => 'BasketManager Pro',
            'short_name' => 'BMPro',
            'description' => 'Professional Basketball Club Management',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#ff6b35',
            'orientation' => 'portrait',
            'categories' => ['sports', 'productivity'],
            'icons' => [
                [
                    'src' => '/images/icons/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => '/images/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ],
            'shortcuts' => [
                [
                    'name' => 'Live Games',
                    'short_name' => 'Live',
                    'url' => '/games/live',
                    'icons' => [['src' => '/images/icons/live-96x96.png', 'sizes' => '96x96']]
                ],
                [
                    'name' => 'Team Stats',
                    'short_name' => 'Stats',
                    'url' => '/statistics',
                    'icons' => [['src' => '/images/icons/stats-96x96.png', 'sizes' => '96x96']]
                ]
            ]
        ];
        
        return response()->json($manifest);
    }
    
    public function serviceWorker(): Response
    {
        $serviceWorkerContent = view('pwa.service-worker')->render();
        
        return response($serviceWorkerContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}

// Push Notification Service
class PushNotificationService
{
    public function __construct(
        private Minishlink\WebPush\WebPush $webPush
    ) {}
    
    public function sendGameStartNotification(Game $game): void
    {
        $payload = [
            'title' => '🏀 Spiel beginnt!',
            'body' => "{$game->homeTeam->name} vs {$game->awayTeam->name}",
            'icon' => '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => "game-start-{$game->id}",
            'data' => [
                'game_id' => $game->id,
                'url' => "/games/{$game->id}/live",
                'actions' => [
                    ['action' => 'view', 'title' => 'Live verfolgen'],
                    ['action' => 'dismiss', 'title' => 'Später']
                ]
            ]
        ];
        
        $subscribers = $this->getSubscribersForGame($game);
        
        foreach ($subscribers as $subscription) {
            SendPushNotificationJob::dispatch($subscription, $payload);
        }
    }
    
    private function getSubscribersForGame(Game $game): Collection
    {
        return PushSubscription::whereHas('user.teams', function ($query) use ($game) {
            $query->whereIn('id', [$game->home_team_id, $game->away_team_id]);
        })->get();
    }
}

// Service Worker Template (resources/views/pwa/service-worker.blade.php)
const CACHE_NAME = 'basketmanager-v{{ config("app.version") }}';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192x192.png',
    '/offline.html'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});

// Fetch event with network-first strategy for API calls
self.addEventListener('fetch', (event) => {
    if (event.request.url.includes('/api/')) {
        // Network first for API calls
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
    } else {
        // Cache first for static assets
        event.respondWith(
            caches.match(event.request)
                .then((response) => response || fetch(event.request))
        );
    }
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    // Sync offline actions when connection is restored
    const offlineActions = await getOfflineActions();
    
    for (const action of offlineActions) {
        try {
            await fetch(action.url, {
                method: action.method,
                headers: action.headers,
                body: action.body
            });
            await removeOfflineAction(action.id);
        } catch (error) {
            console.error('Background sync failed:', error);
        }
    }
}

// Push notification handler
self.addEventListener('push', (event) => {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        data: data.data,
        actions: data.data.actions,
        requireInteraction: true
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});
```

#### ⚡ Performance Optimization

**Kernziele:**
- Database Query Optimization mit Advanced Indexing
- CDN Integration für globale Content Delivery
- Image Optimization mit WebP und Lazy Loading
- Code Splitting und Bundle Optimization
- Memory Usage Optimization und Monitoring

**Laravel Implementation:**

```php
// Database Performance Optimization Service
class DatabaseOptimizationService
{
    public function __construct(
        private DB $db,
        private Cache $cache
    ) {}
    
    public function optimizeGameQueries(): void
    {
        // Create composite indexes for frequent queries
        $this->db->statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_team_season_status 
            ON games (home_team_id, season, status) 
            INCLUDE (away_team_id, scheduled_at, final_score_home, final_score_away)
        ');
        
        $this->db->statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_performance 
            ON game_actions (game_id, player_id, action_type) 
            INCLUDE (points, quarter, time_remaining)
        ');
        
        // Partitioning for large tables
        $this->partitionGameActionsByMonth();
    }
    
    private function partitionGameActionsByMonth(): void
    {
        $this->db->statement('
            CREATE TABLE IF NOT EXISTS game_actions_partitioned (
                LIKE game_actions INCLUDING ALL
            ) PARTITION BY RANGE (created_at)
        ');
        
        // Create monthly partitions for current and next 12 months
        for ($i = 0; $i < 12; $i++) {
            $startDate = now()->addMonths($i)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $tableName = 'game_actions_' . $startDate->format('Y_m');
            
            $this->db->statement("
                CREATE TABLE IF NOT EXISTS {$tableName} 
                PARTITION OF game_actions_partitioned 
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate}')
            ");
        }
    }
    
    public function getOptimizedTeamStats(Team $team, string $season): array
    {
        // Use materialized view for complex statistics
        return $this->cache->remember(
            "team_stats_optimized:{$team->id}:{$season}",
            3600,
            function () use ($team, $season) {
                return $this->db->select('
                    SELECT * FROM materialized_team_statistics 
                    WHERE team_id = ? AND season = ?
                ', [$team->id, $season]);
            }
        );
    }
}

// CDN Integration Service
class CDNService
{
    public function __construct(
        private CloudflareService $cloudflare
    ) {}
    
    public function optimizeAssets(): void
    {
        // Purge CDN cache for updated assets
        $this->cloudflare->purgeCache([
            'files' => [
                config('app.url') . '/css/app.css',
                config('app.url') . '/js/app.js'
            ]
        ]);
        
        // Enable auto-minification
        $this->cloudflare->updateSettings([
            'minify' => [
                'css' => 'on',
                'html' => 'on',
                'js' => 'on'
            ],
            'brotli' => 'on',
            'rocket_loader' => 'on'
        ]);
    }
    
    public function generateOptimizedImageUrl(string $imagePath, array $options = []): string
    {
        $baseUrl = config('services.cloudflare.images_url');
        $transformations = [];
        
        if (isset($options['width'])) {
            $transformations[] = "w={$options['width']}";
        }
        if (isset($options['height'])) {
            $transformations[] = "h={$options['height']}";
        }
        if (isset($options['quality'])) {
            $transformations[] = "q={$options['quality']}";
        }
        if (isset($options['format'])) {
            $transformations[] = "f={$options['format']}";
        }
        
        $transform = !empty($transformations) ? '/' . implode(',', $transformations) : '';
        
        return "{$baseUrl}{$transform}/{$imagePath}";
    }
}

// Image Optimization Service
class ImageOptimizationService
{
    public function __construct(
        private InterventionImage $image,
        private Storage $storage
    ) {}
    
    public function processAndOptimizeImage(UploadedFile $file, string $collection): Media
    {
        $optimizedImages = [];
        
        // Generate multiple sizes
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 500, 'height' => 500],
            'large' => ['width' => 1200, 'height' => 1200]
        ];
        
        foreach ($sizes as $sizeName => $dimensions) {
            $optimizedImages[$sizeName] = $this->createOptimizedVersion(
                $file, 
                $dimensions['width'], 
                $dimensions['height']
            );
        }
        
        // Convert to WebP for better compression
        $webpVersions = [];
        foreach ($optimizedImages as $sizeName => $imagePath) {
            $webpVersions[$sizeName] = $this->convertToWebP($imagePath);
        }
        
        return $this->storeOptimizedImages($optimizedImages, $webpVersions, $collection);
    }
    
    private function convertToWebP(string $imagePath): string
    {
        $webpPath = str_replace(pathinfo($imagePath, PATHINFO_EXTENSION), 'webp', $imagePath);
        
        $this->image->make($imagePath)
                   ->encode('webp', 85)
                   ->save($webpPath);
                   
        return $webpPath;
    }
}

// Performance Monitoring Service
class PerformanceMonitoringService
{
    public function __construct(
        private Logger $logger,
        private Cache $cache
    ) {}
    
    public function trackApiResponse(Request $request, Response $response, float $executionTime): void
    {
        $metrics = [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->status(),
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_peak_usage(true),
            'query_count' => $this->getQueryCount(),
            'timestamp' => now()->toISOString()
        ];
        
        // Log slow requests
        if ($executionTime > 1000) { // 1 second
            $this->logger->warning('Slow API request detected', $metrics);
        }
        
        // Store metrics for analysis
        $this->storeMetrics($metrics);
    }
    
    private function storeMetrics(array $metrics): void
    {
        $key = 'api_metrics:' . now()->format('Y-m-d-H');
        
        $this->cache->remember($key, 3600, function () {
            return [];
        });
        
        $existingMetrics = $this->cache->get($key, []);
        $existingMetrics[] = $metrics;
        
        $this->cache->put($key, $existingMetrics, 3600);
    }
    
    public function getPerformanceReport(string $period = '24h'): array
    {
        $endTime = now();
        $startTime = match($period) {
            '1h' => $endTime->copy()->subHour(),
            '24h' => $endTime->copy()->subDay(),
            '7d' => $endTime->copy()->subWeek(),
            default => $endTime->copy()->subDay()
        };
        
        return [
            'average_response_time' => $this->calculateAverageResponseTime($startTime, $endTime),
            'slowest_endpoints' => $this->getSlowestEndpoints($startTime, $endTime, 10),
            'error_rate' => $this->calculateErrorRate($startTime, $endTime),
            'throughput' => $this->calculateThroughput($startTime, $endTime),
            'memory_usage_trend' => $this->getMemoryUsageTrend($startTime, $endTime)
        ];
    }
}
```

#### 🏢 Multi-tenant Architecture

**Kernziele:**
- Tenant Isolation mit Single Database + Row Level Security
- Domain-based Tenant Routing und Resolution
- Tenant-specific Customization (Themes, Features, Branding)
- Subscription Management mit Usage-based Billing
- Cross-tenant Security und Data Protection

**Laravel Implementation:**

```php
// Tenant Model
class Tenant extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = [
        'name', 'slug', 'domain', 'subdomain', 
        'database_name', 'settings', 'subscription_tier',
        'is_active', 'trial_ends_at', 'billing_email'
    ];
    
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'features' => 'array'
    ];
    
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
    public function subscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class);
    }
    
    public function usage(): HasMany
    {
        return $this->hasMany(TenantUsage::class);
    }
    
    // Tenant Resolution Methods
    public static function resolveFromDomain(string $domain): ?self
    {
        return static::where('domain', $domain)
                    ->orWhere('subdomain', $domain)
                    ->where('is_active', true)
                    ->first();
    }
    
    public function hasFeature(string $feature): bool
    {
        $tierFeatures = config("tenants.tiers.{$this->subscription_tier}.features", []);
        return in_array($feature, $tierFeatures) || in_array($feature, $this->features ?? []);
    }
    
    public function getCustomization(string $key, $default = null)
    {
        return data_get($this->settings, "customization.{$key}", $default);
    }
}

// Tenant Resolution Middleware
class ResolveTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->getHost();
        $tenant = Tenant::resolveFromDomain($domain);
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        if (!$tenant->is_active) {
            abort(503, 'Tenant account is suspended');
        }
        
        // Set tenant context
        app()->instance('tenant', $tenant);
        app()->instance('tenant.id', $tenant->id);
        
        // Configure tenant-specific database connection
        if ($tenant->database_name) {
            config(['database.connections.tenant.database' => $tenant->database_name]);
            DB::setDefaultConnection('tenant');
        }
        
        // Apply tenant-specific configurations
        $this->applyTenantConfiguration($tenant);
        
        return $next($request);
    }
    
    private function applyTenantConfiguration(Tenant $tenant): void
    {
        // Set tenant-specific app name
        config(['app.name' => $tenant->getCustomization('app_name', config('app.name'))]);
        
        // Apply tenant theme
        $theme = $tenant->getCustomization('theme', 'default');
        view()->share('tenant_theme', $theme);
        
        // Set tenant-specific mail configuration
        if ($mailConfig = $tenant->getCustomization('mail_config')) {
            config(['mail.default' => 'tenant']);
            config(['mail.mailers.tenant' => $mailConfig]);
        }
    }
}

// Tenant Scoping Trait
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (app()->bound('tenant.id')) {
                $model->tenant_id = app('tenant.id');
            }
        });
        
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant.id')) {
                $query->where('tenant_id', app('tenant.id'));
            }
        });
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

// Tenant Subscription Service
class TenantSubscriptionService
{
    public function __construct(
        private PaymentService $paymentService,
        private UsageTracker $usageTracker
    ) {}
    
    public function calculateMonthlyBill(Tenant $tenant): array
    {
        $subscription = $tenant->subscription;
        $baseFee = config("tenants.tiers.{$tenant->subscription_tier}.monthly_fee");
        
        $usage = $this->usageTracker->getMonthlyUsage($tenant);
        $overageCharges = $this->calculateOverageCharges($tenant, $usage);
        
        return [
            'base_fee' => $baseFee,
            'usage' => $usage,
            'overage_charges' => $overageCharges,
            'total' => $baseFee + array_sum($overageCharges),
            'breakdown' => [
                'api_calls' => $overageCharges['api_calls'] ?? 0,
                'storage' => $overageCharges['storage'] ?? 0,
                'users' => $overageCharges['users'] ?? 0,
                'games_processed' => $overageCharges['games_processed'] ?? 0
            ]
        ];
    }
    
    private function calculateOverageCharges(Tenant $tenant, array $usage): array
    {
        $limits = config("tenants.tiers.{$tenant->subscription_tier}.limits");
        $rates = config("tenants.overage_rates");
        $charges = [];
        
        foreach ($usage as $metric => $used) {
            $limit = $limits[$metric] ?? PHP_INT_MAX;
            if ($used > $limit) {
                $overage = $used - $limit;
                $charges[$metric] = $overage * ($rates[$metric] ?? 0);
            }
        }
        
        return $charges;
    }
    
    public function upgradeTenant(Tenant $tenant, string $newTier): bool
    {
        $oldTier = $tenant->subscription_tier;
        $tenant->update(['subscription_tier' => $newTier]);
        
        // Calculate prorated charges
        $proratedCharge = $this->calculateProratedUpgrade($tenant, $oldTier, $newTier);
        
        if ($proratedCharge > 0) {
            $payment = $this->paymentService->chargeTenant($tenant, $proratedCharge);
            
            if (!$payment->successful()) {
                // Rollback upgrade
                $tenant->update(['subscription_tier' => $oldTier]);
                return false;
            }
        }
        
        // Update feature access
        $this->updateTenantFeatures($tenant);
        
        return true;
    }
}

// Tenant Customization Service
class TenantCustomizationService
{
    public function applyTheme(Tenant $tenant, array $themeConfig): void
    {
        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'customization' => array_merge(
                    $tenant->getCustomization('', []),
                    [
                        'theme' => $themeConfig,
                        'updated_at' => now()->toISOString()
                    ]
                )
            ])
        ]);
        
        // Clear tenant cache
        Cache::tags(["tenant:{$tenant->id}"])->flush();
    }
    
    public function uploadTenantLogo(Tenant $tenant, UploadedFile $logo): string
    {
        $path = $logo->storeAs(
            "tenants/{$tenant->id}/branding",
            'logo.' . $logo->getClientOriginalExtension(),
            'public'
        );
        
        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'customization' => array_merge(
                    $tenant->getCustomization('', []),
                    ['logo_path' => $path]
                )
            ])
        ]);
        
        return Storage::url($path);
    }
    
    public function generateTenantCSS(Tenant $tenant): string
    {
        $theme = $tenant->getCustomization('theme', []);
        
        $css = ":root {\n";
        
        if (isset($theme['primary_color'])) {
            $css .= "  --tenant-primary: {$theme['primary_color']};\n";
        }
        
        if (isset($theme['secondary_color'])) {
            $css .= "  --tenant-secondary: {$theme['secondary_color']};\n";
        }
        
        if (isset($theme['font_family'])) {
            $css .= "  --tenant-font: '{$theme['font_family']}';\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
}
```

#### 🧪 Testing Strategy für Phase 4

**Comprehensive Testing Approach:**

```php
// Integration Tests für External APIs
class ExternalIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_dbb_api_integration_syncs_game_data(): void
    {
        $game = Game::factory()->create(['external_game_id' => 'DBB123456']);
        
        Http::fake([
            'api.basketball-bund.de/*' => Http::response([
                'game_id' => 'DBB123456',
                'home_score' => 78,
                'away_score' => 65,
                'status' => 'completed'
            ])
        ]);
        
        $service = app(BasketballFederationService::class);
        $result = $service->syncOfficialGameData($game);
        
        $this->assertNotNull($result);
        $game->refresh();
        
        $this->assertEquals(78, $game->final_score_home);
        $this->assertEquals(65, $game->final_score_away);
    }
    
    public function test_payment_processing_handles_failed_transactions(): void
    {
        $team = Team::factory()->create();
        $tournament = Tournament::factory()->create(['entry_fee' => 50.00]);
        
        // Mock Stripe failure
        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('paymentIntents->create')
                 ->andThrow(new StripeException('Card declined'));
        });
        
        $paymentService = app(PaymentService::class);
        $result = $paymentService->processTeamRegistrationPayment($team, $tournament);
        
        $this->assertFalse($result->successful());
        $this->assertStringContains('Card declined', $result->error());
    }
}

// Performance Tests
class PerformanceTest extends TestCase
{
    public function test_api_response_times_meet_requirements(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(15)->create(['team_id' => $team->id]);
        
        $startTime = microtime(true);
        
        $response = $this->getJson("/api/v4/teams/{$team->id}/statistics");
        
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(200, $executionTime, 'API response time should be under 200ms');
        $response->assertStatus(200);
    }
    
    public function test_database_queries_are_optimized(): void
    {
        DB::enableQueryLog();
        
        $team = Team::factory()->create();
        Game::factory()->count(20)->create(['home_team_id' => $team->id]);
        
        $this->getJson("/api/v4/teams/{$team->id}/games");
        
        $queries = DB::getQueryLog();
        
        // Should not exceed 5 queries due to eager loading
        $this->assertLessThanOrEqual(5, count($queries));
    }
}

// Multi-tenant Tests
class MultiTenantTest extends TestCase
{
    public function test_tenant_data_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        // Create data for tenant 1
        app()->instance('tenant.id', $tenant1->id);
        $team1 = Team::factory()->create();
        
        // Switch to tenant 2
        app()->instance('tenant.id', $tenant2->id);
        $team2 = Team::factory()->create();
        
        // Verify isolation
        $this->assertNotEquals($team1->tenant_id, $team2->tenant_id);
        
        // Verify queries are scoped
        $teams = Team::all();
        $this->assertCount(1, $teams);
        $this->assertEquals($tenant2->id, $teams->first()->tenant_id);
    }
    
    public function test_tenant_feature_restrictions(): void
    {
        $basicTenant = Tenant::factory()->create(['subscription_tier' => 'basic']);
        $enterpriseTenant = Tenant::factory()->create(['subscription_tier' => 'enterprise']);
        
        $this->assertFalse($basicTenant->hasFeature('advanced_analytics'));
        $this->assertTrue($enterpriseTenant->hasFeature('advanced_analytics'));
    }
}

// PWA Tests
class PWATest extends TestCase
{
    public function test_manifest_contains_required_fields(): void
    {
        $response = $this->get('/manifest.json');
        
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonStructure([
                    'name',
                    'short_name',
                    'start_url',
                    'display',
                    'icons' => [
                        '*' => ['src', 'sizes', 'type']
                    ]
                ]);
    }
    
    public function test_service_worker_caches_assets(): void
    {
        $response = $this->get('/sw.js');
        
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/javascript');
        
        $content = $response->getContent();
        $this->assertStringContains('CACHE_NAME', $content);
        $this->assertStringContains('urlsToCache', $content);
    }
}
```

#### 📈 Success Metrics für Phase 4

- ✅ **API Performance**: Response Times <100ms für Cached Requests
- ✅ **Integration Uptime**: >99.5% Verfügbarkeit für External Services
- ✅ **PWA Adoption**: >60% der Mobile Users installieren die PWA
- ✅ **Multi-tenant Efficiency**: Tenant Onboarding <5 Minuten
- ✅ **Database Performance**: Query Times <50ms für 95% aller Requests
- ✅ **CDN Hit Rate**: >85% der Assets werden vom CDN ausgeliefert
- ✅ **Payment Success Rate**: >98% erfolgreiche Transaktionen
- ✅ **Push Notification Engagement**: >40% Click-through Rate
- ✅ **Background Sync Success**: >95% der Offline-Aktionen werden synchronisiert
- ✅ **Memory Usage**: <512MB durchschnittlicher RAM-Verbrauch pro Request

#### 🚀 Deployment Strategy für Phase 4

**Zero-Downtime Deployment mit Blue-Green Strategy:**

```bash
# Deployment Script für Phase 4
#!/bin/bash

# Pre-deployment checks
php artisan basketball:health-check --critical
php artisan basketball:check-external-apis
php artisan queue:work --stop-when-empty

# Database migrations (safe for production)
php artisan migrate --force

# Clear and warm caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Deploy service worker updates
php artisan pwa:generate-manifest
php artisan pwa:update-service-worker

# Update CDN assets
php artisan cdn:push-assets

# Restart queue workers
php artisan horizon:terminate

# Warm application cache
php artisan basketball:warm-cache --all

# Verify deployment
php artisan basketball:verify-integrations
```

Phase 4 schließt die technische Entwicklung von BasketManager Pro ab und bereitet das System für den Enterprise-Einsatz mit Multi-Tenant-Fähigkeiten, umfassenden Integrationen und optimaler Performance vor.

### Phase 5: Emergency & Compliance (Monate 13-15)
- **Emergency Contacts System (aus Basketball_Notfallkontakte_PRD.md)**
- **QR-Code Access System**
- **GDPR/DSGVO Compliance**
- **Mobile Emergency Views**
- **Audit & Security Features**

---

## 🔄 Migration vom Legacy System

### Daten-Migration Strategy

#### Migration Commands
```php
class MigrateLegacyDataCommand extends Command
{
    protected $signature = 'basketball:migrate-legacy {--table=} {--batch-size=100}';
    protected $description = 'Migrate data from legacy BasketManager Pro system';
    
    public function handle(): int
    {
        $this->info('Starting legacy data migration...');
        
        $tables = $this->option('table') 
            ? [$this->option('table')] 
            : ['users', 'teams', 'players', 'games', 'statistics'];
        
        foreach ($tables as $table) {
            $this->info("Migrating {$table}...");
            $this->call("basketball:migrate-{$table}");
        }
        
        $this->info('Legacy migration completed!');
        return Command::SUCCESS;
    }
}

class MigrateUsersCommand extends Command
{
    protected $signature = 'basketball:migrate-users {--batch-size=100}';
    
    public function handle(): int
    {
        $legacyUsers = DB::connection('legacy')->table('users')->get();
        
        $bar = $this->output->createProgressBar($legacyUsers->count());
        
        foreach ($legacyUsers->chunk($this->option('batch-size')) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $legacyUser) {
                    User::create([
                        'id' => $legacyUser->id,
                        'name' => $legacyUser->name,
                        'email' => $legacyUser->email,
                        'password' => $legacyUser->password, // Already hashed
                        'email_verified_at' => $legacyUser->email_verified_at,
                        'legacy_migrated_at' => now(),
                        'created_at' => $legacyUser->created_at,
                        'updated_at' => $legacyUser->updated_at,
                    ]);
                    
                    // Migrate roles
                    if ($legacyUser->role) {
                        $user = User::find($legacyUser->id);
                        $user->assignRole($legacyUser->role);
                    }
                }
            });
            
            $bar->advance($chunk->count());
        }
        
        $bar->finish();
        $this->newLine();
        
        return Command::SUCCESS;
    }
}
```

### Legacy System Compatibility

#### API Bridge für Legacy Clients
```php
class LegacyApiController extends Controller
{
    /**
     * Legacy API compatibility layer
     * Maps old API calls to new Laravel endpoints
     */
    
    public function legacyGetTeams(Request $request): JsonResponse
    {
        // Transform legacy request format to new format
        $transformedRequest = $this->transformLegacyTeamsRequest($request);
        
        // Call new API
        $teams = app(Api\V2\TeamsController::class)->index($transformedRequest);
        
        // Transform response back to legacy format
        return $this->transformToLegacyResponse($teams);
    }
    
    private function transformLegacyTeamsRequest(Request $request): Request
    {
        $newParams = [];
        
        // Map legacy parameters to new ones
        if ($request->has('clubId')) {
            $newParams['club_id'] = $request->clubId;
        }
        
        if ($request->has('seasonYear')) {
            $newParams['season'] = $request->seasonYear;
        }
        
        return new Request($newParams);
    }
    
    private function transformToLegacyResponse($teams): JsonResponse
    {
        // Transform Laravel API Resource format to legacy format
        $legacyFormat = [
            'success' => true,
            'data' => $teams['data'],
            'total' => $teams['meta']['total'] ?? count($teams['data']),
            'pagination' => [
                'page' => $teams['meta']['current_page'] ?? 1,
                'perPage' => $teams['meta']['per_page'] ?? 15,
            ]
        ];
        
        return response()->json($legacyFormat);
    }
}
```

---

## 📝 Zusammenfassung

Dieses Master-PRD definiert die vollständige Laravel-Migration des BasketManager Pro Systems mit folgenden Kernkomponenten:

### ✅ Technische Highlights
- **Laravel 11** mit modernen PHP 8.3+ Features
- **Domain-Driven Design** Architektur
- **API-First** Ansatz mit Laravel API Resources
- **Real-time Features** mit Broadcasting und WebSockets
- **Advanced Caching** mit Redis und Elasticsearch
- **Comprehensive Testing** Strategy
- **Docker & Forge** Deployment Pipeline

### ✅ Basketball-spezifische Features
- **Live-Game Scoring** mit Real-time Updates
- **Advanced Statistics** Engine mit Predictive Analytics
- **Training & Drill** Management System
- **Tournament** Management mit komplexen Brackets
- **Emergency Contacts** System (Integration aus Basketball_Notfallkontakte_PRD.md)
- **GDPR/DSGVO** Compliance für deutsche Basketball-Vereine

### ✅ Skalierung & Performance
- **Multi-tenant** Architektur für mehrere Vereine
- **Optimierte Datenbankabfragen** mit Eloquent
- **Background Job Processing** mit Laravel Horizon
- **CDN Integration** für Media Files
- **Elasticsearch** für erweiterte Suchfunktionen

### ✅ Security & Compliance
- **Multi-layer Authentication** mit Sanctum
- **Role-based Access Control** mit Spatie Laravel Permission
- **Emergency Access System** mit QR-Codes
- **Audit Logging** für alle kritischen Aktionen
- **GDPR Data Export/Deletion** Features

Dieses Master-PRD bildet die Grundlage für die 5 spezifischen Phasen-PRDs, die jeweils detaillierte Implementierungspläne für die einzelnen Entwicklungsabschnitte enthalten.

---

*© 2025 BasketManager Pro - Laravel Master PRD v1.0*