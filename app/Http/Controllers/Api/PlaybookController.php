<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaybookRequest;
use App\Http\Requests\UpdatePlaybookRequest;
use App\Http\Resources\PlaybookResource;
use App\Models\Playbook;
use App\Models\Play;
use App\Models\Game;
use App\Services\TacticBoard\PlaybookService;
use App\Services\TacticBoard\PlayExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlaybookController extends Controller
{
    public function __construct(
        protected PlaybookService $playbookService,
        protected PlayExportService $exportService
    ) {
    }

    /**
     * Display a listing of playbooks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Playbook::class);

        $filters = $request->only([
            'team_id', 'category', 'search', 'sort_by', 'sort_dir',
        ]);

        // Add tenant filter if user has tenant
        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $playbooks = $this->playbookService->getPlaybooks($filters, $request->input('per_page', 15));

        return PlaybookResource::collection($playbooks);
    }

    /**
     * Store a newly created playbook.
     */
    public function store(StorePlaybookRequest $request): PlaybookResource
    {
        $playbook = $this->playbookService->createPlaybook(
            $request->validated(),
            $request->user()
        );

        return new PlaybookResource($playbook->load(['createdBy', 'team', 'plays']));
    }

    /**
     * Display the specified playbook.
     */
    public function show(Playbook $playbook): PlaybookResource
    {
        $this->authorize('view', $playbook);

        return new PlaybookResource($playbook->load(['createdBy', 'team', 'plays']));
    }

    /**
     * Update the specified playbook.
     */
    public function update(UpdatePlaybookRequest $request, Playbook $playbook): PlaybookResource
    {
        $playbook = $this->playbookService->updatePlaybook($playbook, $request->validated());

        return new PlaybookResource($playbook->load(['createdBy', 'team', 'plays']));
    }

    /**
     * Remove the specified playbook.
     */
    public function destroy(Playbook $playbook): JsonResponse
    {
        $this->authorize('delete', $playbook);

        $this->playbookService->deletePlaybook($playbook);

        return response()->json(['message' => 'Playbook gelöscht.']);
    }

    /**
     * Get available categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => $this->playbookService->getCategories(),
        ]);
    }

    /**
     * Duplicate a playbook.
     */
    public function duplicate(Playbook $playbook): PlaybookResource
    {
        $this->authorize('duplicate', $playbook);

        $newPlaybook = $this->playbookService->duplicatePlaybook($playbook, request()->user());

        return new PlaybookResource($newPlaybook->load(['createdBy', 'team', 'plays']));
    }

    /**
     * Add a play to the playbook.
     */
    public function addPlay(Request $request, Playbook $playbook): JsonResponse
    {
        $this->authorize('managePlays', $playbook);

        $request->validate([
            'play_id' => ['required', 'exists:plays,id'],
            'order' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $play = Play::findOrFail($request->input('play_id'));

        $this->playbookService->addPlay(
            $playbook,
            $play,
            $request->input('order'),
            $request->input('notes')
        );

        return response()->json(['message' => 'Spielzug hinzugefügt.']);
    }

    /**
     * Remove a play from the playbook.
     */
    public function removePlay(Playbook $playbook, Play $play): JsonResponse
    {
        $this->authorize('managePlays', $playbook);

        $this->playbookService->removePlay($playbook, $play);

        return response()->json(['message' => 'Spielzug entfernt.']);
    }

    /**
     * Reorder plays in the playbook.
     */
    public function reorderPlays(Request $request, Playbook $playbook): JsonResponse
    {
        $this->authorize('managePlays', $playbook);

        $request->validate([
            'play_ids' => ['required', 'array'],
            'play_ids.*' => ['exists:plays,id'],
        ]);

        $this->playbookService->reorderPlays($playbook, $request->input('play_ids'));

        return response()->json(['message' => 'Reihenfolge aktualisiert.']);
    }

    /**
     * Export playbook as PDF.
     */
    public function exportPdf(Request $request, Playbook $playbook)
    {
        $this->authorize('export', $playbook);

        return $this->exportService->exportPlaybookAsPdf(
            $playbook,
            $request->input('thumbnails', [])
        );
    }

    /**
     * Attach playbook to a game.
     */
    public function attachToGame(Request $request, Playbook $playbook): JsonResponse
    {
        $this->authorize('attachToGame', $playbook);

        $request->validate([
            'game_id' => ['required', 'exists:games,id'],
        ]);

        $game = Game::findOrFail($request->input('game_id'));

        $this->playbookService->attachToGame($playbook, $game);

        return response()->json(['message' => 'Playbook zum Spiel hinzugefügt.']);
    }

    /**
     * Set playbook as default.
     */
    public function setDefault(Playbook $playbook): PlaybookResource
    {
        $this->authorize('update', $playbook);

        $this->playbookService->setAsDefault($playbook);

        return new PlaybookResource($playbook->fresh(['createdBy', 'team', 'plays']));
    }

    /**
     * Get playbook statistics.
     */
    public function statistics(Playbook $playbook): JsonResponse
    {
        $this->authorize('view', $playbook);

        return response()->json([
            'statistics' => $this->playbookService->getPlaybookStatistics($playbook),
        ]);
    }
}
