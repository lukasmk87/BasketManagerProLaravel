<?php

namespace App\Services\TacticBoard;

use App\Models\Drill;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DrillService
{
    /**
     * Get paginated list of drills with filters.
     */
    public function getDrills(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Drill::query()->with(['createdBy', 'tacticCategory']);

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['court_type'])) {
            $query->byCourtType($filters['court_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['difficulty_level'])) {
            $query->byDifficulty($filters['difficulty_level']);
        }

        if (isset($filters['created_by_user_id'])) {
            $query->where('created_by_user_id', $filters['created_by_user_id']);
        }

        if (isset($filters['has_visual'])) {
            if ($filters['has_visual']) {
                $query->whereNotNull('drill_data');
            } else {
                $query->whereNull('drill_data');
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('objectives', 'like', "%{$search}%");
            });
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a new drill with visual data.
     */
    public function createDrill(array $data, User $user): Drill
    {
        return DB::transaction(function () use ($data, $user) {
            $drill = Drill::create([
                'tenant_id' => $data['tenant_id'] ?? $user->tenant_id,
                'created_by_user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'drill_data' => $data['drill_data'] ?? $this->getDefaultDrillData(),
                'animation_data' => $data['animation_data'] ?? null,
                'court_type' => $data['court_type'] ?? 'half_horizontal',
                'category' => $data['category'] ?? 'ball_handling',
                'category_id' => $data['category_id'] ?? null,
                'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
                'age_group' => $data['age_group'] ?? 'all',
                'min_players' => $data['min_players'] ?? 1,
                'max_players' => $data['max_players'] ?? null,
                'estimated_duration' => $data['estimated_duration'] ?? 15,
                'tags' => $data['tags'] ?? null,
                'is_public' => $data['is_public'] ?? false,
                'status' => $data['status'] ?? 'draft',
            ]);

            return $drill;
        });
    }

    /**
     * Update an existing drill.
     */
    public function updateDrill(Drill $drill, array $data): Drill
    {
        return DB::transaction(function () use ($drill, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'drill_data' => $data['drill_data'] ?? null,
                'animation_data' => $data['animation_data'] ?? null,
                'court_type' => $data['court_type'] ?? null,
                'category' => $data['category'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'difficulty_level' => $data['difficulty_level'] ?? null,
                'age_group' => $data['age_group'] ?? null,
                'min_players' => $data['min_players'] ?? null,
                'max_players' => $data['max_players'] ?? null,
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'tags' => $data['tags'] ?? null,
                'is_public' => $data['is_public'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn ($value) => $value !== null);

            $drill->update($updateData);

            return $drill->fresh(['createdBy', 'tacticCategory']);
        });
    }

    /**
     * Duplicate a drill.
     */
    public function duplicateDrill(Drill $drill, ?User $user = null): Drill
    {
        return DB::transaction(function () use ($drill, $user) {
            $duplicate = $drill->replicate();
            $duplicate->name = $drill->name . ' (Kopie)';
            $duplicate->created_by_user_id = $user?->id ?? auth()->id();
            $duplicate->tenant_id = $user?->tenant_id ?? auth()->user()?->tenant_id;
            $duplicate->status = 'draft';
            $duplicate->is_public = false;
            $duplicate->is_featured = false;
            $duplicate->usage_count = 0;
            $duplicate->average_rating = null;
            $duplicate->rating_count = 0;
            $duplicate->thumbnail_path = null;
            $duplicate->save();

            return $duplicate;
        });
    }

    /**
     * Delete a drill.
     */
    public function deleteDrill(Drill $drill): bool
    {
        return $drill->delete();
    }

    /**
     * Get default drill data structure (same as plays).
     */
    public function getDefaultDrillData(): array
    {
        return [
            'elements' => [
                'players' => [],
                'paths' => [],
                'shapes' => [],
                'annotations' => [],
            ],
            'settings' => [
                'showGrid' => true,
                'snapToGrid' => true,
                'gridSize' => 20,
            ],
        ];
    }

    /**
     * Update drill thumbnail.
     */
    public function updateThumbnail(Drill $drill, string $thumbnailPath): void
    {
        $drill->update(['thumbnail_path' => $thumbnailPath]);
    }

    /**
     * Publish a drill.
     */
    public function publishDrill(Drill $drill): Drill
    {
        $drill->update(['status' => 'pending_review']);
        return $drill->fresh();
    }

    /**
     * Archive a drill.
     */
    public function archiveDrill(Drill $drill): Drill
    {
        $drill->update(['status' => 'archived']);
        return $drill->fresh();
    }

    /**
     * Get available categories for drills.
     */
    public function getCategories(): array
    {
        return [
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
    }

    /**
     * Get available court types.
     */
    public function getCourtTypes(): array
    {
        return [
            'half_horizontal' => 'Halbes Feld (horizontal)',
            'full' => 'Ganzes Feld',
            'half_vertical' => 'Halbes Feld (vertikal)',
        ];
    }

    /**
     * Get available difficulty levels.
     */
    public function getDifficultyLevels(): array
    {
        return [
            'beginner' => 'Anfänger',
            'intermediate' => 'Fortgeschritten',
            'advanced' => 'Fortgeschritten+',
            'expert' => 'Experte',
        ];
    }

    /**
     * Get available age groups.
     */
    public function getAgeGroups(): array
    {
        return [
            'all' => 'Alle Altersgruppen',
            'U8' => 'U8',
            'U10' => 'U10',
            'U12' => 'U12',
            'U14' => 'U14',
            'U16' => 'U16',
            'U18' => 'U18',
            'adult' => 'Erwachsene',
        ];
    }

    /**
     * Get drill statistics for dashboard.
     */
    public function getStatistics(?string $tenantId = null, ?int $userId = null): array
    {
        $query = Drill::query();

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        if ($userId) {
            $query->where('created_by_user_id', $userId);
        }

        return [
            'total' => $query->count(),
            'with_visual' => (clone $query)->whereNotNull('drill_data')->count(),
            'published' => (clone $query)->where('status', 'approved')->count(),
            'drafts' => (clone $query)->where('status', 'draft')->count(),
        ];
    }
}
