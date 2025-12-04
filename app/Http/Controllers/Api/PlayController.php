<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayRequest;
use App\Http\Requests\UpdatePlayRequest;
use App\Http\Resources\PlayResource;
use App\Models\Play;
use App\Models\Drill;
use App\Models\TrainingSession;
use App\Services\TacticBoard\PlayService;
use App\Services\TacticBoard\PlayExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayController extends Controller
{
    public function __construct(
        protected PlayService $playService,
        protected PlayExportService $exportService
    ) {
    }

    /**
     * Display a listing of plays.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Play::class);

        $filters = $request->only([
            'category', 'court_type', 'status', 'search', 'tags', 'sort_by', 'sort_dir',
        ]);

        // Add tenant filter if user has tenant
        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $plays = $this->playService->getPlays($filters, $request->input('per_page', 15));

        return PlayResource::collection($plays);
    }

    /**
     * Store a newly created play.
     */
    public function store(StorePlayRequest $request): PlayResource
    {
        $play = $this->playService->createPlay(
            $request->validated(),
            $request->user()
        );

        return new PlayResource($play->load('createdBy'));
    }

    /**
     * Display the specified play.
     */
    public function show(Play $play): PlayResource
    {
        $this->authorize('view', $play);

        return new PlayResource($play->load('createdBy'));
    }

    /**
     * Update the specified play.
     */
    public function update(UpdatePlayRequest $request, Play $play): PlayResource
    {
        $play = $this->playService->updatePlay($play, $request->validated());

        return new PlayResource($play->load('createdBy'));
    }

    /**
     * Remove the specified play.
     */
    public function destroy(Play $play): JsonResponse
    {
        $this->authorize('delete', $play);

        $this->playService->deletePlay($play);

        return response()->json(['message' => 'Spielzug gelöscht.']);
    }

    /**
     * Get available categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => $this->playService->getCategories(),
            'court_types' => $this->playService->getCourtTypes(),
        ]);
    }

    /**
     * Duplicate a play.
     */
    public function duplicate(Play $play): PlayResource
    {
        $this->authorize('duplicate', $play);

        $newPlay = $this->playService->duplicatePlay($play, request()->user());

        return new PlayResource($newPlay->load('createdBy'));
    }

    /**
     * Export play as PNG.
     */
    public function exportPng(Request $request, Play $play): JsonResponse
    {
        $this->authorize('export', $play);

        $request->validate([
            'image_data' => ['required', 'string'],
            'width' => ['nullable', 'integer', 'min:100', 'max:2000'],
        ]);

        $url = $this->exportService->exportAsPng(
            $play,
            $request->input('image_data'),
            $request->input('width', 800)
        );

        return response()->json(['url' => $url]);
    }

    /**
     * Export play as PDF.
     */
    public function exportPdf(Request $request, Play $play)
    {
        $this->authorize('export', $play);

        return $this->exportService->exportPlayAsPdf(
            $play,
            $request->input('thumbnail')
        );
    }

    /**
     * Export play animation as GIF (Phase 11.3).
     */
    public function exportGif(Request $request, Play $play): JsonResponse
    {
        $this->authorize('export', $play);

        $request->validate([
            'gif_data' => ['required', 'string'],
        ]);

        $url = $this->exportService->exportAsGif(
            $play,
            $request->input('gif_data')
        );

        return response()->json(['url' => $url]);
    }

    /**
     * Attach play to a drill.
     */
    public function attachToDrill(Request $request, Play $play): JsonResponse
    {
        $this->authorize('attach', $play);

        $request->validate([
            'drill_id' => ['required', 'exists:drills,id'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        $drill = Drill::findOrFail($request->input('drill_id'));

        $this->playService->attachToDrill($play, $drill, $request->input('order', 1));

        return response()->json(['message' => 'Spielzug zum Drill hinzugefügt.']);
    }

    /**
     * Attach play to a training session.
     */
    public function attachToSession(Request $request, Play $play): JsonResponse
    {
        $this->authorize('attach', $play);

        $request->validate([
            'training_session_id' => ['required', 'exists:training_sessions,id'],
            'order' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = TrainingSession::findOrFail($request->input('training_session_id'));

        $this->playService->attachToTrainingSession(
            $play,
            $session,
            $request->input('order', 1),
            $request->input('notes')
        );

        return response()->json(['message' => 'Spielzug zur Trainingseinheit hinzugefügt.']);
    }

    /**
     * Save thumbnail for a play.
     */
    public function saveThumbnail(Request $request, Play $play): JsonResponse
    {
        $this->authorize('update', $play);

        $request->validate([
            'image_data' => ['required', 'string'],
        ]);

        $thumbnailPath = $this->exportService->saveThumbnail(
            $play,
            $request->input('image_data')
        );

        return response()->json(['thumbnail_path' => $thumbnailPath]);
    }

    /**
     * Publish a play.
     */
    public function publish(Play $play): PlayResource
    {
        $this->authorize('update', $play);

        $play = $this->playService->publishPlay($play);

        return new PlayResource($play);
    }

    /**
     * Archive a play.
     */
    public function archive(Play $play): PlayResource
    {
        $this->authorize('update', $play);

        $play = $this->playService->archivePlay($play);

        return new PlayResource($play);
    }
}
