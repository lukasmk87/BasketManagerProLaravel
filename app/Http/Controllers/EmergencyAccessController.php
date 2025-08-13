<?php

namespace App\Http\Controllers;

use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyContact;
use App\Models\Player;
use App\Services\EmergencyAccessService;
use App\Services\SecurityMonitoringService;
use App\Events\EmergencyAccessUsed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyAccessController extends Controller
{
    public function __construct(
        private EmergencyAccessService $emergencyAccessService,
        private SecurityMonitoringService $securityMonitoringService
    ) {}

    /**
     * Show the emergency access form for QR code access.
     */
    public function showAccessForm(string $accessKey): Response
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$access) {
            // Monitor invalid access attempts
            $this->securityMonitoringService->detectSecurityEvent(request(), 'emergency_access_anomaly', [
                'access_key' => $accessKey,
                'anomaly_type' => 'invalid_access_key',
                'resource' => 'emergency_access_form',
            ]);
            
            return Inertia::render('Emergency/AccessExpired');
        }

        // Monitor emergency access patterns for anomalies
        $this->securityMonitoringService->monitorEmergencyAccess(request(), $accessKey, [
            'action' => 'access_form_view',
            'team_id' => $access->team_id,
        ]);

        // Rate limiting per access key
        if (RateLimiter::tooManyAttempts($accessKey, 10)) {
            // Monitor rate limit violations
            $this->securityMonitoringService->detectSecurityEvent(request(), 'rate_limit_exceeded', [
                'resource' => 'emergency_access',
                'access_key' => $accessKey,
                'limit_type' => 'access_form',
                'retry_after' => RateLimiter::availableIn($accessKey),
            ]);
            
            return Inertia::render('Emergency/AccessLimited', [
                'retryAfter' => RateLimiter::availableIn($accessKey)
            ]);
        }

        return Inertia::render('Emergency/AccessForm', [
            'accessKey' => $accessKey,
            'teamName' => $access->team->name,
            'clubName' => $access->team->club?->name,
            'requiresReason' => $access->requires_reason,
            'usageInstructions' => $access->usage_instructions,
            'emergencyContact' => [
                'person' => $access->emergency_contact_person,
                'phone' => $access->emergency_contact_phone,
            ],
            'emergencyNumbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
        ]);
    }

    /**
     * Process emergency access request.
     */
    public function processAccess(Request $request, string $accessKey)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'urgency_level' => 'required|in:low,medium,high,critical',
        ]);

        try {
            $access = $this->emergencyAccessService->validateAccess($accessKey);

            if (!$access) {
                // Monitor invalid access attempts
                $this->securityMonitoringService->detectSecurityEvent($request, 'emergency_access_anomaly', [
                    'access_key' => $accessKey,
                    'anomaly_type' => 'invalid_or_expired_access',
                    'action' => 'process_access',
                    'urgency_level' => $request->input('urgency_level'),
                ]);
                
                return response()->json(['error' => 'Access expired or invalid'], 404);
            }

            // Check usage limits
            if (!$access->canBeUsed()) {
                // Monitor usage limit violations
                $this->securityMonitoringService->detectSecurityEvent($request, 'emergency_access_anomaly', [
                    'access_key' => $accessKey,
                    'anomaly_type' => 'usage_limit_exceeded',
                    'current_uses' => $access->current_uses ?? 0,
                    'max_uses' => $access->max_uses ?? 'unlimited',
                    'team_id' => $access->team_id,
                ]);
                
                return response()->json(['error' => 'Usage limit exceeded or access expired'], 403);
            }

            // Enhanced emergency access monitoring with context
            $this->securityMonitoringService->monitorEmergencyAccess($request, $accessKey, [
                'action' => 'emergency_access_granted',
                'urgency_level' => $request->input('urgency_level'),
                'reason' => $request->input('reason'),
                'contact_person' => $request->input('contact_person'),
                'team_id' => $access->team_id,
                'access_count' => $access->current_uses + 1,
            ]);

            // Rate limiting
            RateLimiter::hit($accessKey, 3600); // 1 hour window

            // Process the emergency access
            $emergencyData = $this->emergencyAccessService->processEmergencyAccess($access, $request);

            // Log successful access with security context
            Log::info('Emergency access granted', [
                'event_type' => 'emergency_access_success',
                'access_key' => $accessKey,
                'team_id' => $access->team_id,
                'urgency_level' => $request->input('urgency_level'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return Inertia::render('Emergency/ContactsList', $emergencyData);

        } catch (\Exception $e) {
            // Monitor system failures during emergency access
            $this->securityMonitoringService->detectSecurityEvent($request, 'emergency_access_anomaly', [
                'access_key' => $accessKey,
                'anomaly_type' => 'system_failure_during_access',
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'urgency_level' => $request->input('urgency_level'),
            ]);
            
            Log::error('Emergency access failed', [
                'access_key' => $accessKey,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'Access failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Direct emergency access (bypasses form for critical situations).
     */
    public function showDirectAccess(string $accessKey): Response
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['team.players.emergencyContacts' => function ($query) {
                $query->active()
                      ->withConsent()
                      ->byPriority();
            }])
            ->first();

        if (!$access) {
            // Monitor invalid direct access attempts
            $this->securityMonitoringService->detectSecurityEvent(request(), 'emergency_access_anomaly', [
                'access_key' => $accessKey,
                'anomaly_type' => 'invalid_direct_access',
                'resource' => 'emergency_direct_access',
            ]);
            
            return Inertia::render('Emergency/AccessExpired');
        }

        // Monitor direct emergency access (higher severity since it bypasses forms)
        $this->securityMonitoringService->detectSecurityEvent(request(), 'emergency_access_anomaly', [
            'access_key' => $accessKey,
            'anomaly_type' => 'direct_access_bypassed_form',
            'team_id' => $access->team_id,
            'access_type' => 'direct_critical_access',
            'bypassed_security' => true,
        ]);

        // For critical situations, skip the form and show contacts immediately
        $emergencyContacts = $access->team->players
            ->filter(fn($player) => $player->emergencyContacts->isNotEmpty())
            ->map(function ($player) {
                return [
                    'player' => [
                        'id' => $player->id,
                        'name' => $player->full_name,
                        'jersey_number' => $player->jersey_number,
                        'position' => $player->position,
                    ],
                    'contacts' => $player->emergencyContacts->map(function ($contact) {
                        return $contact->emergency_access_info;
                    }),
                ];
            });

        // Log this direct access
        $this->emergencyAccessService->logDirectAccess($access, request());

        return Inertia::render('Emergency/DirectAccess', [
            'team' => $access->team,
            'emergencyContacts' => $emergencyContacts,
            'accessTime' => now()->toISOString(),
            'emergencyInstructions' => $this->emergencyAccessService->getEmergencyInstructions($access->team),
            'emergencyNumbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
        ]);
    }

    /**
     * Show printable emergency contact list.
     */
    public function printableView(string $accessKey): Response
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['team.players.emergencyContacts' => function ($query) {
                $query->active()
                      ->withConsent()
                      ->byPriority();
            }])
            ->first();

        if (!$access) {
            abort(404, 'Access key not found or expired');
        }

        return Inertia::render('Emergency/PrintableView', [
            'team' => $access->team,
            'contacts' => $access->team->players->map(function ($player) {
                return [
                    'player_name' => $player->full_name,
                    'jersey_number' => $player->jersey_number,
                    'contacts' => $player->emergencyContacts->map(function ($contact) {
                        return [
                            'name' => $contact->contact_name ?? $contact->name,
                            'phone' => $contact->display_phone_number,
                            'relationship' => $contact->relationship,
                            'is_primary' => $contact->is_primary,
                            'medical_training' => $contact->has_medical_training,
                            'pickup_authorized' => $contact->emergency_pickup_authorized ?? $contact->can_pickup_player,
                        ];
                    }),
                ];
            }),
            'generated_at' => now()->format('d.m.Y H:i'),
            'emergency_instructions' => $this->emergencyAccessService->getEmergencyInstructions($access->team),
        ]);
    }

    /**
     * Get emergency contacts for a specific player (used by mobile interface).
     */
    public function getPlayerContacts(string $accessKey, int $playerId)
    {
        $access = $this->emergencyAccessService->validateAccess($accessKey);

        if (!$access) {
            return response()->json(['error' => 'Access expired or invalid'], 404);
        }

        $player = Player::where('id', $playerId)
            ->where('team_id', $access->team_id)
            ->with(['emergencyContacts' => function ($query) {
                $query->active()->withConsent()->byPriority();
            }])
            ->first();

        if (!$player) {
            return response()->json(['error' => 'Player not found'], 404);
        }

        // Log the specific player access
        $access->logUsage([
            'action' => 'player_contacts_accessed',
            'player_id' => $playerId,
            'player_name' => $player->full_name,
        ]);

        return response()->json([
            'player' => [
                'id' => $player->id,
                'name' => $player->full_name,
                'jersey_number' => $player->jersey_number,
                'position' => $player->position,
            ],
            'contacts' => $player->emergencyContacts->map(function ($contact) {
                return $contact->emergency_access_info;
            }),
            'accessed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Record an emergency call made through the interface.
     */
    public function recordEmergencyCall(Request $request, string $accessKey)
    {
        $request->validate([
            'contact_id' => 'required|exists:emergency_contacts,id',
            'call_type' => 'required|in:phone,sms',
            'success' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $access = $this->emergencyAccessService->validateAccess($accessKey);

        if (!$access) {
            return response()->json(['error' => 'Access expired or invalid'], 404);
        }

        $contact = EmergencyContact::find($request->contact_id);
        if (!$contact) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        // Log the emergency call
        $contact->logContactAttempt('emergency_interface_call', [
            'call_type' => $request->call_type,
            'success' => $request->boolean('success'),
            'notes' => $request->notes,
            'access_key' => $accessKey,
            'via_qr_access' => true,
        ]);

        // Update access usage log
        $access->logUsage([
            'action' => 'emergency_call_made',
            'contact_id' => $contact->id,
            'contact_name' => $contact->contact_name ?? $contact->name,
            'call_type' => $request->call_type,
            'success' => $request->boolean('success'),
        ]);

        return response()->json([
            'message' => 'Emergency call recorded successfully',
            'call_logged_at' => now()->toISOString(),
        ]);
    }

    /**
     * Report an incident through the emergency interface.
     */
    public function reportIncident(Request $request, string $accessKey)
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'incident_type' => 'required|in:injury,medical_emergency,accident,missing_person,behavioral_incident,facility_emergency,weather_emergency,other',
            'severity' => 'required|in:minor,moderate,severe,critical',
            'description' => 'required|string|max:1000',
            'location' => 'required|string|max:255',
            'reporter_name' => 'required|string|max:255',
            'reporter_phone' => 'nullable|string|max:50',
        ]);

        $access = $this->emergencyAccessService->validateAccess($accessKey);

        if (!$access) {
            return response()->json(['error' => 'Access expired or invalid'], 404);
        }

        // Create emergency incident
        $incident = \App\Models\EmergencyIncident::create([
            'player_id' => $request->player_id,
            'team_id' => $access->team_id,
            'incident_type' => $request->incident_type,
            'severity' => $request->severity,
            'description' => $request->description,
            'occurred_at' => now(),
            'location' => $request->location,
            'reported_by_user_id' => 1, // System user for QR reports
            'reported_at' => now(),
            'response_actions' => [[
                'action' => 'Incident reported via emergency QR code access',
                'timestamp' => now(),
                'reporter_name' => $request->reporter_name,
                'reporter_phone' => $request->reporter_phone,
            ]],
        ]);

        // Log incident creation
        $access->logUsage([
            'action' => 'incident_reported',
            'incident_id' => $incident->incident_id,
            'player_id' => $request->player_id,
            'severity' => $request->severity,
            'reporter_name' => $request->reporter_name,
        ]);

        return response()->json([
            'message' => 'Incident reported successfully',
            'incident_id' => $incident->incident_id,
            'reported_at' => $incident->reported_at->toISOString(),
        ]);
    }

    /**
     * Get offline emergency data for PWA.
     */
    public function getOfflineData(string $accessKey)
    {
        $access = TeamEmergencyAccess::where('access_key', $accessKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['team.players.emergencyContacts' => function ($query) {
                $query->active()->withConsent()->byPriority();
            }])
            ->first();

        if (!$access) {
            return response()->json(['error' => 'Access expired or invalid'], 404);
        }

        $offlineData = [
            'version' => '1.0.0',
            'generated_at' => now()->toISOString(),
            'expires_at' => $access->expires_at->toISOString(),
            'team' => [
                'id' => $access->team->id,
                'name' => $access->team->name,
                'club_name' => $access->team->club?->name,
            ],
            'emergency_contacts' => $access->team->players
                ->filter(fn($player) => $player->emergencyContacts->isNotEmpty())
                ->map(function ($player) {
                    return [
                        'player_id' => $player->id,
                        'player_name' => $player->full_name,
                        'jersey_number' => $player->jersey_number,
                        'contacts' => $player->emergencyContacts->map(function ($contact) {
                            return $contact->emergency_access_info;
                        })->toArray(),
                    ];
                })->values()->toArray(),
            'emergency_instructions' => $this->emergencyAccessService->getEmergencyInstructions($access->team),
        ];

        // Log offline data access
        $access->logUsage([
            'action' => 'offline_data_accessed',
            'data_version' => $offlineData['version'],
        ]);

        return response()->json($offlineData)
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Health check endpoint for emergency system.
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'emergency_access',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    }
}