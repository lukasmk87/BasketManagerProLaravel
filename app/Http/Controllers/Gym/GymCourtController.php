<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\GymCourt;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class GymCourtController extends Controller
{
    /**
     * Get teams for team selection in gym management.
     */
    public function getTeams(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$userClub) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Verein gefunden.'
            ], 404);
        }

        $teams = Team::where('club_id', $userClub->id)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    /**
     * Get courts for a specific gym hall.
     */
    public function getHallCourts(Request $request, $hallId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $courts = $hall->courts()
            ->orderBy('sort_order')
            ->orderBy('court_number')
            ->get(['id', 'name', 'court_number', 'is_active', 'is_main_court', 'metadata']);

        return response()->json([
            'success' => true,
            'data' => $courts->map(function ($court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'court_number' => $court->court_number,
                    'is_active' => $court->is_active,
                    'is_main_court' => $court->is_main_court,
                    'court_identifier' => $court->court_identifier,
                    'color_code' => $court->color_code,
                ];
            }),
        ]);
    }

    /**
     * Update a gym court.
     */
    public function updateCourt(Request $request, $courtId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Bearbeiten von Feldern.'
            ], 403);
        }

        $court = GymCourt::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $courtId)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_main_court' => 'boolean',
        ]);

        // Handle main court setting
        if ($request->has('is_main_court') && $request->is_main_court) {
            // Set as main court (automatically unsets other main courts)
            $court->setAsMainCourt();
        } elseif ($request->has('is_main_court') && !$request->is_main_court && $court->is_main_court) {
            // Unset as main court
            $court->unsetAsMainCourt();
        }

        $court->update([
            'name' => $request->name,
            'is_active' => $request->is_active ?? $court->is_active,
        ]);

        // Refresh to get updated main court status
        $court->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Feld erfolgreich aktualisiert.',
            'data' => [
                'id' => $court->id,
                'name' => $court->name,
                'court_number' => $court->court_number,
                'is_active' => $court->is_active,
                'is_main_court' => $court->is_main_court,
                'court_identifier' => $court->court_identifier,
                'color_code' => $court->color_code,
            ]
        ]);
    }

    /**
     * Create a new court for a gym hall.
     */
    public function createCourt(Request $request, $hallId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Erstellen von Feldern.'
            ], 403);
        }

        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'court_number' => 'required|integer|min:1|unique:gym_courts,court_number,NULL,id,gym_hall_id,' . $hall->id,
        ]);

        $court = $hall->courts()->create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'court_number' => $request->court_number,
            'is_active' => true,
            'sort_order' => $request->court_number,
            'metadata' => [
                'identifier' => (string) $request->court_number,
                'color_code' => '#3B82F6',
                'court_type' => 'full',
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feld erfolgreich erstellt.',
            'data' => [
                'id' => $court->id,
                'name' => $court->name,
                'court_number' => $court->court_number,
                'is_active' => $court->is_active,
                'is_main_court' => $court->is_main_court,
                'court_identifier' => $court->court_identifier,
                'color_code' => $court->color_code,
            ]
        ]);
    }
}
