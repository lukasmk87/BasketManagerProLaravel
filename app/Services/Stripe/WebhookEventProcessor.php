<?php

namespace App\Services\Stripe;

use App\Jobs\ProcessStripeWebhook;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Stripe\Event as StripeEvent;

/**
 * Service for processing and tracking Stripe webhook events
 * Provides event deduplication, retry logic, and audit trails
 */
class WebhookEventProcessor
{
    /**
     * Process webhook event with deduplication and queuing
     *
     * @param StripeEvent $stripeEvent
     * @return bool
     */
    public function processEvent(StripeEvent $stripeEvent): bool
    {
        // Check if we've already processed this event
        $existingEvent = WebhookEvent::where('stripe_event_id', $stripeEvent->id)->first();
        
        if ($existingEvent) {
            Log::info('Webhook event already processed', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'status' => $existingEvent->status,
            ]);
            
            return $existingEvent->status === 'processed';
        }

        // Create webhook event record
        $webhookEvent = $this->createWebhookEventRecord($stripeEvent);
        
        // Determine if event should be processed synchronously or queued
        if ($this->shouldProcessSynchronously($stripeEvent->type)) {
            return $this->processSynchronously($webhookEvent, $stripeEvent);
        } else {
            return $this->processAsynchronously($webhookEvent, $stripeEvent);
        }
    }

    /**
     * Create webhook event database record
     *
     * @param StripeEvent $stripeEvent
     * @return WebhookEvent
     */
    protected function createWebhookEventRecord(StripeEvent $stripeEvent): WebhookEvent
    {
        $tenant = $this->resolveTenantFromEvent($stripeEvent);
        
        return WebhookEvent::create([
            'stripe_event_id' => $stripeEvent->id,
            'event_type' => $stripeEvent->type,
            'tenant_id' => $tenant?->id,
            'status' => 'pending',
            'payload' => $stripeEvent->toArray(),
            'livemode' => $stripeEvent->livemode,
            'api_version' => $stripeEvent->api_version,
            'created_at' => now(),
        ]);
    }

    /**
     * Determine if event should be processed synchronously
     *
     * @param string $eventType
     * @return bool
     */
    protected function shouldProcessSynchronously(string $eventType): bool
    {
        $synchronousEvents = [
            'invoice.payment_failed',
            'customer.subscription.deleted',
            'setup_intent.succeeded',
            'checkout.session.completed',
        ];

        return in_array($eventType, $synchronousEvents);
    }

    /**
     * Process event synchronously
     *
     * @param WebhookEvent $webhookEvent
     * @param StripeEvent $stripeEvent
     * @return bool
     */
    protected function processSynchronously(WebhookEvent $webhookEvent, StripeEvent $stripeEvent): bool
    {
        try {
            $webhookEvent->update([
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Process the event
            app(StripeWebhookController::class)->handleStripeEvent($stripeEvent);

            $webhookEvent->update([
                'status' => 'processed',
                'processing_completed_at' => now(),
            ]);

            Log::info('Webhook event processed synchronously', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
            ]);

            return true;

        } catch (\Exception $e) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_completed_at' => now(),
            ]);

            Log::error('Synchronous webhook processing failed', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process event asynchronously via queue
     *
     * @param WebhookEvent $webhookEvent
     * @param StripeEvent $stripeEvent
     * @return bool
     */
    protected function processAsynchronously(WebhookEvent $webhookEvent, StripeEvent $stripeEvent): bool
    {
        try {
            // Queue the webhook processing job
            ProcessStripeWebhook::dispatch($webhookEvent->id, $stripeEvent->toArray())
                ->onQueue('webhooks')
                ->delay(now()->addSeconds(5)); // Small delay to ensure data consistency

            $webhookEvent->update([
                'status' => 'queued',
                'queued_at' => now(),
            ]);

            Log::info('Webhook event queued for processing', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'webhook_event_id' => $webhookEvent->id,
            ]);

            return true;

        } catch (\Exception $e) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Failed to queue webhook event', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Resolve tenant from Stripe event
     *
     * @param StripeEvent $event
     * @return Tenant|null
     */
    protected function resolveTenantFromEvent(StripeEvent $event): ?Tenant
    {
        $object = $event->data->object;
        
        // Try to get tenant_id from metadata
        $tenantId = $object->metadata->tenant_id ?? null;
        
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        // Try to resolve from customer
        if (isset($object->customer)) {
            $customerId = is_string($object->customer) ? $object->customer : $object->customer->id;
            return Tenant::where('stripe_id', $customerId)->first();
        }

        return null;
    }

    /**
     * Retry failed webhook event
     *
     * @param WebhookEvent $webhookEvent
     * @return bool
     */
    public function retryEvent(WebhookEvent $webhookEvent): bool
    {
        if ($webhookEvent->status !== 'failed') {
            Log::warning('Attempted to retry non-failed webhook event', [
                'webhook_event_id' => $webhookEvent->id,
                'status' => $webhookEvent->status,
            ]);
            return false;
        }

        try {
            // Reconstruct Stripe event from payload
            $stripeEvent = \Stripe\Event::constructFrom($webhookEvent->payload);
            
            // Reset status and retry
            $webhookEvent->update([
                'status' => 'pending',
                'error_message' => null,
                'retry_count' => $webhookEvent->retry_count + 1,
            ]);

            return $this->processEvent($stripeEvent);

        } catch (\Exception $e) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Webhook event retry failed', [
                'webhook_event_id' => $webhookEvent->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get webhook event statistics
     *
     * @param int $days
     * @return array
     */
    public function getEventStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $stats = WebhookEvent::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_events,
                SUM(CASE WHEN status = "processed" THEN 1 ELSE 0 END) as processed_events,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_events,
                SUM(CASE WHEN status = "pending" OR status = "queued" THEN 1 ELSE 0 END) as pending_events,
                AVG(CASE 
                    WHEN processing_completed_at IS NOT NULL AND processing_started_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(MICROSECOND, processing_started_at, processing_completed_at) / 1000000
                    ELSE NULL 
                END) as avg_processing_time_seconds
            ')
            ->first();

        $eventTypes = WebhookEvent::where('created_at', '>=', $startDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get();

        return [
            'period_days' => $days,
            'total_events' => $stats->total_events ?? 0,
            'processed_events' => $stats->processed_events ?? 0,
            'failed_events' => $stats->failed_events ?? 0,
            'pending_events' => $stats->pending_events ?? 0,
            'success_rate' => $stats->total_events > 0 ? 
                round(($stats->processed_events / $stats->total_events) * 100, 2) : 0,
            'avg_processing_time_seconds' => round($stats->avg_processing_time_seconds ?? 0, 3),
            'event_types' => $eventTypes->toArray(),
        ];
    }

    /**
     * Clean up old webhook events
     *
     * @param int $daysToKeep
     * @return int Number of deleted events
     */
    public function cleanupOldEvents(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deletedCount = WebhookEvent::where('created_at', '<', $cutoffDate)
            ->where('status', 'processed')
            ->delete();

        Log::info('Cleaned up old webhook events', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return $deletedCount;
    }
}