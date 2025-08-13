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
