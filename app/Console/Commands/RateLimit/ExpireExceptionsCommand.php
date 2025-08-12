<?php

namespace App\Console\Commands\RateLimit;

use App\Models\RateLimitException;
use Illuminate\Console\Command;

class ExpireExceptionsCommand extends Command
{
    protected $signature = 'rate-limit:expire-exceptions
                          {--dry-run : Show what would be expired without actually expiring}
                          {--force : Force expire exceptions even if not set to auto-expire}';

    protected $description = 'Expire rate limit exceptions that have passed their expiration time';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Checking for expired rate limit exceptions...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No exceptions will be expired');
        }

        try {
            $expiredCount = $this->expireExceptions($dryRun, $force);

            if ($dryRun) {
                $this->info("Would expire {$expiredCount} exceptions");
            } else {
                $this->info("Expired {$expiredCount} exceptions");
            }

            $this->showCurrentExceptions();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error expiring exceptions: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function expireExceptions(bool $dryRun, bool $force): int
    {
        $query = RateLimitException::where('status', 'active');

        // Add expiration conditions
        $query->where(function ($q) use ($force) {
            // Natural expiration
            $q->where('expires_at', '<=', now());
            
            // Max uses reached
            $q->orWhereRaw('max_uses IS NOT NULL AND times_used >= max_uses');
            
            // Force expire if requested
            if ($force) {
                $q->orWhere('auto_expire', true);
            } else {
                $q->where('auto_expire', true);
            }
        });

        if ($dryRun) {
            return $query->count();
        }

        $exceptions = $query->get();
        $count = 0;

        foreach ($exceptions as $exception) {
            $reason = $this->getExpirationReason($exception);
            if ($exception->expire($reason)) {
                $count++;
                $this->line("Expired exception #{$exception->id}: {$reason}");
            }
        }

        return $count;
    }

    protected function getExpirationReason(RateLimitException $exception): string
    {
        if ($exception->expires_at && $exception->expires_at->isPast()) {
            return 'Expired at ' . $exception->expires_at->format('Y-m-d H:i:s');
        }

        if ($exception->max_uses && $exception->times_used >= $exception->max_uses) {
            return "Maximum uses reached ({$exception->times_used}/{$exception->max_uses})";
        }

        return 'Auto-expired by system';
    }

    protected function showCurrentExceptions(): void
    {
        $active = RateLimitException::active()->count();
        $expired = RateLimitException::where('status', 'expired')->count();
        $revoked = RateLimitException::where('status', 'revoked')->count();

        $this->newLine();
        $this->info('Current Exception Statistics:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Active', $active],
                ['Expired', $expired],
                ['Revoked', $revoked],
                ['Total', $active + $expired + $revoked],
            ]
        );

        // Show active exceptions details if there are any
        if ($active > 0) {
            $this->newLine();
            $this->info('Active Exceptions:');

            $activeExceptions = RateLimitException::active()->get();
            $tableData = [];

            foreach ($activeExceptions as $exception) {
                $expiresIn = $exception->expires_at 
                    ? $exception->expires_at->diffForHumans() 
                    : 'Never';

                $usesLeft = $exception->max_uses
                    ? ($exception->max_uses - $exception->times_used)
                    : '∞';

                $tableData[] = [
                    $exception->id,
                    $exception->user_id ?? 'System',
                    $exception->exception_type,
                    $exception->scope,
                    $exception->times_used,
                    $usesLeft,
                    $expiresIn,
                    $exception->auto_expire ? 'Yes' : 'No',
                ];
            }

            $this->table(
                ['ID', 'User', 'Type', 'Scope', 'Used', 'Left', 'Expires', 'Auto-Expire'],
                $tableData
            );
        }
    }
}