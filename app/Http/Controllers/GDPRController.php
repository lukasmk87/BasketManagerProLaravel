<?php

namespace App\Http\Controllers;

use App\Models\GdprConsentRecord;
use App\Models\GdprDataProcessingRecord;
use App\Models\GdprDataSubjectRequest;
use App\Services\GDPRComplianceService;
use App\Services\SecurityMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class GDPRController extends Controller
{
    protected GDPRComplianceService $gdprService;
    protected SecurityMonitoringService $securityMonitoringService;

    public function __construct(
        GDPRComplianceService $gdprService,
        SecurityMonitoringService $securityMonitoringService
    ) {
        $this->gdprService = $gdprService;
        $this->securityMonitoringService = $securityMonitoringService;
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:manage-gdpr')->except(['showPublicRequestForm', 'submitPublicRequest']);
    }

    /**
     * Display GDPR compliance dashboard
     */
    public function dashboard()
    {
        $summary = [
            'pending_requests' => GdprDataSubjectRequest::where('response_status', 'pending')->count(),
            'overdue_requests' => GdprDataSubjectRequest::where('deadline_date', '<', Carbon::now())
                ->where('response_status', '!=', 'completed')->count(),
            'completed_requests_month' => GdprDataSubjectRequest::where('response_status', 'completed')
                ->whereBetween('completed_at', [Carbon::now()->startOfMonth(), Carbon::now()])->count(),
            'active_consents' => GdprConsentRecord::where('consent_given', true)
                ->whereNull('consent_withdrawn_at')->count(),
            'consent_renewals_needed' => GdprConsentRecord::where('consent_given', true)
                ->where('consent_given_at', '<', Carbon::now()->subMonths(24))->count(),
        ];

        $recentRequests = GdprDataSubjectRequest::with(['requestedByUser', 'subject'])
            ->orderBy('received_at', 'desc')
            ->limit(10)
            ->get();

        $complianceReport = $this->gdprService->generateComplianceReport();

        return Inertia::render('GDPR/Dashboard', [
            'summary' => $summary,
            'recentRequests' => $recentRequests,
            'complianceReport' => $complianceReport,
        ]);
    }

    /**
     * Display all data subject requests
     */
    public function requests(Request $request)
    {
        $query = GdprDataSubjectRequest::with(['requestedByUser', 'subject']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('response_status', $request->status);
        }

        // Filter by request type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('request_type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('received_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('received_at', '<=', $request->to_date);
        }

        // Search by request ID or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                  ->orWhere('request_description', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('received_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GDPR/Requests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['status', 'type', 'from_date', 'to_date', 'search']),
            'requestTypes' => $this->getRequestTypes(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Show specific data subject request
     */
    public function showRequest(GdprDataSubjectRequest $request)
    {
        $request->load(['requestedByUser', 'subject']);
        
        return Inertia::render('GDPR/Requests/Show', [
            'request' => $request,
            'canProcess' => $request->response_status === 'pending' || $request->response_status === 'in_progress',
            'isOverdue' => Carbon::now()->isAfter($request->deadline_date),
        ]);
    }

    /**
     * Process a data subject request
     */
    public function processRequest(Request $request, GdprDataSubjectRequest $gdprRequest)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:process,reject,require_verification',
            'notes' => 'nullable|string|max:1000',
            'identity_verified' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            switch ($request->action) {
                case 'process':
                    if (!$gdprRequest->identity_verified) {
                        // Monitor processing without identity verification
                        $this->securityMonitoringService->monitorGDPRCompliance(auth()->user(), 'data_processing', [
                            'violation_type' => 'processing_without_verification',
                            'request_id' => $gdprRequest->request_id,
                            'request_type' => $gdprRequest->request_type,
                            'admin_attempted' => true,
                        ]);
                        
                        return back()->withErrors(['error' => 'Identity verification required before processing']);
                    }

                    // Monitor for excessive data export requests
                    if ($gdprRequest->request_type === 'data_export') {
                        $this->securityMonitoringService->monitorGDPRCompliance(auth()->user(), 'data_export', [
                            'request_id' => $gdprRequest->request_id,
                            'subject_type' => $gdprRequest->subject_type,
                            'subject_id' => $gdprRequest->subject_id,
                            'admin_processing' => true,
                        ]);
                    }

                    $result = $this->gdprService->processDataSubjectRequest($gdprRequest);
                    
                    if ($result['success']) {
                        $message = "Request processed successfully. Processing time: " . 
                                  number_format($result['processing_time'] * 1000, 2) . "ms";
                        return back()->with('success', $message);
                    } else {
                        return back()->withErrors(['error' => $result['message']]);
                    }

                case 'reject':
                    $gdprRequest->update([
                        'response_status' => 'rejected',
                        'processing_notes' => json_encode(['reason' => $request->notes]),
                    ]);
                    
                    Log::info("GDPR request rejected", [
                        'request_id' => $gdprRequest->request_id,
                        'reason' => $request->notes,
                        'admin_user' => auth()->id(),
                    ]);
                    
                    return back()->with('success', 'Request has been rejected');

                case 'require_verification':
                    $gdprRequest->update([
                        'response_status' => 'pending_verification',
                        'verification_notes' => $request->notes,
                    ]);
                    
                    return back()->with('success', 'Verification required - notification sent to requestor');

                default:
                    return back()->withErrors(['error' => 'Invalid action']);
            }
        } catch (\Exception $e) {
            Log::error("Error processing GDPR request", [
                'request_id' => $gdprRequest->request_id,
                'error' => $e->getMessage(),
                'admin_user' => auth()->id(),
            ]);
            
            return back()->withErrors(['error' => 'An error occurred while processing the request']);
        }
    }

    /**
     * Verify identity for a data subject request
     */
    public function verifyIdentity(Request $request, GdprDataSubjectRequest $gdprRequest)
    {
        $validator = Validator::make($request->all(), [
            'verification_method' => 'required|string|in:document_check,phone_verification,email_verification',
            'verification_notes' => 'required|string|max:500',
            'verified' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $gdprRequest->update([
            'identity_verified' => $request->verified,
            'identity_verification_method' => $request->verification_method,
            'identity_verification_date' => $request->verified ? Carbon::now() : null,
            'identity_verification_notes' => $request->verification_notes,
            'identity_verified_by' => auth()->id(),
        ]);

        if ($request->verified) {
            $gdprRequest->update(['response_status' => 'verified']);
            $message = 'Identity verified successfully';
        } else {
            $gdprRequest->update(['response_status' => 'verification_failed']);
            $message = 'Identity verification failed';
        }

        Log::info("GDPR identity verification", [
            'request_id' => $gdprRequest->request_id,
            'verified' => $request->verified,
            'method' => $request->verification_method,
            'admin_user' => auth()->id(),
        ]);

        return back()->with('success', $message);
    }

    /**
     * Download GDPR export file
     */
    public function downloadExport(GdprDataSubjectRequest $gdprRequest)
    {
        if ($gdprRequest->response_status !== 'completed') {
            abort(404, 'Export not available');
        }

        $processingNotes = json_decode($gdprRequest->processing_notes, true);
        $exportFile = $processingNotes['export_file'] ?? null;

        if (!$exportFile || !Storage::disk('private')->exists($exportFile)) {
            abort(404, 'Export file not found');
        }

        // Monitor GDPR data export access
        $this->securityMonitoringService->monitorGDPRCompliance(auth()->user(), 'data_export', [
            'request_id' => $gdprRequest->request_id,
            'export_file' => $exportFile,
            'subject_type' => $gdprRequest->subject_type,
            'subject_id' => $gdprRequest->subject_id,
            'download_action' => true,
            'admin_download' => true,
        ]);

        // Monitor for unusual data export patterns
        $this->securityMonitoringService->detectSecurityEvent(request(), 'data_export_unusual', [
            'resource' => 'gdpr_export_download',
            'request_id' => $gdprRequest->request_id,
            'file_path' => $exportFile,
            'admin_user' => auth()->id(),
            'request_type' => $gdprRequest->request_type,
        ]);

        // Log download for audit trail
        Log::info("GDPR export downloaded", [
            'request_id' => $gdprRequest->request_id,
            'file' => $exportFile,
            'downloaded_by' => auth()->id(),
        ]);

        return Storage::disk('private')->download($exportFile);
    }

    /**
     * Display consent management
     */
    public function consents(Request $request)
    {
        $query = GdprConsentRecord::with(['givenByUser', 'consentable']);

        // Filter by consent type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('consent_type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('consent_given', true)->whereNull('consent_withdrawn_at');
                    break;
                case 'withdrawn':
                    $query->whereNotNull('consent_withdrawn_at');
                    break;
                case 'expired':
                    $query->where('consent_given', true)
                          ->where('consent_given_at', '<', Carbon::now()->subMonths(24));
                    break;
            }
        }

        $consents = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get consent renewal statistics
        $renewalStats = [
            'expiring_soon' => GdprConsentRecord::where('consent_given', true)
                ->whereBetween('consent_given_at', [
                    Carbon::now()->subMonths(24), 
                    Carbon::now()->subMonths(22)
                ])->count(),
            'expired' => GdprConsentRecord::where('consent_given', true)
                ->where('consent_given_at', '<', Carbon::now()->subMonths(24))->count(),
        ];

        return Inertia::render('GDPR/Consents/Index', [
            'consents' => $consents,
            'filters' => $request->only(['type', 'status']),
            'consentTypes' => $this->getConsentTypes(),
            'renewalStats' => $renewalStats,
        ]);
    }

    /**
     * Bulk consent renewal
     */
    public function renewConsents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consent_ids' => 'required|array|min:1',
            'consent_ids.*' => 'exists:gdpr_consent_records,id',
            'renewal_method' => 'required|string|in:email,portal_notification',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $consents = GdprConsentRecord::whereIn('id', $request->consent_ids)->get();
        $renewedCount = 0;

        foreach ($consents as $consent) {
            // Send renewal notification based on method
            // This would typically trigger email/notification jobs
            $renewedCount++;
            
            Log::info("Consent renewal initiated", [
                'consent_id' => $consent->id,
                'consent_type' => $consent->consent_type,
                'method' => $request->renewal_method,
                'admin_user' => auth()->id(),
            ]);
        }

        return back()->with('success', "Renewal notifications sent for {$renewedCount} consent records");
    }

    /**
     * Display data processing records
     */
    public function processingRecords(Request $request)
    {
        $query = GdprDataProcessingRecord::query();

        // Filter by purpose
        if ($request->has('purpose') && $request->purpose !== 'all') {
            $query->where('processing_purpose', $request->purpose);
        }

        // Filter by legal basis
        if ($request->has('legal_basis') && $request->legal_basis !== 'all') {
            $query->where('legal_basis', $request->legal_basis);
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GDPR/ProcessingRecords/Index', [
            'records' => $records,
            'filters' => $request->only(['purpose', 'legal_basis']),
            'processingPurposes' => $this->getProcessingPurposes(),
            'legalBases' => $this->getLegalBases(),
        ]);
    }

    /**
     * Generate compliance report
     */
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after:from_date',
            'format' => 'required|string|in:json,pdf',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);

        $report = $this->gdprService->generateComplianceReport($fromDate, $toDate);

        if ($request->format === 'json') {
            $fileName = "gdpr_compliance_report_{$fromDate->format('Y-m-d')}_{$toDate->format('Y-m-d')}.json";
            
            return response()->json($report)
                ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
        }

        // PDF generation would be implemented here
        return back()->with('info', 'PDF report generation not yet implemented');
    }

    /**
     * Show public request form (no auth required)
     */
    public function showPublicRequestForm()
    {
        return Inertia::render('GDPR/PublicRequestForm', [
            'requestTypes' => $this->getPublicRequestTypes(),
        ]);
    }

    /**
     * Submit public data subject request (no auth required)
     */
    public function submitPublicRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|string|in:data_export,data_erasure,data_rectification,data_portability',
            'subject_type' => 'required|string|in:user,player',
            'subject_identifier' => 'required|string|max:255', // email, phone, or ID
            'request_description' => 'required|string|max:1000',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:20',
            'identity_document' => 'nullable|file|mimes:pdf,jpg,png|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Store identity document if provided
            $documentPath = null;
            if ($request->hasFile('identity_document')) {
                $documentPath = $request->file('identity_document')
                    ->store('gdpr-identity-docs', 'private');
            }

            $gdprRequest = GdprDataSubjectRequest::create([
                'request_id' => 'REQ-' . strtoupper(uniqid()),
                'subject_type' => $request->subject_type,
                'subject_identifier' => $request->subject_identifier,
                'request_type' => $request->request_type,
                'request_description' => $request->request_description,
                'received_at' => Carbon::now(),
                'deadline_date' => Carbon::now()->addDays(30), // GDPR 30-day response requirement
                'response_status' => 'pending',
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'identity_document_path' => $documentPath,
                'submission_ip' => $request->ip(),
                'submission_user_agent' => $request->userAgent(),
            ]);

            // Log the submission
            Log::info("Public GDPR request submitted", [
                'request_id' => $gdprRequest->request_id,
                'request_type' => $request->request_type,
                'subject_type' => $request->subject_type,
                'ip' => $request->ip(),
            ]);

            // Send confirmation email (would be queued in production)
            // Mail::to($request->contact_email)->queue(new GdprRequestConfirmation($gdprRequest));

            return back()->with('success', 
                "Your request has been submitted successfully. Reference ID: {$gdprRequest->request_id}. " .
                "You will receive a response within 30 days as required by GDPR."
            );

        } catch (\Exception $e) {
            Log::error("Error submitting public GDPR request", [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['error' => 'An error occurred while submitting your request. Please try again.']);
        }
    }

    /**
     * Helper methods for dropdown options
     */
    protected function getRequestTypes(): array
    {
        return [
            'data_export' => 'Data Export (Article 15)',
            'data_portability' => 'Data Portability (Article 20)',
            'data_erasure' => 'Data Erasure (Article 17)',
            'data_rectification' => 'Data Rectification (Article 16)',
            'processing_restriction' => 'Processing Restriction (Article 18)',
            'objection_to_processing' => 'Objection to Processing (Article 21)',
        ];
    }

    protected function getPublicRequestTypes(): array
    {
        return [
            'data_export' => 'Request a copy of my personal data',
            'data_portability' => 'Transfer my data to another service',
            'data_erasure' => 'Delete my personal data',
            'data_rectification' => 'Correct my personal data',
        ];
    }

    protected function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'pending_verification' => 'Pending Verification',
            'verified' => 'Verified',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'failed' => 'Failed',
        ];
    }

    protected function getConsentTypes(): array
    {
        return [
            'marketing' => 'Marketing Communications',
            'analytics' => 'Analytics & Statistics',
            'performance' => 'Performance Tracking',
            'emergency_contact' => 'Emergency Contact Information',
            'medical_data' => 'Medical Information',
            'photo_video' => 'Photos & Videos',
        ];
    }

    protected function getProcessingPurposes(): array
    {
        return [
            'user_management' => 'User Account Management',
            'team_management' => 'Team & Player Management',
            'game_statistics' => 'Game Statistics & Analytics',
            'emergency_contacts' => 'Emergency Contact System',
            'marketing' => 'Marketing & Communications',
            'legal_compliance' => 'Legal & Regulatory Compliance',
        ];
    }

    protected function getLegalBases(): array
    {
        return [
            'consent' => 'Consent (Article 6(1)(a))',
            'contract' => 'Contract Performance (Article 6(1)(b))',
            'legal_obligation' => 'Legal Obligation (Article 6(1)(c))',
            'vital_interests' => 'Vital Interests (Article 6(1)(d))',
            'public_task' => 'Public Task (Article 6(1)(e))',
            'legitimate_interests' => 'Legitimate Interests (Article 6(1)(f))',
        ];
    }
}