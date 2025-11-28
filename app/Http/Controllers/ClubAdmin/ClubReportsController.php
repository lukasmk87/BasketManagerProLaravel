<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Services\Club\ClubStatisticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubReportsController extends Controller
{
    public function __construct(
        private ClubStatisticsService $clubStatisticsService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Show reports and statistics page.
     */
    public function __invoke(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        try {
            $clubStats = $this->clubStatisticsService->getClubStatistics($primaryClub);

            return Inertia::render('ClubAdmin/Reports/Index', [
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                ],
                'statistics' => $clubStats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load club reports', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('ClubAdmin/Reports/Index', [
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                ],
                'error' => 'Statistiken konnten nicht geladen werden.',
            ]);
        }
    }
}
