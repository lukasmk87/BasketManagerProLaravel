<?php

namespace App\Services\TacticBoard;

use App\Models\Playbook;
use App\Models\Play;
use App\Models\Game;
use App\Models\User;
use App\Models\BasketballTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaybookService
{
    /**
     * Get paginated list of playbooks with filters.
     */
    public function getPlaybooks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Playbook::query()->with(['createdBy', 'team', 'plays']);

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['team_id'])) {
            $query->forTeam($filters['team_id']);
        }

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
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

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a new playbook.
     */
    public function createPlaybook(array $data, User $user): Playbook
    {
        return DB::transaction(function () use ($data, $user) {
            $playbook = Playbook::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $data['tenant_id'] ?? $user->tenant_id,
                'created_by_user_id' => $user->id,
                'team_id' => $data['team_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? 'practice',
                'is_default' => $data['is_default'] ?? false,
            ]);

            // Add initial plays if provided
            if (isset($data['play_ids']) && is_array($data['play_ids'])) {
                foreach ($data['play_ids'] as $index => $playId) {
                    $playbook->plays()->attach($playId, ['order' => $index + 1]);
                }
            }

            return $playbook->load('plays');
        });
    }

    /**
     * Update an existing playbook.
     */
    public function updatePlaybook(Playbook $playbook, array $data): Playbook
    {
        return DB::transaction(function () use ($playbook, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'team_id' => $data['team_id'] ?? null,
                'category' => $data['category'] ?? null,
                'is_default' => $data['is_default'] ?? null,
            ], fn($value) => $value !== null);

            $playbook->update($updateData);

            return $playbook->fresh(['plays', 'team']);
        });
    }

    /**
     * Delete a playbook.
     */
    public function deletePlaybook(Playbook $playbook): bool
    {
        return DB::transaction(function () use ($playbook) {
            // Detach from games
            $playbook->games()->detach();

            // Detach plays
            $playbook->plays()->detach();

            return $playbook->delete();
        });
    }

    /**
     * Duplicate a playbook.
     */
    public function duplicatePlaybook(Playbook $playbook, ?User $user = null): Playbook
    {
        return $playbook->duplicate($user?->id);
    }

    /**
     * Add a play to a playbook.
     */
    public function addPlay(Playbook $playbook, Play $play, ?int $order = null, ?string $notes = null): void
    {
        $playbook->addPlay($play, $order, $notes);
    }

    /**
     * Remove a play from a playbook.
     */
    public function removePlay(Playbook $playbook, Play $play): void
    {
        $playbook->removePlay($play);
    }

    /**
     * Reorder plays in a playbook.
     */
    public function reorderPlays(Playbook $playbook, array $playIds): void
    {
        $playbook->reorderPlays($playIds);
    }

    /**
     * Attach playbook to a game for preparation.
     */
    public function attachToGame(Playbook $playbook, Game $game): void
    {
        if (!$game->playbooks()->where('playbook_id', $playbook->id)->exists()) {
            $game->playbooks()->attach($playbook->id);
        }
    }

    /**
     * Detach playbook from a game.
     */
    public function detachFromGame(Playbook $playbook, Game $game): void
    {
        $game->playbooks()->detach($playbook->id);
    }

    /**
     * Get playbooks for a team.
     */
    public function getTeamPlaybooks(BasketballTeam $team, ?string $category = null): Collection
    {
        $query = Playbook::forTeam($team->id);

        if ($category) {
            $query->byCategory($category);
        }

        return $query->with('plays')->orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Get default playbook for a team.
     */
    public function getDefaultPlaybook(BasketballTeam $team, string $category = 'game'): ?Playbook
    {
        return Playbook::forTeam($team->id)
            ->byCategory($category)
            ->default()
            ->first();
    }

    /**
     * Set a playbook as default.
     */
    public function setAsDefault(Playbook $playbook): void
    {
        $playbook->setAsDefault();
    }

    /**
     * Get available playbook categories.
     */
    public function getCategories(): array
    {
        return [
            'game' => 'Spielvorbereitung',
            'practice' => 'Training',
            'situational' => 'Situativ',
        ];
    }

    /**
     * Get playbook statistics.
     */
    public function getPlaybookStatistics(Playbook $playbook): array
    {
        $plays = $playbook->plays;

        return [
            'total_plays' => $plays->count(),
            'plays_by_category' => $plays->groupBy('category')->map->count(),
            'total_animation_duration' => $plays->sum(fn($play) => $play->getAnimationDuration()),
            'games_used' => $playbook->games()->count(),
        ];
    }
}
