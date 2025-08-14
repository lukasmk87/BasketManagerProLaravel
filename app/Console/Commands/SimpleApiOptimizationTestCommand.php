<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SimpleApiOptimizationTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test-simple-optimization 
                          {--samples=100 : Number of sample records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simple test for API response optimization without Redis dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $samples = (int) $this->option('samples');

        $this->info("🔧 Simple API Response Optimization Test");
        $this->info("Samples: {$samples}");
        $this->newLine();

        // Test 1: Player Data Optimization
        $this->testPlayerOptimization($samples);
        $this->newLine();

        // Test 2: Shot Chart Optimization
        $this->testShotChartOptimization($samples);
        $this->newLine();

        // Test 3: Statistics Optimization
        $this->testStatisticsOptimization($samples);
        
        $this->newLine();
        $this->info("✅ API Optimization Test completed successfully!");

        return Command::SUCCESS;
    }

    private function testPlayerOptimization(int $samples): void
    {
        $this->info("👥 Testing Player Response Optimization...");

        // Mock player data
        $players = [];
        for ($i = 1; $i <= $samples; $i++) {
            $players[] = [
                'id' => $i,
                'first_name' => 'Player',
                'last_name' => "Number {$i}",
                'position' => ['PG', 'SG', 'SF', 'PF', 'C'][rand(0, 4)],
                'jersey_number' => rand(1, 99),
                'team_id' => rand(1, 30),
                'height' => rand(170, 220),
                'weight' => rand(70, 130),
                'birth_date' => '1995-06-15',
                'nationality' => 'German',
                'status' => 'active',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];
        }

        // Original response
        $originalResponse = ['data' => $players];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized response - only essential fields
        $optimizedPlayers = array_map(function($player) {
            return [
                'id' => $player['id'],
                'name' => $player['first_name'] . ' ' . $player['last_name'],
                'pos' => $player['position'],
                'num' => $player['jersey_number'],
                'team' => $player['team_id']
            ];
        }, $players);

        $optimizedResponse = [
            'data' => $optimizedPlayers,
            'meta' => [
                'total' => count($optimizedPlayers),
                'fields' => ['id', 'name', 'pos', 'num', 'team'],
                'optimized' => true
            ]
        ];

        $optimizedSize = strlen(json_encode($optimizedResponse));
        $reduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        $this->table(
            ['Metric', 'Original', 'Optimized', 'Improvement'],
            [
                ['Size', $this->formatBytes($originalSize), $this->formatBytes($optimizedSize), round($reduction, 1) . '%'],
                ['Fields per Player', '11', '5', 'Field Selection'],
                ['Aliases Used', 'No', 'Yes', 'Shorter Keys']
            ]
        );
    }

    private function testShotChartOptimization(int $samples): void
    {
        $this->info("🏀 Testing Shot Chart Response Optimization...");

        // Mock shot chart data
        $shots = [];
        for ($i = 1; $i <= $samples; $i++) {
            $shots[] = [
                'id' => $i,
                'player_id' => rand(1, 50),
                'game_id' => rand(1, 100),
                'shot_x' => rand(0, 28),
                'shot_y' => rand(0, 15),
                'shot_distance' => round(rand(1, 25) + (rand(0, 99) / 100), 2),
                'shot_zone' => ['paint', 'mid_range', 'three_point'][rand(0, 2)],
                'is_successful' => rand(0, 1) === 1,
                'action_type' => 'field_goal_made',
                'period' => rand(1, 4),
                'recorded_at' => now()->toISOString()
            ];
        }

        // Original response
        $originalResponse = ['data' => $shots];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized response - compressed format
        $optimizedShots = array_map(function($shot) {
            return [
                'x' => $shot['shot_x'],
                'y' => $shot['shot_y'],
                'm' => $shot['is_successful'] ? 1 : 0, // made
                'd' => round($shot['shot_distance'], 1), // distance
                'z' => substr($shot['shot_zone'], 0, 1), // zone abbreviation
                'p' => $shot['period']
            ];
        }, $shots);

        $madeShots = count(array_filter($shots, fn($s) => $s['is_successful']));
        
        $optimizedResponse = [
            'shots' => $optimizedShots,
            'meta' => [
                'total' => count($shots),
                'made' => $madeShots,
                'pct' => count($shots) > 0 ? round($madeShots / count($shots) * 100, 1) : 0,
                'compression' => 'high'
            ]
        ];

        $optimizedSize = strlen(json_encode($optimizedResponse));
        $reduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        $this->table(
            ['Metric', 'Original', 'Optimized', 'Improvement'],
            [
                ['Size', $this->formatBytes($originalSize), $this->formatBytes($optimizedSize), round($reduction, 1) . '%'],
                ['Fields per Shot', '10', '6', 'Compressed Format'],
                ['Added Metadata', 'No', 'Yes', 'Analytics']
            ]
        );
    }

    private function testStatisticsOptimization(int $samples): void
    {
        $this->info("📊 Testing Statistics Response Optimization...");

        // Mock statistics data
        $statistics = [];
        for ($i = 1; $i <= $samples; $i++) {
            $fgMade = rand(0, 20);
            $fgAttempts = rand($fgMade, 25);
            $threePointMade = rand(0, 8);
            $threePointAttempts = rand($threePointMade, 12);

            $statistics[] = [
                'id' => $i,
                'player_id' => rand(1, 50),
                'game_id' => rand(1, 100),
                'points' => rand(0, 45),
                'rebounds' => rand(0, 20),
                'assists' => rand(0, 15),
                'steals' => rand(0, 8),
                'blocks' => rand(0, 6),
                'turnovers' => rand(0, 8),
                'field_goal_made' => $fgMade,
                'field_goal_attempts' => $fgAttempts,
                'three_point_made' => $threePointMade,
                'three_point_attempts' => $threePointAttempts,
                'minutes_played' => rand(15, 48),
                'plus_minus' => rand(-25, 25)
            ];
        }

        // Original response
        $originalResponse = ['data' => $statistics];
        $originalSize = strlen(json_encode($originalResponse));

        // Optimized response - aliases and calculated fields
        $optimizedStats = array_map(function($stat) {
            return [
                'pid' => $stat['player_id'], // player_id alias
                'pts' => $stat['points'],
                'reb' => $stat['rebounds'],
                'ast' => $stat['assists'],
                'stl' => $stat['steals'],
                'blk' => $stat['blocks'],
                'to' => $stat['turnovers'],
                'fg' => $stat['field_goal_made'] . '/' . $stat['field_goal_attempts'],
                'fg_pct' => $stat['field_goal_attempts'] > 0 ? round($stat['field_goal_made'] / $stat['field_goal_attempts'] * 100, 1) : 0,
                '3p' => $stat['three_point_made'] . '/' . $stat['three_point_attempts'],
                '3p_pct' => $stat['three_point_attempts'] > 0 ? round($stat['three_point_made'] / $stat['three_point_attempts'] * 100, 1) : 0,
                'min' => $stat['minutes_played'],
                'pm' => $stat['plus_minus']
            ];
        }, $statistics);

        $optimizedResponse = [
            'data' => $optimizedStats,
            'meta' => [
                'total' => count($optimizedStats),
                'aliases_used' => true,
                'calculated_fields' => ['fg_pct', '3p_pct']
            ]
        ];

        $optimizedSize = strlen(json_encode($optimizedResponse));
        $reduction = ($originalSize - $optimizedSize) / $originalSize * 100;

        $this->table(
            ['Metric', 'Original', 'Optimized', 'Improvement'],
            [
                ['Size', $this->formatBytes($originalSize), $this->formatBytes($optimizedSize), round($reduction, 1) . '%'],
                ['Field Names', 'Long Names', 'Short Aliases', 'Key Length Reduced'],
                ['Calculated %', 'Raw Values', 'Added Percentages', 'Enhanced Data']
            ]
        );
    }

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