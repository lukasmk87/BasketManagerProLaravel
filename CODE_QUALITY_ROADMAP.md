# 📐 Code Quality & Architecture Roadmap - BasketManager Pro

> **Langfristige Verbesserungen für Wartbarkeit, Skalierbarkeit und Code-Qualität**

**Erstellt:** 2025-01-24
**Status:** 🟡 Mittelfristige Prioritäten
**Geschätzter Gesamtaufwand:** 160-220 Stunden (4-5.5 Wochen)

---

## 📊 Overview

| Kategorie | Anzahl | Sprint | Aufwand | Fortschritt |
|-----------|--------|--------|---------|-------------|
| **God Services Refactoring** | 5 | Sprint 4 | 40-60h | 5/5 ✅ |
| **God Controllers Refactoring** | 5 | Sprint 4 | 20-30h | 0/5 |
| **Architektur-Verbesserungen** | 10 | Sprint 5 | 30-40h | 0/10 |
| **Fehlende Features** | 8 | Sprint 6 | 20-30h | 0/8 |
| **Dependency Updates** | 6 | Sprint 7 | 12-18h | 0/6 |
| **Dokumentation** | 5 | Sprint 8 | 8-12h | 0/5 |
| **Gesamt** | **39** | **5 Sprints** | **130-190h** | **5/39** |

---

## 🏗️ CODE QUALITY REFACTORING (PRIORITY 2)

### REFACTOR-001: AIVideoAnalysisService splitten ✅ ERLEDIGT

**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 1,077 Zeilen
**Ergebnis:** 7 fokussierte Services (~100-180 Zeilen je)
**Aufwand:** Abgeschlossen am 2025-01-28

#### Problem

God Service mit zu vielen Verantwortlichkeiten:
- Video Frame Extraction
- AI Player Detection
- AI Court Detection
- AI Action Recognition
- Video Annotation Generation
- Shot Chart Generation
- Performance Analysis

**Betroffene Datei:** `app/Services/AIVideoAnalysisService.php`

#### Lösung: Service-Splitting

**Neue Service-Struktur:**

```
app/Services/ML/VideoAnalysis/
├── VideoFrameExtractor.php         (~150 LOC)
├── AIPlayerDetectionService.php    (~180 LOC)
├── AICourtDetectionService.php     (~120 LOC)
├── AIActionRecognitionService.php  (~200 LOC)
├── VideoAnnotationGenerator.php    (~150 LOC)
└── VideoAnalysisFacade.php         (~100 LOC) - Orchestrator
```

**1. VideoFrameExtractor.php**
```php
<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use Illuminate\Support\Facades\Storage;

class VideoFrameExtractor
{
    /**
     * Extract frames from video at specified interval
     */
    public function extractFrames(
        VideoFile $video,
        int $intervalSeconds = 1,
        string $outputFormat = 'jpg'
    ): array {
        $outputPath = storage_path("app/frames/{$video->id}");

        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        // FFmpeg Frame Extraction
        $command = sprintf(
            'ffmpeg -i %s -vf fps=1/%d %s/frame_%%04d.%s',
            escapeshellarg($video->file_path),
            $intervalSeconds,
            $outputPath,
            $outputFormat
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Frame extraction failed');
        }

        return $this->getExtractedFrames($outputPath);
    }

    /**
     * Extract single frame at specific timestamp
     */
    public function extractFrameAtTimestamp(
        VideoFile $video,
        float $timestampSeconds
    ): string {
        $outputPath = storage_path("app/frames/{$video->id}");
        $framePath = "{$outputPath}/frame_{$timestampSeconds}.jpg";

        $command = sprintf(
            'ffmpeg -ss %f -i %s -vframes 1 %s',
            $timestampSeconds,
            escapeshellarg($video->file_path),
            escapeshellarg($framePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Frame extraction failed');
        }

        return $framePath;
    }

    /**
     * Get all extracted frame paths
     */
    private function getExtractedFrames(string $directory): array
    {
        $frames = glob("{$directory}/*.jpg");
        sort($frames);

        return $frames;
    }

    /**
     * Cleanup extracted frames
     */
    public function cleanup(VideoFile $video): void
    {
        $outputPath = storage_path("app/frames/{$video->id}");

        if (file_exists($outputPath)) {
            array_map('unlink', glob("{$outputPath}/*"));
            rmdir($outputPath);
        }
    }
}
```

**2. AIPlayerDetectionService.php**
```php
<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\Player;

class AIPlayerDetectionService
{
    /**
     * Detect players in frame using ML model
     */
    public function detectPlayers(string $framePath): array
    {
        // Python ML Script aufrufen
        $command = sprintf(
            'python3 %s detect-players --frame %s --output json',
            base_path('ml/player_detection.py'),
            escapeshellarg($framePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Player detection failed');
        }

        return json_decode(implode('', $output), true);
    }

    /**
     * Match detected players to roster
     */
    public function matchPlayersToRoster(
        array $detectedPlayers,
        \Illuminate\Support\Collection $roster
    ): array {
        $matches = [];

        foreach ($detectedPlayers as $detection) {
            $jerseyNumber = $detection['jersey_number'] ?? null;

            if ($jerseyNumber) {
                $player = $roster->firstWhere('jersey_number', $jerseyNumber);

                if ($player) {
                    $matches[] = [
                        'player_id' => $player->id,
                        'player_name' => $player->full_name,
                        'jersey_number' => $jerseyNumber,
                        'bounding_box' => $detection['bbox'],
                        'confidence' => $detection['confidence']
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Track player positions over time
     */
    public function trackPlayerPositions(
        VideoFile $video,
        array $frames
    ): array {
        $positions = [];

        foreach ($frames as $timestamp => $framePath) {
            $detections = $this->detectPlayers($framePath);

            foreach ($detections as $detection) {
                $playerId = $detection['player_id'] ?? null;

                if ($playerId) {
                    $positions[$playerId][] = [
                        'timestamp' => $timestamp,
                        'x' => $detection['bbox']['center_x'],
                        'y' => $detection['bbox']['center_y']
                    ];
                }
            }
        }

        return $positions;
    }
}
```

**3. VideoAnalysisFacade.php (Orchestrator)**
```php
<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use App\Models\Game;
use App\Models\VideoAnalysisSession;

class VideoAnalysisFacade
{
    public function __construct(
        private VideoFrameExtractor $frameExtractor,
        private AIPlayerDetectionService $playerDetection,
        private AICourtDetectionService $courtDetection,
        private AIActionRecognitionService $actionRecognition,
        private VideoAnnotationGenerator $annotationGenerator
    ) {}

    /**
     * Vollständige Video-Analyse durchführen
     */
    public function analyzeVideo(
        VideoFile $video,
        Game $game,
        array $options = []
    ): VideoAnalysisSession {
        // 1. Session erstellen
        $session = VideoAnalysisSession::create([
            'video_id' => $video->id,
            'game_id' => $game->id,
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            // 2. Frames extrahieren
            $frames = $this->frameExtractor->extractFrames($video);
            $session->update(['frames_extracted' => count($frames)]);

            // 3. Court Detection
            $courtData = $this->courtDetection->detectCourt($frames[0]);
            $session->update(['court_detected' => true]);

            // 4. Player Detection & Tracking
            $playerPositions = $this->playerDetection->trackPlayerPositions(
                $video,
                $frames
            );
            $session->update(['players_tracked' => count($playerPositions)]);

            // 5. Action Recognition
            $actions = $this->actionRecognition->recognizeActions(
                $frames,
                $playerPositions,
                $courtData
            );
            $session->update(['actions_detected' => count($actions)]);

            // 6. Annotationen generieren
            $annotations = $this->annotationGenerator->generateAnnotations(
                $video,
                $actions,
                $playerPositions
            );

            // 7. Session abschließen
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'annotations' => $annotations
            ]);

            // 8. Cleanup
            $this->frameExtractor->cleanup($video);

            return $session;

        } catch (\Exception $e) {
            $session->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
```

**Migration für video_analysis_sessions Tabelle:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('video_files')->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('frames_extracted')->default(0);
            $table->boolean('court_detected')->default(false);
            $table->integer('players_tracked')->default(0);
            $table->integer('actions_detected')->default(0);
            $table->json('annotations')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['video_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_analysis_sessions');
    }
};
```

#### Testing

```php
// tests/Unit/Services/ML/VideoAnalysis/VideoFrameExtractorTest.php
public function test_can_extract_frames_from_video()
{
    $video = VideoFile::factory()->create([
        'file_path' => storage_path('app/test_videos/sample.mp4')
    ]);

    $extractor = new VideoFrameExtractor();
    $frames = $extractor->extractFrames($video, intervalSeconds: 1);

    $this->assertNotEmpty($frames);
    $this->assertFileExists($frames[0]);
}

