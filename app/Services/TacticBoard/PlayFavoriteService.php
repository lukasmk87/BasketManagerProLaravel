<?php

namespace App\Services\TacticBoard;

use App\Models\Play;
use App\Models\PlayFavorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PlayFavoriteService
{
    /**
     * Get user's favorite plays with filters.
     */
    public function getUserFavorites(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PlayFavorite::where('user_id', $user->id)
            ->with(['play.createdBy', 'team']);

        if (isset($filters['favorite_type'])) {
            $query->byType($filters['favorite_type']);
        }

        if (isset($filters['team_id'])) {
            $query->byTeam($filters['team_id']);
        }

        if (isset($filters['is_quick_access']) && $filters['is_quick_access']) {
            $query->quickAccess();
        }

        if (isset($filters['category'])) {
            $query->whereHas('play', fn($q) => $q->byCategory($filters['category']));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('play', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->ordered()->paginate($perPage);
    }

    /**
     * Get quick access favorites for a user.
     */
    public function getQuickAccessFavorites(User $user, int $limit = 5): Collection
    {
        return PlayFavorite::where('user_id', $user->id)
            ->quickAccess()
            ->ordered()
            ->with('play')
            ->limit($limit)
            ->get();
    }

    /**
     * Toggle favorite status for a play.
     */
    public function toggleFavorite(Play $play, User $user, array $data = []): ?PlayFavorite
    {
        return DB::transaction(function () use ($play, $user, $data) {
            $existing = PlayFavorite::where('play_id', $play->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $existing->delete();
                return null;
            }

            return PlayFavorite::create([
                'play_id' => $play->id,
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? null,
                'favorite_type' => $data['favorite_type'] ?? 'personal',
                'team_id' => $data['team_id'] ?? null,
                'use_cases' => $data['use_cases'] ?? null,
                'category_override' => $data['category_override'] ?? null,
                'personal_priority' => $data['personal_priority'] ?? 5,
                'is_quick_access' => $data['is_quick_access'] ?? false,
            ]);
        });
    }

    /**
     * Add a play to favorites.
     */
    public function addFavorite(Play $play, User $user, array $data = []): PlayFavorite
    {
        return PlayFavorite::firstOrCreate(
            [
                'play_id' => $play->id,
                'user_id' => $user->id,
            ],
            [
                'notes' => $data['notes'] ?? null,
                'tags' => $data['tags'] ?? null,
                'favorite_type' => $data['favorite_type'] ?? 'personal',
                'team_id' => $data['team_id'] ?? null,
                'use_cases' => $data['use_cases'] ?? null,
                'category_override' => $data['category_override'] ?? null,
                'personal_priority' => $data['personal_priority'] ?? 5,
                'is_quick_access' => $data['is_quick_access'] ?? false,
            ]
        );
    }

    /**
     * Remove a play from favorites.
     */
    public function removeFavorite(Play $play, User $user): bool
    {
        return PlayFavorite::where('play_id', $play->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Check if user has favorited a play.
     */
    public function isFavorited(Play $play, User $user): bool
    {
        return PlayFavorite::where('play_id', $play->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get favorite for a specific play and user.
     */
    public function getFavorite(Play $play, User $user): ?PlayFavorite
    {
        return PlayFavorite::where('play_id', $play->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Update favorite metadata.
     */
    public function updateFavorite(PlayFavorite $favorite, array $data): PlayFavorite
    {
        $updateData = array_filter([
            'notes' => $data['notes'] ?? null,
            'tags' => $data['tags'] ?? null,
            'favorite_type' => $data['favorite_type'] ?? null,
            'team_id' => $data['team_id'] ?? null,
            'use_cases' => $data['use_cases'] ?? null,
            'category_override' => $data['category_override'] ?? null,
            'personal_priority' => $data['personal_priority'] ?? null,
            'is_quick_access' => $data['is_quick_access'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $favorite->update($updateData);
        }

        return $favorite->fresh();
    }

    /**
     * Get user's library (own plays + favorites).
     */
    public function getUserLibrary(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $favoritedPlayIds = PlayFavorite::where('user_id', $user->id)
            ->pluck('play_id');

        $query = Play::where(function ($q) use ($user, $favoritedPlayIds) {
            $q->where('created_by_user_id', $user->id)
              ->orWhereIn('id', $favoritedPlayIds);
        })->with('createdBy');

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            if ($filters['type'] === 'own') {
                $query->where('created_by_user_id', $user->id);
            } elseif ($filters['type'] === 'favorites') {
                $query->whereIn('id', $favoritedPlayIds);
            }
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Get favorites by type.
     */
    public function getFavoritesByType(User $user, string $type): Collection
    {
        return PlayFavorite::where('user_id', $user->id)
            ->byType($type)
            ->with('play')
            ->ordered()
            ->get();
    }

    /**
     * Get high priority favorites.
     */
    public function getHighPriorityFavorites(User $user, int $limit = 10): Collection
    {
        return PlayFavorite::where('user_id', $user->id)
            ->highPriority()
            ->with('play')
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Get favorite statistics for a user.
     */
    public function getUserFavoriteStats(User $user): array
    {
        $favorites = PlayFavorite::where('user_id', $user->id);

        return [
            'total' => $favorites->count(),
            'quick_access' => PlayFavorite::where('user_id', $user->id)->quickAccess()->count(),
            'by_type' => PlayFavorite::where('user_id', $user->id)
                ->selectRaw('favorite_type, COUNT(*) as count')
                ->groupBy('favorite_type')
                ->pluck('count', 'favorite_type')
                ->toArray(),
            'high_priority' => PlayFavorite::where('user_id', $user->id)->highPriority()->count(),
        ];
    }

    /**
     * Bulk update priority for multiple favorites.
     */
    public function bulkUpdatePriority(User $user, array $priorityMap): void
    {
        DB::transaction(function () use ($user, $priorityMap) {
            foreach ($priorityMap as $favoriteId => $priority) {
                PlayFavorite::where('id', $favoriteId)
                    ->where('user_id', $user->id)
                    ->update(['personal_priority' => max(1, min(10, $priority))]);
            }
        });
    }
}
