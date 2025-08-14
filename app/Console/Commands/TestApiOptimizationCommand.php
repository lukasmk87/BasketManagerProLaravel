<?php

namespace App\Console\Commands;

use App\Services\ApiResponseOptimizationService;
use App\Models\Player;
use App\Models\GameAction;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestApiOptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test-optimization 
                          {--type=all : Test type (players, shots, stats, all)}
                          {--samples=100 : Number of sample records}
                          {--compression=medium : Compression level (low, medium, high)}
                          {--show-details : Show detailed optimization results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test API response optimization performance and results';

    private ApiResponseOptimizationService $optimizationService;

    /**
     * Create a new command instance.
     */
    public function __construct(ApiResponseOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $samples = (int) $this->option('samples');
        $compression = $this->option('compression');
        $showDetails = $this->option('show-details');

        $this->info("🔧 API Response Optimization Test");
        $this->info("Type: {$type} | Samples: {$samples} | Compression: {$compression}");
        $this->newLine();

        $results = [];

        if ($type === 'all' || $type === 'players') {
            $results['players'] = $this->testPlayerOptimization($samples, $showDetails);
        }

        if ($type === 'all' || $type === 'shots') {
            $results['shots'] = $this->testShotChartOptimization($samples, $compression, $showDetails);
        }

        if ($type === 'all' || $type === 'stats') {
            $results['stats'] = $this->testStatisticsOptimization($samples, $showDetails);
        }

        $this->displaySummary($results);

        return Command::SUCCESS;
    }

    /**
     * Test Player Response Optimization
     */
    private function testPlayerOptimization(int $samples, bool $showDetails): array
    {
        $this->info("👥 Testing Player Response Optimization...");

        // Mock player data für Test
        $players = collect();
        for ($i = 1; $i <= $samples; $i++) {
            $players->push((object) [
                'id' => $i,
                'first_name' => 'Player',
                'last_name' => "Number {$i}",
                'position' => ['PG', 'SG', 'SF', 'PF', 'C'][rand(0, 4)],
                'jersey_number' => rand(1, 99),
                'team_id' => rand(1, 30),
                'height' => rand(170, 220),
                'weight' => rand(70, 130),
                'birth_date' => now()->subYears(rand(18, 35))->format('Y-m-d'),
                'nationality' => 'German',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Original Response
        $originalResponse = ['data' => $players->toArray()];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized Response - nur wichtige Felder
        $optimizedResponse = $this->optimizationService->optimizeCollectionResponse(
            $players, 
            'player', 
            ['id', 'first_name', 'last_name', 'position', 'jersey_number'], 
            true
        );
        $optimizedSize = strlen(json_encode($optimizedResponse));

        $sizeReduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        if ($showDetails) {
            $this->table(
                ['Metric', 'Original', 'Optimized', 'Improvement'],
                [
                    ['Size (bytes)', number_format($originalSize), number_format($optimizedSize), round($sizeReduction, 1) . '%'],
                    ['Fields', count(get_object_vars($players->first())), count($optimizedResponse['meta']['fields_included']), 'Field Selection'],
                    ['Aliases Used', 'No', 'Yes', 'Shorter Keys']
                ]
            );
        }

        return [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'size_reduction' => $sizeReduction,
            'fields_reduced' => true
        ];
    }

    /**
     * Test Shot Chart Optimization
     */
    private function testShotChartOptimization(int $samples, string $compression, bool $showDetails): array
    {
        $this->info("🏀 Testing Shot Chart Response Optimization...");

        // Mock shot chart data
        $shots = collect();
        for ($i = 1; $i <= $samples; $i++) {
            $shots->push((object) [
                'id' => $i,
                'player_id' => rand(1, 50),
                'game_id' => rand(1, 100),
                'shot_x' => rand(0, 28),
                'shot_y' => rand(0, 15),
                'shot_distance' => rand(1, 25) + (rand(0, 99) / 100),
                'shot_zone' => ['paint', 'mid_range', 'three_point'][rand(0, 2)],
                'is_successful' => rand(0, 1) === 1,
                'action_type' => ['field_goal_made', 'field_goal_missed', 'three_point_made', 'three_point_missed'][rand(0, 3)],
                'period' => rand(1, 4),
                'recorded_at' => now()->subMinutes(rand(0, 120))->toISOString()
            ]);
        }

        // Original Response
        $originalResponse = ['data' => $shots->toArray()];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized Response
        $optimizedResponse = $this->optimizationService->optimizeShotChartResponse($shots, [
            'compression' => $compression,
            'include_metadata' => true
        ]);
        $optimizedSize = strlen(json_encode($optimizedResponse));

        $sizeReduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        if ($showDetails) {
            $this->table(
                ['Metric', 'Original', 'Optimized', 'Improvement'],
                [
                    ['Size (bytes)', number_format($originalSize), number_format($optimizedSize), round($sizeReduction, 1) . '%'],
                    ['Compression Level', 'None', ucfirst($compression), 'Data Structure Optimized'],
                    ['Fields per Shot', '8-10', $compression === 'high' ? '5' : '7', 'Field Reduction'],
                    ['Metadata', 'No', 'Yes', 'Added Analytics']
                ]
            );
        }

        return [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'size_reduction' => $sizeReduction,
            'compression_level' => $compression,
            'metadata_included' => true
        ];
    }

    /**
     * Test Statistics Optimization
     */
    private function testStatisticsOptimization(int $samples, bool $showDetails): array
    {
        $this->info("📊 Testing Statistics Response Optimization...");

        // Mock statistics data
        $statistics = collect();
        for ($i = 1; $i <= $samples; $i++) {
            $statistics->push((object) [
                'id' => $i,
                'player_id' => rand(1, 50),
                'game_id' => rand(1, 100),
                'points' => rand(0, 45),
                'rebounds' => rand(0, 20),
                'assists' => rand(0, 15),
                'steals' => rand(0, 8),
                'blocks' => rand(0, 6),
                'turnovers' => rand(0, 8),
                'field_goal_made' => rand(0, 20),
                'field_goal_attempts' => rand(0, 25),
                'three_point_made' => rand(0, 8),
                'three_point_attempts' => rand(0, 12),
                'free_throw_made' => rand(0, 10),
                'free_throw_attempts' => rand(0, 12),
                'minutes_played' => rand(15, 48),
                'plus_minus' => rand(-25, 25),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Original Response
        $originalResponse = ['data' => $statistics->toArray()];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized Response
        $optimizedResponse = $this->optimizationService->optimizeStatisticsResponse($statistics, [], [
            'precision' => 1,
            'include_calculated' => true
        ]);
        $optimizedSize = strlen(json_encode($optimizedResponse));

        $sizeReduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        if ($showDetails) {
            $this->table(
                ['Metric', 'Original', 'Optimized', 'Improvement'],
                [
                    ['Size (bytes)', number_format($originalSize), number_format($optimizedSize), round($sizeReduction, 1) . '%'],
                    ['Field Names', 'Long Names', 'Aliases (pts, reb, ast)', 'Shorter Keys'],
                    ['Calculated Fields', 'Raw Values', 'Percentages Added', 'Enhanced Data'],
                    ['Precision', 'Full', '1 Decimal', 'Reduced Precision']
                ]
            );
        }

        return [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'size_reduction' => $sizeReduction,
            'aliases_used' => true,
            'calculated_fields' => true
        ];
    }

    /**
     * Display Summary
     */
    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info("📈 Optimization Summary");
        $this->line(str_repeat('=', 50));

        $totalOriginalSize = 0;
        $totalOptimizedSize = 0;

        foreach ($results as $type => $result) {
            $totalOriginalSize += $result['original_size'];
            $totalOptimizedSize += $result['optimized_size'];

            $this->line(sprintf(
                "%s: %s → %s (%.1f%% reduction)",
                ucfirst($type),
                $this->formatBytes($result['original_size']),
                $this->formatBytes($result['optimized_size']),
                $result['size_reduction']
            ));
        }

        $this->newLine();
        $overallReduction = ($totalOriginalSize - $totalOptimizedSize) / $totalOriginalSize * 100;
        
        $this->info(sprintf(
            "🎯 Overall Optimization: %s → %s (%.1f%% reduction)",
            $this->formatBytes($totalOriginalSize),
            $this->formatBytes($totalOptimizedSize),
            $overallReduction
        ));

        // Performance Recommendations
        $this->newLine();
        $this->info("💡 Recommendations:");
        
        if ($overallReduction > 50) {
            $this->line("✅ Excellent optimization! Consider implementing in production.");
        } elseif ($overallReduction > 30) {
            $this->line("✅ Good optimization results. Worth implementing for large responses.");
        } else {
            $this->line("⚠️  Moderate optimization. Consider for high-traffic endpoints only.");
        }

        $this->line("• Use field selection for large collections");
        $this->line("• Apply compression for shot chart and statistics data");
        $this->line("• Implement response caching for frequently requested data");
        $this->line("• Consider pagination for large datasets");
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}