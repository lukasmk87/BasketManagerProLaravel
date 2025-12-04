<?php

namespace App\Services\TacticBoard;

use App\Models\Play;
use App\Models\Drill;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayService
{
    /**
     * Get paginated list of plays with filters.
     */
    public function getPlays(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Play::query()->with('createdBy');

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['court_type'])) {
            $query->byCourtType($filters['court_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['created_by_user_id'])) {
            $query->where('created_by_user_id', $filters['created_by_user_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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
     * Create a new play.
     */
    public function createPlay(array $data, User $user): Play
    {
        return DB::transaction(function () use ($data, $user) {
            $play = Play::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $data['tenant_id'] ?? $user->tenant_id,
                'created_by_user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'court_type' => $data['court_type'] ?? 'half_horizontal',
                'play_data' => $data['play_data'] ?? $this->getDefaultPlayData(),
                'animation_data' => $data['animation_data'] ?? null,
                'category' => $data['category'] ?? 'offense',
                'tags' => $data['tags'] ?? null,
                'is_public' => $data['is_public'] ?? false,
                'status' => $data['status'] ?? 'draft',
            ]);

            return $play;
        });
    }

    /**
     * Update an existing play.
     */
    public function updatePlay(Play $play, array $data): Play
    {
        return DB::transaction(function () use ($play, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'court_type' => $data['court_type'] ?? null,
                'play_data' => $data['play_data'] ?? null,
                'animation_data' => $data['animation_data'] ?? null,
                'category' => $data['category'] ?? null,
                'tags' => $data['tags'] ?? null,
                'is_public' => $data['is_public'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn($value) => $value !== null);

            $play->update($updateData);

            return $play->fresh();
        });
    }

    /**
     * Duplicate a play.
     */
    public function duplicatePlay(Play $play, ?User $user = null): Play
    {
        return $play->duplicate($user?->id);
    }

    /**
     * Delete a play.
     */
    public function deletePlay(Play $play): bool
    {
        return DB::transaction(function () use ($play) {
            // Detach from all relationships
            $play->playbooks()->detach();
            $play->drills()->detach();
            $play->trainingSessions()->detach();

            return $play->delete();
        });
    }

    /**
     * Attach play to a drill.
     */
    public function attachToDrill(Play $play, Drill $drill, int $order = 1): void
    {
        if (!$drill->plays()->where('play_id', $play->id)->exists()) {
            $drill->plays()->attach($play->id, ['order' => $order]);
            $play->incrementUsage();
        }
    }

    /**
     * Detach play from a drill.
     */
    public function detachFromDrill(Play $play, Drill $drill): void
    {
        $drill->plays()->detach($play->id);
    }

    /**
     * Attach play to a training session.
     */
    public function attachToTrainingSession(Play $play, TrainingSession $session, int $order = 1, ?string $notes = null): void
    {
        if (!$session->plays()->where('play_id', $play->id)->exists()) {
            $session->plays()->attach($play->id, [
                'order' => $order,
                'notes' => $notes,
            ]);
            $play->incrementUsage();
        }
    }

    /**
     * Get plays by category.
     */
    public function getPlaysByCategory(string $category, ?string $tenantId = null): Collection
    {
        $query = Play::byCategory($category)->published();

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        return $query->orderBy('usage_count', 'desc')->get();
    }

    /**
     * Search plays.
     */
    public function searchPlays(string $query, ?User $user = null, int $limit = 20): Collection
    {
        $search = Play::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%");
        });

        if ($user) {
            $search->accessibleByUser($user->id);
        } else {
            $search->public();
        }

        return $search->limit($limit)->get();
    }

    /**
     * Get default play data structure.
     */
    public function getDefaultPlayData(): array
    {
        return [
            'version' => '1.0',
            'court' => [
                'type' => 'half_horizontal',
                'backgroundColor' => '#1a5f2a',
                'lineColor' => '#ffffff',
            ],
            'elements' => [
                'players' => [],
                'paths' => [],
                'shapes' => [],
                'annotations' => [],
            ],
        ];
    }

    /**
     * Get available play categories.
     */
    public function getCategories(): array
    {
        return [
            ['value' => 'offense', 'label' => 'Offense'],
            ['value' => 'defense', 'label' => 'Defense'],
            ['value' => 'press_break', 'label' => 'Press Break'],
            ['value' => 'inbound', 'label' => 'Einwurf'],
            ['value' => 'fast_break', 'label' => 'Fast Break'],
            ['value' => 'zone', 'label' => 'Zonenverteidigung'],
            ['value' => 'man_to_man', 'label' => 'Mann-gegen-Mann'],
            ['value' => 'transition', 'label' => 'Transition'],
            ['value' => 'special', 'label' => 'Spezial'],
        ];
    }

    /**
     * Get available court types.
     */
    public function getCourtTypes(): array
    {
        return [
            ['value' => 'half_horizontal', 'label' => 'Halbes Feld (horizontal)'],
            ['value' => 'full', 'label' => 'Ganzes Feld'],
            ['value' => 'half_vertical', 'label' => 'Halbes Feld (vertikal)'],
        ];
    }

    /**
     * Update play thumbnail.
     */
    public function updateThumbnail(Play $play, string $thumbnailPath): void
    {
        $play->update(['thumbnail_path' => $thumbnailPath]);
    }

    /**
     * Publish a play.
     */
    public function publishPlay(Play $play): Play
    {
        return $play->publish();
    }

    /**
     * Archive a play.
     */
    public function archivePlay(Play $play): Play
    {
        return $play->archive();
    }
}
