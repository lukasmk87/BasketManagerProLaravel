<?php

namespace App\Services;

use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyContact;
use App\Models\Team;
use App\Models\EmergencyIncident;
use App\Events\EmergencyAccessUsed;
use App\Jobs\SendEmergencyNotification;
use App\Mail\EmergencyAccessAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EmergencyAccessService
{
    public function createEmergencyAccess(Team $team, array $options = []): TeamEmergencyAccess
    {
        $accessKey = $this->generateSecureAccessKey();
        
        $access = TeamEmergencyAccess::create([
            'team_id' => $team->id,
            'created_by_user_id' => auth()->id(),
            'access_key' => $accessKey,
            'access_type' => $options['access_type'] ?? 'emergency_only',
            'permissions' => $options['permissions'] ?? null,
            'expires_at' => $options['expires_at'] ?? now()->addYear(),
            'max_uses' => $options['max_uses'] ?? null,
            'emergency_contact_person' => $options['emergency_contact_person'] ?? null,
            'emergency_contact_phone' => $options['emergency_contact_phone'] ?? null,
            'usage_instructions' => $options['usage_instructions'] ?? $this->getDefaultInstructions(),
            'venue_information' => $options['venue_information'] ?? null,
            'requires_reason' => $options['requires_reason'] ?? false,
            'send_notifications' => $options['send_notifications'] ?? true,
            'notification_recipients' => $options['notification_recipients'] ?? $this->getDefaultNotificationRecipients($team),
            'log_detailed_access' => $options['log_detailed_access'] ?? true,
        ]);

        // Generate QR Code
        $this->generateQRCode($access);

        return $access;
    }

    public function generateQRCode(TeamEmergencyAccess $access): void
    {
        $url = route('emergency.access.form', ['accessKey' => $access->access_key]);
        
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($url);

        $filename = "emergency_qr_{$access->team->id}_{$access->id}.png";
        $path = storage_path("app/public/emergency_qr/{$filename}");
        
        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, $qrCode);

        $access->update([
            'qr_code_url' => $url,
            'qr_code_filename' => $filename,
            'qr_code_metadata' => [
                'size' => '300x300',
                'format' => 'png',
                'error_correction' => 'H',
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function getEmergencyContacts(TeamEmergencyAccess $access, string $urgencyLevel): array
    {
        $query = EmergencyContact::whereHas('player', function ($q) use ($access) {
            $q->where('team_id', $access->team_id);
        })
        ->active()
        ->withConsent()
        ->with(['player']);

        // Filter by urgency level
        switch ($urgencyLevel) {
            case 'critical':
                $query->where(function ($q) {
                    $q->where('is_primary', true)
                      ->orWhere('medical_decisions_authorized', true)
                      ->orWhere('available_24_7', true);
                });
                break;
            case 'high':
                $query->where(function ($q) {
                    $q->where('is_primary', true)
                      ->orWhere('priority_order', '<=', 2);
                });
                break;
            case 'medium':
                $query->where('priority_order', '<=', 3);
                break;
            default: // low
                // Show all active contacts
                break;
        }

        $contacts = $query->byPriority()->get();

        return $contacts->groupBy('player_id')->map(function ($playerContacts) {
            $player = $playerContacts->first()->player;
            
            return [
                'player' => [
                    'id' => $player->id,
                    'name' => $player->full_name,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                ],
                'contacts' => $playerContacts->map(function ($contact) {
                    return $contact->emergency_access_info;
                })->toArray(),
            ];
        })->values()->toArray();
    }

    public function logAccess(TeamEmergencyAccess $access, Request $request): void
    {
        $logData = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'reason' => $request->input('reason'),
            'contact_person' => $request->input('contact_person'),
            'urgency_level' => $request->input('urgency_level'),
            'referrer' => $request->header('referer'),
        ];

        // Update access record
        $access->incrementUsage($logData);

        // Create detailed log entry
        Log::channel('emergency')->info('Emergency access used', [
            'access_id' => $access->id,
            'team_id' => $access->team_id,
            'team_name' => $access->team->name,
            'access_key' => $access->access_key,
            'log_data' => $logData,
        ]);

        // Create emergency incident if high urgency
        if (in_array($request->input('urgency_level'), ['high', 'critical'])) {
            $this->createEmergencyIncident($access, $request);
        }
    }

    public function logDirectAccess(TeamEmergencyAccess $access, Request $request): void
    {
        $logData = [
            'type' => 'direct_access',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        $access->incrementUsage($logData);

        Log::channel('emergency')->warning('Direct emergency access used', [
            'access_id' => $access->id,
            'team_id' => $access->team_id,
            'team_name' => $access->team->name,
            'log_data' => $logData,
        ]);
    }

    public function sendAccessNotifications(TeamEmergencyAccess $access, Request $request): void
    {
        $recipients = $access->notification_recipients ?? [];
        
        foreach ($recipients as $recipient) {
            SendEmergencyNotification::dispatch(
                $recipient,
                $access,
                $request->all()
            );
        }
    }

    public function getEmergencyInstructions(Team $team): array
    {
        return [
            'emergency_numbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
            'team_specific' => [
                'venue_address' => $team->primary_venue_address ?? 'Address not provided',
                'nearest_hospital' => $team->nearest_hospital ?? 'Please locate nearest hospital',
                'team_emergency_contact' => $team->emergency_contact_info ?? null,
            ],
            'instructions' => [
                'Stay calm and assess the situation',
                'Call emergency services (112) if life-threatening',
                'Contact the person\'s emergency contacts',
                'Provide clear location information',
                'Stay with the person until help arrives',
                'Document what happened for follow-up',
            ],
        ];
    }

    public function validateAccess(string $accessKey): ?TeamEmergencyAccess
    {
        return TeamEmergencyAccess::where('access_key', $accessKey)
            ->active()
            ->first();
    }

    public function processEmergencyAccess(TeamEmergencyAccess $access, Request $request): array
    {
        // Validate access permissions
        if (!$access->canBeUsed()) {
            throw new \Exception('Emergency access is no longer valid or has reached usage limit');
        }

        // Log the access attempt
        $this->logAccess($access, $request);

        // Get emergency contacts based on urgency
        $emergencyContacts = $this->getEmergencyContacts(
            $access,
            $request->input('urgency_level', 'medium')
        );

        // Send notifications if enabled
        if ($access->shouldSendNotifications()) {
            $this->sendAccessNotifications($access, $request);
        }

        // Broadcast emergency access event
        broadcast(new EmergencyAccessUsed($access, $request->all()));

        return [
            'team' => $access->team->load('club'),
            'emergency_contacts' => $emergencyContacts,
            'access_info' => [
                'accessed_at' => now()->toISOString(),
                'urgency_level' => $request->input('urgency_level'),
                'reason' => $request->input('reason'),
                'usage_count' => $access->current_uses,
            ],
            'emergency_instructions' => $this->getEmergencyInstructions($access->team),
        ];
    }

    public function createTeamAccessKey(Team $team, array $options = []): TeamEmergencyAccess
    {
        // Deactivate any existing active keys if requested
        if ($options['deactivate_existing'] ?? false) {
            TeamEmergencyAccess::where('team_id', $team->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $this->createEmergencyAccess($team, $options);
    }

    public function renewAccessKey(TeamEmergencyAccess $access, int $additionalHours = 8760): TeamEmergencyAccess
    {
        $access->extend($additionalHours, auth()->user());
        
        if ($access->qr_code_filename) {
            $this->regenerateQRCode($access);
        }

        return $access;
    }

    public function regenerateQRCode(TeamEmergencyAccess $access): void
    {
        // Remove old QR code file
        if ($access->qr_code_filename) {
            $oldPath = storage_path("app/public/emergency_qr/{$access->qr_code_filename}");
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Generate new QR code
        $this->generateQRCode($access);
    }

    public function getAccessStatistics(Team $team): array
    {
        $access = TeamEmergencyAccess::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        if (!$access) {
            return [
                'has_active_access' => false,
                'message' => 'No active emergency access key found',
            ];
        }

        return array_merge([
            'has_active_access' => true,
            'access_key_id' => $access->id,
            'created_at' => $access->created_at,
            'qr_code_available' => !empty($access->qr_code_filename),
        ], $access->getUsageStatistics());
    }

    public function deactivateAccess(TeamEmergencyAccess $access, string $reason = 'Manual deactivation'): void
    {
        $access->deactivate($reason);

        // Remove QR code file
        if ($access->qr_code_filename) {
            $qrPath = storage_path("app/public/emergency_qr/{$access->qr_code_filename}");
            if (file_exists($qrPath)) {
                unlink($qrPath);
            }
        }

        // Log deactivation
        Log::channel('emergency')->info('Emergency access deactivated', [
            'access_id' => $access->id,
            'team_id' => $access->team_id,
            'reason' => $reason,
            'deactivated_by' => auth()->user()?->name,
        ]);
    }

    public function bulkCreateAccessKeys(array $teamIds, array $options = []): array
    {
        $results = [];
        
        foreach ($teamIds as $teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                $results[$teamId] = ['success' => false, 'error' => 'Team not found'];
                continue;
            }

            try {
                $access = $this->createEmergencyAccess($team, $options);
                $results[$teamId] = [
                    'success' => true,
                    'access_id' => $access->id,
                    'access_key' => $access->access_key,
                    'qr_code_url' => $access->getEmergencyAccessUrl(),
                ];
            } catch (\Exception $e) {
                $results[$teamId] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function generateSecureAccessKey(): string
    {
        do {
            $key = Str::random(32);
        } while (TeamEmergencyAccess::where('access_key', $key)->exists());

        return $key;
    }

    private function getDefaultInstructions(): string
    {
        return 'Use this QR code only in emergency situations to access player emergency contact information. Scan the code and follow the instructions provided.';
    }

    private function getDefaultNotificationRecipients(Team $team): array
    {
        $recipients = [];
        
        // Add head coach if available
        if ($team->headCoach && $team->headCoach->email) {
            $recipients[] = [
                'type' => 'email',
                'address' => $team->headCoach->email,
                'name' => $team->headCoach->name,
                'role' => 'head_coach',
            ];
        }

        // Add club admin if available
        if ($team->club && $team->club->emergency_contact_email) {
            $recipients[] = [
                'type' => 'email',
                'address' => $team->club->emergency_contact_email,
                'name' => $team->club->name . ' Administration',
                'role' => 'club_admin',
            ];
        }

        return $recipients;
    }

    private function createEmergencyIncident(TeamEmergencyAccess $access, Request $request): void
    {
        $incidentId = 'EMG-' . date('Y') . '-' . str_pad(EmergencyIncident::count() + 1, 3, '0', STR_PAD_LEFT);

        EmergencyIncident::create([
            'incident_id' => $incidentId,
            'team_id' => $access->team_id,
            'incident_type' => 'emergency_access_used',
            'severity' => $request->input('urgency_level'),
            'description' => 'Emergency access was used with urgency level: ' . $request->input('urgency_level') . 
                           ($request->input('reason') ? '. Reason: ' . $request->input('reason') : ''),
            'occurred_at' => now(),
            'location' => 'Unknown (via QR code)',
            'reported_by_user_id' => 1, // System user
            'reported_at' => now(),
            'status' => 'active',
        ]);
    }

    public function cleanupExpiredAccess(): int
    {
        return TeamEmergencyAccess::expired()
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function getExpiringAccess(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return TeamEmergencyAccess::where('is_active', true)
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->with(['team', 'createdBy'])
            ->get();
    }

    /**
     * Get offline emergency data for PWA caching
     *
     * @param TeamEmergencyAccess $access
     * @return array
     */
    public function getOfflineEmergencyData(TeamEmergencyAccess $access): array
    {
        $emergencyContacts = $access->team->players
            ->filter(fn($player) => $player->emergencyContacts->isNotEmpty())
            ->map(function ($player) {
                return [
                    'player_id' => $player->id,
                    'player_name' => $player->full_name,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'contacts' => $player->emergencyContacts
                        ->where('is_active', true)
                        ->where('consent_given', true)
                        ->sortBy('priority')
                        ->map(function ($contact) {
                            return [
                                'id' => $contact->id,
                                'name' => $contact->contact_name,
                                'phone' => $contact->display_phone_number,
                                'secondary_phone' => $contact->secondary_phone ? 
                                    $this->formatPhoneNumber($contact->secondary_phone) : null,
                                'relationship' => $contact->relationship,
                                'is_primary' => $contact->is_primary,
                                'priority' => $contact->priority,
                                'medical_training' => $contact->has_medical_training,
                                'pickup_authorized' => $contact->emergency_pickup_authorized,
                                'medical_decisions' => $contact->medical_decisions_authorized,
                                'available_24_7' => $contact->available_24_7,
                                'special_instructions' => $contact->special_instructions,
                            ];
                        })->values()->toArray(),
                ];
            })->values()->toArray();

        return [
            'version' => '1.0.0',
            'generated_at' => now()->toISOString(),
            'access_key' => $access->access_key,
            'team' => [
                'id' => $access->team->id,
                'name' => $access->team->name,
                'club_name' => $access->team->club->name,
            ],
            'emergency_contacts' => $emergencyContacts,
            'emergency_instructions' => $this->getEmergencyInstructions($access->team),
            'offline_capabilities' => [
                'contact_list_access' => true,
                'phone_calling' => true,
                'incident_reporting' => true,
                'gps_location' => true,
                'offline_sync' => true,
            ],
            'cache_strategy' => [
                'contacts_cache_duration' => 86400, // 24 hours
                'emergency_numbers_cache_duration' => 604800, // 1 week
                'instructions_cache_duration' => 604800, // 1 week
            ],
        ];
    }

    /**
     * Get emergency instructions for a team
     *
     * @param Team $team
     * @return array
     */
    public function getEmergencyInstructions(Team $team): array
    {
        return [
            'emergency_numbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
            'team_specific' => [
                'venue_address' => $team->primary_venue_address ?? 'Address not provided',
                'nearest_hospital' => $team->nearest_hospital ?? 'Please locate nearest hospital',
                'team_emergency_contact' => $team->emergency_contact_info ?? null,
            ],
            'instructions' => [
                'Stay calm and assess the situation',
                'Call emergency services (112) if life-threatening',
                'Contact the person\'s emergency contacts',
                'Provide clear location information',
                'Stay with the person until help arrives',
                'Document what happened for follow-up',
            ],
        ];
    }

    /**
     * Format phone number for display
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
        }
        return $phone;
    }
}