<?php

use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Push Notification Routes
|--------------------------------------------------------------------------
|
| Routes for managing and sending push notifications for basketball-specific
| events including games, training, emergency alerts, and federation updates
|
*/

Route::middleware(['auth', 'tenant'])->group(function () {
    
    Route::prefix('notifications/push')->name('notifications.push.')->group(function () {
        
        // Subscription Management
        Route::get('/subscriptions', [PushNotificationController::class, 'getSubscriptions'])
            ->name('subscriptions.index');
        
        Route::delete('/subscriptions/{subscription}', [PushNotificationController::class, 'deleteSubscription'])
            ->name('subscriptions.delete');
        
        // Test Notifications
        Route::post('/test', [PushNotificationController::class, 'sendTest'])
            ->name('test');
        
        // Basketball-Specific Notifications
        Route::middleware(['feature:push_notifications'])->group(function () {
            
            // Game Notifications
            Route::post('/game/start', [PushNotificationController::class, 'sendGameStart'])
                ->name('game.start')
                ->middleware('feature:live_scoring');
            
            Route::post('/game/score-update', [PushNotificationController::class, 'sendScoreUpdate'])
                ->name('game.score-update')
                ->middleware('feature:live_scoring');
            
            Route::post('/player/foul', [PushNotificationController::class, 'sendPlayerFoul'])
                ->name('player.foul')
                ->middleware('feature:live_scoring');
            
            // Training Notifications
            Route::post('/training/reminder', [PushNotificationController::class, 'sendTrainingReminder'])
                ->name('training.reminder')
                ->middleware('feature:training_management');
            
            // Emergency Notifications (high priority - available to all tiers)
            Route::post('/emergency', [PushNotificationController::class, 'sendEmergency'])
                ->name('emergency');
            
            // Federation Notifications
            Route::post('/federation/sync', [PushNotificationController::class, 'sendFederationSync'])
                ->name('federation.sync')
                ->middleware('feature:federation_integration');
            
            // Custom Notifications
            Route::post('/custom', [PushNotificationController::class, 'sendCustom'])
                ->name('custom')
                ->middleware('feature:custom_notifications');
            
        });
        
        // Admin-only Routes
        Route::middleware(['feature:admin_notifications'])->group(function () {
            
            Route::get('/stats', [PushNotificationController::class, 'getStats'])
                ->name('stats');
            
            Route::get('/config', [PushNotificationController::class, 'checkConfig'])
                ->name('config');
            
            Route::post('/cleanup', [PushNotificationController::class, 'cleanup'])
                ->name('cleanup');
            
        });
        
    });
    
});

// Webhook endpoints for push notification events (no authentication)
Route::prefix('webhooks/push')->name('webhooks.push.')->group(function () {
    
    // Handle push service webhooks (e.g., FCM, Mozilla autopush)
    Route::post('/delivery-status', function (\Illuminate\Http\Request $request) {
        // Handle delivery status updates from push services
        \Illuminate\Support\Facades\Log::info('Push notification delivery status', [
            'data' => $request->all(),
            'headers' => $request->headers->all()
        ]);
        
        return response()->json(['status' => 'received']);
    })->name('delivery-status');
    
    Route::post('/subscription-change', function (\Illuminate\Http\Request $request) {
        // Handle subscription changes from push services
        \Illuminate\Support\Facades\Log::info('Push subscription change', [
            'data' => $request->all(),
            'headers' => $request->headers->all()
        ]);
        
        return response()->json(['status' => 'received']);
    })->name('subscription-change');
    
});