// tests/Integration/VideoAnalysisFacadeTest.php
public function test_complete_video_analysis_workflow()
{
    $video = VideoFile::factory()->create();
    $game = Game::factory()->create();

    $facade = app(VideoAnalysisFacade::class);
    $session = $facade->analyzeVideo($video, $game);

    $this->assertEquals('completed', $session->status);
    $this->assertGreaterThan(0, $session->frames_extracted);
    $this->assertTrue($session->court_detected);
}
```

#### Checklist

- [x] 7 neue Service-Klassen erstellt (verantwortungsbasierte Struktur)
- [x] `VideoAnalysisService` als Orchestrator (Facade)
- [x] `config/video_analysis.php` erstellt
- [x] Bestehenden Code migriert (Controller, Job)
- [ ] Unit Tests (ausstehend - wird separat erstellt)
- [ ] Integration Tests (ausstehend)
- [x] Alte `AIVideoAnalysisService` als deprecated markiert
- [ ] Nach 2 Wochen alte Klasse löschen

#### Implementierte Service-Struktur

```
app/Services/ML/VideoAnalysis/
├── VideoAnalysisService.php           # Facade (~150 LOC)
├── VideoAnalysisConfigService.php     # Config & Validation (~80 LOC)
├── VideoFrameExtractionService.php    # Frame Extraction (~120 LOC)
├── PythonScriptExecutorService.php    # Python Execution (~180 LOC)
├── VideoAnalysisResultProcessor.php   # Result Processing (~150 LOC)
├── VideoAnnotationGeneratorService.php # Annotations (~130 LOC)
└── VideoAnalysisReportService.php     # Reports (~100 LOC)
```

---

### REFACTOR-002: StatisticsService splitten ✅ ERLEDIGT

**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 1,289 Zeilen
**Ergebnis:** 7 fokussierte Services (~80-250 Zeilen je)
**Aufwand:** Abgeschlossen am 2025-01-28

#### Problem

Service vermischt Player-, Team-, Game- und Season-Statistiken mit viel Code-Duplikation.

**Betroffene Datei:** `app/Services/StatisticsService.php`

#### Lösung: Service-Splitting nach Verantwortung (7 Services)

**Implementierte Struktur:**

```
app/Services/Statistics/
├── StatisticsService.php              # Facade/Orchestrator (~120 LOC)
├── PlayerStatisticsService.php        # Spieler-Stats (~270 LOC)
├── TeamStatisticsService.php          # Team-Stats (~280 LOC)
├── GameStatisticsService.php          # Game-Level Stats (~80 LOC)
├── AdvancedMetricsService.php         # Advanced Calculations (~200 LOC)
├── ShotChartService.php               # Shot Chart & Zones (~120 LOC)
└── StatisticsCacheManager.php         # Cache-Strategie (~130 LOC)
```

**1. PlayerStatisticsCalculator.php**
```php
<?php

namespace App\Services\Statistics;

use App\Models\Player;
use App\Models\Game;
use App\Models\GameAction;
use Illuminate\Support\Facades\Cache;

class PlayerStatisticsCalculator
{
    private int $cacheTtl = 3600; // 1 Stunde

    /**
     * Berechne Season Stats für Player
     */
    public function calculateSeasonStats(Player $player, string $season): array
    {
        $cacheKey = "player:{$player->id}:season:{$season}:stats";

        return Cache::tags(['player:' . $player->id, 'season:' . $season])
            ->remember($cacheKey, $this->cacheTtl, function() use ($player, $season) {
                return $this->computeSeasonStats($player, $season);
            });
    }

    /**
     * Tatsächliche Berechnung
     */
    private function computeSeasonStats(Player $player, string $season): array
    {
        $actions = GameAction::whereHas('game', function ($query) use ($season) {
                $query->where('season', $season)
                      ->where('status', 'finished');
            })
            ->where('player_id', $player->id)
            ->get();

        return [
            'games_played' => $actions->pluck('game_id')->unique()->count(),
            'total_points' => $this->calculateTotalPoints($actions),
            'field_goals' => $this->calculateFieldGoals($actions),
            'three_pointers' => $this->calculateThreePointers($actions),
            'free_throws' => $this->calculateFreeThrows($actions),
            'rebounds' => $this->calculateRebounds($actions),
            'assists' => $actions->where('action_type', 'assist')->count(),
            'steals' => $actions->where('action_type', 'steal')->count(),
            'blocks' => $actions->where('action_type', 'block')->count(),
            'turnovers' => $actions->where('action_type', 'turnover')->count(),
            'fouls' => $actions->where('action_type', 'foul')->count(),
            'minutes_played' => $this->calculateMinutesPlayed($actions),
            'per' => $this->calculatePER($actions),
            'true_shooting_percentage' => $this->calculateTrueShootingPercentage($actions),
        ];
    }

    /**
     * Berechne Game Stats für Player
     */
    public function calculateGameStats(Player $player, Game $game): array
    {
        $cacheKey = "player:{$player->id}:game:{$game->id}:stats";
        $ttl = $game->status === 'live' ? 300 : 3600; // 5 Min für live, 1h für finished

        return Cache::tags(['player:' . $player->id, 'game:' . $game->id])
            ->remember($cacheKey, $ttl, function() use ($player, $game) {
                $actions = GameAction::where('game_id', $game->id)
                    ->where('player_id', $player->id)
                    ->get();

                return $this->computeStatsFromActions($actions);
            });
    }

    /**
     * Field Goal Berechnung (2PT + 3PT)
     */
    private function calculateFieldGoals(Collection $actions): array
    {
        $made = $actions->whereIn('action_type', ['field_goal', 'three_pointer'])
            ->where('successful', true)
            ->count();

        $attempted = $actions->whereIn('action_type', ['field_goal', 'three_pointer'])
            ->count();

        return [
            'made' => $made,
            'attempted' => $attempted,
            'percentage' => $attempted > 0 ? round(($made / $attempted) * 100, 1) : 0
        ];
    }

    /**
     * Three-Pointer Berechnung
     */
    private function calculateThreePointers(Collection $actions): array
    {
        $made = $actions->where('action_type', 'three_pointer')
            ->where('successful', true)
            ->count();

        $attempted = $actions->where('action_type', 'three_pointer')->count();

        return [
            'made' => $made,
            'attempted' => $attempted,
            'percentage' => $attempted > 0 ? round(($made / $attempted) * 100, 1) : 0
        ];
    }

    /**
     * Free Throws Berechnung
     */
    private function calculateFreeThrows(Collection $actions): array
    {
        $made = $actions->where('action_type', 'free_throw')
            ->where('successful', true)
            ->count();

        $attempted = $actions->where('action_type', 'free_throw')->count();

        return [
            'made' => $made,
            'attempted' => $attempted,
            'percentage' => $attempted > 0 ? round(($made / $attempted) * 100, 1) : 0
        ];
    }

    /**
     * Rebounds Berechnung
     */
    private function calculateRebounds(Collection $actions): array
    {
        $offensive = $actions->where('action_type', 'offensive_rebound')->count();
        $defensive = $actions->where('action_type', 'defensive_rebound')->count();

        return [
            'offensive' => $offensive,
            'defensive' => $defensive,
            'total' => $offensive + $defensive
        ];
    }

    /**
     * Total Points Berechnung
     */
    private function calculateTotalPoints(Collection $actions): int
    {
        return $actions->whereIn('action_type', ['field_goal', 'three_pointer', 'free_throw'])
            ->where('successful', true)
            ->sum('points');
    }

    /**
     * Minutes Played Berechnung
     */
    private function calculateMinutesPlayed(Collection $actions): int
    {
        // Implementierung basierend auf check-in/check-out actions
        $checkIns = $actions->where('action_type', 'substitution_in');
        $checkOuts = $actions->where('action_type', 'substitution_out');

        // Vereinfachte Berechnung
        return $checkIns->count() > 0 ? 25 : 0; // TODO: Präzise Berechnung
    }

    /**
     * PER (Player Efficiency Rating) Berechnung
     */
    private function calculatePER(Collection $actions): float
    {
        // PER Formula: [Points + Rebounds + Assists + Steals + Blocks - Turnovers - Missed FG - Missed FT] / Games
        $stats = $this->computeStatsFromActions($actions);

        $per = (
            $stats['total_points'] +
            $stats['rebounds']['total'] +
            $stats['assists'] +
            $stats['steals'] +
            $stats['blocks'] -
            $stats['turnovers'] -
            ($stats['field_goals']['attempted'] - $stats['field_goals']['made']) -
            ($stats['free_throws']['attempted'] - $stats['free_throws']['made'])
        ) / max($stats['games_played'], 1);

        return round($per, 2);
    }

    /**
     * True Shooting Percentage Berechnung
     */
    private function calculateTrueShootingPercentage(Collection $actions): float
    {
        $points = $this->calculateTotalPoints($actions);
        $fga = $actions->whereIn('action_type', ['field_goal', 'three_pointer'])->count();
        $fta = $actions->where('action_type', 'free_throw')->count();

        $denominator = 2 * ($fga + (0.44 * $fta));

        return $denominator > 0 ? round(($points / $denominator) * 100, 1) : 0;
    }

