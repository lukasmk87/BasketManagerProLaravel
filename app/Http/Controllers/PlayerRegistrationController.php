<?php

namespace App\Http\Controllers;

use App\Models\PlayerRegistrationInvitation;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Services\PlayerRegistrationService;
use App\Http\Requests\StorePlayerRegistrationInvitationRequest;
use App\Http\Requests\SubmitPlayerRegistrationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerRegistrationController extends Controller
{
    protected PlayerRegistrationService $registrationService;

    public function __construct(PlayerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    // ============================================
    // TRAINER SECTION (AUTH + PERMISSION REQUIRED)
    // ============================================

    /**
     * Display a listing of player registration invitations.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PlayerRegistrationInvitation::class);

        $user = Auth::user();

        // Get user's clubs (trainer or club admin)
        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

        // Query invitations
        $query = PlayerRegistrationInvitation::with(['club', 'creator', 'targetTeam', 'registeredPlayers'])
            ->whereIn('club_id', $userClubIds)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('filter')) {
            $filter = $request->get('filter');
            if ($filter === 'active') {
                $query->active();
            } elseif ($filter === 'expired') {
                $query->expired();
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('invitation_token', 'like', "%{$search}%")
                  ->orWhereHas('targetTeam', function ($teamQuery) use ($search) {
                      $teamQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invitations = $query->paginate(15)->withQueryString();

        return Inertia::render('Trainer/PlayerInvitations/Index', [
            'invitations' => $invitations,
            'filters' => [
                'filter' => $request->get('filter'),
                'search' => $request->get('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create(): Response
    {
        $this->authorize('create', PlayerRegistrationInvitation::class);

        $user = Auth::user();

        // Get clubs the user has access to
        $clubs = $user->clubs()->get();

        // Get teams for those clubs
        $teams = BasketballTeam::whereIn('club_id', $clubs->pluck('id'))->get();

        return Inertia::render('Trainer/PlayerInvitations/Create', [
            'clubs' => $clubs,
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created invitation.
     */
    public function store(StorePlayerRegistrationInvitationRequest $request): RedirectResponse
    {
        // Authorization is handled by the Form Request

        try {
            $invitation = $this->registrationService->createInvitation(
                Auth::id(),
                $request->validated('club_id'),
                [
                    'target_team_id' => $request->validated('target_team_id'),
                    'expires_at' => $request->validated('expires_at'),
                    'max_registrations' => $request->validated('max_registrations', 50),
                    'qr_size' => $request->validated('qr_size', 300),
                    'settings' => $request->validated('settings', []),
                ]
            );

            return redirect()
                ->route('trainer.invitations.show', $invitation)
                ->with('success', __('Invitation created successfully! Share the QR code or link with potential players.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to create invitation: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified invitation with statistics.
     */
    public function show(PlayerRegistrationInvitation $invitation): Response
    {
        $this->authorize('view', $invitation);

        $invitation->load(['club', 'creator', 'targetTeam', 'registeredPlayers.user']);

        $statistics = $invitation->getStatistics();

        return Inertia::render('Trainer/PlayerInvitations/Show', [
            'invitation' => $invitation,
            'statistics' => $statistics,
            'registeredPlayers' => $invitation->registeredPlayers,
        ]);
    }

    /**
     * Deactivate the specified invitation.
     */
    public function destroy(PlayerRegistrationInvitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        try {
            $this->registrationService->deactivateInvitation($invitation->id);

            return redirect()
                ->route('trainer.invitations.index')
                ->with('success', __('Invitation deactivated successfully.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', __('Failed to deactivate invitation: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Download QR code in specified format.
     */
    public function downloadQR(PlayerRegistrationInvitation $invitation, string $format = 'png'): BinaryFileResponse
    {
        $this->authorize('view', $invitation);

        // Validate format
        if (!in_array($format, ['png', 'svg', 'pdf'])) {
            abort(400, 'Invalid format. Supported formats: png, svg, pdf');
        }

        // Check if QR code exists (use public disk)
        if (!$invitation->qr_code_path || !Storage::disk('public')->exists($invitation->qr_code_path)) {
            abort(404, 'QR code not found');
        }

        $filePath = storage_path('app/public/' . $invitation->qr_code_path);
        $fileName = "player_registration_qr_{$invitation->id}.{$format}";

        return response()->download($filePath, $fileName);
    }

    // ============================================
    // PUBLIC SECTION (NO AUTH REQUIRED)
    // ============================================

    /**
     * Show the public registration form.
     */
    public function showRegistrationForm(string $token): Response
    {
        // Validate token
        $validation = $this->registrationService->validateToken($token);

        if (!$validation['valid']) {
            return Inertia::render('Public/RegistrationError', [
                'error' => $validation['error'],
            ]);
        }

        $invitation = $validation['invitation'];
        $invitation->load(['club', 'targetTeam']);

        return Inertia::render('Public/PlayerRegistration', [
            'invitation' => [
                'token' => $invitation->invitation_token,
                'club' => [
                    'id' => $invitation->club->id,
                    'name' => $invitation->club->name,
                    'logo_url' => $invitation->club->logo_path
                        ? Storage::url($invitation->club->logo_path)
                        : null,
                ],
                'target_team' => $invitation->targetTeam ? [
                    'id' => $invitation->targetTeam->id,
                    'name' => $invitation->targetTeam->name,
                ] : null,
                'expires_at' => $invitation->expires_at->toISOString(),
                'remaining_spots' => $invitation->remaining_registrations,
            ],
        ]);
    }

    /**
     * Submit the public registration form.
     */
    public function submitRegistration(string $token, SubmitPlayerRegistrationRequest $request): RedirectResponse
    {
        // Validation is handled by the Form Request
        $result = $this->registrationService->registerPlayer(
            $token,
            $request->validated()
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['error']);
        }

        // Redirect to success page
        return redirect()
            ->route('public.player.success', ['token' => $token])
            ->with('success', __('Registration successful! The club administrator will review your registration and assign you to a team.'));
    }

    /**
     * Show registration success page.
     */
    public function success(string $token): Response
    {
        $validation = $this->registrationService->validateToken($token);

        $clubName = $validation['invitation']?->club->name ?? 'Basketball Club';

        return Inertia::render('Public/RegistrationSuccess', [
            'clubName' => $clubName,
        ]);
    }
}
