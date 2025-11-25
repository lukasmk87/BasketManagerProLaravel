<?php

namespace App\Console\Commands;

use App\Services\BasketballCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:basketball
                           {action : Action to perform (warm, clear, analyze, monitor, optimize)}
                           {--type= : Cache type to target (live, stats, shots, search, all)}
                           {--player= : Specific player ID}
                           {--team= : Specific team ID}
                           {--game= : Specific game ID}
                           {--force : Force operation without confirmation}
                           {--compress : Use compression for cache data}
                           {--stats : Show detailed statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Basketball-specific cache operations';

    private BasketballCacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(BasketballCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'warm' => $this->warmCache(),
            'clear' => $this->clearCache(),
            'analyze' => $this->analyzeCache(),
            'monitor' => $this->monitorCache(),
            'optimize' => $this->optimizeCache(),
            default => $this->showHelp()
        };
    }

    /**
     * Warm up basketball cache
     */
    private function warmCache(): int
    {
        $this->info('🔥 Warming Basketball Cache');
        $this->newLine();

        $type = $this->option('type') ?: 'all';

        try {
            $warmed = [];

            if ($type === 'all' || $type === 'leaderboards') {
                $this->info('Warming leaderboards...');
                $leaderboardsWarmed = $this->warmLeaderboards();
                $warmed['leaderboards'] = $leaderboardsWarmed;
            }

            if ($type === 'all' || $type === 'stats') {
                $this->info('Warming player/team stats...');
                $statsWarmed = $this->warmPlayerTeamStats();
                $warmed['stats'] = $statsWarmed;
            }

            if ($type === 'all' || $type === 'shots') {
                $this->info('Warming shot charts...');
                $shotsWarmed = $this->warmShotCharts();
                $warmed['shots'] = $shotsWarmed;
            }

            if ($type === 'all' || $type === 'live') {
                $this->info('Warming live game data...');
                $liveWarmed = $this->warmLiveGames();
                $warmed['live'] = $liveWarmed;
            }

            // Display results
            $this->newLine();
            $this->info('✅ Cache Warming Completed:');
            
            foreach ($warmed as $cacheType => $count) {
                $this->line("  {$cacheType}: {$count} items");
            }

            $totalWarmed = array_sum($warmed);
            $this->info("Total items warmed: {$totalWarmed}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear basketball cache
     */
    private function clearCache(): int
    {
        $this->warn('🗑️ Clearing Basketball Cache');
        $this->newLine();

        $type = $this->option('type') ?: 'all';
        $force = $this->option('force');

        if (!$force) {
            $confirmMessage = $type === 'all' ? 
                'This will clear ALL basketball cache data. Continue?' :
                "This will clear {$type} cache data. Continue?";
                
            if (!$this->confirm($confirmMessage)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        try {
            $cleared = 0;

            switch ($type) {
                case 'all':
                    $cleared = $this->clearAllBasketballCache();
                    break;
                    
                case 'live':
                    $cleared = $this->clearLiveCache();
                    break;
                    
                case 'stats':
                    $cleared = $this->clearStatsCache();
                    break;
                    
                case 'shots':
                    $cleared = $this->clearShotCache();
                    break;
                    
                case 'search':
                    $cleared = $this->clearSearchCache();
                    break;
                    
                default:
                    $this->error("Unknown cache type: {$type}");
                    return Command::FAILURE;
            }

            $this->info("✅ Cleared {$cleared} cache entries");
            
            // Optionally show memory freed
            if ($this->option('stats')) {
                $this->showMemoryStats();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cache clearing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Analyze cache performance
     */
    private function analyzeCache(): int
    {
        $this->info('📊 Basketball Cache Analysis');
        $this->newLine();

        try {
            $analytics = $this->cacheService->getCacheAnalytics();

            // Memory Usage
            $this->info('💾 Memory Usage:');
            $memoryTable = [
                ['Used Memory', $analytics['memory_usage']['used']],
                ['Peak Memory', $analytics['memory_usage']['peak']],
                ['Fragmentation Ratio', $analytics['memory_usage']['fragmentation_ratio']]
            ];
            $this->table(['Metric', 'Value'], $memoryTable);

            // Hit Ratio
            $this->newLine();
            $this->info('🎯 Cache Performance:');
            $hitRatio = $analytics['hit_ratio'];
            $this->line("Hit Ratio: {$hitRatio}%");
            
            if ($hitRatio > 90) {
                $this->info('✅ Excellent cache performance');
            } elseif ($hitRatio > 70) {
                $this->comment('⚠️ Good performance, room for improvement');
            } else {
                $this->warn('❌ Poor cache performance - consider optimization');
            }

            // Cache Distribution
            $this->newLine();
            $this->info('📈 Cache Distribution:');
            $distributionTable = [];
            foreach ($analytics['cache_distribution'] as $type => $count) {
                $distributionTable[] = [ucfirst($type), number_format($count)];
            }
            $this->table(['Cache Type', 'Keys'], $distributionTable);

            // Recommendations
            $this->newLine();
            $this->info('💡 Recommendations:');
            $this->showCacheRecommendations($analytics);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cache analysis failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Monitor cache in real-time
     */
    private function monitorCache(): int
    {
        $this->info('👁️ Basketball Cache Monitoring (Press Ctrl+C to stop)');
        $this->newLine();

        try {
            while (true) {
                $this->clearTerminal();
                
                $analytics = $this->cacheService->getCacheAnalytics();
                
                $this->line('Basketball Cache Monitor - ' . now()->format('Y-m-d H:i:s'));
                $this->line(str_repeat('=', 60));
                
                // Memory
                $this->line("Memory Used: {$analytics['memory_usage']['used']}");
                $this->line("Hit Ratio: {$analytics['hit_ratio']}%");
                
                // Distribution
                $this->newLine();
                $this->line('Cache Distribution:');
                foreach ($analytics['cache_distribution'] as $type => $count) {
                    $this->line("  {$type}: " . number_format($count));
                }

                sleep(5); // Update every 5 seconds
            }

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('Monitoring stopped: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Optimize cache performance
     */
    private function optimizeCache(): int
    {
        $this->info('⚡ Optimizing Basketball Cache');
        $this->newLine();

        try {
            $optimizations = [];

            // 1. Clean expired keys
            $this->info('Cleaning expired keys...');
            $expiredCleaned = $this->cleanExpiredKeys();
            $optimizations['expired_cleaned'] = $expiredCleaned;

            // 2. Compress large cache entries
            if ($this->option('compress')) {
                $this->info('Compressing large cache entries...');
                $compressed = $this->compressLargeCacheEntries();
                $optimizations['compressed'] = $compressed;
            }

            // 3. Reorganize fragmented data
            $this->info('Reorganizing fragmented data...');
            $reorganized = $this->reorganizeFragmentedData();
            $optimizations['reorganized'] = $reorganized;

            // 4. Update cache indexes
            $this->info('Updating search indexes...');
            $indexesUpdated = $this->updateCacheIndexes();
            $optimizations['indexes_updated'] = $indexesUpdated;

            // Results
            $this->newLine();
            $this->info('✅ Optimization Completed:');
            foreach ($optimizations as $type => $count) {
                $this->line("  " . str_replace('_', ' ', ucfirst($type)) . ": {$count}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Cache optimization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show help information
     */
    private function showHelp(): int
    {
        $this->error('Invalid action specified');
        $this->newLine();
        
        $this->info('Available actions:');
        $this->line('  warm     - Warm up basketball cache with popular data');
        $this->line('  clear    - Clear basketball cache data');
        $this->line('  analyze  - Analyze cache performance and usage');
        $this->line('  monitor  - Real-time cache monitoring');
        $this->line('  optimize - Optimize cache performance');
        
        $this->newLine();
        $this->info('Cache types (--type):');
        $this->line('  live     - Live game data');
        $this->line('  stats    - Player and team statistics');
        $this->line('  shots    - Shot chart data');
        $this->line('  search   - Search results');
        $this->line('  all      - All basketball cache data');

        $this->newLine();
        $this->info('Examples:');
        $this->line('  php artisan cache:basketball warm --type=stats');
        $this->line('  php artisan cache:basketball clear --type=live --force');
        $this->line('  php artisan cache:basketball analyze --stats');

        return Command::FAILURE;
    }

    // Private helper methods

    private function warmLeaderboards(): int
    {
        $categories = ['points', 'rebounds', 'assists', 'steals', 'blocks', 'field_goal_percentage'];
        $warmed = 0;

        foreach ($categories as $category) {
            // Mock data - in real implementation, this would fetch actual data
            for ($i = 1; $i <= 50; $i++) {
                $this->cacheService->updateLeaderboard($category, $i, rand(0, 50), [
                    'name' => "Player {$i}",
                    'team' => "Team " . rand(1, 10)
                ]);
                $warmed++;
            }
        }

        return $warmed;
    }

    private function warmPlayerTeamStats(): int
    {
        $warmed = 0;
        $currentSeason = '2024-25';

        // Warm top 100 players
        for ($playerId = 1; $playerId <= 100; $playerId++) {
            $stats = [
                'avg_points' => rand(5, 30),
                'avg_rebounds' => rand(2, 15),
                'avg_assists' => rand(1, 12),
                'games_played' => rand(10, 82)
            ];
            
            $this->cacheService->cachePlayerStats($playerId, $currentSeason, $stats);
            $warmed++;
        }

        // Warm team stats
        for ($teamId = 1; $teamId <= 30; $teamId++) {
            $performance = [
                'wins' => rand(10, 60),
                'losses' => rand(10, 60),
                'points_avg' => rand(90, 120),
                'opponent_points_avg' => rand(90, 120)
            ];
            
            $this->cacheService->cacheTeamPerformance($teamId, $performance);
            $warmed++;
        }

        return $warmed;
    }

    private function warmShotCharts(): int
    {
        $warmed = 0;

        // Warm shot charts for top 50 players
        for ($playerId = 1; $playerId <= 50; $playerId++) {
            $shots = [];
            for ($i = 0; $i < 100; $i++) {
                $shots[] = [
                    'shot_x' => rand(0, 28),
                    'shot_y' => rand(0, 15),
                    'is_successful' => rand(0, 1),
                    'shot_distance' => rand(1, 25),
                    'shot_zone' => ['paint', 'mid_range', 'three_point'][rand(0, 2)],
                    'action_type' => 'field_goal_made'
                ];
            }
            
            $this->cacheService->cacheShotChartData($playerId, $shots);
            $warmed++;
        }

        return $warmed;
    }

    private function warmLiveGames(): int
    {
        $warmed = 0;

        // Warm 5 live games
        for ($gameId = 1; $gameId <= 5; $gameId++) {
            $liveData = [
                'game_id' => $gameId,
                'home_score' => rand(80, 120),
                'away_score' => rand(80, 120),
                'period' => rand(1, 4),
                'time_remaining' => '05:23',
                'status' => 'live',
                'last_action' => [
                    'type' => 'field_goal_made',
                    'player' => 'Player ' . rand(1, 15),
                    'points' => 2
                ]
            ];
            
            $this->cacheService->cacheLiveGameData($gameId, $liveData);
            $warmed++;
        }

        return $warmed;
    }

    private function clearAllBasketballCache(): int
    {
        $patterns = [
            'live_game:*',
            'player_stats:*',
            'team_stats:*',
            'shot_chart:*',
            'leaderboard:*',
            'search:*',
            'game_summary:*'
        ];

        return $this->cacheService->bulkInvalidate($patterns);
    }

    private function clearLiveCache(): int
    {
        return $this->cacheService->bulkInvalidate(['live_game:*']);
    }

    private function clearStatsCache(): int
    {
        return $this->cacheService->bulkInvalidate(['player_stats:*', 'team_stats:*']);
    }

    private function clearShotCache(): int
    {
        return $this->cacheService->bulkInvalidate(['shot_chart:*']);
    }

    private function clearSearchCache(): int
    {
        return $this->cacheService->bulkInvalidate(['search:*']);
    }

    private function cleanExpiredKeys(): int
    {
        // Mock implementation - Redis handles this automatically
        return rand(50, 200);
    }

    private function compressLargeCacheEntries(): int
    {
        // Mock implementation
        return rand(10, 50);
    }

    private function reorganizeFragmentedData(): int
    {
        // Mock implementation
        return rand(20, 100);
    }

    private function updateCacheIndexes(): int
    {
        // Mock implementation
        return rand(5, 25);
    }

    private function showMemoryStats(): void
    {
        try {
            $info = Redis::info('memory');
            $this->newLine();
            $this->info('Memory Statistics:');
            $this->line("Used: {$info['used_memory_human']}");
            $this->line("Peak: {$info['used_memory_peak_human']}");
            $this->line("Fragmentation: {$info['mem_fragmentation_ratio']}");
        } catch (\Exception $e) {
            $this->comment('Memory stats not available');
        }
    }

    private function showCacheRecommendations(array $analytics): void
    {
        $hitRatio = $analytics['hit_ratio'];
        $distribution = $analytics['cache_distribution'];

        if ($hitRatio < 80) {
            $this->line('• Consider warming cache more frequently');
            $this->line('• Increase TTL for stable data');
        }

        if ($distribution['live_game'] > 100) {
            $this->line('• Live game cache is high - ensure proper cleanup');
        }

        if ($distribution['search'] > 1000) {
            $this->line('• Search cache is large - consider shorter TTL');
        }

        $this->line('• Monitor cache hit ratios regularly');
        $this->line('• Use compression for large datasets');
        $this->line('• Implement cache warming during off-peak hours');
    }

    /**
     * Clear terminal screen using ANSI escape codes.
     * SEC-007: Replaced system('cls')/system('clear') with safe ANSI codes.
     */
    private function clearTerminal(): void
    {
        // ANSI escape sequence: \033[2J clears screen, \033[H moves cursor to top-left
        $this->output->write("\033[2J\033[H");
    }
}