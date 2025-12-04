<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayFavoriteResource;
use App\Http\Resources\PlayResource;
use App\Models\Play;
use App\Models\PlayFavorite;
use App\Services\TacticBoard\PlayFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayFavoriteController extends Controller
{
    public function __construct(
        protected PlayFavoriteService $favoriteService
    ) {}

    /**
     * Get user's favorites.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['favorite_type', 'team_id', 'is_quick_access', 'category', 'search']);
        $favorites = $this->favoriteService->getUserFavorites($request->user(), $filters);

        return PlayFavoriteResource::collection($favorites);
    }

    /**
     * Get quick access favorites.
     */
    public function quickAccess(Request $request): AnonymousResourceCollection
    {
        $limit = $request->input('limit', 5);

        return PlayFavoriteResource::collection(
            $this->favoriteService->getQuickAccessFavorites($request->user(), $limit)
        );
    }

    /**
     * Get user's library (own plays + favorites).
     */
    public function library(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['category', 'status', 'search', 'type']);
        $plays = $this->favoriteService->getUserLibrary($request->user(), $filters);

        return PlayResource::collection($plays);
    }

    /**
     * Toggle favorite status.
     */
    public function toggle(Request $request, Play $play): JsonResponse
    {
        $this->authorize('view', $play);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'favorite_type' => ['nullable', 'in:personal,team_specific,training,game_prep'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'use_cases' => ['nullable', 'array'],
            'personal_priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_quick_access' => ['nullable', 'boolean'],
        ]);

        $favorite = $this->favoriteService->toggleFavorite($play, $request->user(), $request->all());

        return response()->json([
            'is_favorited' => $favorite !== null,
            'favorite' => $favorite ? new PlayFavoriteResource($favorite->load('play')) : null,
        ]);
    }

    /**
     * Check if play is favorited.
     */
    public function check(Request $request, Play $play): JsonResponse
    {
        $isFavorited = $this->favoriteService->isFavorited($play, $request->user());
        $favorite = null;

        if ($isFavorited) {
            $favorite = $this->favoriteService->getFavorite($play, $request->user());
        }

        return response()->json([
            'is_favorited' => $isFavorited,
            'favorite' => $favorite ? new PlayFavoriteResource($favorite) : null,
        ]);
    }

    /**
     * Update favorite metadata.
     */
    public function update(Request $request, PlayFavorite $favorite): PlayFavoriteResource
    {
        // Ensure user owns this favorite
        if ($favorite->user_id !== $request->user()->id) {
            abort(403, 'Sie können nur Ihre eigenen Favoriten bearbeiten.');
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'favorite_type' => ['nullable', 'in:personal,team_specific,training,game_prep'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'use_cases' => ['nullable', 'array'],
            'category_override' => ['nullable', 'string', 'max:100'],
            'personal_priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_quick_access' => ['nullable', 'boolean'],
        ]);

        $favorite = $this->favoriteService->updateFavorite($favorite, $request->all());

        return new PlayFavoriteResource($favorite->load('play'));
    }

    /**
     * Delete a favorite.
     */
    public function destroy(Request $request, PlayFavorite $favorite): JsonResponse
    {
        if ($favorite->user_id !== $request->user()->id) {
            abort(403, 'Sie können nur Ihre eigenen Favoriten löschen.');
        }

        $favorite->delete();

        return response()->json(['message' => 'Favorit entfernt.']);
    }

    /**
     * Get favorite statistics for current user.
     */
    public function stats(Request $request): JsonResponse
    {
        return response()->json(
            $this->favoriteService->getUserFavoriteStats($request->user())
        );
    }

    /**
     * Get favorites by type.
     */
    public function byType(Request $request, string $type): AnonymousResourceCollection
    {
        if (!in_array($type, ['personal', 'team_specific', 'training', 'game_prep'])) {
            abort(400, 'Ungültiger Favoriten-Typ.');
        }

        return PlayFavoriteResource::collection(
            $this->favoriteService->getFavoritesByType($request->user(), $type)
        );
    }

    /**
     * Toggle quick access for a favorite.
     */
    public function toggleQuickAccess(Request $request, PlayFavorite $favorite): PlayFavoriteResource
    {
        if ($favorite->user_id !== $request->user()->id) {
            abort(403, 'Sie können nur Ihre eigenen Favoriten bearbeiten.');
        }

        $favorite->toggleQuickAccess();

        return new PlayFavoriteResource($favorite->fresh()->load('play'));
    }
}
