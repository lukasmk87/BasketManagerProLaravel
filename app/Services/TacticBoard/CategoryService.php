<?php

namespace App\Services\TacticBoard;

use App\Models\TacticCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Get all categories with optional filters.
     */
    public function getCategories(array $filters = []): Collection
    {
        $query = TacticCategory::query();

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        } else {
            $query->systemWide();
        }

        if (isset($filters['type'])) {
            if ($filters['type'] === 'play') {
                $query->forPlays();
            } elseif ($filters['type'] === 'drill') {
                $query->forDrills();
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->ordered()->get();
    }

    /**
     * Get categories for plays (type: play or both).
     */
    public function getCategoriesForPlays(?string $tenantId = null): Collection
    {
        return TacticCategory::forTenant($tenantId)
            ->forPlays()
            ->ordered()
            ->get();
    }

    /**
     * Get categories for drills (type: drill or both).
     */
    public function getCategoriesForDrills(?string $tenantId = null): Collection
    {
        return TacticCategory::forTenant($tenantId)
            ->forDrills()
            ->ordered()
            ->get();
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): TacticCategory
    {
        return DB::transaction(function () use ($data) {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (TacticCategory::where('slug', $slug)
                ->where('tenant_id', $data['tenant_id'] ?? null)
                ->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            return TacticCategory::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'type' => $data['type'] ?? 'both',
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? '#3B82F6',
                'icon' => $data['icon'] ?? null,
                'sort_order' => $data['sort_order'] ?? $this->getNextSortOrder($data['tenant_id'] ?? null),
                'is_system' => false, // User-created categories are never system categories
            ]);
        });
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(TacticCategory $category, array $data): TacticCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['type']) && !$category->is_system) {
                $updateData['type'] = $data['type'];
            }

            if (isset($data['color'])) {
                $updateData['color'] = $data['color'];
            }

            if (isset($data['icon'])) {
                $updateData['icon'] = $data['icon'];
            }

            if (isset($data['sort_order'])) {
                $updateData['sort_order'] = $data['sort_order'];
            }

            $category->update($updateData);

            return $category->fresh();
        });
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(TacticCategory $category): bool
    {
        if (!$category->canBeDeleted()) {
            return false;
        }

        return $category->delete();
    }

    /**
     * Reorder categories.
     */
    public function reorderCategories(array $orderedIds, ?string $tenantId = null): void
    {
        DB::transaction(function () use ($orderedIds, $tenantId) {
            foreach ($orderedIds as $index => $id) {
                TacticCategory::where('id', $id)
                    ->where(function ($q) use ($tenantId) {
                        $q->whereNull('tenant_id');
                        if ($tenantId) {
                            $q->orWhere('tenant_id', $tenantId);
                        }
                    })
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }

    /**
     * Get the next sort order for a new category.
     */
    private function getNextSortOrder(?string $tenantId): int
    {
        $maxOrder = TacticCategory::forTenant($tenantId)->max('sort_order');

        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Get a category by slug.
     */
    public function getCategoryBySlug(string $slug, ?string $tenantId = null): ?TacticCategory
    {
        return TacticCategory::forTenant($tenantId)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get category statistics.
     */
    public function getCategoryStatistics(?string $tenantId = null): array
    {
        $categories = $this->getCategories(['tenant_id' => $tenantId]);

        return [
            'total' => $categories->count(),
            'play_categories' => $categories->filter(fn ($c) => $c->is_for_plays)->count(),
            'drill_categories' => $categories->filter(fn ($c) => $c->is_for_drills)->count(),
            'system_categories' => $categories->filter(fn ($c) => $c->is_system)->count(),
            'custom_categories' => $categories->filter(fn ($c) => !$c->is_system)->count(),
        ];
    }
}
