<?php

namespace App\Http\Controllers;

use App\Models\ClubInvitation;
use App\Models\Club;
use App\Services\ClubInvitationService;
use App\Http\Requests\StoreClubInvitationRequest;
use App\Http\Requests\SubmitClubRegistrationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClubInvitationController extends Controller
{
    protected ClubInvitationService $invitationService;

    public function __construct(ClubInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    // ============================================
    // CLUB ADMIN SECTION (AUTH + PERMISSION REQUIRED)
    // ============================================

    /**
     * Display a listing of club invitations.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClubInvitation::class);

        $user = Auth::user();

        // Get user's clubs (club admin)
        $userClubIds = $user->getAdministeredClubIds();

        // Query invitations
        $query = ClubInvitation::with(['club', 'creator'])
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
                  ->orWhereHas('club', function ($clubQuery) use ($search) {
                      $clubQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invitations = $query->paginate(15)->withQueryString();

        return Inertia::render('ClubAdmin/Invitations/Index', [
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
        $this->authorize('create', ClubInvitation::class);

        $user = Auth::user();

        // Get clubs the user has access to
        $clubs = $user->getAdministeredClubs(false);

        return Inertia::render('ClubAdmin/Invitations/Create', [
            'clubs' => $clubs->map(fn($club) => [
                'id' => $club->id,
                'name' => $club->name,
                'logo_url' => $club->logo_url,
            ]),
            'available_roles' => [
                ['value' => 'member', 'label' => 'Mitglied'],
                ['value' => 'player', 'label' => 'Spieler'],
                ['value' => 'parent', 'label' => 'Elternteil'],
                ['value' => 'volunteer', 'label' => 'Freiwilliger'],
                ['value' => 'sponsor', 'label' => 'Sponsor'],
            ],
        ]);
    }

    /**
     * Store a newly created invitation.
     */
    public function store(StoreClubInvitationRequest $request): RedirectResponse
    {
        // Authorization is handled by the Form Request

        try {
            $invitation = $this->invitationService->createInvitation(
                Auth::id(),
                $request->validated('club_id'),
                [
                    'default_role' => $request->validated('default_role'),
                    'expires_at' => $request->validated('expires_at'),
                    'max_uses' => $request->validated('max_uses', 100),
                    'qr_size' => $request->validated('qr_size', 300),
                    'qr_format' => $request->validated('qr_format', 'svg'),
                    'settings' => $request->validated('settings', []),
                ]
            );

            return redirect()
                ->route('club-admin.invitations.show', $invitation)
                ->with('success', __('Einladung erfolgreich erstellt! Teilen Sie den QR-Code oder Link mit potenziellen Mitgliedern.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Fehler beim Erstellen der Einladung: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified invitation with statistics.
     */
    public function show(ClubInvitation $invitation): Response
    {
        $this->authorize('view', $invitation);

        $invitation->load(['club', 'creator']);

        $statistics = $invitation->getStatistics();

        return Inertia::render('ClubAdmin/Invitations/Show', [
            'invitation' => [
                'id' => $invitation->id,
                'invitation_token' => $invitation->invitation_token,
                'registration_url' => $invitation->registration_url,
                'qr_code_url' => $invitation->qr_code_path ? Storage::disk('public')->url($invitation->qr_code_path) : null,
                'club' => [
                    'id' => $invitation->club->id,
                    'name' => $invitation->club->name,
                    'logo_url' => $invitation->club->logo_url,
                ],
                'creator' => [
                    'id' => $invitation->creator->id,
                    'name' => $invitation->creator->name,
                ],
                'default_role' => $invitation->default_role,
                'expires_at' => $invitation->expires_at->toISOString(),
                'current_uses' => $invitation->current_uses,
                'max_uses' => $invitation->max_uses,
                'is_active' => $invitation->is_active,
                'created_at' => $invitation->created_at->toISOString(),
            ],
            'statistics' => $statistics,
        ]);
    }

    /**
     * Deactivate the specified invitation.
     */
    public function destroy(ClubInvitation $invitation): RedirectResponse
    {
        $this->authorize('delete', $invitation);

        try {
            $this->invitationService->deactivateInvitation($invitation->id);

            return redirect()
                ->route('club-admin.invitations.index')
                ->with('success', __('Einladung erfolgreich deaktiviert.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', __('Fehler beim Deaktivieren der Einladung: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Download QR code in specified format.
     */
    public function downloadQR(ClubInvitation $invitation, string $format = 'png'): BinaryFileResponse
    {
        $this->authorize('view', $invitation);

        // Validate format
        if (!in_array($format, ['png', 'svg', 'pdf'])) {
            abort(400, 'Ungültiges Format. Unterstützte Formate: png, svg, pdf');
        }

        // Check if QR code exists (use public disk)
        if (!$invitation->qr_code_path || !Storage::disk('public')->exists($invitation->qr_code_path)) {
            abort(404, 'QR-Code nicht gefunden');
        }

        $filePath = storage_path('app/public/' . $invitation->qr_code_path);
        $fileName = "club_invitation_qr_{$invitation->id}.{$format}";

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
        $validation = $this->invitationService->validateToken($token);

        if (!$validation['valid']) {
            return Inertia::render('Public/RegistrationError', [
                'error' => $validation['error'],
            ]);
        }

        $invitation = $validation['invitation'];
        $invitation->load(['club']);

        return Inertia::render('Public/ClubRegistration', [
            'invitation' => [
                'token' => $invitation->invitation_token,
                'club' => [
                    'id' => $invitation->club->id,
                    'name' => $invitation->club->name,
                    'logo_url' => $invitation->club->logo_path
                        ? Storage::url($invitation->club->logo_path)
                        : null,
                    'description' => $invitation->club->description,
                ],
                'default_role' => $invitation->default_role,
                'expires_at' => $invitation->expires_at->toISOString(),
                'remaining_uses' => $invitation->remaining_uses,
            ],
        ]);
    }

    /**
     * Submit the public registration form.
     */
    public function submitRegistration(string $token, SubmitClubRegistrationRequest $request): RedirectResponse
    {
        // Validation is handled by the Form Request
        $result = $this->invitationService->registerUserWithClub(
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
            ->route('public.club.success', ['token' => $token])
            ->with('success', __('Registrierung erfolgreich! Sie können sich nun anmelden.'));
    }

    /**
     * Show registration success page.
     */
    public function success(string $token): Response
    {
        $validation = $this->invitationService->validateToken($token);

        $clubName = $validation['invitation']?->club->name ?? 'Basketball Club';

        return Inertia::render('Public/ClubRegistrationSuccess', [
            'clubName' => $clubName,
        ]);
    }
}