    /**
     * Cache invalidieren
     */
    public function clearCache(Player $player): void
    {
        Cache::tags(['player:' . $player->id])->flush();
    }
}
```

**2. StatisticsFacade.php (Orchestrator)**
```php
<?php

namespace App\Services\Statistics;

use App\Models\Player;
use App\Models\BasketballTeam;
use App\Models\Game;

class StatisticsFacade
{
    public function __construct(
        private PlayerStatisticsCalculator $playerStats,
        private TeamStatisticsCalculator $teamStats,
        private GameStatisticsCalculator $gameStats,
        private SeasonStatisticsCalculator $seasonStats
    ) {}

    /**
     * Zentrale Methode für Player Stats
     */
    public function getPlayerStats(
        Player $player,
        ?string $season = null,
        ?Game $game = null
    ): array {
        if ($game) {
            return $this->playerStats->calculateGameStats($player, $game);
        }

        if ($season) {
            return $this->playerStats->calculateSeasonStats($player, $season);
        }

        return $this->playerStats->calculateCareerStats($player);
    }

    /**
     * Zentrale Methode für Team Stats
     */
    public function getTeamStats(
        BasketballTeam $team,
        ?string $season = null
    ): array {
        if ($season) {
            return $this->teamStats->calculateSeasonStats($team, $season);
        }

        return $this->teamStats->calculateAllTimeStats($team);
    }

    /**
     * Cache Management
     */
    public function clearAllCaches(): void
    {
        Cache::tags(['statistics'])->flush();
    }

    public function clearPlayerCache(Player $player): void
    {
        $this->playerStats->clearCache($player);
    }

    public function clearTeamCache(BasketballTeam $team): void
    {
        $this->teamStats->clearCache($team);
    }
}
```

**Service Provider Registrierung:**

```php
// app/Providers/StatisticsServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Statistics\StatisticsFacade;
use App\Services\Statistics\PlayerStatisticsCalculator;
use App\Services\Statistics\TeamStatisticsCalculator;
use App\Services\Statistics\GameStatisticsCalculator;
use App\Services\Statistics\SeasonStatisticsCalculator;

class StatisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton für bessere Performance
        $this->app->singleton(PlayerStatisticsCalculator::class);
        $this->app->singleton(TeamStatisticsCalculator::class);
        $this->app->singleton(GameStatisticsCalculator::class);
        $this->app->singleton(SeasonStatisticsCalculator::class);

        $this->app->singleton(StatisticsFacade::class, function ($app) {
            return new StatisticsFacade(
                $app->make(PlayerStatisticsCalculator::class),
                $app->make(TeamStatisticsCalculator::class),
                $app->make(GameStatisticsCalculator::class),
                $app->make(SeasonStatisticsCalculator::class)
            );
        });

        // Alias für einfacheren Zugriff
        $this->app->alias(StatisticsFacade::class, 'statistics');
    }
}
```

**Controller Migration:**

```php
// Vorher (alter StatisticsService):
use App\Services\StatisticsService;

public function __construct(
    private StatisticsService $statisticsService
) {}

$stats = $this->statisticsService->getPlayerSeasonStats($player, $season);

// Nachher (neue Facade):
use App\Services\Statistics\StatisticsFacade;

public function __construct(
    private StatisticsFacade $statistics
) {}

$stats = $this->statistics->getPlayerStats($player, season: $season);

// Oder via Helper:
$stats = app('statistics')->getPlayerStats($player, season: $season);
```

#### Checklist

- [ ] 4 Calculator-Klassen erstellen
- [ ] `StatisticsFacade` implementieren
- [ ] `StatisticsServiceProvider` erstellen
- [ ] Service Provider in `config/app.php` registrieren
- [ ] Controller zu neuen Services migrieren (10+ Controller)
- [ ] Tests migrieren und erweitern
- [ ] Cache-Tags überall implementieren
- [ ] Dokumentation schreiben
- [ ] Alte `StatisticsService` deprecaten
- [ ] Nach 4 Wochen alte Klasse löschen

---

### REFACTOR-003: SubscriptionAnalyticsService splitten ✅ ERLEDIGT

**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 851 Zeilen
**Ergebnis:** 6 fokussierte Services (~80-220 Zeilen je)
**Aufwand:** Abgeschlossen am 2025-11-28

#### Problem

God Service mit zu vielen Verantwortlichkeiten:
- MRR-Berechnung (Club & Tenant-Level)
- Churn-Analyse (Customer & Revenue Churn)
- LTV-Berechnung (Lifetime Value & Cohort)
- Health-Metriken (Trial Conversion, Upgrades)
- Cache-Management

**Betroffene Datei:** `app/Services/Stripe/SubscriptionAnalyticsService.php`

#### Lösung: Service-Splitting nach Verantwortung (6 Services)

**Implementierte Struktur:**

```
app/Services/Stripe/Analytics/
├── SubscriptionAnalyticsService.php       # Facade/Orchestrator (~120 LOC)
├── AnalyticsCacheManager.php              # Cache-Strategie (~80 LOC)
├── MRRCalculatorService.php               # MRR-Berechnungen (~220 LOC)
├── ChurnAnalyzerService.php               # Churn-Analyse (~180 LOC)
├── LTVCalculatorService.php               # LTV & Cohorts (~200 LOC)
└── SubscriptionHealthMetricsService.php   # Health-Metriken (~130 LOC)
```

**Service-Verantwortlichkeiten:**

1. **AnalyticsCacheManager**: Cache-Keys, TTLs, Invalidierung
2. **MRRCalculatorService**: calculateClubMRR, calculateTenantMRR, getHistoricalMRR, getMRRGrowthRate, getMRRByPlan
3. **ChurnAnalyzerService**: calculateMonthlyChurnRate, getChurnByPlan, getChurnReasons, calculateRevenueChurn
4. **LTVCalculatorService**: calculateAverageLTV, getLTVByPlan, getCohortAnalysis, getCustomerLifetimeStats
5. **SubscriptionHealthMetricsService**: getActiveSubscriptionsCount, getTrialConversionRate, getAverageSubscriptionDuration, getUpgradeDowngradeRates, getHealthScore
6. **SubscriptionAnalyticsService**: Facade mit rückwärtskompatibler API

#### Checklist

- [x] 6 neue Service-Klassen erstellt
- [x] `SubscriptionAnalyticsService` als Orchestrator (Facade)
- [x] Bestehende Logik migriert
- [x] AppServiceProvider.php aktualisiert (Singleton-Bindings & Alias)
- [x] Alte Klasse als deprecated markiert (extends neue Facade)
- [x] Rückwärtskompatibilität über Alias gewährleistet
- [ ] Unit Tests (ausstehend - existierende Tests sollten weiterhin funktionieren)
- [ ] Nach 4 Wochen alte Klasse löschen

---

### REFACTOR-004: GymScheduleService splitten ✅ ERLEDIGT

**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 756 Zeilen
**Ergebnis:** 4 fokussierte Services (~100-280 Zeilen je)
**Aufwand:** Abgeschlossen am 2025-11-28

#### Problem

God Service mit zu vielen Verantwortlichkeiten:
- Buchungs-Lifecycle (release, request, approve, reject, cancel)
- Konflikt-Erkennung & Validierung
- TimeSlot-Verwaltung & Slot-Optimierung
- Statistiken & Reports

**Betroffene Datei:** `app/Services/GymScheduleService.php`

#### Lösung: Service-Splitting nach Verantwortung (4 Services)

**Implementierte Struktur:**

```
app/Services/Gym/
├── GymConflictDetector.php         (~120 LOC) - Konflikt-Erkennung & Validierung
├── GymBookingService.php           (~180 LOC) - Buchungs-Lifecycle & Anfragen
├── GymScheduleOptimizer.php        (~280 LOC) - Slot-Optimierung & Generierung
└── GymStatisticsService.php        (~100 LOC) - Statistiken & Reports
```

**Service-Verantwortlichkeiten:**

1. **GymConflictDetector**: getTimeSlotConflicts, validateTimeSlot, validateFlexibleBookingTime, validateCourtSelection
2. **GymBookingService**: releaseBooking, requestBooking, approveBookingRequest, rejectBookingRequest, cancelBooking, getAvailableTimeSlotsForTeam, getPendingRequestsForTeam, getRequestsByTeam, canUserReleaseBooking, canUserCancelBooking, processExpiredRequests
3. **GymScheduleOptimizer**: createTimeSlot, assignTimeSlotToTeam, generateBookingsForTimeSlot, generateDailyTimeGrid, findAvailableSlots, createMultiCourtBooking, getCourtSchedule, getOptimalCourtAssignments, generateFlexibleBookings
4. **GymStatisticsService**: getClubWeeklySchedule, getClubUtilizationStats, processPastBookings, getTeamBookingStats

**Controller-Migration:**
- `GymManagementController.php` - Ungenutzte Dependency entfernt
- `GymHallController.php` - Verwendet jetzt `GymScheduleOptimizer`
- `GymHallCourtController.php` - Verwendet jetzt `GymScheduleOptimizer`

**Unit Tests erstellt:**
```
tests/Unit/Services/Gym/
├── GymConflictDetectorTest.php
├── GymBookingServiceTest.php
├── GymScheduleOptimizerTest.php
└── GymStatisticsServiceTest.php
```

#### Checklist

- [x] 4 neue Service-Klassen erstellt
- [x] `GymScheduleOptimizer` injiziert `GymConflictDetector`
- [x] Controller auf neue Services migriert
- [x] Alte `GymScheduleService` gelöscht
- [x] Unit Tests erstellt (4 Test-Dateien)
- [x] Rückwärtskompatibilität durch direkte Migration

---

### REFACTOR-005: ClubService splitten ✅ ERLEDIGT

**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 829 Zeilen
**Ergebnis:** 4 fokussierte Services (~90-280 Zeilen je)
**Aufwand:** Abgeschlossen am 2025-11-28

#### Problem

God Service mit zu vielen Verantwortlichkeiten:
- CRUD-Operationen (createClub, updateClub, deleteClub)
- Mitgliederverwaltung (addMemberToClub, removeMemberFromClub)
- Statistiken & Reports (getClubStatistics, generateClubReport)
- Subscription-Plan-Management
- Logo-Upload/Delete

**Betroffene Datei:** `app/Services/ClubService.php`

#### Lösung: Service-Splitting nach Verantwortung (4 Services)

**Implementierte Struktur:**

```
app/Services/Club/
├── ClubCrudService.php               (~250 LOC) - CRUD + Logo-Operationen
├── ClubMembershipService.php         (~110 LOC) - Mitgliederverwaltung
├── ClubStatisticsService.php         (~280 LOC) - Statistiken & Reports
└── ClubSubscriptionPlanService.php   (~90 LOC) - Plan-Management
```

**Service-Verantwortlichkeiten:**

1. **ClubCrudService**: createClub, updateClub, deleteClub, uploadClubLogo, deleteClubLogo, generateUniqueSlug
2. **ClubMembershipService**: addMemberToClub, removeMemberFromClub
3. **ClubStatisticsService**: getClubStatistics, generateClubReport, getClubSeasons, verifyClub, generateEmergencyQRData
4. **ClubSubscriptionPlanService**: createClubPlan, updateClubPlan, assignPlanToClub, getClubsWithoutPlan, getClubUsageStats

**Controller-Migration:**
- `ClubController.php` - Verwendet ClubCrudService + ClubStatisticsService
- `ClubAdminPanelController.php` - Verwendet ClubStatisticsService
- `DashboardController.php` - Verwendet ClubStatisticsService
- `Api/V2/ClubController.php` - Verwendet ClubCrudService
- `OnboardingService.php` - Verwendet ClubCrudService

**Unit Tests erstellt:**
```
tests/Unit/Services/Club/
├── ClubCrudServiceTest.php
├── ClubMembershipServiceTest.php
├── ClubStatisticsServiceTest.php
└── ClubSubscriptionPlanServiceTest.php
```

#### Checklist

- [x] 4 neue Service-Klassen erstellt
- [x] Controller direkt auf neue Services migriert (keine Facade)
- [x] AppServiceProvider.php mit Singleton-Bindings aktualisiert
- [x] Alte `ClubService.php` gelöscht
- [x] Unit Tests erstellt (4 Test-Dateien)

---

### REFACTOR-006: GymManagementController splitten ✅ ERLEDIGT

**Status:** ✅ Erledigt am 2025-11-28
**Schweregrad:** 🟠 HOCH
**Ursprüngliche Größe:** 1,412 Zeilen
**Aufwand:** ~4 Stunden

#### Lösung: Controller-Splitting (Implementiert)

**Neue Controller-Struktur:**

```
app/Http/Controllers/Gym/
├── GymDashboardController.php     (~230 LOC) - Dashboard & Views
├── GymTimeSlotController.php      (~750 LOC) - TimeSlot & Team-Zuordnungen
└── GymCourtController.php         (~190 LOC) - Court-Management
```

**Migrierte Methoden:**
- `GymDashboardController`: index, halls, bookings, requests, create, getGymStatistics
- `GymTimeSlotController`: getHallTimeSlots, updateHallTimeSlots, updateTimeSlotCustomTimes, getAvailableTeams, assignTeamToTimeSlot, removeTeamFromTimeSlot, getTeamTimeSlots, getTimeSlotSegments, assignTeamToSegment, removeTeamSegmentAssignment, getTimeSlotTeamAssignments
- `GymCourtController`: getTeams, getHallCourts, updateCourt, createCourt

**Aktualisierte Routes:**
- `routes/web.php` (gym-management.* routes)
- `routes/api/v2.php` (TimeSlot API routes)

**1. GymHallController.php (CRUD Only)**

```php
<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\Club;
use App\Http\Requests\StoreGymHallRequest;
use App\Http\Requests\UpdateGymHallRequest;
use Inertia\Inertia;
use Inertia\Response;

