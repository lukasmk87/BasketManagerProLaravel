<?php

use App\Http\Controllers\GDPRController;
use App\Http\Controllers\DataSubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GDPR Routes
|--------------------------------------------------------------------------
|
| Routes for GDPR compliance features including admin management,
| data subject requests, consent management, and public access forms.
|
*/

// Public GDPR routes (no authentication required)
Route::prefix('gdpr/public')->name('gdpr.public.')->group(function () {
    // Public data subject request form
    Route::get('/request', [GDPRController::class, 'showPublicRequestForm'])
        ->name('request.form');
    
    Route::post('/request', [GDPRController::class, 'submitPublicRequest'])
        ->middleware(['throttle:5,60']) // Limit to 5 requests per hour
        ->name('request.submit');
});

// Admin GDPR Management Routes (requires manage-gdpr permission)
Route::prefix('admin/gdpr')->name('gdpr.admin.')->middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard and overview
    Route::get('/dashboard', [GDPRController::class, 'dashboard'])
        ->name('dashboard');

    // Data Subject Requests Management
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [GDPRController::class, 'requests'])
            ->name('index');
        
        Route::get('/{request}', [GDPRController::class, 'showRequest'])
            ->name('show');
        
        Route::post('/{request}/process', [GDPRController::class, 'processRequest'])
            ->name('process');
        
        Route::post('/{request}/verify-identity', [GDPRController::class, 'verifyIdentity'])
            ->name('verify-identity');
        
        Route::get('/{request}/download-export', [GDPRController::class, 'downloadExport'])
            ->name('download-export');
    });

    // Consent Management
    Route::prefix('consents')->name('consents.')->group(function () {
        Route::get('/', [GDPRController::class, 'consents'])
            ->name('index');
        
        Route::post('/bulk-renewal', [GDPRController::class, 'renewConsents'])
            ->name('bulk-renewal');
    });

    // Data Processing Records
    Route::get('/processing-records', [GDPRController::class, 'processingRecords'])
        ->name('processing-records');

    // Compliance Reporting
    Route::post('/generate-report', [GDPRController::class, 'generateReport'])
        ->name('generate-report');
});

// User Data Subject Rights Routes (authenticated users managing their own data)
Route::prefix('my-data')->name('data-subject.')->middleware(['auth', 'verified'])->group(function () {
    
    // Personal data dashboard
    Route::get('/dashboard', [DataSubjectController::class, 'dashboard'])
        ->name('dashboard');

    // Consent management
    Route::get('/consents', [DataSubjectController::class, 'consents'])
        ->name('consents');
    
    Route::post('/consents/update', [DataSubjectController::class, 'updateConsent'])
        ->name('consents.update');

    // Data subject requests
    Route::get('/requests', [DataSubjectController::class, 'requests'])
        ->name('requests');
    
    Route::get('/requests/create', [DataSubjectController::class, 'createRequest'])
        ->name('requests.create');
    
    Route::post('/requests', [DataSubjectController::class, 'storeRequest'])
        ->middleware(['throttle:3,1440']) // Limit to 3 requests per day
        ->name('requests.store');
    
    Route::get('/requests/{request}', [DataSubjectController::class, 'showRequest'])
        ->name('requests.show');
    
    Route::get('/requests/{request}/download', [DataSubjectController::class, 'downloadExport'])
        ->name('requests.download');

    // Privacy settings
    Route::get('/privacy-settings', [DataSubjectController::class, 'privacySettings'])
        ->name('privacy-settings');
});

// API Routes for GDPR functionality
Route::prefix('api/gdpr')->name('api.gdpr.')->middleware(['auth:sanctum'])->group(function () {
    
    // User consent API endpoints
    Route::prefix('consents')->name('consents.')->group(function () {
        Route::get('/mine', function () {
            $user = auth()->user();
            return response()->json([
                'consents' => \App\Models\GdprConsentRecord::where('consentable_type', get_class($user))
                    ->where('consentable_id', $user->id)
                    ->where('consent_given', true)
                    ->whereNull('consent_withdrawn_at')
                    ->get(['consent_type', 'consent_given_at', 'processing_purposes'])
            ]);
        })->name('mine');
        
        Route::post('/update', [DataSubjectController::class, 'updateConsent'])
            ->name('update');
    });

    // Data export API
    Route::get('/my-data/export', function () {
        $user = auth()->user();
        $gdprService = app(\App\Services\GDPRComplianceService::class);
        
        // Create a temporary request for API export
        $tempRequest = new \App\Models\GdprDataSubjectRequest([
            'request_id' => 'API-' . time(),
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'request_type' => 'data_export',
            'identity_verified' => true,
        ]);
        $tempRequest->subject = $user;
        
        $exportData = $gdprService->handleDataExportRequest($tempRequest);
        
        return response()->json([
            'data' => $exportData,
            'export_date' => now()->toISOString(),
            'rights_info' => [
                'right_to_rectification' => 'You can request corrections to your data',
                'right_to_erasure' => 'You can request deletion of your data',
                'right_to_portability' => 'You can request your data in a portable format',
            ]
        ]);
    })->middleware(['throttle:1,1440'])->name('export'); // Once per day
});

