<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Basketball-specific scheduled tasks
use Illuminate\Console\Scheduling\Schedule;

// Clean up old push subscriptions weekly
Artisan::command('basketmanager:scheduled-cleanup', function () {
    $this->info('🏀 Running BasketManager Pro scheduled cleanup tasks...');

    // Cleanup push subscriptions
    $this->call('push:cleanup', ['--force' => true, '--days' => 90]);

    $this->info('✅ Scheduled cleanup completed!');
})->purpose('Run scheduled cleanup tasks for BasketManager Pro');

// Subscription Analytics Scheduled Tasks
app(Schedule::class)->command('subscription:update-mrr --type=daily')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->name('subscription-mrr-daily')
    ->description('Calculate daily MRR snapshots');

app(Schedule::class)->command('subscription:update-mrr --type=monthly')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->name('subscription-mrr-monthly')
    ->description('Calculate monthly MRR snapshots');

app(Schedule::class)->command('subscription:calculate-churn')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->name('subscription-churn')
    ->description('Calculate monthly churn rates');

app(Schedule::class)->command('subscription:update-cohorts')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->name('subscription-cohorts')
    ->description('Update cohort retention analytics');

// Club Transfer Scheduled Tasks
app(Schedule::class)->command('club-transfer:cleanup-rollback-data')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->name('club-transfer-cleanup-rollback')
    ->description('Cleanup expired club transfer rollback data (>24h)');

// Optional: Weekly cleanup of old transfer records
app(Schedule::class)->call(function () {
    // Delete soft-deleted transfers older than 90 days
    \App\Models\ClubTransfer::onlyTrashed()
        ->where('deleted_at', '<', now()->subDays(90))
        ->forceDelete();
})->weekly()
    ->sundays()
    ->at('05:00')
    ->name('club-transfer-cleanup-old-records')
    ->description('Permanently delete old club transfer records (>90 days)');
