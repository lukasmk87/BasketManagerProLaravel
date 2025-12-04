<?php

namespace App\Http\Controllers;

use App\Models\Drill;
use App\Services\TacticBoard\CategoryService;
use App\Services\TacticBoard\DrillService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DrillTacticBoardController extends Controller
{
    public function __construct(
        protected DrillService $drillService,
        protected CategoryService $categoryService
    ) {
    }

    /**
     * Display the drills list page.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Drill::class);

        $filters = $request->only(['category', 'category_id', 'court_type', 'status', 'difficulty_level', 'search', 'has_visual']);

        if ($request->user()->tenant_id) {
            $filters['tenant_id'] = $request->user()->tenant_id;
        }

        $drills = $this->drillService->getDrills($filters, 12);

        return Inertia::render('TacticBoard/Drills/Index', [
            'drills' => $drills,
            'filters' => $filters,
            'categories' => $this->categoryService->getCategoriesForDrills($request->user()->tenant_id),
            'courtTypes' => $this->drillService->getCourtTypes(),
            'difficultyLevels' => $this->drillService->getDifficultyLevels(),
        ]);
    }

    /**
     * Display the create drill page.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Drill::class);

        return Inertia::render('TacticBoard/Drills/Create', [
            'categories' => $this->categoryService->getCategoriesForDrills($request->user()->tenant_id),
            'courtTypes' => $this->drillService->getCourtTypes(),
            'difficultyLevels' => $this->drillService->getDifficultyLevels(),
            'ageGroups' => $this->drillService->getAgeGroups(),
            'defaultDrillData' => $this->drillService->getDefaultDrillData(),
        ]);
    }

    /**
     * Display the edit drill page.
     */
    public function edit(Request $request, Drill $drill): Response
    {
        $this->authorize('update', $drill);

        return Inertia::render('TacticBoard/Drills/Edit', [
            'drill' => $drill->load(['createdBy', 'tacticCategory']),
            'categories' => $this->categoryService->getCategoriesForDrills($request->user()->tenant_id),
            'courtTypes' => $this->drillService->getCourtTypes(),
            'difficultyLevels' => $this->drillService->getDifficultyLevels(),
            'ageGroups' => $this->drillService->getAgeGroups(),
        ]);
    }

    /**
     * Display a single drill.
     */
    public function show(Drill $drill): Response
    {
        $this->authorize('view', $drill);

        return Inertia::render('TacticBoard/Drills/Show', [
            'drill' => $drill->load(['createdBy', 'tacticCategory', 'plays']),
            'canEdit' => $drill->created_by_user_id === auth()->id() ||
                auth()->user()->can('manage tactic board'),
        ]);
    }
}
