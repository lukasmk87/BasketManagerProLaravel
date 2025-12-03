<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Playbook;
use App\Services\TacticBoard\PlayService;
use App\Services\TacticBoard\PlaybookService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TacticBoardController extends Controller
{
    public function __construct(
        protected PlayService $playService,
        protected PlaybookService $playbookService
    ) {
    }

    /**
     * Display the plays index page.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Play::class);

        $filters = $request->only(['category', 'court_type', 'status', 'search']);

        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $plays = $this->playService->getPlays($filters, 12);

        return Inertia::render('TacticBoard/Index', [
            'plays' => $plays,
            'filters' => $filters,
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
        ]);
    }

    /**
     * Display the create play page.
     */
    public function createPlay(): Response
    {
        $this->authorize('create', Play::class);

        return Inertia::render('TacticBoard/Create', [
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
            'defaultPlayData' => $this->playService->getDefaultPlayData(),
        ]);
    }

    /**
     * Display the edit play page.
     */
    public function editPlay(Play $play): Response
    {
        $this->authorize('update', $play);

        return Inertia::render('TacticBoard/Edit', [
            'play' => $play->load('createdBy'),
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
        ]);
    }

    /**
     * Display a single play.
     */
    public function showPlay(Play $play): Response
    {
        $this->authorize('view', $play);

        return Inertia::render('TacticBoard/Show', [
            'play' => $play->load(['createdBy', 'playbooks', 'drills']),
            'canEdit' => $play->created_by_user_id === auth()->id() ||
                auth()->user()->can('manage tactic board'),
        ]);
    }

    /**
     * Display the playbooks index page.
     */
    public function playbooksIndex(Request $request): Response
    {
        $this->authorize('viewAny', Playbook::class);

        $filters = $request->only(['team_id', 'category', 'search']);

        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $playbooks = $this->playbookService->getPlaybooks($filters, 12);

        return Inertia::render('TacticBoard/Playbooks/Index', [
            'playbooks' => $playbooks,
            'filters' => $filters,
            'categories' => $this->playbookService->getCategories(),
        ]);
    }

    /**
     * Display the create playbook page.
     */
    public function createPlaybook(Request $request): Response
    {
        $this->authorize('create', Playbook::class);

        // Get available plays for selection
        $filters = [];
        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }
        $filters['status'] = 'published';

        $plays = $this->playService->getPlays($filters, 100);

        // Get user's teams
        $teams = $request->user()->teams()->get(['basketball_teams.id', 'basketball_teams.name']);

        return Inertia::render('TacticBoard/Playbooks/Create', [
            'categories' => $this->playbookService->getCategories(),
            'availablePlays' => $plays,
            'teams' => $teams,
        ]);
    }

    /**
     * Display a single playbook.
     */
    public function showPlaybook(Playbook $playbook): Response
    {
        $this->authorize('view', $playbook);

        return Inertia::render('TacticBoard/Playbooks/Show', [
            'playbook' => $playbook->load(['createdBy', 'team', 'plays']),
            'statistics' => $this->playbookService->getPlaybookStatistics($playbook),
            'canEdit' => $playbook->created_by_user_id === auth()->id() ||
                auth()->user()->can('manage tactic board'),
        ]);
    }

    /**
     * Display the edit playbook page.
     */
    public function editPlaybook(Request $request, Playbook $playbook): Response
    {
        $this->authorize('update', $playbook);

        // Get available plays for selection
        $filters = [];
        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }
        $filters['status'] = 'published';

        $plays = $this->playService->getPlays($filters, 100);

        // Get user's teams
        $teams = $request->user()->teams()->get(['basketball_teams.id', 'basketball_teams.name']);

        return Inertia::render('TacticBoard/Playbooks/Edit', [
            'playbook' => $playbook->load(['createdBy', 'team', 'plays']),
            'categories' => $this->playbookService->getCategories(),
            'availablePlays' => $plays,
            'teams' => $teams,
        ]);
    }
}
