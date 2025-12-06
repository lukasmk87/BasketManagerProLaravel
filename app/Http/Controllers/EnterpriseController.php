<?php

namespace App\Http\Controllers;

use App\Models\EnterpriseLead;
use App\Services\EnterprisePageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnterpriseController extends Controller
{
    public function __construct(
        private EnterprisePageService $enterprisePageService
    ) {}

    /**
     * Display the enterprise marketing page.
     */
    public function index(): View
    {
        // Get content from CMS (with fallback to defaults)
        $locale = app()->getLocale();
        $content = $this->enterprisePageService->getAllContent(null, $locale);

        return view('enterprise', [
            'content' => $content,
            'organizationTypes' => EnterpriseLead::ORGANIZATION_TYPES,
            'clubCountOptions' => EnterpriseLead::CLUB_COUNT_OPTIONS,
            'teamCountOptions' => EnterpriseLead::TEAM_COUNT_OPTIONS,
        ]);
    }
}
