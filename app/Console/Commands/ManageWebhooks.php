<?php

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use App\Services\Stripe\WebhookEventProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:manage 
                            {action : Action to perform (stats, retry, cleanup)}
                            {--days=7 : Number of days for stats or cleanup}
                            {--event-id= : Specific webhook event ID to retry}
                            {--failed-only : Only process failed webhooks}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage webhook events (stats, retry failed events, cleanup old events)';

    private WebhookEventProcessor $eventProcessor;

    public function __construct(WebhookEventProcessor $eventProcessor)
    {
        parent::__construct();
        $this->eventProcessor = $eventProcessor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'stats':
                return $this->showStats();
            
            case 'retry':
                return $this->retryFailedEvents();
            
            case 'cleanup':
                return $this->cleanupOldEvents();
            
            default:
                $this->error("Unknown action: {$action}");
                $this->line('Available actions: stats, retry, cleanup');
                return 1;
        }
    }

    /**
     * Show webhook event statistics
     */
    private function showStats(): int
    {
        $days = (int) $this->option('days');
        $stats = $this->eventProcessor->getEventStatistics($days);

        $this->info("📊 Webhook Event Statistics (Last {$days} days)");
        $this->newLine();

        $this->table([
            'Metric', 'Value'
        ], [
            ['Total Events', number_format($stats['total_events'])],
            ['Processed Events', number_format($stats['processed_events'])],
            ['Failed Events', number_format($stats['failed_events'])],
            ['Pending Events', number_format($stats['pending_events'])],
            ['Success Rate', $stats['success_rate'] . '%'],
            ['Avg Processing Time', $stats['avg_processing_time_seconds'] . 's'],
        ]);

        if (!empty($stats['event_types'])) {
            $this->newLine();
            $this->info('📈 Event Types Distribution:');
            $this->table([
                'Event Type', 'Count'
            ], array_map(function ($eventType) {
                return [$eventType['event_type'], number_format($eventType['count'])];
            }, $stats['event_types']));
        }

        return 0;
    }

    /**
     * Retry failed webhook events
     */
    private function retryFailedEvents(): int
    {
        $eventId = $this->option('event-id');
        
        if ($eventId) {
            return $this->retrySpecificEvent($eventId);
        }

        return $this->retryAllFailedEvents();
    }

    /**
     * Retry a specific webhook event
     */
    private function retrySpecificEvent(string $eventId): int
    {
        $webhookEvent = WebhookEvent::find($eventId);
        
        if (!$webhookEvent) {
            $this->error("Webhook event {$eventId} not found.");
            return 1;
        }

        if ($webhookEvent->status !== WebhookEvent::STATUS_FAILED) {
            $this->error("Webhook event {$eventId} is not in failed status (current: {$webhookEvent->status}).");
            return 1;
        }

        $this->info("Retrying webhook event {$eventId} ({$webhookEvent->event_type})...");
        
        $success = $this->eventProcessor->retryEvent($webhookEvent);
        
        if ($success) {
            $this->info("✅ Webhook event {$eventId} retried successfully.");
            return 0;
        } else {
            $this->error("❌ Failed to retry webhook event {$eventId}.");
            return 1;
        }
    }

    /**
     * Retry all failed webhook events
     */
    private function retryAllFailedEvents(): int
    {
        $failedEvents = WebhookEvent::failed()
            ->where('retry_count', '<', 3)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($failedEvents->isEmpty()) {
            $this->info('No failed webhook events found to retry.');
            return 0;
        }

        $this->info("Found {$failedEvents->count()} failed webhook events to retry.");

        if (!$this->option('force') && !$this->confirm('Do you want to retry all failed events?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $successCount = 0;
        $failureCount = 0;

        $progressBar = $this->output->createProgressBar($failedEvents->count());
        $progressBar->start();

        foreach ($failedEvents as $webhookEvent) {
            $success = $this->eventProcessor->retryEvent($webhookEvent);
            
            if ($success) {
                $successCount++;
            } else {
                $failureCount++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully retried: {$successCount} events");
        if ($failureCount > 0) {
            $this->error("❌ Failed to retry: {$failureCount} events");
        }

        return $failureCount > 0 ? 1 : 0;
    }

    /**
     * Cleanup old webhook events
     */
    private function cleanupOldEvents(): int
    {
        $days = (int) $this->option('days');
        
        if ($days < 7) {
            $this->error('Minimum cleanup period is 7 days for safety.');
            return 1;
        }

        $this->info("Cleaning up webhook events older than {$days} days...");

        if (!$this->option('force')) {
            $oldEventsCount = WebhookEvent::where('created_at', '<', now()->subDays($days))
                ->where('status', WebhookEvent::STATUS_PROCESSED)
                ->count();

            if ($oldEventsCount === 0) {
                $this->info('No old webhook events found to cleanup.');
                return 0;
            }

            if (!$this->confirm("This will delete {$oldEventsCount} old webhook events. Continue?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $deletedCount = $this->eventProcessor->cleanupOldEvents($days);
        
        $this->info("✅ Cleaned up {$deletedCount} old webhook events.");
        
        Log::info('Webhook cleanup completed via command', [
            'deleted_count' => $deletedCount,
            'days' => $days,
        ]);

        return 0;
    }
}