class GymHallController extends Controller
{
    /**
     * Liste aller Gym Halls
     */
    public function index(): Response
    {
        $this->authorize('viewAny', GymHall::class);

        $halls = GymHall::with('club:id,name')
            ->select(['id', 'name', 'address', 'capacity', 'club_id', 'is_active'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Gym/Halls/Index', [
            'halls' => $halls
        ]);
    }

    /**
     * Zeige einzelne Gym Hall
     */
    public function show(GymHall $hall): Response
    {
        $this->authorize('view', $hall);

        $hall->load([
            'club:id,name',
            'timeSlots' => fn($q) => $q->orderBy('start_time')
        ]);

        return Inertia::render('Gym/Halls/Show', [
            'hall' => $hall
        ]);
    }

    /**
     * Erstelle neue Gym Hall
     */
    public function store(StoreGymHallRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', GymHall::class);

        $hall = GymHall::create($request->validated());

        return response()->json([
            'message' => 'Gym Hall erfolgreich erstellt',
            'hall' => $hall
        ], 201);
    }

    /**
     * Update Gym Hall
     */
    public function update(UpdateGymHallRequest $request, GymHall $hall): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $hall);

        $hall->update($request->validated());

        return response()->json([
            'message' => 'Gym Hall aktualisiert',
            'hall' => $hall
        ]);
    }

    /**
     * Lösche Gym Hall
     */
    public function destroy(GymHall $hall): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $hall);

        $hall->delete();

        return response()->json([
            'message' => 'Gym Hall gelöscht'
        ]);
    }
}
```

**2. GymBookingController.php**

```php
<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymBooking;
use App\Models\GymTimeSlot;
use App\Models\BasketballTeam;
use App\Services\Gym\GymBookingService;
use App\Services\Gym\GymConflictDetector;
use App\Http\Requests\StoreGymBookingRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GymBookingController extends Controller
{
    public function __construct(
        private GymBookingService $bookingService,
        private GymConflictDetector $conflictDetector
    ) {}

    /**
     * Liste aller Bookings
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', GymBooking::class);

        $bookings = GymBooking::with([
                'team:id,name',
                'timeSlot:id,start_time,end_time',
                'gymHall:id,name'
            ])
            ->when($request->date, fn($q, $date) =>
                $q->whereDate('start_time', $date)
            )
            ->when($request->team_id, fn($q, $teamId) =>
                $q->where('team_id', $teamId)
            )
            ->orderBy('start_time')
            ->paginate(50);

        return Inertia::render('Gym/Bookings/Index', [
            'bookings' => $bookings
        ]);
    }

    /**
     * Erstelle neue Booking
     */
    public function store(StoreGymBookingRequest $request)
    {
        $this->authorize('create', GymBooking::class);

        // Check Conflicts
        $conflicts = $this->conflictDetector->checkConflicts(
            $request->gym_hall_id,
            $request->start_time,
            $request->end_time
        );

        if (!empty($conflicts)) {
            return response()->json([
                'message' => 'Zeitraum bereits gebucht',
                'conflicts' => $conflicts
            ], 422);
        }

        // Create Booking
        $booking = $this->bookingService->createBooking(
            $request->validated()
        );

        return response()->json([
            'message' => 'Booking erstellt',
            'booking' => $booking
        ], 201);
    }

    /**
     * Cancel Booking
     */
    public function cancel(GymBooking $booking)
    {
        $this->authorize('cancel', $booking);

        $this->bookingService->cancelBooking($booking);

        return response()->json([
            'message' => 'Booking storniert'
        ]);
    }

    /**
     * Check availability für Zeitraum
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'gym_hall_id' => 'required|exists:gym_halls,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time'
        ]);

        $available = $this->conflictDetector->isAvailable(
            $validated['gym_hall_id'],
            $validated['start_time'],
            $validated['end_time']
        );

        return response()->json([
            'available' => $available,
            'conflicts' => !$available ? $this->conflictDetector->getConflicts() : []
        ]);
    }
}
```

**3. GymAnalyticsController.php**

```php
<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Services\Gym\GymAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GymAnalyticsController extends Controller
{
    public function __construct(
        private GymAnalyticsService $analytics
    ) {}

    /**
     * Übersicht Dashboard
     */
    public function dashboard()
    {
        $this->authorize('viewAnalytics', GymHall::class);

        $stats = $this->analytics->getDashboardStats();

        return Inertia::render('Gym/Analytics/Dashboard', [
            'stats' => $stats
        ]);
    }

    /**
     * Utilization Report
     */
    public function utilization(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'gym_hall_id' => 'nullable|exists:gym_halls,id'
        ]);

        $report = $this->analytics->getUtilizationReport(
            $validated['start_date'],
            $validated['end_date'],
            $validated['gym_hall_id'] ?? null
        );

        return response()->json($report);
    }

    /**
     * Team Usage Statistics
     */
    public function teamUsage(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $stats = $this->analytics->getTeamUsageStats(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json($stats);
    }

    /**
     * Export Report
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'format' => 'required|in:pdf,xlsx'
        ]);

        $export = $this->analytics->exportReport(
            $validated['start_date'],
            $validated['end_date'],
            $validated['format']
        );

        return response()->download($export);
    }
}
```

**Routes Update:**

```php
// routes/web.php oder routes/gym.php

use App\Http\Controllers\Gym\GymHallController;
use App\Http\Controllers\Gym\GymBookingController;
use App\Http\Controllers\Gym\GymAnalyticsController;

Route::middleware(['auth', 'verified'])->prefix('gym')->group(function () {
    // Gym Halls CRUD
    Route::resource('halls', GymHallController::class);

    // Bookings
    Route::resource('bookings', GymBookingController::class);
    Route::post('bookings/{booking}/cancel', [GymBookingController::class, 'cancel'])
        ->name('gym.bookings.cancel');
    Route::post('bookings/check-availability', [GymBookingController::class, 'checkAvailability'])
        ->name('gym.bookings.check-availability');

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard', [GymAnalyticsController::class, 'dashboard'])
            ->name('gym.analytics.dashboard');
        Route::get('utilization', [GymAnalyticsController::class, 'utilization'])
            ->name('gym.analytics.utilization');
        Route::get('team-usage', [GymAnalyticsController::class, 'teamUsage'])
            ->name('gym.analytics.team-usage');
        Route::get('export', [GymAnalyticsController::class, 'export'])
            ->name('gym.analytics.export');
    });
});
```

#### Checklist

- [ ] 3 neue Controller erstellen
- [ ] Routes umorganisieren
- [ ] Authorization Checks migrieren
- [ ] Form Requests erstellen (Store/Update)
- [ ] Services erstellen (GymBookingService, GymConflictDetector, GymAnalyticsService)
- [ ] Tests migrieren
- [ ] Frontend-Routes aktualisieren
- [ ] Alte Controller als deprecated markieren
- [ ] Nach 2 Wochen alten Controller löschen

---

### REFACTOR-007 bis REFACTOR-010

*Weitere God Controllers (verkürzt):*

**REFACTOR-007: ClubAdminPanelController** (1,363 LOC → 4 Controller)
- `ClubAdminDashboardController`
- `ClubMembershipController`
- `ClubFinancialsController`
- `ClubReportsController`

**REFACTOR-008: PWAController** (877 LOC → 3 Controller)
- `PWAManifestController`
- `ServiceWorkerController`
- `PushNotificationController`

**REFACTOR-009: Fat Models reduzieren**
- `GymTimeSlot.php` (1,267 LOC) - Business Logic zu Services
- `Game.php` (1,062 LOC) - Value Objects einführen
- `User.php` (835 LOC) - Role/Tenant Management extrahieren

---

## 🏛️ ARCHITEKTUR-VERBESSERUNGEN (PRIORITY 3)

### ARCH-001: Service Locator Pattern eliminieren

**Schweregrad:** 🟡 MITTEL
**Aufwand:** 8-12 Stunden

#### Problem

20+ Stellen verwenden `app(ServiceClass::class)` statt Constructor Injection.

**Beispiele:**

```php
// app/Traits/HasLocalePreference.php:25
$localizationService = app(LocalizationService::class);

// app/Actions/Jetstream/DeleteUser.php:30
$userService = app(UserService::class);

// app/Console/Commands/CalculateSubscriptionChurnCommand.php:176
app(ClubSubscriptionNotificationService::class)->sendHighChurnAlert($tenant, []);
```

#### Risiko

- Versteckte Dependencies
- Schwer testbar
- Keine IDE-Unterstützung
- Verletzt Dependency Inversion Principle

#### Lösung

**Vorher (Service Locator):**

```php
// app/Traits/HasLocalePreference.php
trait HasLocalePreference
{
    public function getPreferredLocale(): string
    {
        $localizationService = app(LocalizationService::class); // ❌
        return $localizationService->getUserLocale($this);
    }
}
```

**Nachher (Proper Architecture):**

```php
// Option 1: Trait mit Service Injection (komplexer aber sauberer)
trait HasLocalePreference
{
    public function getPreferredLocale(): string
    {
        return app(LocalizationService::class)->getUserLocale($this);
    }
}

// Oder Option 2: Direkt auf Model ohne Service
trait HasLocalePreference
{
    public function getPreferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }
}
```

**Commands mit Constructor Injection:**

```php
// Vorher:
class CalculateSubscriptionChurnCommand extends Command
{
    public function handle()
    {
        $service = app(ClubSubscriptionNotificationService::class); // ❌
        $service->sendHighChurnAlert($tenant, []);
    }
}

// Nachher:
class CalculateSubscriptionChurnCommand extends Command
{
    public function __construct(
        private ClubSubscriptionNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->notificationService->sendHighChurnAlert($tenant, []); // ✅
    }
}
```

**Actions mit Constructor Injection:**

```php
// Vorher:
class DeleteUser
{
    public function delete(User $user)
    {
        $userService = app(UserService::class); // ❌
        $userService->deleteUserData($user);
    }
}

// Nachher:
class DeleteUser
{
    public function __construct(
        private UserService $userService
    ) {}

    public function delete(User $user)
    {
        $this->userService->deleteUserData($user); // ✅
    }
}
```

#### Alle Service Locator Aufrufe finden

```bash
grep -rn "app(" app/ | grep -v "app.php" | grep -v "config(" | grep "::class"

# Oder spezifischer:
grep -rn "app(.*Service::class)" app/
```

#### Checklist

- [ ] Alle `app(Service::class)` Aufrufe finden
- [ ] Zu Constructor Injection migrieren (20+ Stellen)
- [ ] Tests anpassen
- [ ] IDE Helper regenerieren: `php artisan ide-helper:generate`
- [ ] Code Review
- [ ] Deployment

---

### ARCH-002: Interfaces einführen

**Schweregrad:** 🟡 MITTEL
**Aufwand:** 10-14 Stunden

#### Problem

Nur 1 Interface (`SDKGeneratorInterface`) vorhanden. Keine Abstractions für Services.

#### Lösung

**4 Core Interfaces definieren:**

```
app/Contracts/
├── Statistics/
│   └── StatisticsCalculatorInterface.php
├── Payment/
│   └── PaymentProcessorInterface.php
├── Notification/
│   └── NotificationSenderInterface.php
└── Cache/
    └── CacheStrategyInterface.php
```

**1. StatisticsCalculatorInterface.php**

```php
<?php

namespace App\Contracts\Statistics;

interface StatisticsCalculatorInterface
{
    /**
     * Calculate statistics for given entity
     *
     * @param  mixed  $entity  Player, Team, or Game
     * @param  array  $options  Calculation options
     * @return array  Statistics array
     */
    public function calculate($entity, array $options = []): array;

    /**
     * Clear cached statistics
     *
     * @param  mixed  $entity
     * @return void
     */
    public function clearCache($entity): void;

    /**
     * Get supported calculation types
     *
     * @return array
     */
    public function getSupportedTypes(): array;
}
```

**Implementation:**

```php
<?php

