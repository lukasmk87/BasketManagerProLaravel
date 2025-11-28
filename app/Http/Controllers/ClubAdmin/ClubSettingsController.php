<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClubAdmin\UpdateClubSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show club settings page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        return Inertia::render('ClubAdmin/Settings', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
                'short_name' => $primaryClub->short_name,
                'logo_url' => $primaryClub->logo_url,
                'description' => $primaryClub->description,
                'website' => $primaryClub->website,
                'email' => $primaryClub->email,
                'phone' => $primaryClub->phone,
                'address' => $primaryClub->address,
                'city' => $primaryClub->city,
                'postal_code' => $primaryClub->postal_code,
                'country' => $primaryClub->country,
                'facebook_url' => $primaryClub->facebook_url,
                'twitter_url' => $primaryClub->twitter_url,
                'instagram_url' => $primaryClub->instagram_url,
                'is_active' => $primaryClub->is_active,
                'is_verified' => $primaryClub->is_verified,
            ],
        ]);
    }

    /**
     * Update club settings.
     */
    public function update(UpdateClubSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        try {
            $primaryClub->update($request->validated());

            return redirect()->route('club-admin.settings')
                ->with('success', 'Club-Einstellungen erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club settings', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren der Einstellungen.')
                ->withInput();
        }
    }
}
