<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\GymHall;
use App\Models\GymBooking;
use App\Models\GymBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GymDashboardController extends Controller
{
    /**
     * Display the gym management dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['tenant_admin', 'super_admin']);
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        // For admins, load all clubs for selection
        $availableClubs = [];
        if ($isAdmin) {
            $availableClubs = Club::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($club) => ['id' => $club->id, 'name' => $club->name])
                ->toArray();
        }

        // Get gym halls for the user's club
        $gymHalls = collect();
        if ($userClub) {
            $gymHalls = GymHall::where('club_id', $userClub->id)
                ->with(['timeSlots', 'bookings.team'])
                ->orderBy('name')
                ->get();
        } elseif ($isAdmin) {
            // Super Admin without club: Load all halls from all clubs
            $gymHalls = GymHall::with(['timeSlots', 'bookings.team', 'club:id,name'])
                ->orderBy('name')
                ->get();
        }

        // Get statistics
        $stats = $this->getGymStatistics($userClub);

        // Get pending booking requests for club admins
        $pendingRequests = collect();
        if ($userClub && $user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            $pendingRequests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->where('status', 'pending')
                ->with(['team', 'timeSlot'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get user permissions
        $userPermissions = [
            'canManageHalls' => $user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin']),
            'canBookHalls' => $user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin', 'trainer', 'team_manager']),
            'canApproveRequests' => $user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin']),
        ];

        return Inertia::render('Gym/Dashboard', [
            'gymHalls' => $gymHalls,
            'initialStats' => $stats,
            'pendingRequests' => $pendingRequests,
            'userPermissions' => $userPermissions,
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
            'availableClubs' => $availableClubs,
        ]);
    }

    /**
     * Show the form for creating a new gym hall.
     */
    public function create(): Response
    {
        $this->authorize('create', GymHall::class);

        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['tenant_admin', 'super_admin']);
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        // For admins, load all clubs for selection
        $availableClubs = [];
        if ($isAdmin) {
            $availableClubs = Club::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($club) => ['id' => $club->id, 'name' => $club->name])
                ->toArray();
        }

        return Inertia::render('Gym/CreateHall', [
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
            'availableClubs' => $availableClubs,
        ]);
    }

    /**
     * Show halls management page.
     */
    public function halls(): Response
    {
        $this->authorize('viewAny', GymHall::class);

        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['tenant_admin', 'super_admin']);
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        // For admins without a club, load all gym halls grouped by club
        $gymHalls = collect();
        $availableClubs = [];

        if ($isAdmin) {
            // Admin/Superadmin: Load all clubs for dropdown
            $availableClubs = Club::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($club) => ['id' => $club->id, 'name' => $club->name])
                ->toArray();

            // Load gym halls for current club (if any) or all halls for super_admin
            if ($userClub) {
                $gymHalls = GymHall::where('club_id', $userClub->id)
                    ->withCount(['timeSlots', 'bookings'])
                    ->with([
                        'timeSlots' => function ($query) {
                            $query->orderBy('day_of_week')->orderBy('start_time');
                        },
                        'courts' => function ($query) {
                            $query->active()->orderBy('sort_order');
                        },
                        'club:id,name'
                    ])
                    ->orderBy('name')
                    ->get();
            } else {
                // Super Admin without club: Load all halls from all clubs
                $gymHalls = GymHall::withCount(['timeSlots', 'bookings'])
                    ->with([
                        'timeSlots' => function ($query) {
                            $query->orderBy('day_of_week')->orderBy('start_time');
                        },
                        'courts' => function ($query) {
                            $query->active()->orderBy('sort_order');
                        },
                        'club:id,name'
                    ])
                    ->orderBy('name')
                    ->get();
            }
        } elseif ($userClub) {
            // Regular user with club
            $gymHalls = GymHall::where('club_id', $userClub->id)
                ->withCount(['timeSlots', 'bookings'])
                ->with([
                    'timeSlots' => function ($query) {
                        $query->orderBy('day_of_week')->orderBy('start_time');
                    },
                    'courts' => function ($query) {
                        $query->active()->orderBy('sort_order');
                    }
                ])
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Gym/Halls', [
            'gymHalls' => $gymHalls,
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
            'availableClubs' => $availableClubs,
        ]);
    }

    /**
     * Show bookings management page.
     */
    public function bookings(): Response
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        $bookings = collect();
        if ($userClub) {
            $query = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            });

            // Filter by user's team if not admin
            if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
                $userTeam = $user->currentTeam;
                if ($userTeam) {
                    $query->where('team_id', $userTeam->id);
                }
            }

            $bookings = $query->with(['gymTimeSlot.gymHall', 'team'])
                ->orderBy('booking_date', 'desc')
                ->orderBy('start_time')
                ->paginate(20);
        }

        return Inertia::render('Gym/Bookings', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Show booking requests management page.
     */
    public function requests(): Response
    {
        $this->authorize('viewAny', GymBookingRequest::class);

        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        $requests = collect();
        if ($userClub) {
            $requests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->with(['team', 'timeSlot', 'requestedBy'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return Inertia::render('Gym/Requests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Get gym statistics for the dashboard.
     */
    private function getGymStatistics($club): array
    {
        if (!$club) {
            return [
                'total_halls' => 0,
                'active_bookings' => 0,
                'pending_requests' => 0,
                'utilization_rate' => 0,
            ];
        }

        $totalHalls = GymHall::where('club_id', $club->id)->count();

        $activeBookings = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('booking_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();

        $pendingRequests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('status', 'pending')
            ->count();

        // Calculate utilization rate (simplified)
        $utilizationRate = 0;
        if ($totalHalls > 0) {
            $totalPossibleSlots = $totalHalls * 7 * 12; // 7 days, 12 possible time slots per day
            $bookedSlots = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
                    $query->where('club_id', $club->id);
                })
                ->where('booking_date', '>=', now()->startOfWeek())
                ->where('booking_date', '<=', now()->endOfWeek())
                ->where('status', 'confirmed')
                ->count();

            $utilizationRate = $totalPossibleSlots > 0 ? round(($bookedSlots / $totalPossibleSlots) * 100, 1) : 0;
        }

        return [
            'total_halls' => $totalHalls,
            'active_bookings' => $activeBookings,
            'pending_requests' => $pendingRequests,
            'utilization_rate' => $utilizationRate,
        ];
    }
}
