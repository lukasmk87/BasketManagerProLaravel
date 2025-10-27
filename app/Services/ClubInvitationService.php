<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClubInvitationService
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Create a new club invitation.
     *
     * @param int $userId Creator user ID
     * @param int $clubId Club ID
     * @param array $options Options (default_role, expires_at, max_uses, settings)
     * @return ClubInvitation
     */
    public function createInvitation(int $userId, int $clubId, array $options = []): ClubInvitation
    {
        $expiresDays = $options['expires_days'] ?? config('club_invitation.expires_days', 30);
        $expiresAt = isset($options['expires_at'])
            ? Carbon::parse($options['expires_at'])
            : now()->addDays($expiresDays);

        $invitation = ClubInvitation::create([
            'club_id' => $clubId,
            'created_by_user_id' => $userId,
            'default_role' => $options['default_role'] ?? 'member',
            'expires_at' => $expiresAt,
            'max_uses' => $options['max_uses'] ?? config('club_invitation.max_per_invitation', 100),
            'settings' => $options['settings'] ?? [],
        ]);

        // Generate QR Code
        // Default to SVG format for better compatibility (no Imagick dependency)
        $qrResult = $this->qrCodeService->generateClubInvitationQR($invitation, [
            'size' => $options['qr_size'] ?? 300,
            'format' => $options['qr_format'] ?? 'svg',
        ]);

        $invitation->update([
            'qr_code_path' => $qrResult['file_path'],
            'qr_code_metadata' => $qrResult['metadata'] ?? [],
        ]);

        Log::info('Club invitation created', [
            'invitation_id' => $invitation->id,
            'created_by' => $userId,
            'club_id' => $clubId,
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return $invitation->fresh();
    }

    /**
     * Validate an invitation token.
     *
     * @param string $token
     * @return array{valid: bool, invitation: ?ClubInvitation, error: ?string}
     */
    public function validateToken(string $token): array
    {
        $invitation = ClubInvitation::where('invitation_token', $token)->first();

        if (!$invitation) {
            return [
                'valid' => false,
                'invitation' => null,
                'error' => 'Ungültiger Einladungs-Code',
            ];
        }

        if (!$invitation->is_active) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'Diese Einladung wurde deaktiviert',
            ];
        }

        if ($invitation->expires_at->isPast()) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'Diese Einladung ist abgelaufen',
            ];
        }

        if ($invitation->has_reached_limit) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'Diese Einladung hat das maximale Nutzungslimit erreicht',
            ];
        }

        return [
            'valid' => true,
            'invitation' => $invitation,
            'error' => null,
        ];
    }

    /**
     * Register a new user via invitation token and associate with club.
     *
     * @param string $token Invitation token
     * @param array $userData User data (name, email, password, phone, birth_date, etc.)
     * @return array{success: bool, user: ?User, invitation: ?ClubInvitation, error: ?string}
     */
    public function registerUserWithClub(string $token, array $userData): array
    {
        // Validate token first
        $validation = $this->validateToken($token);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'user' => null,
                'invitation' => $validation['invitation'],
                'error' => $validation['error'],
            ];
        }

        $invitation = $validation['invitation'];

        // Check if email already exists
        if (User::where('email', $userData['email'])->exists()) {
            return [
                'success' => false,
                'user' => null,
                'invitation' => $invitation,
                'error' => 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits',
            ];
        }

        try {
            DB::beginTransaction();

            // Create User
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'phone' => $userData['phone'] ?? null,
                'date_of_birth' => isset($userData['date_of_birth']) ? Carbon::parse($userData['date_of_birth']) : null,
                'gender' => $userData['gender'] ?? null,
                'is_active' => true,
            ]);

            // Assign Spatie role based on club role
            $spatieRole = $this->mapClubRoleToSpatieRole($invitation->default_role);
            $user->assignRole($spatieRole);

            // Associate user with club
            $invitation->club->addMember($user, $invitation->default_role, [
                'registered_via_invitation_id' => $invitation->id,
            ]);

            // Increment invitation usage count
            $invitation->incrementUses();

            DB::commit();

            // Send notifications (optional - implement later)
            // $this->sendRegistrationNotifications($invitation, $user);

            Log::info('New user registered via club invitation', [
                'user_id' => $user->id,
                'invitation_id' => $invitation->id,
                'club_id' => $invitation->club_id,
                'email' => $user->email,
                'role' => $invitation->default_role,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'invitation' => $invitation,
                'error' => null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Club invitation registration failed', [
                'invitation_id' => $invitation->id,
                'email' => $userData['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'invitation' => $invitation,
                'error' => 'Registrierung fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get statistics for an invitation.
     *
     * @param int $invitationId
     * @return array
     */
    public function getInvitationStats(int $invitationId): array
    {
        $invitation = ClubInvitation::with('registeredUsers')->find($invitationId);

        if (!$invitation) {
            return [];
        }

        return $invitation->getStatistics();
    }

    /**
     * Deactivate an invitation.
     *
     * @param int $invitationId
     * @return bool
     */
    public function deactivateInvitation(int $invitationId): bool
    {
        $invitation = ClubInvitation::find($invitationId);

        if (!$invitation) {
            return false;
        }

        Log::info('Club invitation deactivated', [
            'invitation_id' => $invitationId,
        ]);

        return $invitation->deactivate();
    }

    /**
     * Get all invitations for a club.
     *
     * @param int $clubId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClubInvitations(int $clubId)
    {
        return ClubInvitation::with(['creator'])
            ->where('club_id', $clubId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Map club role to Spatie permission role.
     *
     * @param string $clubRole
     * @return string
     */
    protected function mapClubRoleToSpatieRole(string $clubRole): string
    {
        return match($clubRole) {
            'player' => 'player',
            'parent' => 'parent',
            'volunteer', 'sponsor', 'member' => 'guest',
            default => 'guest',
        };
    }

    /**
     * Send notifications after registration (optional - to be implemented).
     *
     * @param ClubInvitation $invitation
     * @param User $user
     * @return void
     */
    protected function sendRegistrationNotifications(ClubInvitation $invitation, User $user): void
    {
        // TODO: Implement welcome email notification to new member
        // TODO: Implement notification to club admin/creator
    }
}
