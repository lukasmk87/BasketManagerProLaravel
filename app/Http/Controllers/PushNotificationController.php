<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotification;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Push Notification Controller
 * 
 * Handles push notification management, sending, and subscription management
 * for basketball-specific notifications
 */
class PushNotificationController extends Controller
{
    private PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Send game start notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendGameStart(Request $request)
    {
        $request->validate([
            'game_id' => 'required|uuid',
            'home_team' => 'required|string|max:100',
            'away_team' => 'required|string|max:100',
            'start_time' => 'required|date',
            'location' => 'nullable|string|max:200',
            'recipients' => 'array',
            'recipients.*' => 'uuid'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Get recipients or default to game participants
        $recipients = $request->input('recipients', []);
        
        if (empty($recipients)) {
            // Default logic to get game participants would go here
            $recipients = [$user->id]; // Placeholder
        }

        // Queue the notification job
        SendPushNotification::dispatch(
            'game_start',
            $request->all(),
            $recipients,
            $tenantId
        );

        Log::info('Game start notification queued', [
            'game_id' => $request->input('game_id'),
            'tenant_id' => $tenantId,
            'recipient_count' => count($recipients)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Game start notification queued for delivery',
            'recipients' => count($recipients)
        ]);
    }

    /**
     * Send player foul notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPlayerFoul(Request $request)
    {
        $request->validate([
            'player_id' => 'required|uuid',
            'player_name' => 'required|string|max:100',
            'game_id' => 'required|uuid',
            'foul_count' => 'required|integer|min:1|max:5',
            'foul_type' => 'required|string|max:50',
            'quarter' => 'nullable|string|max:10'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Get team coaches and management
        $recipients = [$user->id]; // Placeholder for actual team management logic

        SendPushNotification::dispatch(
            'player_foul',
            $request->all(),
            $recipients,
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Player foul notification queued for delivery'
        ]);
    }

    /**
     * Send training reminder
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTrainingReminder(Request $request)
    {
        $request->validate([
            'training_id' => 'required|uuid',
            'name' => 'required|string|max:200',
            'start_time' => 'required|date',
            'location' => 'nullable|string|max:200',
            'minutes_until' => 'required|integer|min:1|max:1440',
            'team_id' => 'nullable|uuid'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Get training participants
        $recipients = [$user->id]; // Placeholder for actual training participants logic

        SendPushNotification::dispatch(
            'training_reminder',
            $request->all(),
            $recipients,
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Training reminder queued for delivery'
        ]);
    }

    /**
     * Send emergency notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmergency(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'type' => 'required|string|in:medical,security,weather,general',
            'location' => 'nullable|string|max:200',
            'contact' => 'nullable|string|max:100',
            'severity' => 'required|string|in:low,medium,high,critical',
            'recipients' => 'array',
            'recipients.*' => 'uuid'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // For emergency notifications, default to all users in tenant if no specific recipients
        $recipients = $request->input('recipients');
        
        if (empty($recipients)) {
            // Get all active users in tenant
            $recipients = \App\Models\User::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        // Emergency notifications are sent immediately (high priority queue)
        SendPushNotification::dispatch(
            'emergency',
            $request->all(),
            $recipients,
            $tenantId
        )->onQueue('high');

        Log::warning('Emergency notification sent', [
            'type' => $request->input('type'),
            'severity' => $request->input('severity'),
            'tenant_id' => $tenantId,
            'recipient_count' => count($recipients),
            'sender_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Emergency notification sent immediately',
            'recipients' => count($recipients)
        ]);
    }

    /**
     * Send score update notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendScoreUpdate(Request $request)
    {
        $request->validate([
            'game_id' => 'required|uuid',
            'home_team' => 'required|string|max:100',
            'away_team' => 'required|string|max:100',
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'quarter' => 'required|string|max:10',
            'time_remaining' => 'nullable|string|max:10'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Get game followers
        $recipients = [$user->id]; // Placeholder for actual game followers logic

        SendPushNotification::dispatch(
            'score_update',
            $request->all(),
            $recipients,
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Score update notification queued for delivery'
        ]);
    }

    /**
     * Send federation sync notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFederationSync(Request $request)
    {
        $request->validate([
            'federation_type' => 'required|string|in:dbb,fiba',
            'sync_type' => 'required|string|in:player,team,game,competition',
            'message' => 'required|string|max:300',
            'details' => 'nullable|array'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Send to federation administrators
        $recipients = \App\Models\User::where('tenant_id', $tenantId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['tenant_admin', 'club_admin', 'federation_admin']);
            })
            ->pluck('id')
            ->toArray();

        SendPushNotification::dispatch(
            'federation_sync',
            $request->all(),
            $recipients,
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Federation sync notification queued for delivery',
            'recipients' => count($recipients)
        ]);
    }

    /**
     * Send custom notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCustom(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:300',
            'type' => 'nullable|string|max:50',
            'url' => 'nullable|url',
            'icon' => 'nullable|url',
            'data' => 'nullable|array',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'uuid'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $notificationData = [
            'notification' => [
                'type' => $request->input('type', 'custom'),
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'icon' => $request->input('icon', '/images/logo-192.png'),
                'data' => array_merge($request->input('data', []), [
                    'url' => $request->input('url'),
                    'custom' => true,
                    'sender_id' => $user->id
                ])
            ]
        ];

        SendPushNotification::dispatch(
            'custom',
            $notificationData,
            $request->input('recipients'),
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Custom notification queued for delivery',
            'recipients' => count($request->input('recipients'))
        ]);
    }

    /**
     * Send test notification to current user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTest(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        SendPushNotification::dispatch(
            'test',
            ['message' => 'Test notification from BasketManager Pro'],
            [$user->id],
            $tenantId
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification queued for delivery'
        ]);
    }

    /**
     * Get user's push subscriptions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptions(Request $request)
    {
        $user = $request->user();
        
        $subscriptions = $user->pushSubscriptions()
            ->active()
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'display_name' => $subscription->display_name,
                    'browser_info' => $subscription->getBrowserInfo(),
                    'created_at' => $subscription->created_at,
                    'last_used_at' => $subscription->last_used_at,
                    'is_likely_valid' => $subscription->isLikelyValid()
                ];
            });

        return response()->json([
            'subscriptions' => $subscriptions,
            'count' => $subscriptions->count()
        ]);
    }

    /**
     * Delete a push subscription
     *
     * @param Request $request
     * @param string $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubscription(Request $request, string $subscriptionId)
    {
        $user = $request->user();
        
        $subscription = $user->pushSubscriptions()
            ->where('id', $subscriptionId)
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $subscription->delete();

        Log::info('Push subscription deleted', [
            'subscription_id' => $subscriptionId,
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription deleted successfully'
        ]);
    }

    /**
     * Get notification statistics for tenant
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        $user = $request->user();
        $tenantId = $user->tenant_id;
        $days = $request->input('days', 30);

        // Check if user has permission to view stats
        if (!$user->hasAnyRole(['tenant_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $stats = $this->pushService->getNotificationStats($tenantId, $days);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'period' => "{$days} days"
        ]);
    }

    /**
     * Check VAPID configuration
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkConfig(Request $request)
    {
        $user = $request->user();

        // Only admins can check config
        if (!$user->hasAnyRole(['tenant_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $isConfigValid = $this->pushService->validateVapidConfig();

        return response()->json([
            'success' => true,
            'vapid_configured' => $isConfigValid,
            'push_notifications_available' => $isConfigValid
        ]);
    }

    /**
     * Cleanup old subscriptions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:30|max:365'
        ]);

        $user = $request->user();

        // Only admins can cleanup subscriptions
        if (!$user->hasRole('tenant_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions'
            ], 403);
        }

        $days = $request->input('days', 90);
        $cleanedCount = PushSubscription::cleanup($days);

        Log::info('Push subscriptions cleaned up', [
            'cleaned_count' => $cleanedCount,
            'days_threshold' => $days,
            'admin_user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => "Cleaned up {$cleanedCount} old subscriptions",
            'cleaned_count' => $cleanedCount
        ]);
    }
}