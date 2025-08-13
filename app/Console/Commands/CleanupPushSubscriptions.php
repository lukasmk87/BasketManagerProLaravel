<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupPushSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:cleanup 
                           {--days=90 : Number of days after which subscriptions are considered stale}
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old and inactive push notification subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("🏀 BasketManager Pro Push Subscription Cleanup");
        $this->info("Finding subscriptions older than {$days} days...");

        // Find stale subscriptions
        $staleSubscriptions = PushSubscription::where(function ($query) use ($days) {
            $query->where('last_used_at', '<', now()->subDays($days))
                  ->orWhereNull('last_used_at')
                  ->where('created_at', '<', now()->subDays($days));
        })->get();

        // Find inactive subscriptions
        $inactiveSubscriptions = PushSubscription::where('is_active', false)->get();

        // Find likely invalid subscriptions
        $invalidSubscriptions = PushSubscription::where(function ($query) {
            $query->where('endpoint', 'not like', 'https://%')
                  ->orWhere('p256dh_key', '')
                  ->orWhere('auth_token', '');
        })->get();

        $totalToCleanup = $staleSubscriptions->count() + 
                         $inactiveSubscriptions->count() + 
                         $invalidSubscriptions->count();

        if ($totalToCleanup === 0) {
            $this->info("✅ No subscriptions need cleanup!");
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->line("📊 Cleanup Summary:");
        $this->line("  • Stale subscriptions (>{$days} days): " . $staleSubscriptions->count());
        $this->line("  • Inactive subscriptions: " . $inactiveSubscriptions->count());
        $this->line("  • Invalid subscriptions: " . $invalidSubscriptions->count());
        $this->line("  • Total to cleanup: {$totalToCleanup}");

        if ($dryRun) {
            $this->newLine();
            $this->warn("🔍 DRY RUN MODE - No subscriptions will be deleted");
            
            if ($staleSubscriptions->count() > 0) {
                $this->line("\nStale subscriptions that would be deleted:");
                foreach ($staleSubscriptions->take(10) as $subscription) {
                    $browserInfo = $subscription->getBrowserInfo();
                    $lastUsed = $subscription->last_used_at 
                        ? $subscription->last_used_at->diffForHumans()
                        : 'Never used';
                    
                    $this->line("  • {$subscription->id} ({$browserInfo['browser']} on {$browserInfo['platform']}) - Last used: {$lastUsed}");
                }
                
                if ($staleSubscriptions->count() > 10) {
                    $remaining = $staleSubscriptions->count() - 10;
                    $this->line("  ... and {$remaining} more");
                }
            }
            
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirm("Are you sure you want to delete {$totalToCleanup} push subscriptions?")) {
                $this->info("Cleanup cancelled.");
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->info("🧹 Starting cleanup...");

        $deletedCount = 0;
        $errorCount = 0;

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalToCleanup);
        $progressBar->start();

        // Cleanup stale subscriptions
        foreach ($staleSubscriptions as $subscription) {
            try {
                $subscription->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to delete stale push subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }

        // Cleanup inactive subscriptions
        foreach ($inactiveSubscriptions as $subscription) {
            try {
                $subscription->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to delete inactive push subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }

        // Cleanup invalid subscriptions
        foreach ($invalidSubscriptions as $subscription) {
            try {
                $subscription->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to delete invalid push subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        if ($errorCount === 0) {
            $this->info("✅ Cleanup completed successfully!");
        } else {
            $this->warn("⚠️  Cleanup completed with some errors.");
        }

        $this->line("📈 Results:");
        $this->line("  • Subscriptions deleted: {$deletedCount}");
        $this->line("  • Errors encountered: {$errorCount}");

        // Show remaining subscription count
        $remainingCount = PushSubscription::count();
        $this->line("  • Remaining subscriptions: {$remainingCount}");

        // Log the cleanup
        Log::info('Push subscription cleanup completed', [
            'deleted_count' => $deletedCount,
            'error_count' => $errorCount,
            'remaining_count' => $remainingCount,
            'cleanup_threshold_days' => $days,
            'dry_run' => false
        ]);

        return Command::SUCCESS;
    }
}