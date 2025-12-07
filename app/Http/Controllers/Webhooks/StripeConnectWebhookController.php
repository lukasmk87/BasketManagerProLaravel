<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Stripe\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeConnectWebhookController extends Controller
{
    public function __construct(
        protected StripeConnectService $connectService
    ) {}

    /**
     * Handle incoming Stripe Connect webhooks.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.connect.webhook_secret');

        if (! $webhookSecret) {
            Log::error('Stripe Connect webhook secret not configured');

            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe Connect webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe Connect webhook parsing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Stripe Connect webhook received', [
            'event_type' => $event->type,
            'event_id' => $event->id,
        ]);

        try {
            $this->processEvent($event);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Stripe Connect webhook processing failed', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Process the webhook event.
     */
    protected function processEvent(Event $event): void
    {
        match ($event->type) {
            'account.updated' => $this->handleAccountUpdated($event),
            'account.application.authorized' => $this->handleAccountAuthorized($event),
            'account.application.deauthorized' => $this->handleAccountDeauthorized($event),
            'capability.updated' => $this->handleCapabilityUpdated($event),
            'payout.paid' => $this->handlePayoutPaid($event),
            'payout.failed' => $this->handlePayoutFailed($event),
            'payout.created' => $this->handlePayoutCreated($event),
            default => Log::info('Unhandled Connect webhook event', ['type' => $event->type]),
        };
    }

    /**
     * Handle account.updated event.
     */
    protected function handleAccountUpdated(Event $event): void
    {
        $account = $event->data->object;
        $accountId = $account->id;

        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if (! $tenant) {
            Log::warning('Tenant not found for Connect account', ['account_id' => $accountId]);

            return;
        }

        $tenant->updateConnectStatus([
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
            'requirements' => [
                'disabled_reason' => $account->requirements->disabled_reason ?? null,
            ],
        ]);

        Log::info('Tenant Connect status updated from webhook', [
            'tenant_id' => $tenant->id,
            'account_id' => $accountId,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
        ]);
    }

    /**
     * Handle account.application.authorized event.
     */
    protected function handleAccountAuthorized(Event $event): void
    {
        $account = $event->data->object;
        $accountId = $account->id;

        Log::info('Connect account authorized', ['account_id' => $accountId]);

        // This typically happens after OAuth flow - tenant should already be linked
        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if ($tenant) {
            $this->connectService->refreshAccountStatus($tenant);
        }
    }

    /**
     * Handle account.application.deauthorized event.
     */
    protected function handleAccountDeauthorized(Event $event): void
    {
        $account = $event->data->object;
        $accountId = $account->id;

        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if ($tenant) {
            $tenant->disconnectStripeConnect();

            Log::info('Tenant Connect account deauthorized', [
                'tenant_id' => $tenant->id,
                'account_id' => $accountId,
            ]);
        }
    }

    /**
     * Handle capability.updated event.
     */
    protected function handleCapabilityUpdated(Event $event): void
    {
        $capability = $event->data->object;
        $accountId = $capability->account;

        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if ($tenant) {
            // Refresh full account status when capabilities change
            $this->connectService->refreshAccountStatus($tenant);

            Log::info('Tenant Connect capability updated', [
                'tenant_id' => $tenant->id,
                'account_id' => $accountId,
                'capability' => $capability->id,
                'status' => $capability->status,
            ]);
        }
    }

    /**
     * Handle payout.paid event.
     */
    protected function handlePayoutPaid(Event $event): void
    {
        $payout = $event->data->object;
        $accountId = $event->account;

        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if ($tenant) {
            Log::info('Tenant Connect payout paid', [
                'tenant_id' => $tenant->id,
                'account_id' => $accountId,
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'currency' => $payout->currency,
            ]);
        }
    }

    /**
     * Handle payout.failed event.
     */
    protected function handlePayoutFailed(Event $event): void
    {
        $payout = $event->data->object;
        $accountId = $event->account;

        $tenant = Tenant::where('stripe_connect_account_id', $accountId)->first();

        if ($tenant) {
            Log::error('Tenant Connect payout failed', [
                'tenant_id' => $tenant->id,
                'account_id' => $accountId,
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'failure_code' => $payout->failure_code ?? null,
                'failure_message' => $payout->failure_message ?? null,
            ]);

            // TODO: Notify tenant admin about failed payout
        }
    }

    /**
     * Handle payout.created event.
     */
    protected function handlePayoutCreated(Event $event): void
    {
        $payout = $event->data->object;
        $accountId = $event->account;

        Log::info('Tenant Connect payout created', [
            'account_id' => $accountId,
            'payout_id' => $payout->id,
            'amount' => $payout->amount,
            'arrival_date' => date('Y-m-d', $payout->arrival_date),
        ]);
    }
}
