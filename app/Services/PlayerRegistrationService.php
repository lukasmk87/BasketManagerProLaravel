<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Player;
use App\Models\PlayerRegistrationInvitation;
use App\Models\User;
use App\Models\BasketballTeam;
use App\Notifications\PlayerRegisteredNotification;
use App\Notifications\PlayerAssignedNotification;
use App\Notifications\RegistrationWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PlayerRegistrationService
{
    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Create a new player registration invitation.
     *
     * @param int $userId Creator user ID
     * @param int $clubId Club ID
     * @param array $options Options (target_team_id, expires_at, max_registrations, settings)
     * @return PlayerRegistrationInvitation
     */
    public function createInvitation(int $userId, int $clubId, array $options = []): PlayerRegistrationInvitation
    {
        $expiresDays = $options['expires_days'] ?? config('player_registration.expires_days', 30);
        $expiresAt = isset($options['expires_at'])
            ? Carbon::parse($options['expires_at'])
            : now()->addDays($expiresDays);

        $invitation = PlayerRegistrationInvitation::create([
            'club_id' => $clubId,
            'created_by_user_id' => $userId,
            'target_team_id' => $options['target_team_id'] ?? null,
            'expires_at' => $expiresAt,
            'max_registrations' => $options['max_registrations'] ?? config('player_registration.max_per_invitation', 50),
            'settings' => $options['settings'] ?? [],
        ]);

        // Generate QR Code
        // Default to SVG format for better compatibility (no Imagick dependency)
        $qrResult = $this->qrCodeService->generatePlayerRegistrationQR($invitation, [
            'size' => $options['qr_size'] ?? 300,
            'format' => $options['qr_format'] ?? 'svg',
        ]);

        $invitation->update([
            'qr_code_path' => $qrResult['file_path'],
            'qr_code_metadata' => $qrResult['metadata'] ?? [],
        ]);

        Log::info('Player registration invitation created', [
            'invitation_id' => $invitation->id,
            'created_by' => $userId,
            'club_id' => $clubId,
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return $invitation->fresh();
    }

    /**
     * Validate a registration token.
     *
     * @param string $token
     * @return array{valid: bool, invitation: ?PlayerRegistrationInvitation, error: ?string}
     */
    public function validateToken(string $token): array
    {
        $invitation = PlayerRegistrationInvitation::where('invitation_token', $token)->first();

        if (!$invitation) {
            return [
                'valid' => false,
                'invitation' => null,
                'error' => 'Invalid invitation token',
            ];
        }

        if (!$invitation->is_active) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'This invitation has been deactivated',
            ];
        }

        if ($invitation->expires_at->isPast()) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'This invitation has expired',
            ];
        }

        if ($invitation->has_reached_limit) {
            return [
                'valid' => false,
                'invitation' => $invitation,
                'error' => 'This invitation has reached its registration limit',
            ];
        }

        return [
            'valid' => true,
            'invitation' => $invitation,
            'error' => null,
        ];
    }

    /**
     * Register a new player via invitation token.
     *
     * @param string $token Invitation token
     * @param array $playerData Player data (first_name, last_name, email, birth_date, etc.)
     * @return array{success: bool, user: ?User, player: ?Player, error: ?string}
     */
    public function registerPlayer(string $token, array $playerData): array
    {
        // Validate token first
        $validation = $this->validateToken($token);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'user' => null,
                'player' => null,
                'error' => $validation['error'],
            ];
        }

        $invitation = $validation['invitation'];

        // Check if email already exists
        if (User::where('email', $playerData['email'])->exists()) {
            return [
                'success' => false,
                'user' => null,
                'player' => null,
                'error' => 'A user with this email already exists',
            ];
        }

        try {
            DB::beginTransaction();

            // Create User
            $user = User::create([
                'name' => trim($playerData['first_name'] . ' ' . $playerData['last_name']),
                'email' => $playerData['email'],
                'password' => Hash::make(Str::random(32)), // Random password, user must reset
                'phone' => $playerData['phone'] ?? null,
                'birth_date' => isset($playerData['birth_date']) ? Carbon::parse($playerData['birth_date']) : null,
                'account_status' => 'pending',
                'pending_verification' => config('player_registration.require_email_verification', false),
            ]);

            // Assign 'player' role
            $user->assignRole('player');

            // Create Player profile
            $player = Player::create([
                'user_id' => $user->id,
                'pending_team_assignment' => true,
                'registered_via_invitation_id' => $invitation->id,
                'registration_completed_at' => now(),
                'status' => 'inactive',
                // Optional fields
                'height_cm' => $playerData['height_cm'] ?? null,
                'weight_kg' => $playerData['weight_kg'] ?? null,
                // Note: Position and other team-specific data stored in pivot when assigned
            ]);

            // Increment invitation registration count
            $invitation->incrementRegistrations();

            DB::commit();

            // Send notifications
            $this->sendRegistrationNotifications($invitation, $user, $player);

            Log::info('New player registered via invitation', [
                'player_id' => $player->id,
                'user_id' => $user->id,
                'invitation_id' => $invitation->id,
                'club_id' => $invitation->club_id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'player' => $player,
                'error' => null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Player registration failed', [
                'invitation_id' => $invitation->id,
                'email' => $playerData['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'player' => null,
                'error' => 'Registration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Assign a pending player to a team and activate them.
     *
     * @param int $playerId
     * @param int $teamId
     * @param int $assignedBy User ID who assigned
     * @param array $teamData Optional team-specific data (jersey_number, primary_position, etc.)
     * @return array{success: bool, player: ?Player, error: ?string}
     */
    public function assignPlayerToTeam(int $playerId, int $teamId, int $assignedBy, array $teamData = []): array
    {
        $player = Player::with('user')->find($playerId);

        if (!$player) {
            return [
                'success' => false,
                'player' => null,
                'error' => 'Player not found',
            ];
        }

        if (!$player->pending_team_assignment) {
            return [
                'success' => false,
                'player' => $player,
                'error' => 'Player is not pending team assignment',
            ];
        }

        $team = BasketballTeam::find($teamId);
        if (!$team) {
            return [
                'success' => false,
                'player' => $player,
                'error' => 'Team not found',
            ];
        }

        try {
            DB::beginTransaction();

            // Attach player to team with pivot data
            $player->teams()->attach($teamId, [
                'jersey_number' => $teamData['jersey_number'] ?? null,
                'primary_position' => $teamData['primary_position'] ?? null,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Update player status
            $player->update([
                'pending_team_assignment' => false,
                'status' => 'active',
            ]);

            // Update user account status
            $player->user->update([
                'account_status' => 'active',
            ]);

            DB::commit();

            // Send notification to player
            if ($player->user->email) {
                $player->user->notify(new PlayerAssignedNotification($player, $team));
            }

            Log::info('Player assigned to team', [
                'player_id' => $playerId,
                'team_id' => $teamId,
                'assigned_by' => $assignedBy,
            ]);

            return [
                'success' => true,
                'player' => $player->fresh(),
                'error' => null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Player team assignment failed', [
                'player_id' => $playerId,
                'team_id' => $teamId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'player' => $player,
                'error' => 'Assignment failed: ' . $e->getMessage(),
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
        $invitation = PlayerRegistrationInvitation::with('registeredPlayers')->find($invitationId);

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
        $invitation = PlayerRegistrationInvitation::find($invitationId);

        if (!$invitation) {
            return false;
        }

        Log::info('Player registration invitation deactivated', [
            'invitation_id' => $invitationId,
        ]);

        return $invitation->deactivate();
    }

    /**
     * Get all pending players for a club.
     *
     * @param int $clubId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingPlayers(int $clubId)
    {
        return Player::with(['user', 'teams'])
            ->where('pending_team_assignment', true)
            ->whereHas('registeredViaInvitation', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->orderBy('registration_completed_at', 'desc')
            ->get();
    }

    /**
     * Send notifications after player registration.
     *
     * @param PlayerRegistrationInvitation $invitation
     * @param User $user
     * @param Player $player
     * @return void
     */
    protected function sendRegistrationNotifications(PlayerRegistrationInvitation $invitation, User $user, Player $player): void
    {
        // Welcome notification to new player
        if ($user->email) {
            $user->notify(new RegistrationWelcomeNotification($invitation->club, $invitation->targetTeam));
        }

        // Notification to trainer who created the invitation
        if ($invitation->creator && $invitation->creator->email) {
            $invitation->creator->notify(new PlayerRegisteredNotification($player, $invitation));
        }

        // Optional: Notify club admins
        $clubAdmins = User::role('club_admin')
            ->whereHas('clubs', function ($query) use ($invitation) {
                $query->where('clubs.id', $invitation->club_id);
            })
            ->get();

        foreach ($clubAdmins as $admin) {
            if ($admin->email) {
                $admin->notify(new PlayerRegisteredNotification($player, $invitation));
            }
        }
    }
}