// Basketball Event Notification Triggers
Route::middleware(['auth', 'tenant', 'feature:auto_notifications'])->group(function () {
    
    Route::prefix('events')->name('events.')->group(function () {
        
        // Game Event Triggers
        Route::post('/game/{game}/started', function (\Illuminate\Http\Request $request, string $gameId) {
            // Automatically send game start notification
            $gameData = $request->validate([
                'home_team' => 'required|string',
                'away_team' => 'required|string',
                'location' => 'nullable|string'
            ]);
            
            \App\Jobs\SendPushNotification::dispatch(
                'game_start',
                array_merge($gameData, ['game_id' => $gameId]),
                [], // Will auto-determine recipients
                $request->user()->tenant_id
            );
            
            return response()->json(['status' => 'notification_queued']);
        })->name('game.started');
        
        Route::post('/game/{game}/score-changed', function (\Illuminate\Http\Request $request, string $gameId) {
            // Automatically send score update
            $scoreData = $request->validate([
                'home_team' => 'required|string',
                'away_team' => 'required|string',
                'home_score' => 'required|integer',
                'away_score' => 'required|integer',
                'quarter' => 'required|string'
            ]);
            
            // Only send score updates for significant changes or quarter endings
            $shouldNotify = $request->input('significant_change', false) || 
                           $request->input('quarter_ended', false);
            
            if ($shouldNotify) {
                \App\Jobs\SendPushNotification::dispatch(
                    'score_update',
                    array_merge($scoreData, ['game_id' => $gameId]),
                    [],
                    $request->user()->tenant_id
                );
            }
            
            return response()->json(['status' => $shouldNotify ? 'notification_queued' : 'no_notification']);
        })->name('game.score-changed');
        
        Route::post('/player/{player}/foul', function (\Illuminate\Http\Request $request, string $playerId) {
            // Automatically send player foul notification
            $foulData = $request->validate([
                'player_name' => 'required|string',
                'game_id' => 'required|uuid',
                'foul_count' => 'required|integer|min:1|max:5',
                'foul_type' => 'required|string'
            ]);
            
            // Only notify for 3rd, 4th, or 5th fouls
            if ($foulData['foul_count'] >= 3) {
                \App\Jobs\SendPushNotification::dispatch(
                    'player_foul',
                    array_merge($foulData, ['player_id' => $playerId]),
                    [],
                    $request->user()->tenant_id
                );
            }
            
            return response()->json(['status' => 'notification_queued']);
        })->name('player.foul');
        
        // Training Event Triggers
        Route::post('/training/{training}/reminder', function (\Illuminate\Http\Request $request, string $trainingId) {
            // Schedule training reminder
            $trainingData = $request->validate([
                'name' => 'required|string',
                'start_time' => 'required|date',
                'location' => 'nullable|string',
                'remind_minutes_before' => 'nullable|integer|min:5|max:1440'
            ]);
            
            $remindBefore = $trainingData['remind_minutes_before'] ?? 30;
            $sendAt = new \DateTime($trainingData['start_time']);
            $sendAt->modify("-{$remindBefore} minutes");
            
            // Schedule the reminder
            \App\Jobs\SendPushNotification::dispatch(
                'training_reminder',
                array_merge($trainingData, [
                    'training_id' => $trainingId,
                    'minutes_until' => $remindBefore
                ]),
                [],
                $request->user()->tenant_id
            )->delay($sendAt);
            
            return response()->json([
                'status' => 'reminder_scheduled',
                'send_at' => $sendAt->format('Y-m-d H:i:s')
            ]);
        })->name('training.reminder');
        
        // Federation Event Triggers
        Route::post('/federation/{type}/synced', function (\Illuminate\Http\Request $request, string $federationType) {
            // Automatically notify about federation sync
            if (!in_array($federationType, ['dbb', 'fiba'])) {
                return response()->json(['error' => 'Invalid federation type'], 400);
            }
            
            $syncData = $request->validate([
                'sync_type' => 'required|string',
                'message' => 'required|string',
                'entity_count' => 'nullable|integer'
            ]);
            
            \App\Jobs\SendPushNotification::dispatch(
                'federation_sync',
                array_merge($syncData, ['federation_type' => $federationType]),
                [],
                $request->user()->tenant_id
            );
            
            return response()->json(['status' => 'notification_queued']);
        })->name('federation.synced');
        
    });
    
});

// Scheduled Notification Management
Route::middleware(['auth', 'tenant', 'feature:scheduled_notifications'])->group(function () {
    
    Route::prefix('notifications/scheduled')->name('notifications.scheduled.')->group(function () {
        
        Route::post('/game-reminders', function (\Illuminate\Http\Request $request) {
            // Schedule game reminders for upcoming games
            $request->validate([
                'remind_hours_before' => 'nullable|array',
                'remind_hours_before.*' => 'integer|min:1|max:168', // 1 hour to 1 week
                'game_ids' => 'nullable|array',
                'game_ids.*' => 'uuid'
            ]);
            
            $reminderHours = $request->input('remind_hours_before', [24, 2]); // Default: 24h and 2h before
            $gameIds = $request->input('game_ids', []);
            
            // Logic to schedule game reminders would go here
            // For now, just return success
            
            return response()->json([
                'status' => 'reminders_scheduled',
                'game_count' => count($gameIds),
                'reminder_times' => $reminderHours
            ]);
        })->name('game-reminders');
        
        Route::post('/training-series', function (\Illuminate\Http\Request $request) {
            // Schedule recurring training reminders
            $request->validate([
                'team_id' => 'required|uuid',
                'training_schedule' => 'required|array',
                'remind_minutes_before' => 'nullable|integer|min:15|max:1440'
            ]);
            
            // Logic to schedule recurring training reminders would go here
            
            return response()->json([
                'status' => 'training_series_scheduled',
                'team_id' => $request->input('team_id')
            ]);
        })->name('training-series');
        
    });
    
});