namespace App\Services\Statistics;

use App\Contracts\Statistics\StatisticsCalculatorInterface;
use App\Models\Player;

class PlayerStatisticsCalculator implements StatisticsCalculatorInterface
{
    public function calculate($entity, array $options = []): array
    {
        if (!$entity instanceof Player) {
            throw new \InvalidArgumentException('Entity must be Player instance');
        }

        return $this->calculateSeasonStats($entity, $options['season'] ?? null);
    }

    public function clearCache($entity): void
    {
        Cache::tags(['player:' . $entity->id])->flush();
    }

    public function getSupportedTypes(): array
    {
        return ['season', 'game', 'career'];
    }
}
```

**2. PaymentProcessorInterface.php**

```php
<?php

namespace App\Contracts\Payment;

use App\DataTransferObjects\PaymentRequest;
use App\DataTransferObjects\PaymentResult;

interface PaymentProcessorInterface
{
    /**
     * Process payment
     *
     * @param  PaymentRequest  $request
     * @return PaymentResult
     */
    public function processPayment(PaymentRequest $request): PaymentResult;

    /**
     * Refund payment
     *
     * @param  string  $paymentId
     * @param  float  $amount
     * @return PaymentResult
     */
    public function refundPayment(string $paymentId, float $amount): PaymentResult;

