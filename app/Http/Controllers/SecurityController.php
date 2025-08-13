<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Services\SecurityMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __construct(
        private SecurityMonitoringService $securityMonitoringService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:manage-security')->except(['dashboard']);
        $this->middleware('permission:view-security-dashboard')->only(['dashboard']);
    }

    /**
     * Display security dashboard
     */
    public function dashboard(): Response
    {
        // Get recent security events
        $recentEvents = SecurityEvent::with(['user', 'assignedTo'])
            ->orderBy('occurred_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'severity' => $event->severity,
                    'status' => $event->status,
                    'description' => $event->description,
                    'occurred_at' => $event->occurred_at->toISOString(),
                    'source_ip' => $event->source_ip,
                    'user' => $event->user ? [
                        'id' => $event->user->id,
                        'name' => $event->user->name,
                    ] : null,
                    'assigned_to' => $event->assignedTo ? [
                        'id' => $event->assignedTo->id,
                        'name' => $event->assignedTo->name,
                    ] : null,
                    'requires_investigation' => $event->requires_investigation,
                    'severity_icon' => $event->getSeverityIcon(),
                    'severity_color' => $event->getSeverityColor(),
                    'is_critical' => $event->isCritical(),
                    'is_emergency_related' => $event->isEmergencyRelated(),
                    'is_gdpr_related' => $event->isGdprRelated(),
                    'time_since_occurred' => $event->getTimeSinceOccurred(),
                ];
            });

        // Get security metrics
        $metrics = [
            'total_events_today' => SecurityEvent::recent(24)->count(),
            'critical_events_today' => SecurityEvent::recent(24)->critical()->count(),
            'unresolved_events' => SecurityEvent::unresolved()->count(),
            'emergency_related_events_today' => SecurityEvent::recent(24)->emergencyRelated()->count(),
            'gdpr_related_events_today' => SecurityEvent::recent(24)->gdprRelated()->count(),
            'events_requiring_investigation' => SecurityEvent::where('requires_investigation', true)
                ->unresolved()->count(),
        ];

        // Get security trends (last 7 days)
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M j'),
                'total_events' => SecurityEvent::whereDate('occurred_at', $date)->count(),
                'critical_events' => SecurityEvent::whereDate('occurred_at', $date)->critical()->count(),
                'emergency_events' => SecurityEvent::whereDate('occurred_at', $date)->emergencyRelated()->count(),
                'gdpr_events' => SecurityEvent::whereDate('occurred_at', $date)->gdprRelated()->count(),
            ];
        }

        // Get event type distribution
        $eventTypeDistribution = SecurityEvent::selectRaw('event_type, COUNT(*) as count')
            ->recent(7 * 24) // Last 7 days
            ->groupBy('event_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->event_type => $item->count];
            });

        // Get top source IPs
        $topSourceIPs = SecurityEvent::selectRaw('source_ip, COUNT(*) as count')
            ->recent(24)
            ->groupBy('source_ip')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'ip' => $item->source_ip,
                    'count' => $item->count,
                ];
            });

        return Inertia::render('Security/Dashboard', [
            'recentEvents' => $recentEvents,
            'metrics' => $metrics,
            'trends' => $trends,
            'eventTypeDistribution' => $eventTypeDistribution,
            'topSourceIPs' => $topSourceIPs,
        ]);
    }

    /**
     * Get all security events with filtering
     */
    public function events(Request $request): Response
    {
        $query = SecurityEvent::with(['user', 'assignedTo']);

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        // Filter by event type
        if ($request->has('event_type') && $request->event_type !== 'all') {
            $query->where('event_type', $request->event_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('occurred_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('occurred_at', '<=', $request->to_date);
        }

        // Search by event ID or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('event_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('source_ip', 'like', "%{$search}%");
            });
        }

        $events = $query->orderBy('occurred_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Transform events for frontend
        $events->getCollection()->transform(function ($event) {
            return [
                'id' => $event->id,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'status' => $event->status,
                'description' => $event->description,
                'occurred_at' => $event->occurred_at->toISOString(),
                'source_ip' => $event->source_ip,
                'user_agent' => $event->user_agent,
                'user' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                ] : null,
                'assigned_to' => $event->assignedTo ? [
                    'id' => $event->assignedTo->id,
                    'name' => $event->assignedTo->name,
                ] : null,
                'requires_investigation' => $event->requires_investigation,
                'severity_icon' => $event->getSeverityIcon(),
                'severity_color' => $event->getSeverityColor(),
                'is_critical' => $event->isCritical(),
                'is_emergency_related' => $event->isEmergencyRelated(),
                'is_gdpr_related' => $event->isGdprRelated(),
                'time_since_occurred' => $event->getTimeSinceOccurred(),
                'resolution_time' => $event->getResolutionTime(),
            ];
        });

        return Inertia::render('Security/Events/Index', [
            'events' => $events,
            'filters' => $request->only(['severity', 'event_type', 'status', 'from_date', 'to_date', 'search']),
            'eventTypes' => $this->getEventTypes(),
            'severityLevels' => ['critical', 'high', 'medium', 'low'],
            'statusOptions' => ['active', 'investigating', 'resolved', 'false_positive'],
        ]);
    }

    /**
     * Show specific security event details
     */
    public function show(SecurityEvent $securityEvent): Response
    {
        $securityEvent->load(['user', 'assignedTo']);

        $eventData = [
            'id' => $securityEvent->id,
            'event_id' => $securityEvent->event_id,
            'event_type' => $securityEvent->event_type,
            'severity' => $securityEvent->severity,
            'status' => $securityEvent->status,
            'description' => $securityEvent->description,
            'event_data' => $securityEvent->event_data,
            'occurred_at' => $securityEvent->occurred_at->toISOString(),
            'source_ip' => $securityEvent->source_ip,
            'user_agent' => $securityEvent->user_agent,
            'request_uri' => $securityEvent->request_uri,
            'request_method' => $securityEvent->request_method,
            'user' => $securityEvent->user ? [
                'id' => $securityEvent->user->id,
                'name' => $securityEvent->user->name,
                'email' => $securityEvent->user->email,
            ] : null,
            'assigned_to' => $securityEvent->assignedTo ? [
                'id' => $securityEvent->assignedTo->id,
                'name' => $securityEvent->assignedTo->name,
            ] : null,
            'detection_method' => $securityEvent->detection_method,
            'detector_name' => $securityEvent->detector_name,
            'confidence_score' => $securityEvent->confidence_score,
            'automated_actions' => $securityEvent->automated_actions,
            'requires_investigation' => $securityEvent->requires_investigation,
            'requires_notification' => $securityEvent->requires_notification,
            'investigation_notes' => $securityEvent->investigation_notes,
            'resolution_notes' => $securityEvent->resolution_notes,
            'resolved_at' => $securityEvent->resolved_at?->toISOString(),
            'severity_icon' => $securityEvent->getSeverityIcon(),
            'severity_color' => $securityEvent->getSeverityColor(),
            'is_critical' => $securityEvent->isCritical(),
            'is_emergency_related' => $securityEvent->isEmergencyRelated(),
            'is_gdpr_related' => $securityEvent->isGdprRelated(),
            'time_since_occurred' => $securityEvent->getTimeSinceOccurred(),
            'resolution_time' => $securityEvent->getResolutionTime(),
        ];

        return Inertia::render('Security/Events/Show', [
            'securityEvent' => $eventData,
        ]);
    }

    /**
     * Update security event status
     */
    public function updateEvent(Request $request, SecurityEvent $securityEvent): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,investigating,resolved,false_positive',
            'notes' => 'nullable|string|max:1000',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->status === 'resolved') {
            $updateData['resolved_at'] = now();
            $updateData['resolution_notes'] = $request->notes;
        }

        if ($request->status === 'investigating' && $request->assigned_to_user_id) {
            $updateData['assigned_to_user_id'] = $request->assigned_to_user_id;
        }

        if ($request->notes) {
            $updateData['investigation_notes'] = $request->notes;
        }

        $securityEvent->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Security event updated successfully',
            'event' => [
                'id' => $securityEvent->id,
                'status' => $securityEvent->status,
                'resolved_at' => $securityEvent->resolved_at?->toISOString(),
                'resolution_time' => $securityEvent->getResolutionTime(),
            ]
        ]);
    }

    /**
     * Generate security report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'timeframe' => 'required|string|in:24 hours,7 days,30 days,90 days',
            'include_details' => 'boolean',
        ]);

        $report = $this->securityMonitoringService->generateSecurityReport([
            'timeframe' => $request->timeframe,
            'include_details' => $request->include_details ?? false,
        ]);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Get security metrics for API
     */
    public function metrics(): JsonResponse
    {
        $metrics = [
            'today' => [
                'total_events' => SecurityEvent::recent(24)->count(),
                'critical_events' => SecurityEvent::recent(24)->critical()->count(),
                'high_severity_events' => SecurityEvent::recent(24)->high()->count(),
                'emergency_events' => SecurityEvent::recent(24)->emergencyRelated()->count(),
                'gdpr_events' => SecurityEvent::recent(24)->gdprRelated()->count(),
            ],
            'this_week' => [
                'total_events' => SecurityEvent::recent(7 * 24)->count(),
                'unresolved_events' => SecurityEvent::unresolved()->count(),
                'events_requiring_investigation' => SecurityEvent::where('requires_investigation', true)->unresolved()->count(),
            ],
            'all_time' => [
                'total_events' => SecurityEvent::count(),
                'resolved_events' => SecurityEvent::whereIn('status', ['resolved', 'false_positive'])->count(),
            ],
        ];

        return response()->json($metrics);
    }

    /**
     * Get available event types
     */
    private function getEventTypes(): array
    {
        return [
            'authentication_failure' => 'Authentication Failure',
            'authorization_violation' => 'Authorization Violation',
            'data_access_violation' => 'Data Access Violation',
            'emergency_access_misuse' => 'Emergency Access Misuse',
            'emergency_access_anomaly' => 'Emergency Access Anomaly',
            'gdpr_violation' => 'GDPR Violation',
            'gdpr_compliance_violation' => 'GDPR Compliance Violation',
            'suspicious_activity' => 'Suspicious Activity',
            'brute_force_attempt' => 'Brute Force Attempt',
            'rate_limit_exceeded' => 'Rate Limit Exceeded',
            'ip_blocked' => 'IP Blocked',
            'session_hijack_attempt' => 'Session Hijack Attempt',
            'privilege_escalation' => 'Privilege Escalation',
            'data_export_unusual' => 'Unusual Data Export',
        ];
    }
}