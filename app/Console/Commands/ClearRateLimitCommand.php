<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ClearRateLimitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate-limit:clear
                            {key? : Specific rate limit key to clear (optional)}
                            {--all : Clear all rate limit entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear rate limit entries from cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $key = $this->argument('key');
        $all = $this->option('all');

        if ($all) {
            // Clear all cache (includes rate limits)
            Cache::flush();
            $this->info('✅ All rate limit entries cleared (cache flushed).');
            return Command::SUCCESS;
        }

        if ($key) {
            // Clear specific rate limit key
            RateLimiter::clear($key);
            $this->info("✅ Rate limit cleared for key: {$key}");
            return Command::SUCCESS;
        }

        // Interactive mode - clear common rate limiters
        $this->info('🔧 Clearing common rate limit entries...');

        $cleared = 0;

        // Clear login rate limits (common patterns)
        $patterns = [
            'login:*',
            'two-factor:*',
            'player-registration:*',
            'invitation-creation:*',
        ];

        foreach ($patterns as $pattern) {
            try {
                // Get cache keys matching pattern
                $keys = Cache::get($pattern, []);

                if (is_array($keys)) {
                    foreach ($keys as $k) {
                        RateLimiter::clear($k);
                        $cleared++;
                    }
                }
            } catch (\Exception $e) {
                // Continue if pattern doesn't exist
                continue;
            }
        }

        if ($cleared > 0) {
            $this->info("✅ Cleared {$cleared} rate limit entries.");
        } else {
            $this->warn('⚠️  No rate limit entries found to clear.');
            $this->line('');
            $this->line('💡 To clear all cache (including rate limits), use:');
            $this->line('   php artisan cache:clear');
            $this->line('');
            $this->line('💡 Or to force clear all rate limits:');
            $this->line('   php artisan rate-limit:clear --all');
        }

        return Command::SUCCESS;
    }
}
