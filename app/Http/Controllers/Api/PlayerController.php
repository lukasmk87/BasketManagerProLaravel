<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    /**
     * Display a listing of the players.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with(['user', 'club', 'teams']);
        
        // Apply filters if provided
        if ($request->filled('club_id')) {
            $query->where('club_id', $request->club_id);
        }
        
        if ($request->filled('team_id')) {
            $query->whereHas('teams', function($q) use ($request) {
                $q->where('id', $request->team_id);
            });
        }
        
        $players = $query->paginate($request->get('per_page', 15));
        
        return response()->json($players);
    }

    /**
     * Store a newly created player.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jersey_number' => 'required|integer|min:0|max:99',
            'position' => 'nullable|string|in:PG,SG,SF,PF,C',
            'height' => 'nullable|integer|min:140|max:250',
            'weight' => 'nullable|integer|min:40|max:200',
            'dominant_hand' => 'nullable|string|in:left,right,both',
            'club_id' => 'required|exists:clubs,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $player = Player::create($validated);
        $player->load(['user', 'club', 'teams']);

        return response()->json($player, 201);
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): JsonResponse
    {
        $player->load(['user', 'club', 'teams', 'statistics']);
        
        return response()->json($player);
    }

    /**
     * Update the specified player.
     */
    public function update(Request $request, Player $player): JsonResponse
    {
        $validated = $request->validate([
            'jersey_number' => 'sometimes|integer|min:0|max:99',
            'position' => 'nullable|string|in:PG,SG,SF,PF,C',
            'height' => 'nullable|integer|min:140|max:250',
            'weight' => 'nullable|integer|min:40|max:200',
            'dominant_hand' => 'nullable|string|in:left,right,both',
            'club_id' => 'sometimes|exists:clubs,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $player->update($validated);
        $player->load(['user', 'club', 'teams']);

        return response()->json($player);
    }

    /**
     * Get games for the specified player.
     */
    public function games(Player $player): JsonResponse
    {
        $games = $player->games()
            ->with(['homeTeam', 'awayTeam', 'statistics'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(15);
            
        return response()->json($games);
    }
}