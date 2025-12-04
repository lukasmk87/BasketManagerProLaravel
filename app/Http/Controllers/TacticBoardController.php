<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Playbook;
use App\Services\TacticBoard\PlayFavoriteService;
use App\Services\TacticBoard\PlayService;
use App\Services\TacticBoard\PlaybookService;
use App\Services\TacticBoard\PlayTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TacticBoardController extends Controller
{
    public function __construct(
        protected PlayService $playService,
        protected PlaybookService $playbookService,
        protected PlayTemplateService $templateService,
        protected PlayFavoriteService $favoriteService
    ) {
    }

    /**
     * Display the tactic board dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Play::class);

        $user = $request->user();
        $tenantFilter = [];
        if ($user->tenant_id) {
            $tenantFilter['tenant_id'] = $user->tenant_id;
        }

        // Get recent plays (last 6)
        $recentPlays = $this->playService->getPlays(
            array_merge($tenantFilter, ['status' => 'published']),
            6
        );

        // Get recent playbooks (last 6)
        $recentPlaybooks = $this->playbookService->getPlaybooks($tenantFilter, 6);

        // Get stats
        $stats = [
            'total_plays' => Play::where('created_by_user_id', $user->id)
                ->where('is_system_template', false)
                ->count(),
            'total_playbooks' => Playbook::where('created_by_user_id', $user->id)->count(),
            'favorites' => $user->playFavorites()->count(),
        ];

        return Inertia::render('TacticBoard/Index', [
            'recentPlays' => $recentPlays,
            'recentPlaybooks' => $recentPlaybooks,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the plays list page.
     */
    public function plays(Request $request): Response
    {
        $this->authorize('viewAny', Play::class);

        $filters = $request->only(['category', 'court_type', 'status', 'search']);

        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $plays = $this->playService->getPlays($filters, 12);

        return Inertia::render('TacticBoard/Plays/Index', [
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

        return Inertia::render('TacticBoard/Plays/Create', [
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
            'defaultPlayData' => $this->playService->getDefaultPlayData(),
            'featuredTemplates' => $this->templateService->getFeaturedTemplates(6),
        ]);
    }

    /**
     * Display the edit play page.
     */
    public function editPlay(Play $play): Response
    {
        $this->authorize('update', $play);

        return Inertia::render('TacticBoard/Plays/Edit', [
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

        return Inertia::render('TacticBoard/Plays/Show', [
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

    /**
     * Display the template gallery page.
     */
    public function templates(Request $request): Response
    {
        $this->authorize('viewAny', Play::class);

        $filters = $request->only(['category', 'court_type', 'search', 'tags']);

        $templates = $this->templateService->getSystemTemplates($filters, 12);

        return Inertia::render('TacticBoard/Templates/Index', [
            'templates' => $templates,
            'filters' => $filters,
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
            'availableTags' => $this->templateService->getTemplateTags(),
        ]);
    }

    /**
     * Display the user's library page (own plays + favorites).
     */
    public function library(Request $request): Response
    {
        $this->authorize('viewAny', Play::class);

        $user = $request->user();
        $tab = $request->get('tab', 'all');
        $filters = $request->only(['category', 'court_type', 'search', 'tags']);

        // Get plays based on active tab
        $plays = $this->favoriteService->getUserLibrary($user, array_merge($filters, ['tab' => $tab]), 12);

        // Get quick access favorites
        $quickAccessFavorites = $this->favoriteService->getQuickAccessFavorites($user, 5);

        // Get stats
        $stats = [
            'my_plays' => Play::where('created_by_user_id', $user->id)
                ->where('is_system_template', false)
                ->count(),
            'favorites' => $user->playFavorites()->count(),
            'total' => Play::where('created_by_user_id', $user->id)
                ->where('is_system_template', false)
                ->count() + $user->playFavorites()->count(),
        ];

        return Inertia::render('TacticBoard/Library/Index', [
            'plays' => $plays,
            'quickAccessFavorites' => $quickAccessFavorites,
            'filters' => array_merge($filters, ['tab' => $tab]),
            'categories' => $this->playService->getCategories(),
            'courtTypes' => $this->playService->getCourtTypes(),
            'availableTags' => $this->templateService->getTemplateTags(),
            'stats' => $stats,
        ]);
    }
}
