<?php

namespace App\Jobs;

use App\Http\Controllers\StripeWebhookController;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Event as StripeEvent;

/**
 * Job for processing Stripe webhook events asynchronously
 * Provides retry logic and error handling
 */
class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    public $timeout = 120; // 2 minutes

    private int $webhookEventId;
    private array $stripeEventData;

    /**
     * Create a new job instance
     *
     * @param int $webhookEventId
     * @param array $stripeEventData
     */
    public function __construct(int $webhookEventId, array $stripeEventData)
    {
        $this->webhookEventId = $webhookEventId;
        $this->stripeEventData = $stripeEventData;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);
        
        if (!$webhookEvent) {
            Log::error('Webhook event not found for processing', [
                'webhook_event_id' => $this->webhookEventId,
            ]);
            return;
        }

        // Update status to processing
        $webhookEvent->update([
            'status' => WebhookEvent::STATUS_PROCESSING,
            'processing_started_at' => now(),
        ]);

        try {
            // Reconstruct Stripe event from data
            $stripeEvent = StripeEvent::constructFrom($this->stripeEventData);
            
            Log::info('Processing webhook event asynchronously', [
                'webhook_event_id' => $this->webhookEventId,
                'stripe_event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'attempt' => $this->attempts(),
            ]);

            // Process the event
            app(StripeWebhookController::class)->handleStripeEvent($stripeEvent);

            // Mark as processed
            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processing_completed_at' => now(),
                'error_message' => null,
            ]);

            Log::info('Webhook event processed successfully', [
                'webhook_event_id' => $this->webhookEventId,
                'stripe_event_id' => $stripeEvent->id,
                'processing_duration' => $webhookEvent->processing_duration,
            ]);

        } catch (\Exception $e) {
            $this->handleProcessingError($webhookEvent, $e);
        }
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);
        
        if ($webhookEvent) {
            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'processing_completed_at' => now(),
                'error_message' => $exception->getMessage(),
                'retry_count' => $this->attempts(),
            ]);
        }

        Log::error('Webhook processing job failed permanently', [
            'webhook_event_id' => $this->webhookEventId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators of critical webhook failure
        $this->notifyWebhookFailure($webhookEvent, $exception);
    }

    /**
     * Handle processing error during job execution
     *
     * @param WebhookEvent $webhookEvent
     * @param \Exception $exception
     * @throws \Exception
     */
    protected function handleProcessingError(WebhookEvent $webhookEvent, \Exception $exception): void
    {
        $attempt = $this->attempts();
        $maxAttempts = $this->tries;

        Log::error('Webhook processing error', [
            'webhook_event_id' => $this->webhookEventId,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'error' => $exception->getMessage(),
        ]);

        // Update webhook event with error info
        $webhookEvent->update([
            'error_message' => $exception->getMessage(),
            'retry_count' => $attempt,
            'processing_completed_at' => now(),
        ]);

        // If this isn't the last attempt, mark as pending for retry
        if ($attempt < $maxAttempts) {
            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_PENDING,
                'processing_started_at' => null,
                'processing_completed_at' => null,
            ]);
        } else {
            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_FAILED,
            ]);
        }

        // Re-throw to trigger Laravel's retry mechanism
        throw $exception;
    }

    /**
     * Determine if the error is retryable
     *
     * @param \Exception $exception
     * @return bool
     */
    protected function isRetryableError(\Exception $exception): bool
    {
        // Don't retry certain types of errors
        $nonRetryableErrors = [
            \Stripe\Exception\InvalidRequestException::class,
            \Stripe\Exception\AuthenticationException::class,
        ];

        foreach ($nonRetryableErrors as $errorClass) {
            if ($exception instanceof $errorClass) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the number of seconds to wait before retrying the job
     *
     * @return int
     */
    public function retryAfter(): int
    {
        $attempt = $this->attempts();
        return $this->backoff[$attempt - 1] ?? 900; // Default to 15 minutes
    }

    /**
     * Notify administrators of webhook failure
     *
     * @param WebhookEvent|null $webhookEvent
     * @param \Throwable $exception
     * @return void
     */
    protected function notifyWebhookFailure(?WebhookEvent $webhookEvent, \Throwable $exception): void
    {
        // This could send emails, Slack notifications, etc.
        // For now, just ensure it's logged prominently
        Log::critical('Critical webhook processing failure requires attention', [
            'webhook_event_id' => $this->webhookEventId,
            'stripe_event_id' => $webhookEvent?->stripe_event_id,
            'event_type' => $webhookEvent?->event_type,
            'tenant_id' => $webhookEvent?->tenant_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the unique ID for the job
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'stripe_webhook_' . $this->webhookEventId;
    }

    /**
     * Get the tags that should be applied to the job
     *
     * @return array
     */
    public function tags(): array
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);
        
        $tags = ['stripe', 'webhook'];
        
        if ($webhookEvent) {
            $tags[] = $webhookEvent->event_type;
            if ($webhookEvent->tenant_id) {
                $tags[] = 'tenant:' . $webhookEvent->tenant_id;
            }
        }
        
        return $tags;
    }
}