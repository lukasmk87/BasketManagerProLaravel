<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\EmergencyContacts\StoreEmergencyContactRequest;
use App\Http\Requests\Api\V2\EmergencyContacts\UpdateEmergencyContactRequest;
use App\Http\Requests\Api\V2\EmergencyContacts\IndexEmergencyContactsRequest;
use App\Http\Resources\EmergencyContactResource;
use App\Models\EmergencyContact;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmergencyContactController extends Controller
{
    /**
     * Display a listing of emergency contacts.
     */
    public function index(IndexEmergencyContactsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmergencyContact::class);

        $contacts = EmergencyContact::query()
            ->with(['player.user:id,name', 'player.team:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhereHas('player', function ($playerQuery) use ($search) {
                          $playerQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                      });
                });
            })
            ->when($request->filled('player_id'), function ($query) use ($request) {
                $query->where('player_id', $request->player_id);
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                $query->whereHas('player', function ($q) use ($request) {
                    $q->where('team_id', $request->team_id);
                });
            })
            ->when($request->filled('club_id'), function ($query) use ($request) {
                $query->whereHas('player.team', function ($q) use ($request) {
                    $q->where('club_id', $request->club_id);
                });
            })
            ->when($request->filled('relationship'), function ($query) use ($request) {
                $query->where('relationship', $request->relationship);
            })
            ->when($request->filled('is_primary'), function ($query) use ($request) {
                $primary = $request->is_primary === 'true';
                $query->where('is_primary', $primary);
            })
            ->when($request->filled('consent_given'), function ($query) use ($request) {
                $consent = $request->consent_given === 'true';
                $query->where('consent_given', $consent);
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->filled('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
                
                $allowedSortFields = ['contact_name', 'relationship', 'created_at'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                }
            })
            ->orderBy('is_primary', 'desc')
            ->orderBy('contact_name')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        return EmergencyContactResource::collection($contacts);
    }

    /**
     * Store a newly created emergency contact.
     */
    public function store(StoreEmergencyContactRequest $request): EmergencyContactResource
    {
        $this->authorize('create', EmergencyContact::class);

        $contactData = $request->validated();
        
        // If this is marked as primary, unset other primary contacts for this player
        if ($contactData['is_primary'] ?? false) {
            EmergencyContact::where('player_id', $contactData['player_id'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $contact = EmergencyContact::create($contactData);

        return new EmergencyContactResource($contact->load(['player.user', 'player.team']));
    }

    /**
     * Display the specified emergency contact.
     */
    public function show(EmergencyContact $emergencyContact): EmergencyContactResource
    {
        $this->authorize('view', $emergencyContact);

        $emergencyContact->load([
            'player.user:id,name,birth_date',
            'player.team.club:id,name',
        ]);

        return new EmergencyContactResource($emergencyContact);
    }

    /**
     * Update the specified emergency contact.
     */
    public function update(UpdateEmergencyContactRequest $request, EmergencyContact $emergencyContact): EmergencyContactResource
    {
        $this->authorize('update', $emergencyContact);

        $contactData = $request->validated();
        
        // If this is being marked as primary, unset other primary contacts for this player
        if (($contactData['is_primary'] ?? false) && !$emergencyContact->is_primary) {
            EmergencyContact::where('player_id', $emergencyContact->player_id)
                ->where('id', '!=', $emergencyContact->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $emergencyContact->update($contactData);

        return new EmergencyContactResource($emergencyContact->load(['player.user', 'player.team']));
    }

    /**
     * Remove the specified emergency contact.
     */
    public function destroy(EmergencyContact $emergencyContact): JsonResponse
    {
        $this->authorize('delete', $emergencyContact);

        $emergencyContact->delete();

        return response()->json([
            'message' => 'Notfallkontakt erfolgreich gelöscht.',
        ]);
    }

    /**
     * Get emergency contacts for a specific player.
     */
    public function byPlayer(Player $player): AnonymousResourceCollection
    {
        $this->authorize('viewEmergencyContacts', $player);

        $contacts = $player->emergencyContacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('contact_name')
            ->get();

        return EmergencyContactResource::collection($contacts);
    }

    /**
     * Set emergency contact as primary.
     */
    public function setPrimary(EmergencyContact $emergencyContact): EmergencyContactResource
    {
        $this->authorize('update', $emergencyContact);

        // Unset other primary contacts for this player
        EmergencyContact::where('player_id', $emergencyContact->player_id)
            ->where('id', '!=', $emergencyContact->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        // Set this contact as primary
        $emergencyContact->update(['is_primary' => true]);

        return new EmergencyContactResource($emergencyContact->load(['player.user', 'player.team']));
    }

    /**
     * Update consent status for emergency contact.
     */
    public function updateConsent(EmergencyContact $emergencyContact, UpdateEmergencyContactRequest $request): EmergencyContactResource
    {
        $this->authorize('update', $emergencyContact);

        $validated = $request->validate([
            'consent_given' => 'required|boolean',
        ]);

        $updateData = $validated;
        if ($validated['consent_given']) {
            $updateData['consent_given_at'] = now();
        } else {
            $updateData['consent_given_at'] = null;
        }

        $emergencyContact->update($updateData);

        return new EmergencyContactResource($emergencyContact->load(['player.user', 'player.team']));
    }

    /**
     * Generate QR code for emergency access.
     */
    public function generateQR(EmergencyContact $emergencyContact): JsonResponse
    {
        $this->authorize('generateEmergencyQR', $emergencyContact);

        // Generate emergency access URL with temporary token
        $accessToken = \Str::random(64);
        
        // Store access token with expiration (you might want to create a separate table for this)
        cache()->put(
            "emergency_access:{$accessToken}", 
            [
                'contact_id' => $emergencyContact->id,
                'player_id' => $emergencyContact->player_id,
                'team_id' => $emergencyContact->player->team_id,
            ],
            now()->addHours(config('basketball.emergency_access_duration', 24))
        );

        $qrData = [
            'type' => 'emergency_contact',
            'contact_id' => $emergencyContact->id,
            'player_name' => $emergencyContact->player->full_name,
            'team_name' => $emergencyContact->player->team?->name,
            'access_token' => $accessToken,
            'generated_at' => now()->toISOString(),
            'expires_at' => now()->addHours(config('basketball.emergency_access_duration', 24))->toISOString(),
        ];

        $accessUrl = route('emergency.access', ['token' => $accessToken]);

        return response()->json([
            'qr_data' => $qrData,
            'access_url' => $accessUrl,
            'expires_at' => $qrData['expires_at'],
        ]);
    }

    /**
     * Emergency access endpoint (public access with token).
     */
    public function emergencyAccess(string $token): JsonResponse
    {
        $cacheKey = "emergency_access:{$token}";
        $accessData = cache()->get($cacheKey);

        if (!$accessData) {
            return response()->json([
                'message' => 'Ungültiger oder abgelaufener Notfall-Zugangstoken.',
            ], 404);
        }

        $contact = EmergencyContact::with([
            'player.user:id,name,birth_date',
            'player.team.club:id,name',
        ])->find($accessData['contact_id']);

        if (!$contact) {
            return response()->json([
                'message' => 'Notfallkontakt nicht gefunden.',
            ], 404);
        }

        // Log emergency access
        activity()
            ->performedOn($contact)
            ->withProperties([
                'access_token' => $token,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('emergency_access_used');

        $emergencyData = [
            'player' => [
                'name' => $contact->player->full_name,
                'age' => $contact->player->user?->birth_date?->age,
                'team' => $contact->player->team?->name,
                'club' => $contact->player->team?->club?->name,
                'jersey_number' => $contact->player->jersey_number,
                'position' => $contact->player->primary_position,
            ],
            'emergency_contact' => [
                'name' => $contact->contact_name,
                'phone' => $contact->phone_number,
                'relationship' => $contact->relationship,
                'is_primary' => $contact->is_primary,
            ],
            'medical_info' => [
                'conditions' => $contact->player->medical_conditions,
                'allergies' => $contact->player->allergies,
                'medications' => $contact->player->medications,
                'blood_type' => $contact->player->blood_type,
                'doctor_contact' => $contact->player->emergency_medical_contact,
                'doctor_phone' => $contact->player->emergency_medical_phone,
                'preferred_hospital' => $contact->player->preferred_hospital,
            ],
            'access_info' => [
                'accessed_at' => now()->toISOString(),
                'token_expires_at' => cache()->get($cacheKey . '_expires'),
            ],
        ];

        return response()->json($emergencyData);
    }

    /**
     * Get emergency contacts statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', EmergencyContact::class);

        $stats = [
            'total_contacts' => EmergencyContact::count(),
            'primary_contacts' => EmergencyContact::where('is_primary', true)->count(),
            'consented_contacts' => EmergencyContact::where('consent_given', true)->count(),
            'by_relationship' => EmergencyContact::select('relationship')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('relationship')
                ->orderBy('count', 'desc')
                ->get(),
            'recent_additions' => EmergencyContact::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json($stats);
    }
}