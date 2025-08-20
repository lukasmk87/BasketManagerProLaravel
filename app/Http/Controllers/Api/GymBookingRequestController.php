<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GymBookingRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of gym booking requests.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'team_id' => 'nullable|exists:teams,id',
            'gym_hall_id' => 'nullable|exists:gym_halls,id',
        ]);

        $user = Auth::user();
        $clubId = $user->currentTeam?->club_id ?? $user->clubs()->first()?->id;

        // If no club association, return empty list instead of error
        if (!$clubId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                ],
                'message' => 'No club associated with user'
            ]);
        }

        $query = GymBookingRequest::whereHas('gymHall', function ($query) use ($clubId) {
            $query->where('club_id', $clubId);
        });

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by team if provided
        if ($request->has('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        // Filter by gym hall if provided
        if ($request->has('gym_hall_id')) {
            $query->where('gym_hall_id', $request->input('gym_hall_id'));
        }

        $requests = $query->with(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    /**
     * Store a newly created booking request.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'gym_hall_id' => 'required|exists:gym_halls,id',
            'team_id' => 'required|exists:teams,id',
            'requested_date' => 'required|date|after_or_equal:today',
            'requested_start_time' => 'required|date_format:H:i',
            'requested_end_time' => 'required|date_format:H:i|after:requested_start_time',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $bookingRequest = GymBookingRequest::create([
            'gym_hall_id' => $request->input('gym_hall_id'),
            'team_id' => $request->input('team_id'),
            'requested_by' => Auth::id(),
            'requested_date' => $request->input('requested_date'),
            'requested_start_time' => $request->input('requested_start_time'),
            'requested_end_time' => $request->input('requested_end_time'),
            'purpose' => $request->input('purpose'),
            'notes' => $request->input('notes'),
            'priority' => $request->input('priority', 'normal'),
            'status' => 'pending',
        ]);

        $bookingRequest->load(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request created successfully',
            'data' => $bookingRequest
        ], 201);
    }

    /**
     * Display the specified booking request.
     */
    public function show(GymBookingRequest $gymBookingRequest): JsonResponse
    {
        $this->authorize('view', $gymBookingRequest);

        $gymBookingRequest->load(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'data' => $gymBookingRequest
        ]);
    }

    /**
     * Update the specified booking request.
     */
    public function update(Request $request, GymBookingRequest $gymBookingRequest): JsonResponse
    {
        $this->authorize('update', $gymBookingRequest);

        $request->validate([
            'requested_date' => 'nullable|date|after_or_equal:today',
            'requested_start_time' => 'nullable|date_format:H:i',
            'requested_end_time' => 'nullable|date_format:H:i|after:requested_start_time',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $gymBookingRequest->update($request->only([
            'requested_date',
            'requested_start_time',
            'requested_end_time',
            'purpose',
            'notes',
            'priority'
        ]));

        $gymBookingRequest->load(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request updated successfully',
            'data' => $gymBookingRequest
        ]);
    }

    /**
     * Remove the specified booking request.
     */
    public function destroy(GymBookingRequest $gymBookingRequest): JsonResponse
    {
        $this->authorize('delete', $gymBookingRequest);

        $gymBookingRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking request deleted successfully'
        ]);
    }

    /**
     * Approve a booking request.
     */
    public function approve(Request $request, GymBookingRequest $gymBookingRequest): JsonResponse
    {
        $this->authorize('approve', $gymBookingRequest);

        $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $gymBookingRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        $gymBookingRequest->load(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request approved successfully',
            'data' => $gymBookingRequest
        ]);
    }

    /**
     * Reject a booking request.
     */
    public function reject(Request $request, GymBookingRequest $gymBookingRequest): JsonResponse
    {
        $this->authorize('reject', $gymBookingRequest);

        $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $gymBookingRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        $gymBookingRequest->load(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Booking request rejected',
            'data' => $gymBookingRequest
        ]);
    }

    /**
     * Get booking requests for a specific team.
     */
    public function forTeam(Request $request, $teamId): JsonResponse
    {
        $requests = GymBookingRequest::where('team_id', $teamId)
            ->with(['gymHall:id,name', 'requestedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }

    /**
     * Get booking requests made by a specific team.
     */
    public function byTeam(Request $request, $teamId): JsonResponse
    {
        $requests = GymBookingRequest::where('team_id', $teamId)
            ->with(['gymHall:id,name', 'requestedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ]
        ]);
    }
}