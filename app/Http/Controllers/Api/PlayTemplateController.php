<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayResource;
use App\Models\Play;
use App\Services\TacticBoard\PlayTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayTemplateController extends Controller
{
    public function __construct(
        protected PlayTemplateService $templateService
    ) {}

    /**
     * Get all system templates.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Play::class);

        $filters = $request->only(['category', 'court_type', 'search', 'tags']);
        $templates = $this->templateService->getSystemTemplates($filters, $request->input('per_page', 12));

        return PlayResource::collection($templates);
    }

    /**
     * Get featured templates.
     */
    public function featured(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Play::class);

        $limit = $request->input('limit', 6);

        return PlayResource::collection(
            $this->templateService->getFeaturedTemplates($limit)
        );
    }

    /**
     * Get templates grouped by category.
     */
    public function byCategory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Play::class);

        return response()->json([
            'templates' => $this->templateService->getTemplatesByCategory(),
            'tags' => $this->templateService->getTemplateTags(),
        ]);
    }

    /**
     * Create play from template.
     */
    public function createFromTemplate(Request $request, Play $play): PlayResource
    {
        $this->authorize('duplicate', $play);

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $newPlay = $this->templateService->createFromTemplate(
            $play,
            $request->user(),
            $request->only(['name'])
        );

        return new PlayResource($newPlay->load('createdBy'));
    }

    /**
     * Get template statistics (admin only).
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('manageTemplates', Play::class);

        return response()->json($this->templateService->getTemplateStats());
    }

    /**
     * Toggle featured status (admin only).
     */
    public function toggleFeatured(Request $request, Play $play): PlayResource
    {
        $this->authorize('feature', $play);

        $play = $this->templateService->toggleFeatured($play);

        return new PlayResource($play);
    }

    /**
     * Update template order (admin only).
     */
    public function updateOrder(Request $request, Play $play): PlayResource
    {
        $this->authorize('manageTemplates', Play::class);

        $request->validate([
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $this->templateService->updateTemplateOrder($play, $request->input('order'));

        return new PlayResource($play->fresh());
    }
}
