<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\StoreClubMemberRequest;
use App\Http\Requests\ClubAdmin\UpdateClubMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ClubMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show club members management page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $members = $primaryClub->users()
            ->select(['users.id', 'users.name', 'users.email', 'users.is_active'])
            ->withPivot('role', 'joined_at', 'is_active')
            ->with('roles:id,name')
            ->orderBy('pivot_joined_at', 'desc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'club_role' => $member->pivot->role,
                    'membership_is_active' => $member->pivot->is_active,
                    'joined_at' => $member->pivot->joined_at,
                    'roles' => $member->roles->pluck('name')->toArray(),
                    'is_active' => $member->is_active,
                ];
            });

        return Inertia::render('ClubAdmin/Members/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'members' => $members,
            'available_roles' => ['member', 'player', 'trainer', 'team_manager', 'scorer'],
        ]);
    }

    /**
     * Show the create member form.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $availableRoles = [
            ['value' => 'member', 'label' => 'Mitglied'],
            ['value' => 'player', 'label' => 'Spieler'],
            ['value' => 'trainer', 'label' => 'Trainer'],
            ['value' => 'assistant_coach', 'label' => 'Co-Trainer'],
            ['value' => 'team_manager', 'label' => 'Team Manager'],
            ['value' => 'scorer', 'label' => 'Anschreiber'],
            ['value' => 'volunteer', 'label' => 'Freiwilliger'],
        ];

        return Inertia::render('ClubAdmin/Members/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'available_roles' => $availableRoles,
        ]);
    }

    /**
     * Store a newly created member.
     */
    public function store(StoreClubMemberRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $validated = $request->validated();

        try {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $primaryClub->users()->attach($newUser->id, [
                'role' => $validated['club_role'],
                'joined_at' => now(),
                'is_active' => true,
            ]);

            $spatieRole = match ($validated['club_role']) {
                'trainer', 'assistant_coach' => 'trainer',
                'player' => 'player',
                default => 'guest',
            };
            $newUser->assignRole($spatieRole);

            Log::info('Club admin created new member', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'new_user_id' => $newUser->id,
                'club_role' => $validated['club_role'],
            ]);

            return redirect()->route('club-admin.members.index')
                ->with('success', 'Mitglied wurde erfolgreich hinzugefügt.');
        } catch (\Exception $e) {
            Log::error('Failed to create club member', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Hinzufügen des Mitglieds: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit member form.
     */
    public function edit(User $member): Response
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $this->authorize('update', $member);

        $member->load('roles:id,name');

        $clubMembership = $member->clubs()
            ->where('clubs.id', $primaryClub->id)
            ->withPivot('role', 'joined_at', 'is_active')
            ->first();

        if (! $clubMembership) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        $availableRoles = [
            ['value' => 'member', 'label' => 'Mitglied'],
            ['value' => 'player', 'label' => 'Spieler'],
            ['value' => 'trainer', 'label' => 'Trainer'],
            ['value' => 'assistant_coach', 'label' => 'Co-Trainer'],
            ['value' => 'team_manager', 'label' => 'Team Manager'],
            ['value' => 'scorer', 'label' => 'Anschreiber'],
            ['value' => 'volunteer', 'label' => 'Freiwilliger'],
        ];

        return Inertia::render('ClubAdmin/Members/Edit', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'club_role' => $clubMembership->pivot->role,
                'is_active' => $member->is_active,
                'membership_is_active' => $clubMembership->pivot->is_active,
                'joined_at' => $clubMembership->pivot->joined_at,
                'roles' => $member->roles->pluck('name')->toArray(),
            ],
            'available_roles' => $availableRoles,
        ]);
    }

    /**
     * Update an existing member.
     */
    public function update(UpdateClubMemberRequest $request, User $member): RedirectResponse
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $this->authorize('update', $member);

        if (! $member->clubs()->where('clubs.id', $primaryClub->id)->exists()) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        $validated = $request->validated();

        try {
            $member->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $member->clubs()->updateExistingPivot($primaryClub->id, [
                'role' => $validated['club_role'],
                'is_active' => $validated['membership_is_active'] ?? true,
            ]);

            $newSpatieRole = match ($validated['club_role']) {
                'trainer', 'assistant_coach' => 'trainer',
                'player' => 'player',
                default => 'guest',
            };
            $member->syncRoles([$newSpatieRole]);

            Log::info('Club admin updated member', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $member->id,
                'club_role' => $validated['club_role'],
            ]);

            return redirect()->route('club-admin.members.index')
                ->with('success', 'Mitglied wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club member', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Mitglieds: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Send password reset link to a member.
     */
    public function sendPasswordReset(User $member): RedirectResponse
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $this->authorize('sendPasswordReset', $member);

        if (! $member->clubs()->where('clubs.id', $primaryClub->id)->exists()) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        try {
            $status = Password::sendResetLink(
                ['email' => $member->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                Log::info('Club admin sent password reset', [
                    'club_admin_id' => $authUser->id,
                    'club_id' => $primaryClub->id,
                    'user_id' => $member->id,
                ]);

                return back()->with('success', 'Passwort-Reset-Link wurde erfolgreich gesendet.');
            }

            return back()->with('error', 'Fehler beim Senden des Passwort-Reset-Links.');
        } catch (\Exception $e) {
            Log::error('Failed to send password reset', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Senden des Passwort-Reset-Links: ' . $e->getMessage());
        }
    }
}
