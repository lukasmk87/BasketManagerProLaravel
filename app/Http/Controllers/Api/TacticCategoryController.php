<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TacticCategory;
use App\Services\TacticBoard\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TacticCategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {
    }

    /**
     * Display a listing of all categories.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'search']);

        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $categories = $this->categoryService->getCategories($filters);

        return response()->json([
            'data' => $categories,
            'meta' => $this->categoryService->getCategoryStatistics($request->user()->tenant_id),
        ]);
    }

    /**
     * Get categories for plays.
     */
    public function forPlays(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $categories = $this->categoryService->getCategoriesForPlays($tenantId);

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get categories for drills.
     */
    public function forDrills(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $categories = $this->categoryService->getCategoriesForDrills($tenantId);

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'type' => 'required|in:play,drill,both',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($request->user()->tenant_id) {
            $validated['tenant_id'] = $request->user()->tenant_id;
        }

        $category = $this->categoryService->createCategory($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Kategorie erfolgreich erstellt.',
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(TacticCategory $category): JsonResponse
    {
        return response()->json([
            'data' => $category,
            'usage' => [
                'plays_count' => $category->getPlaysCount(),
                'drills_count' => $category->getDrillsCount(),
            ],
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, TacticCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:play,drill,both',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = $this->categoryService->updateCategory($category, $validated);

        return response()->json([
            'data' => $category,
            'message' => 'Kategorie erfolgreich aktualisiert.',
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(TacticCategory $category): JsonResponse
    {
        if (!$category->canBeDeleted()) {
            return response()->json([
                'message' => 'System-Kategorien können nicht gelöscht werden.',
            ], 403);
        }

        $usageCount = $category->getTotalUsageCount();
        if ($usageCount > 0) {
            return response()->json([
                'message' => "Diese Kategorie wird noch von {$usageCount} Einträgen verwendet und kann nicht gelöscht werden.",
            ], 422);
        }

        $this->categoryService->deleteCategory($category);

        return response()->json([
            'message' => 'Kategorie erfolgreich gelöscht.',
        ]);
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'required|integer|exists:tactic_categories,id',
        ]);

        $this->categoryService->reorderCategories(
            $validated['ordered_ids'],
            $request->user()->tenant_id
        );

        return response()->json([
            'message' => 'Reihenfolge erfolgreich aktualisiert.',
        ]);
    }

    /**
     * Get category statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->categoryService->getCategoryStatistics($request->user()->tenant_id);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
