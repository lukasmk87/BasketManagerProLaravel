<?php

namespace App\Console\Commands\RateLimit;

use App\Models\User;
use App\Models\ApiUsageTracking;
use App\Models\RateLimitException;
use App\Services\EnterpriseRateLimitService;
use Illuminate\Console\Command;

class MonitorRateLimitsCommand extends Command
{
    protected $signature = 'rate-limit:monitor
                          {--user= : Monitor specific user ID}
                          {--tier= : Monitor specific subscription tier}
                          {--threshold=80 : Alert threshold percentage}
                          {--period=last_hour : Time period (last_hour, last_24_hours, last_week)}';

    protected $description = 'Monitor rate limit usage and alert on high usage';

    protected EnterpriseRateLimitService $rateLimitService;

    public function __construct(EnterpriseRateLimitService $rateLimitService)
    {
        parent::__construct();
        $this->rateLimitService = $rateLimitService;
    }

    public function handle(): int
    {
        $userId = $this->option('user');
        $tier = $this->option('tier');
        $threshold = (float) $this->option('threshold');
        $period = $this->option('period');

        $this->info("Monitoring rate limit usage (threshold: {$threshold}%)...");

        try {
            if ($userId) {
                $this->monitorUser($userId, $threshold);
            } elseif ($tier) {
                $this->monitorTier($tier, $threshold);
            } else {
                $this->monitorAll($threshold, $period);
            }

            $this->showActiveExceptions();
            $this->showTopConsumers($period);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error monitoring rate limits: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function monitorUser(int $userId, float $threshold): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        $status = $this->rateLimitService->getStatus($user);
        $this->showUserStatus($user, $status, $threshold);
    }

    protected function monitorTier(string $tier, float $threshold): void
    {
        $users = User::where('subscription_tier', $tier)->get();
        $this->info("Monitoring {$users->count()} users on '{$tier}' tier...");

        $alertCount = 0;
        foreach ($users as $user) {
            $status = $this->rateLimitService->getStatus($user);
            if ($this->shouldAlert($status, $threshold)) {
                $this->showUserStatus($user, $status, $threshold);
                $alertCount++;
            }
        }

        if ($alertCount === 0) {
            $this->info("No users on '{$tier}' tier are above {$threshold}% threshold");
        } else {
            $this->warn("Found {$alertCount} users above threshold on '{$tier}' tier");
        }
    }

    protected function monitorAll(float $threshold, string $period): void
    {
        // Get top consumers who might be approaching limits
        $topConsumers = ApiUsageTracking::getTopConsumers(20, $period);
        
        $this->info("Top API consumers in {$period}:");
        $this->newLine();

        $alertCount = 0;
        $tableData = [];

        foreach ($topConsumers as $consumer) {
            $user = $consumer->user_id ? User::find($consumer->user_id) : null;
            $status = $user ? $this->rateLimitService->getStatus($user) : null;
            
            $isAlert = $status && $this->shouldAlert($status, $threshold);
            if ($isAlert) $alertCount++;

            $tableData[] = [
                $consumer->user_id ?? 'Anonymous',
                $consumer->subscription_tier ?? 'free',
                number_format($consumer->total_requests),
                '$' . number_format($consumer->total_cost, 4),
                $consumer->avg_response_time ? round($consumer->avg_response_time) . 'ms' : 'N/A',
                $isAlert ? '⚠️  ALERT' : '✅ OK',
            ];
        }

        $this->table(
            ['User ID', 'Tier', 'Requests', 'Cost', 'Avg Response', 'Status'],
            $tableData
        );

        if ($alertCount > 0) {
            $this->warn("Found {$alertCount} users above {$threshold}% threshold");
        } else {
            $this->info("No users are above {$threshold}% threshold");
        }
    }

    protected function showUserStatus(User $user, array $status, float $threshold): void
    {
        $hourlyPercent = $status['percentage_used']['hourly'];
        $burstPercent = $status['percentage_used']['minutely'];
        $concurrentPercent = $status['percentage_used']['concurrent'];

        $alertLevel = max($hourlyPercent, $burstPercent, $concurrentPercent) >= $threshold ? 'warn' : 'info';

        $this->newLine();
        $this->{$alertLevel}("User {$user->id} ({$user->email}) - Tier: {$status['subscription_tier']}");

        $this->table(
            ['Limit Type', 'Used', 'Limit', 'Percentage', 'Reset In'],
            [
                [
                    'Hourly',
                    number_format($status['usage']['hourly']['total_cost']),
                    number_format($status['limits']['requests_per_hour']),
                    round($hourlyPercent, 1) . '%',
                    $this->formatTime($status['time_until_reset']['hourly']),
                ],
                [
                    'Burst (per minute)',
                    number_format($status['usage']['minutely']['total_cost']),
                    number_format($status['limits']['burst_per_minute']),
                    round($burstPercent, 1) . '%',
                    $this->formatTime($status['time_until_reset']['minutely']),
                ],
                [
                    'Concurrent',
                    $status['usage']['concurrent'],
                    $status['limits']['concurrent_requests'],
                    round($concurrentPercent, 1) . '%',
                    'Real-time',
                ],
            ]
        );
    }

    protected function shouldAlert(array $status, float $threshold): bool
    {
        return max(
            $status['percentage_used']['hourly'],
            $status['percentage_used']['minutely'],
            $status['percentage_used']['concurrent']
        ) >= $threshold;
    }

    protected function showActiveExceptions(): void
    {
        $exceptions = RateLimitException::active()->get();

        if ($exceptions->count() === 0) {
            $this->info("No active rate limit exceptions");
            return;
        }

        $this->newLine();
        $this->info("Active Rate Limit Exceptions:");

        $tableData = [];
        foreach ($exceptions as $exception) {
            $tableData[] = [
                $exception->id,
                $exception->user_id ?? 'N/A',
                $exception->exception_type,
                $exception->scope,
                $exception->times_used . '/' . ($exception->max_uses ?? '∞'),
                $exception->expires_at?->diffForHumans() ?? 'Never',
                $exception->reason ?: 'No reason provided',
            ];
        }

        $this->table(
            ['ID', 'User', 'Type', 'Scope', 'Uses', 'Expires', 'Reason'],
            $tableData
        );
    }

    protected function showTopConsumers(string $period): void
    {
        $this->newLine();
        $this->info("Current System Statistics:");

        $stats = [
            'Total Active Users' => User::where('api_access_enabled', true)->count(),
            'Active Exceptions' => RateLimitException::active()->count(),
            'Requests Today' => ApiUsageTracking::whereDate('created_at', today())->sum('request_count'),
            'Overage Requests Today' => ApiUsageTracking::whereDate('created_at', today())->where('is_overage', true)->sum('request_count'),
        ];

        foreach ($stats as $label => $value) {
            $this->info("{$label}: " . number_format($value));
        }
    }

    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . 'm';
        } else {
            return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        }
    }
}