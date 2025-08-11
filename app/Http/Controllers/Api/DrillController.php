<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drill;
use App\Models\Team;
use App\Http\Requests\Drill\CreateDrillRequest;
use App\Http\Requests\Drill\UpdateDrillRequest;
use App\Http\Resources\DrillResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DrillController extends Controller
{
    /**
     * Display a listing of drills.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Drill::with(['createdBy', 'ratings']);

        // Filter by visibility
        if (!$request->has('include_private')) {
            $query->public();
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }

        // Filter by age group
        if ($request->has('age_group')) {
            $query->byAgeGroup($request->age_group);
        }

        // Filter by player count
        if ($request->has('player_count')) {
            $query->forPlayerCount($request->player_count);
        }

        // Filter by duration
        if ($request->has('max_duration')) {
            $query->where('estimated_duration', '<=', $request->max_duration);
        }

        // Filter by equipment requirements
        if ($request->has('no_equipment')) {
            $query->where(function ($q) {
                $q->whereNull('required_equipment')
                  ->orWhereJsonLength('required_equipment', 0);
            });
        }

        // Filter by court requirements
        if ($request->has('court_type')) {
            match ($request->court_type) {
                'full_court' => $query->where('requires_full_court', true),
                'half_court' => $query->where('requires_half_court', true),
                'no_court' => $query->where('requires_full_court', false)
                                   ->where('requires_half_court', false),
                default => null,
            };
        }

        // Search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('objectives', 'like', "%{$searchTerm}%")
                  ->orWhere('search_keywords', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tags = explode(',', $request->tags);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        // Special filters
        if ($request->has('filter')) {
            match ($request->filter) {
                'featured' => $query->featured(),
                'popular' => $query->popular(),
                'highly_rated' => $query->highlyRated(),
                'recently_added' => $query->where('created_at', '>=', now()->subDays(30)),
                default => null,
            };
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        match ($sortBy) {
            'rating' => $query->orderBy('average_rating', $sortOrder)->orderBy('rating_count', 'desc'),
            'popularity' => $query->orderBy('usage_count', $sortOrder),
            'duration' => $query->orderBy('estimated_duration', $sortOrder),
            default => $query->orderBy($sortBy, $sortOrder),
        };

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $drills = $query->paginate($perPage);

        return response()->json([
            'data' => DrillResource::collection($drills->items()),
            'meta' => [
                'current_page' => $drills->currentPage(),
                'last_page' => $drills->lastPage(),
                'per_page' => $drills->perPage(),
                'total' => $drills->total(),
            ]
        ]);
    }

    /**
     * Store a newly created drill.
     */
    public function store(CreateDrillRequest $request): JsonResponse
    {
        try {
            $drillData = array_merge($request->validated(), [
                'created_by_user_id' => auth()->id(),
                'status' => 'draft',
            ]);

            $drill = Drill::create($drillData);

            return response()->json([
                'message' => 'Drill erfolgreich erstellt',
                'data' => new DrillResource($drill)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified drill.
     */
    public function show(Drill $drill): JsonResponse
    {
        $drill->load(['createdBy', 'ratings.user', 'favorites']);

        return response()->json([
            'data' => new DrillResource($drill),
            'similar_drills' => DrillResource::collection($drill->getSimilarDrills()),
        ]);
    }

    /**
     * Update the specified drill.
     */
    public function update(UpdateDrillRequest $request, Drill $drill): JsonResponse
    {
        try {
            // Check if user can update this drill
            if ($drill->created_by_user_id !== auth()->id() && !auth()->user()->can('edit-drills')) {
                return response()->json([
                    'message' => 'Keine Berechtigung zum Bearbeiten dieses Drills'
                ], 403);
            }

            $drill->update($request->validated());

            return response()->json([
                'message' => 'Drill erfolgreich aktualisiert',
                'data' => new DrillResource($drill)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified drill.
     */
    public function destroy(Drill $drill): JsonResponse
    {
        try {
            // Check if user can delete this drill
            if ($drill->created_by_user_id !== auth()->id() && !auth()->user()->can('delete-drills')) {
                return response()->json([
                    'message' => 'Keine Berechtigung zum Löschen dieses Drills'
                ], 403);
            }

            $drill->delete();

            return response()->json([
                'message' => 'Drill erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Duplicate a drill.
     */
    public function duplicate(Drill $drill): JsonResponse
    {
        try {
            $duplicate = $drill->duplicate(auth()->id());

            return response()->json([
                'message' => 'Drill erfolgreich dupliziert',
                'data' => new DrillResource($duplicate)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Duplizieren des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Rate a drill.
     */
    public function rate(Request $request, Drill $drill): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
                'effectiveness_rating' => 'nullable|integer|min:1|max:10',
                'engagement_rating' => 'nullable|integer|min:1|max:10',
                'difficulty_rating' => 'nullable|integer|min:1|max:10',
                'would_recommend' => 'nullable|boolean',
                'pros' => 'nullable|string',
                'cons' => 'nullable|string',
            ]);

            // Check if user already rated this drill
            $existingRating = $drill->ratings()->where('user_id', auth()->id())->first();

            if ($existingRating) {
                $existingRating->update($validated);
                $message = 'Bewertung erfolgreich aktualisiert';
            } else {
                $drill->ratings()->create(array_merge($validated, [
                    'user_id' => auth()->id(),
                ]));
                $message = 'Bewertung erfolgreich hinzugefügt';
            }

            $drill->recalculateAverageRating();

            return response()->json([
                'message' => $message,
                'data' => [
                    'average_rating' => $drill->fresh()->average_rating,
                    'rating_count' => $drill->fresh()->rating_count,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Bewerten des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Add drill to favorites.
     */
    public function addToFavorites(Request $request, Drill $drill): JsonResponse
    {
        try {
            $validated = $request->validate([
                'notes' => 'nullable|string',
                'tags' => 'nullable|array',
                'favorite_type' => 'nullable|in:personal,team_specific,age_group,situational',
                'team_id' => 'nullable|exists:teams,id',
                'intended_age_group' => 'nullable|string',
                'personal_priority' => 'nullable|integer|min:1|max:10',
            ]);

            $favorite = $drill->favorites()->updateOrCreate(
                ['user_id' => auth()->id()],
                array_merge($validated, [
                    'favorite_type' => $validated['favorite_type'] ?? 'personal',
                    'personal_priority' => $validated['personal_priority'] ?? 5,
                ])
            );

            return response()->json([
                'message' => 'Drill zu Favoriten hinzugefügt',
                'data' => $favorite
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hinzufügen zu Favoriten',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove drill from favorites.
     */
    public function removeFromFavorites(Drill $drill): JsonResponse
    {
        try {
            $drill->favorites()->where('user_id', auth()->id())->delete();

            return response()->json([
                'message' => 'Drill aus Favoriten entfernt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen aus Favoriten',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get drill recommendations for a team.
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'team_id' => 'required|exists:teams,id',
                'category' => 'nullable|string',
                'difficulty' => 'nullable|string',
                'duration' => 'nullable|integer',
                'focus_areas' => 'nullable|array',
                'limit' => 'nullable|integer|min:1|max:20',
            ]);

            $team = Team::findOrFail($validated['team_id']);
            
            $criteria = collect($validated)->except('team_id')->toArray();
            $drills = app(TrainingService::class)->recommendDrills($team, $criteria);

            return response()->json([
                'data' => DrillResource::collection($drills),
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'category' => $team->category,
                    'active_players' => $team->activePlayers()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Empfehlungen',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get drill categories.
     */
    public function categories(): JsonResponse
    {
        $categories = [
            'ball_handling' => 'Ballhandling',
            'shooting' => 'Wurf',
            'passing' => 'Passen',
            'defense' => 'Verteidigung',
            'rebounding' => 'Rebound',
            'conditioning' => 'Kondition',
            'agility' => 'Beweglichkeit',
            'footwork' => 'Beinarbeit',
            'team_offense' => 'Team-Offense',
            'team_defense' => 'Team-Defense',
            'transition' => 'Transition',
            'set_plays' => 'Spielzüge',
            'scrimmage' => 'Scrimmage',
            'warm_up' => 'Aufwärmen',
            'cool_down' => 'Abwärmen',
        ];

        $counts = Drill::public()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'data' => collect($categories)->map(function ($display, $key) use ($counts) {
                return [
                    'key' => $key,
                    'display' => $display,
                    'count' => $counts[$key] ?? 0,
                ];
            })->values()
        ]);
    }

    /**
     * Get drill statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_drills' => Drill::public()->count(),
            'total_categories' => Drill::public()->distinct('category')->count(),
            'most_popular' => Drill::public()->orderBy('usage_count', 'desc')->limit(5)->get(['id', 'name', 'usage_count']),
            'highest_rated' => Drill::public()->highlyRated()->limit(5)->get(['id', 'name', 'average_rating', 'rating_count']),
            'newest' => Drill::public()->latest()->limit(5)->get(['id', 'name', 'created_at']),
            'by_difficulty' => Drill::public()
                ->selectRaw('difficulty_level, COUNT(*) as count')
                ->groupBy('difficulty_level')
                ->pluck('count', 'difficulty_level'),
            'by_age_group' => Drill::public()
                ->selectRaw('age_group, COUNT(*) as count')
                ->groupBy('age_group')
                ->pluck('count', 'age_group'),
            'average_duration' => Drill::public()->avg('estimated_duration'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}