    /**
     * Get payment status
     *
     * @param  string  $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId): string;

    /**
     * Verify webhook signature
     *
     * @param  array  $payload
     * @param  string  $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;
}
```

**Stripe Implementation:**

```php
<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentProcessorInterface;
use App\DataTransferObjects\PaymentRequest;
use App\DataTransferObjects\PaymentResult;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct(
        private \Stripe\StripeClient $stripe
    ) {}

    public function processPayment(PaymentRequest $request): PaymentResult
    {
        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $request->amount * 100,
            'currency' => $request->currency,
            'customer' => $request->customerId,
            'payment_method' => $request->paymentMethodId,
            'confirm' => true
        ]);

        return new PaymentResult(
            success: $paymentIntent->status === 'succeeded',
            paymentId: $paymentIntent->id,
            amount: $paymentIntent->amount / 100,
            status: $paymentIntent->status
        );
    }

    // ... andere Methoden
}
```

**Service Provider Binding:**

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Interface Binding
    $this->app->bind(
        \App\Contracts\Statistics\StatisticsCalculatorInterface::class,
        \App\Services\Statistics\PlayerStatisticsCalculator::class
    );

    $this->app->bind(
        \App\Contracts\Payment\PaymentProcessorInterface::class,
        \App\Services\Payment\StripePaymentProcessor::class
    );

    // ... weitere Bindings
}
```

**Usage mit Interface:**

```php
// Controller
public function __construct(
    private PaymentProcessorInterface $paymentProcessor // ✅ Interface!
) {}

public function processPayment(Request $request)
{
    $paymentRequest = new PaymentRequest(
        amount: $request->amount,
        currency: 'eur',
        customerId: $request->customer_id,
        paymentMethodId: $request->payment_method_id
    );

    $result = $this->paymentProcessor->processPayment($paymentRequest);

    return response()->json($result);
}
```

#### Vorteile

✅ **Testbarkeit:** Einfach mockbar
```php
// Test
$mock = Mockery::mock(PaymentProcessorInterface::class);
$mock->shouldReceive('processPayment')->andReturn(new PaymentResult(...));
```

✅ **Austauschbarkeit:** Einfach Stripe → PayPal wechseln
```php
$this->app->bind(
    PaymentProcessorInterface::class,
    PayPalPaymentProcessor::class // Neue Implementation
);
```

✅ **Mehrere Implementierungen:**
```php
// config/payment.php
'default' => env('PAYMENT_PROCESSOR', 'stripe'),
'processors' => [
    'stripe' => StripePaymentProcessor::class,
    'paypal' => PayPalPaymentProcessor::class,
    'mock' => MockPaymentProcessor::class // Für Tests
]

// Service Provider
$processor = config('payment.processors')[config('payment.default')];
$this->app->bind(PaymentProcessorInterface::class, $processor);
```

#### Checklist

- [ ] 4 Core Interfaces definieren
- [ ] Bestehende Services implementieren Interfaces
- [ ] Service Provider Bindings hinzufügen
- [ ] Tests mit Mock-Implementations
- [ ] Dokumentation schreiben
- [ ] Deployment

---

### ARCH-003 bis ARCH-010

*Weitere Architektur-Verbesserungen (verkürzt):*

**ARCH-003: Custom Exceptions erweitern** (6-8h)
- 8 neue Exception-Typen
- Exception Handler-Logik

**ARCH-004: API Response Standardisierung** (4-6h)
- `ApiResponse` Helper-Klasse
- Einheitliches JSON-Format

**ARCH-005: Value Objects einführen** (8-12h)
- `GameSchedule`, `GameScore`, `GameMetadata`
- `PlayerStats`, `TeamStats`

**ARCH-006: Event-Driven Refactoring** (10-14h)
- Domain Events für wichtige Actions
- Event Listeners konsolidieren

**ARCH-007: Query Objects** (6-8h)
- Komplexe Queries aus Controllern extrahieren

**ARCH-008: DTOs (Data Transfer Objects)** (8-10h)
- Request/Response DTOs
- Validation in DTOs

---

## 🎯 FEHLENDE FEATURES (PRIORITY 4)

### FEATURE-001: Fehlende Artisan Commands

**Aufwand:** 4-6 Stunden

#### Commands erstellen

```php
// app/Console/Commands/RetryFailedClubTransfers.php
<?php

namespace App\Console\Commands;

use App\Models\ClubTransfer;
use App\Services\ClubTransferService;
use Illuminate\Console\Command;

class RetryFailedClubTransfers extends Command
{
    protected $signature = 'club:retry-failed-transfers
                            {--transfer-id= : Specific transfer ID to retry}
                            {--age= : Retry transfers older than X hours}
                            {--limit=10 : Maximum number of transfers to retry}';

    protected $description = 'Retry failed club transfers';

    public function __construct(
        private ClubTransferService $transferService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($transferId = $this->option('transfer-id')) {
            return $this->retrySpecificTransfer($transferId);
        }

        $query = ClubTransfer::where('status', 'failed');

        if ($age = $this->option('age')) {
            $query->where('updated_at', '<=', now()->subHours($age));
        }

        $transfers = $query->limit($this->option('limit'))->get();

        if ($transfers->isEmpty()) {
            $this->info('No failed transfers found.');
            return Command::SUCCESS;
        }

        $this->info("Retrying {$transfers->count()} failed transfers...");

        $progressBar = $this->output->createProgressBar($transfers->count());

        foreach ($transfers as $transfer) {
            try {
                $this->transferService->retryTransfer($transfer);
                $this->line(" ✓ Transfer {$transfer->id} retried");
            } catch (\Exception $e) {
                $this->error(" ✗ Transfer {$transfer->id} failed: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return Command::SUCCESS;
    }

    private function retrySpecificTransfer(int $transferId): int
    {
        $transfer = ClubTransfer::find($transferId);

        if (!$transfer) {
            $this->error("Transfer {$transferId} not found.");
            return Command::FAILURE;
        }

        if ($transfer->status !== 'failed') {
            $this->error("Transfer {$transferId} is not in failed status.");
            return Command::FAILURE;
        }

        try {
            $this->transferService->retryTransfer($transfer);
            $this->info("Transfer {$transferId} retried successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Transfer {$transferId} failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
```

```php
// app/Console/Commands/CleanupClubTransferRollbackData.php
<?php

namespace App\Console\Commands;

use App\Models\ClubTransferRollbackData;
use Illuminate\Console\Command;

class CleanupClubTransferRollbackData extends Command
{
    protected $signature = 'club:cleanup-rollback-data
                            {--days=30 : Delete rollback data older than X days}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Cleanup old club transfer rollback data';

    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $query = ClubTransferRollbackData::whereHas('transfer', function($q) {
                $q->whereIn('status', ['completed', 'failed']);
            })
            ->where('created_at', '<=', now()->subDays($days));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No rollback data to cleanup.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} rollback data records (dry run).");
            return Command::SUCCESS;
        }

        if ($this->confirm("Delete {$count} rollback data records?")) {
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} rollback data records.");
        } else {
            $this->info('Cleanup cancelled.');
        }

        return Command::SUCCESS;
    }
}
```

**Registrieren in `app/Console/Kernel.php`:**

```php
protected $commands = [
    Commands\RetryFailedClubTransfers::class,
    Commands\CleanupClubTransferRollbackData::class,
];

protected function schedule(Schedule $schedule): void
{
    // Auto-Retry failed transfers täglich
    $schedule->command('club:retry-failed-transfers --age=24 --limit=50')
        ->daily()
        ->at('03:00');

    // Cleanup alte Rollback-Daten wöchentlich
    $schedule->command('club:cleanup-rollback-data --days=30')
        ->weekly()
        ->sundays()
        ->at('04:00');
}
```

#### Checklist

- [ ] `RetryFailedClubTransfersCommand` erstellen
- [ ] `CleanupClubTransferRollbackDataCommand` erstellen
- [ ] Commands in Kernel registrieren
- [ ] Scheduled Tasks hinzufügen
- [ ] Tests schreiben
- [ ] Dokumentation aktualisieren (CLAUDE.md)

---

### FEATURE-002: Training Websocket Channel

**Aufwand:** 3-4 Stunden

#### Problem

Training Broadcasting Channel ist dokumentiert aber nicht implementiert.

**CLAUDE.md Zeile 423:**
```markdown
- `training.{trainingId}` - Training session updates
```

#### Lösung

**1. Channel in `routes/channels.php` definieren:**

```php
use App\Models\TrainingSession;
use App\Models\User;

// Training Session Channel
Broadcast::channel('training.{trainingId}', function (User $user, int $trainingId) {
    $training = TrainingSession::find($trainingId);

    if (!$training) {
        return false;
    }

    // User muss Coach oder Player des Teams sein
    return $user->coachedTeams->contains($training->team_id) ||
           $user->teams->contains($training->team_id) ||
           $user->hasRole(['club_admin', 'super_admin']);
});
```

**2. Training Events erstellen:**

```php
// app/Events/Training/TrainingSessionStarted.php
<?php

namespace App\Events\Training;

use App\Models\TrainingSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrainingSessionStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TrainingSession $training
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('training.' . $this->training->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'training.started';
    }

    public function broadcastWith(): array
    {
        return [
            'training_id' => $this->training->id,
            'status' => $this->training->status,
            'started_at' => $this->training->started_at,
        ];
    }
}
```

**Weitere Training Events:**

```php
// app/Events/Training/
TrainingSessionStarted.php
TrainingSessionCompleted.php
TrainingAttendanceMarked.php
TrainingDrillAdded.php
TrainingNoteAdded.php
```

**3. Frontend Integration (Vue.js):**

```javascript
// resources/js/Components/Training/LiveTrainingSession.vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Echo from 'laravel-echo';

const props = defineProps({
    trainingId: Number
});

const trainingStatus = ref('scheduled');
const attendees = ref([]);

let channel = null;

onMounted(() => {
    // Subscribe to training channel
    channel = window.Echo.channel(`training.${props.trainingId}`)
        .listen('.training.started', (e) => {
            console.log('Training started:', e);
            trainingStatus.value = 'active';
        })
        .listen('.training.attendance', (e) => {
            console.log('Attendance marked:', e);
            updateAttendance(e.player_id, e.status);
        })
        .listen('.training.completed', (e) => {
            console.log('Training completed:', e);
            trainingStatus.value = 'completed';
        });
});

onUnmounted(() => {
    if (channel) {
        window.Echo.leave(`training.${props.trainingId}`);
    }
});

function updateAttendance(playerId, status) {
    const attendee = attendees.value.find(a => a.player_id === playerId);
    if (attendee) {
        attendee.status = status;
    }
}
</script>

<template>
    <div>
        <h2>Training Session</h2>
        <div class="status-badge" :class="trainingStatus">
            {{ trainingStatus }}
        </div>

        <div v-if="trainingStatus === 'active'">
            <h3>Live Attendance</h3>
            <ul>
                <li v-for="attendee in attendees" :key="attendee.player_id">
                    {{ attendee.name }} - {{ attendee.status }}
                </li>
            </ul>
        </div>
    </div>
</template>
```

**4. Service Integration:**

```php
// app/Services/TrainingService.php
public function startTraining(TrainingSession $training): void
{
    $training->update([
        'status' => 'active',
        'started_at' => now()
    ]);

    // Broadcast Event
    event(new TrainingSessionStarted($training));
}

public function markAttendance(TrainingSession $training, Player $player, string $status): void
{
    TrainingAttendance::updateOrCreate(
        [
            'training_session_id' => $training->id,
            'player_id' => $player->id,
        ],
        [
            'status' => $status,
            'marked_at' => now()
        ]
    );

    // Broadcast Event
    event(new TrainingAttendanceMarked($training, $player, $status));
}
```

#### Checklist

- [ ] Channel in `routes/channels.php` definieren
- [ ] 5 Training Events erstellen
- [ ] `TrainingService` mit Broadcasting erweitern
- [ ] Vue Component für Live Training
- [ ] Tests schreiben (Channel Authorization, Events)
- [ ] Dokumentation aktualisieren

---

### FEATURE-003 bis FEATURE-008

*Weitere fehlende Features (verkürzt):*

**FEATURE-003: Financial Tracking implementieren** (8-12h)
- TODO in `ClubAdminPanelController.php:422`
- Income/Expense Tracking
- Budgets & Reports

**FEATURE-004: Storage Calculation (bereits in SEC-008)**

**FEATURE-005: Tournament Award Auto-Generation** (6-8h)
- TODO in `TournamentAwardController.php:400`

**FEATURE-006: getConflictingBookings Method** (4-6h)
- TODO in `GymHall.php:511`

---

## 📦 DEPENDENCY MANAGEMENT (PRIORITY 5)

### DEP-001: Composer Package Updates

**Aufwand:** 8-12 Stunden

#### Major Updates

```bash
# Aktueller Stand (composer outdated):
laravel/cashier             15.7.1  → 16.0.5  (Breaking Changes)
stripe/stripe-php           16.6.0  → 19.0.0  (Breaking Changes)
spatie/laravel-medialibrary 11.17.3 → 12.x    (Major Update)

# Minor Updates:
laravel/framework           12.37.0 → 12.39.0
laravel/sail                1.47.0  → 1.48.1
barryvdh/laravel-debugbar   3.16.0  → 3.16.1
```

#### Update-Plan

**Phase 1: Minor Updates (sicher)**
```bash
composer update laravel/framework
composer update laravel/sail
composer update barryvdh/laravel-debugbar

# Tests ausführen
php artisan test
```

**Phase 2: Laravel Cashier 15 → 16 (Breaking Changes)**

```bash
# Changelog lesen
# https://github.com/laravel/cashier-stripe/releases/tag/v16.0.0

# Update
composer require laravel/cashier:^16.0

# Breaking Changes prüfen:
# - Neue Webhooks
# - API Änderungen
# - Migration Scripts
```

**Breaking Changes in Cashier 16:**
```php
// Vorher (Cashier 15):
$subscription = $user->subscription('default');

// Nachher (Cashier 16):
$subscription = $user->subscriptions()->where('name', 'default')->first();

// Neue Methoden:
$user->chargeWithPaymentMethod($paymentMethod, $amount);
$user->retrievePaymentIntent($paymentIntentId);
```

**Phase 3: Stripe PHP SDK 16 → 19 (Breaking Changes)**

```bash
# Breaking Changes:
# - API Version Changes
# - Deprecated Methods entfernt
# - Neue Required Parameters

composer require stripe/stripe-php:^19.0

# Update Stripe API Version:
\Stripe\Stripe::setApiVersion('2024-11-20.acacia');
```

**PHP Extension installieren:**

```bash
# EXIF Extension (für Medialibrary)
# Ubuntu/Debian:
sudo apt-get install php8.2-exif

# macOS:
brew install php@8.2

# php.ini:
extension=exif

# Verify:
php -m | grep exif
```

#### Testing nach Updates

```php
// tests/Integration/PackageCompatibilityTest.php
public function test_cashier_integration_works_after_update()
{
    $user = User::factory()->create();

    // Cashier Methoden testen
    $this->assertInstanceOf(Billable::class, $user);
    $this->assertFalse($user->subscribed());
}

public function test_stripe_sdk_works_after_update()
{
    $stripe = new \Stripe\StripeClient(config('cashier.secret'));

    $customer = $stripe->customers->create([
        'email' => 'test@example.com'
    ]);

    $this->assertNotNull($customer->id);
}
```

#### Checklist

- [ ] Minor Updates durchführen (Phase 1)
- [ ] Tests nach Phase 1 ausführen
- [ ] Cashier Changelog studieren
- [ ] Cashier Update (Phase 2)
- [ ] Subscription Tests nach Cashier-Update
- [ ] Stripe SDK Changelog studieren
- [ ] Stripe SDK Update (Phase 3)
- [ ] EXIF Extension installieren
- [ ] Full Test Suite ausführen
- [ ] Staging Deployment & Testing
- [ ] Production Deployment

---

## 📝 DOKUMENTATION (PRIORITY 6)

### DOC-001: CLAUDE.md Korrigieren

**Aufwand:** 2-3 Stunden

#### Diskrepanzen

```markdown
# Aktuell (falsch):
- 75 Services (tatsächlich: ~62)
- 85 Test Files (tatsächlich: 82)
- 78 Models (tatsächlich: 72-76)

# Commands dokumentiert aber fehlen:
- php artisan club:retry-failed-transfers
- php artisan club:cleanup-rollback-data
```

#### Fix

```markdown
# CLAUDE.md korrigieren:

## Service-Oriented Architecture

**62 Services** in `app/Services/` (statt 75)

## Testing Strategy

**82 test files** (statt 85)

## Key Models

**76 Models** mit umfassenden Relationships (statt 78)
```

#### Checklist

- [ ] Service-Count korrigieren
- [ ] Model-Count korrigieren
- [ ] Test-Count korrigieren
- [ ] Fehlende Commands dokumentieren (sobald implementiert)
- [ ] Code Review
- [ ] Git Commit

---

## 📊 SPRINT-PLANUNG

### Sprint 4: Code Quality Refactoring (2-3 Wochen)

**Zeitraum:** Wochen 7-9
**Aufwand:** 60-80 Stunden

**Aufgaben:**
- [x] REFACTOR-001: AIVideoAnalysisService splitten (12-16h) ✅ Erledigt 2025-01-28
- [x] REFACTOR-002: StatisticsService splitten (10-14h) ✅ Erledigt 2025-01-28
- [x] REFACTOR-003: SubscriptionAnalyticsService splitten (8-12h) ✅ Erledigt 2025-11-28
- [x] REFACTOR-004: GymScheduleService splitten (6-10h) ✅ Erledigt 2025-11-28
- [x] REFACTOR-006: GymManagementController splitten (6-8h) ✅ Erledigt 2025-11-28
- [ ] REFACTOR-007: ClubAdminPanelController splitten (8-10h)
- [ ] REFACTOR-009: Fat Models reduzieren (10-14h)

**Definition of Done:**
- ✅ Alle God Services < 300 LOC
- ✅ Alle God Controllers < 500 LOC
- ✅ Tests migriert und grün
- ✅ Alte Klassen deprecated
- ✅ Dokumentation aktualisiert

---

### Sprint 5: Architektur-Verbesserungen (2 Wochen)

**Zeitraum:** Wochen 10-11
**Aufwand:** 30-40 Stunden

**Aufgaben:**
- [ ] ARCH-001: Service Locator eliminieren (8-12h)
- [ ] ARCH-002: Interfaces einführen (10-14h)
- [ ] ARCH-003: Custom Exceptions erweitern (6-8h)
- [ ] ARCH-004: API Response Standardisierung (4-6h)

**Definition of Done:**
- ✅ 0 Service Locator Aufrufe
- ✅ 4 Core Interfaces implementiert
- ✅ 8 neue Custom Exceptions
- ✅ Einheitliches API Response Format
- ✅ Tests grün

---

### Sprint 6: Fehlende Features (1-2 Wochen)

**Zeitraum:** Wochen 12-13
**Aufwand:** 20-30 Stunden

**Aufgaben:**
- [ ] FEATURE-001: Artisan Commands (4-6h)
- [ ] FEATURE-002: Training Websocket Channel (3-4h)
- [ ] FEATURE-003: Financial Tracking (8-12h)
- [ ] FEATURE-005: Tournament Award Auto-Gen (6-8h)

**Definition of Done:**
- ✅ 2 neue Artisan Commands
- ✅ Training Broadcasting funktional
- ✅ Financial Tracking implementiert
- ✅ Alle TODOs erledigt

---

### Sprint 7: Dependencies & Updates (1 Woche)

**Zeitraum:** Woche 14
**Aufwand:** 12-18 Stunden

**Aufgaben:**
- [ ] DEP-001: Composer Updates (8-12h)
- [ ] PHP Extension Installation (2-3h)
- [ ] Compatibility Testing (2-3h)

**Definition of Done:**
- ✅ Alle Packages aktuell
- ✅ EXIF Extension installiert
- ✅ Keine Composer Security Warnings
- ✅ Full Test Suite grün

---

### Sprint 8: Dokumentation (3-5 Tage)

**Zeitraum:** Woche 15
**Aufwand:** 8-12 Stunden

**Aufgaben:**
- [ ] DOC-001: CLAUDE.md korrigieren (2-3h)
- [ ] API Dokumentation vervollständigen (3-4h)
- [ ] Architecture Decision Records (ADR) (3-5h)

**Definition of Done:**
- ✅ CLAUDE.md 100% korrekt
- ✅ API Docs vollständig
- ✅ ADRs für alle Architektur-Entscheidungen

---

## 🎯 ERFOLGSMETRIKEN

### Code Quality KPIs

**Vorher:**
- ❌ 5 God Services (>800 LOC) → **1 verbleibend** (AIVideoAnalysisService, StatisticsService, SubscriptionAnalyticsService, GymScheduleService refactored ✅)
- ❌ 5 God Controllers (>800 LOC)
- ❌ 20+ Service Locator Aufrufe
- ❌ 1 Interface nur
- ❌ 8+ TODOs in Production Code
- ❌ 15+ fehlende Tests

**Nachher:**
- ✅ 0 Services > 400 LOC
- ✅ 0 Controller > 500 LOC
- ✅ 0 Service Locator Aufrufe
- ✅ 4+ Core Interfaces
- ✅ 0 TODOs in Production Code
- ✅ 90%+ Test Coverage

### Maintainability Metrics

**Complexity:**
- ✅ Cyclomatic Complexity < 10
- ✅ Max Method Length < 50 LOC
- ✅ Max Class Length < 500 LOC

**Dependencies:**
- ✅ Alle Packages aktuell
- ✅ 0 Security Vulnerabilities
- ✅ Dependency Injection > 95%

### Documentation Metrics

- ✅ 100% API Endpoints dokumentiert
- ✅ 100% Services mit Docblocks
- ✅ 90%+ Public Methods dokumentiert
- ✅ Architecture Decision Records vorhanden

---

## 📋 ABSCHLUSS-CHECKLISTE

### Vor Production Deployment

- [ ] Alle Critical/High Priority Issues behoben
- [ ] 80%+ Test Coverage
- [ ] Performance Tests bestanden
- [ ] Security Audit durchgeführt
- [ ] Dokumentation vollständig
- [ ] Code Review abgeschlossen
- [ ] Staging Tests erfolgreich
- [ ] Rollback-Plan vorhanden
- [ ] Monitoring konfiguriert
- [ ] Team Training durchgeführt

### Nach Deployment

- [ ] 24h Production Monitoring
- [ ] Performance Metriken prüfen
- [ ] Error Rates überwachen
- [ ] User Feedback sammeln
- [ ] Post-Mortem Meeting
- [ ] Lessons Learned dokumentieren

---

**Letzte Aktualisierung:** 2025-11-28
**Nächstes Review:** Nach Sprint 4 Completion
**Maintainer:** Development Team

---

## 📝 CHANGELOG

### 2025-11-28
- ✅ **REFACTOR-005 abgeschlossen**: ClubService (829 LOC) in 4 Services aufgeteilt
  - Neue Services in `app/Services/Club/`
  - ClubCrudService: CRUD-Operationen, Logo-Management, Slug-Generierung (6 Methoden)
  - ClubMembershipService: Mitgliederverwaltung, Rollen-Zuweisung (2 Methoden)
  - ClubStatisticsService: Statistiken, Reports, QR-Daten, Verifizierung (6 Methoden)
  - ClubSubscriptionPlanService: Abo-Pläne, Club-Zuweisungen, Usage-Stats (5 Methoden)
  - Controller migriert: ClubController, ClubAdminPanelController, DashboardController, Api/V2/ClubController
  - Services migriert: OnboardingService
  - Alte ClubService gelöscht (keine Facade - direkte Migration)
  - Unit Tests erstellt (4 Test-Dateien)

- ✅ **REFACTOR-004 abgeschlossen**: GymScheduleService (756 LOC) in 4 Services aufgeteilt
  - Neue Services in `app/Services/Gym/`
  - GymConflictDetector: Konflikt-Erkennung & Validierung (4 Methoden)
  - GymBookingService: Buchungs-Lifecycle & Anfragen (11 Methoden)
  - GymScheduleOptimizer: Slot-Optimierung & Generierung (9 Methoden)
  - GymStatisticsService: Statistiken & Reports (4 Methoden)
  - Controller migriert: GymManagementController, GymHallController, GymHallCourtController
  - Alte GymScheduleService gelöscht (keine Facade - direkte Migration)
  - Unit Tests erstellt (4 Test-Dateien)

- ✅ **REFACTOR-003 abgeschlossen**: SubscriptionAnalyticsService (851 LOC) in 6 Services aufgeteilt
  - Neue Services in `app/Services/Stripe/Analytics/`
  - AnalyticsCacheManager: Cache-Keys, TTLs, Invalidierung
  - MRRCalculatorService: MRR-Berechnungen (Club & Tenant-Level)
  - ChurnAnalyzerService: Churn-Analyse (Customer & Revenue)
  - LTVCalculatorService: LTV & Cohort-Analysen
  - SubscriptionHealthMetricsService: Health-Metriken (Trial, Upgrades)
  - SubscriptionAnalyticsService: Facade/Orchestrator für Rückwärtskompatibilität
  - AppServiceProvider mit Singleton-Bindings und Alias aktualisiert
  - Alte Klasse als deprecated markiert (extends neue Facade)

### 2025-01-28
- ✅ **REFACTOR-002 abgeschlossen**: StatisticsService (1,289 LOC) in 7 Services aufgeteilt
  - Neue Services in `app/Services/Statistics/`
  - StatisticsCacheManager: Cache-Strategie & Invalidierung
  - AdvancedMetricsService: Komplexe Basketball-Metriken (TS%, PER, ORtg, DRtg)
  - ShotChartService: Shot Chart & Zone-Analyse
  - PlayerStatisticsService: Spieler-Stats mit Chunking
  - TeamStatisticsService: Team-Stats & Analytics
  - GameStatisticsService: Game-Level Stats
  - StatisticsService: Facade/Orchestrator für Rückwärtskompatibilität
  - Cache-Strategie (PERF-007, PERF-008) beibehalten

- ✅ **REFACTOR-001 abgeschlossen**: AIVideoAnalysisService (1,077 LOC) in 7 Services aufgeteilt
  - Neue Services in `app/Services/ML/VideoAnalysis/`
  - Konfiguration extrahiert nach `config/video_analysis.php`
  - Backward-Kompatibilität über Service-Alias gewährleistet
