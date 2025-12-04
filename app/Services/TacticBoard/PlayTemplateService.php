<?php

namespace App\Services\TacticBoard;

use App\Models\Play;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PlayTemplateService
{
    public function __construct(
        protected PlayService $playService
    ) {}

    /**
     * Get system templates (tenant_id = null, is_system_template = true).
     */
    public function getSystemTemplates(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Play::systemTemplates()
            ->with('createdBy');

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['court_type'])) {
            $query->byCourtType($filters['court_type']);
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

        // Sort by featured first, then order, then name
        $query->orderByDesc('is_featured')
              ->orderBy('template_order')
              ->orderBy('name');

        return $query->paginate($perPage);
    }

    /**
     * Get featured templates for display on landing/create pages.
     */
    public function getFeaturedTemplates(int $limit = 6): Collection
    {
        return Play::systemTemplates()
            ->featured()
            ->orderBy('template_order')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all published templates (system + public).
     */
    public function getAllTemplates(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Play::templates()
            ->with('createdBy');

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['court_type'])) {
            $query->byCourtType($filters['court_type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('is_featured')
              ->orderByDesc('is_system_template')
              ->orderBy('template_order')
              ->orderBy('name');

        return $query->paginate($perPage);
    }

    /**
     * Create a play from template (duplicate with modifications).
     */
    public function createFromTemplate(Play $template, User $user, array $overrides = []): Play
    {
        $newPlay = $template->duplicate($user->id, $user->tenant_id);

        // Apply name override if provided
        if (!empty($overrides['name'])) {
            $newPlay->update(['name' => $overrides['name']]);
        }

        // Increment usage count on template
        $template->incrementUsage();

        return $newPlay->fresh();
    }

    /**
     * Get templates grouped by category for gallery display.
     */
    public function getTemplatesByCategory(): array
    {
        $templates = Play::systemTemplates()
            ->orderByDesc('is_featured')
            ->orderBy('template_order')
            ->get();

        $grouped = $templates->groupBy('category');

        // Convert to array with category info
        $result = [];
        $categories = $this->playService->getCategories();

        foreach ($categories as $category) {
            $categoryTemplates = $grouped->get($category['value'], collect());
            if ($categoryTemplates->count() > 0) {
                $result[$category['value']] = [
                    'label' => $category['label'],
                    'templates' => $categoryTemplates->toArray(),
                ];
            }
        }

        return $result;
    }

    /**
     * Get all available template tags for filtering.
     */
    public function getTemplateTags(): array
    {
        $templates = Play::systemTemplates()->pluck('tags')->filter();

        $allTags = [];
        foreach ($templates as $tags) {
            if (is_array($tags)) {
                $allTags = array_merge($allTags, $tags);
            }
        }

        return array_values(array_unique($allTags));
    }

    /**
     * Get template statistics.
     */
    public function getTemplateStats(): array
    {
        return [
            'total' => Play::systemTemplates()->count(),
            'featured' => Play::systemTemplates()->featured()->count(),
            'by_category' => Play::systemTemplates()
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'most_used' => Play::systemTemplates()
                ->orderByDesc('usage_count')
                ->limit(5)
                ->get(['id', 'name', 'usage_count']),
        ];
    }

    /**
     * Create a system template (admin only).
     */
    public function createSystemTemplate(array $data, User $admin): Play
    {
        $data['tenant_id'] = null;
        $data['is_system_template'] = true;
        $data['status'] = 'published';
        $data['is_public'] = true;

        return $this->playService->createPlay($data, $admin);
    }

    /**
     * Update template order.
     */
    public function updateTemplateOrder(Play $template, int $order): void
    {
        $template->update(['template_order' => $order]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Play $template): Play
    {
        $template->update(['is_featured' => !$template->is_featured]);
        return $template->fresh();
    }
}