// Webhook routes for external GDPR integrations
Route::prefix('webhooks/gdpr')->name('webhooks.gdpr.')->group(function () {
    
    // Webhook for consent management platforms
    Route::post('/consent-update', function (\Illuminate\Http\Request $request) {
        // Validate webhook signature
        $signature = $request->header('X-GDPR-Signature');
        $expectedSignature = hash_hmac('sha256', $request->getContent(), config('gdpr.webhook_secret'));
        
        if (!hash_equals($signature, $expectedSignature)) {
            abort(403, 'Invalid signature');
        }
        
        // Process consent update
        $data = $request->json()->all();
        
        \Illuminate\Support\Facades\Log::info('GDPR consent webhook received', [
            'user_id' => $data['user_id'] ?? null,
            'consent_type' => $data['consent_type'] ?? null,
            'action' => $data['action'] ?? null,
        ]);
        
        return response()->json(['status' => 'processed']);
    })->middleware(['throttle:60,1'])->name('consent-update');
    
    // Webhook for automated data erasure confirmations
    Route::post('/erasure-confirmation', function (\Illuminate\Http\Request $request) {
        $signature = $request->header('X-GDPR-Signature');
        $expectedSignature = hash_hmac('sha256', $request->getContent(), config('gdpr.webhook_secret'));
        
        if (!hash_equals($signature, $expectedSignature)) {
            abort(403, 'Invalid signature');
        }
        
        $data = $request->json()->all();
        
        // Update request status
        if ($requestId = $data['request_id'] ?? null) {
            $gdprRequest = \App\Models\GdprDataSubjectRequest::where('request_id', $requestId)->first();
            if ($gdprRequest) {
                $gdprRequest->update([
                    'external_processing_status' => $data['status'],
                    'external_processing_notes' => json_encode($data['notes'] ?? []),
                ]);
            }
        }
        
        \Illuminate\Support\Facades\Log::info('GDPR erasure webhook received', [
            'request_id' => $data['request_id'] ?? null,
            'status' => $data['status'] ?? null,
        ]);
        
        return response()->json(['status' => 'processed']);
    })->middleware(['throttle:60,1'])->name('erasure-confirmation');
});

// Health check endpoint for GDPR system
Route::get('/gdpr/health', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'checks' => [
            'database' => 'ok',
            'storage' => 'ok',
            'compliance_service' => 'ok',
        ],
        'statistics' => [
            'pending_requests' => \App\Models\GdprDataSubjectRequest::where('response_status', 'pending')->count(),
            'overdue_requests' => \App\Models\GdprDataSubjectRequest::where('deadline_date', '<', now())
                ->where('response_status', '!=', 'completed')->count(),
            'active_consents' => \App\Models\GdprConsentRecord::where('consent_given', true)
                ->whereNull('consent_withdrawn_at')->count(),
        ]
    ];
    
    // Check for critical issues
    if ($health['statistics']['overdue_requests'] > 0) {
        $health['status'] = 'warning';
        $health['issues'][] = "There are {$health['statistics']['overdue_requests']} overdue GDPR requests";
    }
    
    // Check storage accessibility
    try {
        \Illuminate\Support\Facades\Storage::disk('private')->exists('test');
        $health['checks']['storage'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['storage'] = 'error';
        $health['status'] = 'error';
        $health['issues'][] = 'Storage access error: ' . $e->getMessage();
    }
    
    $statusCode = $health['status'] === 'error' ? 500 : ($health['status'] === 'warning' ? 200 : 200);
    
    return response()->json($health, $statusCode);
})->name('gdpr.health');