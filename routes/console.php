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

// SEC-008: Daily storage sync for all clubs
// Runs at 06:00 after other maintenance tasks to sync calculated storage usage
app(Schedule::class)->command('club:sync-storage')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('club-storage-sync')
    ->description('Sync storage usage calculations for all clubs');

// Invoice Scheduled Tasks
// Generate recurring invoices for invoice-paying clubs on the 1st of each month
app(Schedule::class)->command('invoices:generate-recurring')
    ->monthlyOn(1, '08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('invoices-generate-recurring')
    ->description('Generate recurring invoices for clubs paying via invoice');

// Process overdue invoices daily - send reminders, suspend subscriptions
app(Schedule::class)->command('invoices:process-overdue')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('invoices-process-overdue')
    ->description('Process overdue invoices, send reminders, and suspend subscriptions');

// Event Response Reminders - remind players to respond to upcoming events
app(Schedule::class)->command('events:send-reminders --hours=48')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->name('events-send-reminders')
    ->description('Send reminders to players who have not responded to upcoming events');
