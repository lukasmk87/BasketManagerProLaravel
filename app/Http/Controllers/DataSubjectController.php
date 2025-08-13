<?php

namespace App\Http\Controllers;

use App\Models\GdprConsentRecord;
use App\Models\GdprDataSubjectRequest;
use App\Services\GDPRComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class DataSubjectController extends Controller
{
    protected GDPRComplianceService $gdprService;

    public function __construct(GDPRComplianceService $gdprService)
    {
        $this->gdprService = $gdprService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display user's GDPR data dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get user's data overview
        $dataOverview = [
            'personal_data' => $this->getUserPersonalData($user),
            'active_consents' => GdprConsentRecord::where('consentable_type', get_class($user))
                ->where('consentable_id', $user->id)
                ->where('consent_given', true)
                ->whereNull('consent_withdrawn_at')
                ->count(),
            'withdrawn_consents' => GdprConsentRecord::where('consentable_type', get_class($user))
                ->where('consentable_id', $user->id)
                ->whereNotNull('consent_withdrawn_at')
                ->count(),
            'pending_requests' => GdprDataSubjectRequest::where('subject_type', get_class($user))
                ->where('subject_id', $user->id)
                ->where('response_status', 'pending')
                ->count(),
        ];

        // Get recent requests
        $recentRequests = GdprDataSubjectRequest::where('subject_type', get_class($user))
            ->where('subject_id', $user->id)
            ->orderBy('received_at', 'desc')
            ->limit(5)
            ->get();

        // Get consent renewal notifications
        $renewalNotifications = $this->gdprService->checkConsentRenewal($user);

        return Inertia::render('DataSubject/Dashboard', [
            'dataOverview' => $dataOverview,
            'recentRequests' => $recentRequests,
            'renewalNotifications' => $renewalNotifications,
        ]);
    }

    /**
     * Display user's consent management
     */
    public function consents()
    {
        $user = Auth::user();

        $consents = GdprConsentRecord::where('consentable_type', get_class($user))
            ->where('consentable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('consent_type');

        $consentTypes = [
            'marketing' => [
                'title' => 'Marketing Communications',
                'description' => 'Receive newsletters, promotional emails, and marketing materials',
                'required' => false,
            ],
            'analytics' => [
                'title' => 'Analytics & Performance',
                'description' => 'Help us improve our services by analyzing usage patterns',
                'required' => false,
            ],
            'emergency_contact' => [
                'title' => 'Emergency Contact Information',
                'description' => 'Store and use emergency contact information for safety purposes',
                'required' => true,
                'legal_basis' => 'Vital interests and legitimate interests',
            ],
            'photo_video' => [
                'title' => 'Photos & Videos',
                'description' => 'Use photos and videos for team documentation and promotional materials',
                'required' => false,
            ],
        ];

        return Inertia::render('DataSubject/Consents', [
            'consents' => $consents,
            'consentTypes' => $consentTypes,
        ]);
    }

    /**
     * Update consent status
     */
    public function updateConsent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consent_type' => 'required|string|in:marketing,analytics,photo_video,emergency_contact',
            'consent_given' => 'required|boolean',
            'consent_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = Auth::user();

        try {
            // Record the new consent
            $consentData = [
                'type' => $request->consent_type,
                'text' => $this->getConsentText($request->consent_type),
                'version' => $request->consent_version ?? '1.0',
                'given' => $request->consent_given,
                'method' => 'user_portal',
                'purposes' => $this->getConsentPurposes($request->consent_type),
                'data_categories' => $this->getConsentDataCategories($request->consent_type),
                'legal_basis' => $request->consent_given ? 'consent' : 'withdrawal',
            ];

            $consent = $this->gdprService->recordConsent($user, $user, $consentData);

            $action = $request->consent_given ? 'granted' : 'withdrawn';
            
            return back()->with('success', "Consent for {$request->consent_type} has been {$action}");

        } catch (\Exception $e) {
            Log::error("Error updating user consent", [
                'user_id' => $user->id,
                'consent_type' => $request->consent_type,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while updating your consent']);
        }
    }

    /**
     * Display user's data subject requests
     */
    public function requests()
    {
        $user = Auth::user();

        $requests = GdprDataSubjectRequest::where('subject_type', get_class($user))
            ->where('subject_id', $user->id)
            ->orderBy('received_at', 'desc')
            ->get();

        return Inertia::render('DataSubject/Requests', [
            'requests' => $requests,
            'canCreateNewRequest' => $this->canCreateNewRequest($user),
        ]);
    }

    /**
     * Show form for creating a new data subject request
     */
    public function createRequest()
    {
        $user = Auth::user();

        if (!$this->canCreateNewRequest($user)) {
            return redirect()->route('data-subject.requests')
                ->withErrors(['error' => 'You have pending requests. Please wait for them to be processed before creating new ones.']);
        }

        $requestTypes = [
            'data_export' => [
                'title' => 'Request My Data',
                'description' => 'Get a copy of all personal data we have about you',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 15',
            ],
            'data_portability' => [
                'title' => 'Data Portability',
                'description' => 'Get your data in a format that can be transferred to another service',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 20',
            ],
            'data_rectification' => [
                'title' => 'Correct My Data',
                'description' => 'Request corrections to inaccurate or incomplete personal data',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 16',
            ],
            'data_erasure' => [
                'title' => 'Delete My Data',
                'description' => 'Request deletion of your personal data (Right to be Forgotten)',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 17',
            ],
            'processing_restriction' => [
                'title' => 'Restrict Processing',
                'description' => 'Limit how we process your personal data',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 18',
            ],
            'objection_to_processing' => [
                'title' => 'Object to Processing',
                'description' => 'Object to certain types of data processing',
                'processing_time' => 'Up to 30 days',
                'article' => 'GDPR Article 21',
            ],
        ];

        return Inertia::render('DataSubject/CreateRequest', [
            'requestTypes' => $requestTypes,
        ]);
    }

    /**
     * Store a new data subject request
     */
    public function storeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|string|in:data_export,data_portability,data_rectification,data_erasure,processing_restriction,objection_to_processing',
            'request_description' => 'required|string|max:1000',
            'specific_data' => 'nullable|array',
            'urgent' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        if (!$this->canCreateNewRequest($user)) {
            return back()->withErrors(['error' => 'You have pending requests. Please wait for them to be processed.']);
        }

        try {
            $gdprRequest = GdprDataSubjectRequest::create([
                'request_id' => 'USR-' . strtoupper(uniqid()),
                'subject_type' => get_class($user),
                'subject_id' => $user->id,
                'requested_by_user_id' => $user->id,
                'request_type' => $request->request_type,
                'request_description' => $request->request_description,
                'specific_data_requested' => $request->specific_data ? json_encode($request->specific_data) : null,
                'received_at' => Carbon::now(),
                'deadline_date' => Carbon::now()->addDays($request->urgent ? 15 : 30),
                'response_status' => 'pending',
                'identity_verified' => true, // User is already authenticated
                'identity_verification_method' => 'authenticated_session',
                'identity_verification_date' => Carbon::now(),
                'priority' => $request->urgent ? 'high' : 'normal',
                'submission_ip' => $request->ip(),
                'submission_user_agent' => $request->userAgent(),
            ]);

            Log::info("User GDPR request created", [
                'request_id' => $gdprRequest->request_id,
                'request_type' => $request->request_type,
                'user_id' => $user->id,
            ]);

            return redirect()->route('data-subject.requests')
                ->with('success', "Your request has been submitted successfully. Reference ID: {$gdprRequest->request_id}");

        } catch (\Exception $e) {
            Log::error("Error creating user GDPR request", [
                'user_id' => $user->id,
                'request_type' => $request->request_type,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while submitting your request. Please try again.']);
        }
    }

    /**
     * Show specific request details
     */
    public function showRequest(GdprDataSubjectRequest $request)
    {
        $user = Auth::user();

        // Ensure user can only view their own requests
        if ($request->subject_type !== get_class($user) || $request->subject_id !== $user->id) {
            abort(403, 'Access denied');
        }

        return Inertia::render('DataSubject/ShowRequest', [
            'request' => $request,
            'canDownload' => $request->response_status === 'completed' && 
                           in_array($request->request_type, ['data_export', 'data_portability']),
        ]);
    }

    /**
     * Download export file for user's own request
     */
    public function downloadExport(GdprDataSubjectRequest $gdprRequest)
    {
        $user = Auth::user();

        // Ensure user can only download their own exports
        if ($gdprRequest->subject_type !== get_class($user) || $gdprRequest->subject_id !== $user->id) {
            abort(403, 'Access denied');
        }

        if ($gdprRequest->response_status !== 'completed') {
            abort(404, 'Export not available');
        }

        $processingNotes = json_decode($gdprRequest->processing_notes, true);
        $exportFile = $processingNotes['export_file'] ?? null;

        if (!$exportFile || !Storage::disk('private')->exists($exportFile)) {
            abort(404, 'Export file not found or expired');
        }

        // Check expiration (30 days)
        $expirationDate = $gdprRequest->completed_at->addDays(30);
        if (Carbon::now()->isAfter($expirationDate)) {
            abort(404, 'Export file has expired');
        }

        // Log download
        Log::info("User downloaded GDPR export", [
            'request_id' => $gdprRequest->request_id,
            'user_id' => $user->id,
        ]);

        return Storage::disk('private')->download($exportFile, 
            "my_data_export_{$gdprRequest->request_id}.zip");
    }

    /**
     * Display user's privacy settings
     */
    public function privacySettings()
    {
        $user = Auth::user();

        $privacySettings = [
            'data_retention' => [
                'account_data' => 'Retained until account deletion',
                'activity_logs' => 'Retained for 2 years',
                'emergency_contacts' => 'Retained until player reaches majority + 5 years',
            ],
            'data_sharing' => [
                'internal_teams' => true,
                'emergency_services' => true,
                'third_party_analytics' => false,
            ],
            'communication_preferences' => [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'marketing_emails' => false,
            ],
        ];

        return Inertia::render('DataSubject/PrivacySettings', [
            'privacySettings' => $privacySettings,
            'lastUpdated' => $user->updated_at,
        ]);
    }

    /**
     * Get user's personal data summary
     */
    protected function getUserPersonalData($user): array
    {
        $data = [
            'basic_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? 'Not provided',
                'created_at' => $user->created_at->format('Y-m-d H:i'),
                'last_login' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never',
            ],
        ];

        // Add player data if user is a player
        if ($user->player) {
            $data['player_info'] = [
                'jersey_number' => $user->player->jersey_number,
                'position' => $user->player->position,
                'team' => $user->player->team->name ?? 'No team',
                'birth_date' => $user->player->birth_date,
            ];
        }

        // Add emergency contacts if any
        $emergencyContacts = $user->emergencyContacts ?? collect();
        if ($emergencyContacts->isNotEmpty()) {
            $data['emergency_contacts'] = $emergencyContacts->map(function ($contact) {
                return [
                    'name' => $contact->name,
                    'relationship' => $contact->relationship,
                    'phone' => $contact->phone,
                ];
            })->toArray();
        }

        return $data;
    }

    /**
     * Check if user can create a new request
     */
    protected function canCreateNewRequest($user): bool
    {
        $pendingRequests = GdprDataSubjectRequest::where('subject_type', get_class($user))
            ->where('subject_id', $user->id)
            ->whereIn('response_status', ['pending', 'in_progress', 'pending_verification'])
            ->count();

        return $pendingRequests === 0;
    }

    /**
     * Get consent text for different types
     */
    protected function getConsentText(string $consentType): string
    {
        $consentTexts = [
            'marketing' => 'I consent to receive marketing communications, newsletters, and promotional materials from BasketManager Pro.',
            'analytics' => 'I consent to the collection and analysis of my usage data to help improve the service.',
            'photo_video' => 'I consent to the use of photos and videos featuring me for team documentation and promotional purposes.',
            'emergency_contact' => 'I consent to the storage and use of emergency contact information for safety and emergency response purposes.',
        ];

        return $consentTexts[$consentType] ?? 'General consent for data processing';
    }

    /**
     * Get processing purposes for consent type
     */
    protected function getConsentPurposes(string $consentType): array
    {
        $purposes = [
            'marketing' => ['email_marketing', 'promotional_communications', 'newsletter'],
            'analytics' => ['usage_analytics', 'performance_improvement', 'user_behavior_analysis'],
            'photo_video' => ['team_documentation', 'promotional_materials', 'website_content'],
            'emergency_contact' => ['emergency_response', 'safety_communications', 'parent_notifications'],
        ];

        return $purposes[$consentType] ?? [];
    }

    /**
     * Get data categories for consent type
     */
    protected function getConsentDataCategories(string $consentType): array
    {
        $categories = [
            'marketing' => ['contact_information', 'preferences', 'communication_history'],
            'analytics' => ['usage_data', 'device_information', 'interaction_patterns'],
            'photo_video' => ['visual_media', 'identification_data', 'event_participation'],
            'emergency_contact' => ['contact_details', 'relationship_information', 'medical_notes'],
        ];

        return $categories[$consentType] ?? [];
    }
}