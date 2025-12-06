<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterprisePageContent;
use App\Services\EnterprisePageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EnterprisePageController extends Controller
{
    public function __construct(
        private EnterprisePageService $enterprisePageService
    ) {
    }

    /**
     * Display a listing of all enterprise page sections (Admin).
     */
    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId();
        $currentLocale = $request->query('locale', 'de');

        // Get all sections with their content for current locale
        $sections = [];
        foreach (EnterprisePageContent::SECTIONS as $section) {
            $draft = $this->enterprisePageService->getDraft($section, $tenantId, $currentLocale);

            // Get status for both locales
            $draftDe = $this->enterprisePageService->getDraft($section, $tenantId, 'de');
            $draftEn = $this->enterprisePageService->getDraft($section, $tenantId, 'en');

            $sections[] = [
                'section' => $section,
                'label' => EnterprisePageContent::getSectionLabel($section),
                'description' => $this->getSectionDescription($section),
                'icon' => EnterprisePageContent::getSectionIcon($section),
                'is_published' => $draft?->is_published ?? false,
                'published_at' => $draft?->published_at,
                'updated_at' => $draft?->updated_at,
                'has_content' => $draft !== null,
                'locale_status' => [
                    'de' => $this->getLocaleStatus($draftDe),
                    'en' => $this->getLocaleStatus($draftEn),
                ],
            ];
        }

        return Inertia::render('Admin/EnterprisePage/Index', [
            'sections' => $sections,
            'tenant_id' => $tenantId,
            'current_locale' => $currentLocale,
            'available_locales' => ['de', 'en'],
            'is_super_admin' => Auth::user()?->hasRole('super_admin'),
        ]);
    }

    /**
     * Show the form for editing a specific section (Admin).
     */
    public function edit(Request $request, string $section): Response
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $currentLocale = $request->query('locale', 'de');

        // Get existing draft or create from published/default
        $draft = $this->enterprisePageService->getDraft($section, $tenantId, $currentLocale);

        // If no draft exists, get published content to use as starting point
        if (!$draft) {
            $publishedContent = $this->enterprisePageService->getContent($section, $tenantId, $currentLocale);
            $draft = new EnterprisePageContent([
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $currentLocale,
                'content' => $publishedContent,
                'is_published' => false,
            ]);
        }

        // Check if other locale has content (for copy feature)
        $otherLocale = $currentLocale === 'de' ? 'en' : 'de';
        $otherLocaleDraft = $this->enterprisePageService->getDraft($section, $tenantId, $otherLocale);

        return Inertia::render('Admin/EnterprisePage/EditSection', [
            'section' => $section,
            'label' => EnterprisePageContent::getSectionLabel($section),
            'description' => $this->getSectionDescription($section),
            'icon' => EnterprisePageContent::getSectionIcon($section),
            'content' => $draft->content,
            'is_published' => $draft->is_published,
            'published_at' => $draft->published_at,
            'tenant_id' => $tenantId,
            'current_locale' => $currentLocale,
            'available_locales' => ['de', 'en'],
            'other_locale_has_content' => $otherLocaleDraft !== null,
            'schema' => $this->getSectionSchema($section),
        ]);
    }

    /**
     * Update a specific section (Admin).
     */
    public function update(Request $request, string $section)
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        // Validate based on section type
        $validated = $this->validateSectionContent($request, $section);

        // Save as draft (not published)
        $this->enterprisePageService->saveContent(
            $section,
            $validated['content'],
            $tenantId,
            $locale,
            false
        );

        return redirect()->route('admin.enterprise-page.index', ['locale' => $locale])
            ->with('success', 'Inhalte wurden erfolgreich als Entwurf gespeichert.');
    }

    /**
     * Publish a specific section (Admin).
     */
    public function publish(Request $request, string $section)
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        $success = $this->enterprisePageService->publishContent($section, $tenantId, $locale);

        if ($success) {
            return redirect()->route('admin.enterprise-page.index', ['locale' => $locale])
                ->with('success', 'Inhalte wurden erfolgreich veröffentlicht.');
        }

        return redirect()->route('admin.enterprise-page.index', ['locale' => $locale])
            ->with('error', 'Fehler beim Veröffentlichen. Bitte stellen Sie sicher, dass Inhalte vorhanden sind.');
    }

    /**
     * Unpublish a specific section (Admin).
     */
    public function unpublish(Request $request, string $section)
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        $success = $this->enterprisePageService->unpublishContent($section, $tenantId, $locale);

        if ($success) {
            return redirect()->route('admin.enterprise-page.index', ['locale' => $locale])
                ->with('success', 'Veröffentlichung wurde rückgängig gemacht.');
        }

        return redirect()->route('admin.enterprise-page.index', ['locale' => $locale])
            ->with('error', 'Fehler beim Zurücknehmen der Veröffentlichung.');
    }

    /**
     * Show preview of a specific section (Admin).
     */
    public function preview(Request $request, string $section): Response
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->query('locale', 'de');

        // Get draft content
        $draft = $this->enterprisePageService->getDraft($section, $tenantId, $locale);
        $content = $draft ? $draft->content : $this->enterprisePageService->getContent($section, $tenantId, $locale);

        // Return preview view
        return Inertia::render('Admin/EnterprisePage/Preview', [
            'section' => $section,
            'label' => EnterprisePageContent::getSectionLabel($section),
            'content' => $content,
            'current_locale' => $locale,
            'is_preview' => true,
        ]);
    }

    /**
     * Copy content from one locale to another (Admin).
     */
    public function copyToLocale(Request $request, string $section)
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $validated = $request->validate([
            'from_locale' => 'required|in:de,en',
            'to_locale' => 'required|in:de,en',
            'overwrite' => 'sometimes|boolean',
        ]);

        $tenantId = $this->getTenantId();

        $result = $this->enterprisePageService->copyContentToLocale(
            $section,
            $tenantId,
            $validated['from_locale'],
            $validated['to_locale'],
            $validated['overwrite'] ?? false
        );

        if ($result) {
            return redirect()
                ->route('admin.enterprise-page.edit', ['section' => $section, 'locale' => $validated['to_locale']])
                ->with('success', "Inhalte wurden erfolgreich von {$validated['from_locale']} nach {$validated['to_locale']} kopiert.");
        }

        return redirect()
            ->route('admin.enterprise-page.edit', ['section' => $section, 'locale' => $validated['to_locale']])
            ->with('error', 'Fehler beim Kopieren der Inhalte. Bitte stellen Sie sicher, dass Quellinhalte vorhanden sind.');
    }

    /**
     * Get locale status for a draft.
     */
    private function getLocaleStatus(?EnterprisePageContent $draft): string
    {
        if (!$draft) {
            return 'not_configured';
        }

        return $draft->is_published ? 'published' : 'draft';
    }

    /**
     * Get tenant ID for current user.
     * Super admin can manage global content (tenant_id = null).
     */
    private function getTenantId(): ?string
    {
        $user = Auth::user();

        // Super admin manages global content
        if ($user && $user->hasRole('super_admin')) {
            return null;
        }

        // Club admin manages their tenant content
        return $user?->tenant_id ?? $user?->club?->tenant_id ?? null;
    }

    /**
     * Get description for section.
     */
    private function getSectionDescription(string $section): string
    {
        return match ($section) {
            'seo' => 'Meta-Title, Description und Keywords für Suchmaschinen',
            'hero' => 'Hauptbereich mit Headline, Stats und Call-to-Actions',
            'audience' => 'Zielgruppen: Verbände und große Organisationen',
            'whitelabel' => 'White-Label Features und Branding-Optionen',
            'multiclub' => 'Multi-Club Management Funktionen',
            'federation' => 'DBB/FIBA Verbandsintegration Features',
            'usecases' => 'Erfolgsgeschichten und Referenzen',
            'pricing' => 'Enterprise Preise und Vergleich',
            'faq' => 'Häufig gestellte Fragen für Enterprise Kunden',
            'contact' => 'Kontakt-Bereich Headlines und Texte',
            default => '',
        };
    }

    /**
     * Get JSON schema for section editor.
     */
    private function getSectionSchema(string $section): array
    {
        return match ($section) {
            'seo' => [
                'title' => ['type' => 'text', 'required' => true, 'label' => 'Meta Title', 'maxlength' => 60],
                'description' => ['type' => 'textarea', 'required' => true, 'label' => 'Meta Description', 'maxlength' => 160],
                'keywords' => ['type' => 'text', 'required' => false, 'label' => 'Keywords'],
            ],
            'hero' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Hauptüberschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => true, 'label' => 'Unterüberschrift'],
                'stats' => ['type' => 'array', 'label' => 'Statistiken'],
                'cta_primary_text' => ['type' => 'text', 'required' => true, 'label' => 'Primärer Button Text'],
                'cta_primary_link' => ['type' => 'text', 'required' => true, 'label' => 'Primärer Button Link'],
                'cta_secondary_text' => ['type' => 'text', 'required' => false, 'label' => 'Sekundärer Button Text'],
                'cta_secondary_link' => ['type' => 'text', 'required' => false, 'label' => 'Sekundärer Button Link'],
            ],
            'audience' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'verbaende' => ['type' => 'object', 'label' => 'Für Verbände'],
                'organisationen' => ['type' => 'object', 'label' => 'Für Organisationen'],
            ],
            'whitelabel' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'items' => ['type' => 'array', 'label' => 'Features'],
            ],
            'multiclub' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'items' => ['type' => 'array', 'label' => 'Features'],
            ],
            'federation' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'items' => ['type' => 'array', 'label' => 'Integrationen'],
            ],
            'usecases' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'items' => ['type' => 'array', 'label' => 'Fallstudien'],
            ],
            'pricing' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'base_price' => ['type' => 'text', 'required' => true, 'label' => 'Basis-Preis'],
                'base_price_period' => ['type' => 'text', 'required' => false, 'label' => 'Zeitraum'],
                'base_price_note' => ['type' => 'text', 'required' => false, 'label' => 'Preis-Hinweis'],
                'features' => ['type' => 'array', 'label' => 'Feature-Liste'],
                'comparison' => ['type' => 'object', 'label' => 'Plan-Vergleich'],
                'cta_text' => ['type' => 'text', 'required' => true, 'label' => 'CTA Text'],
                'cta_link' => ['type' => 'text', 'required' => true, 'label' => 'CTA Link'],
            ],
            'faq' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'items' => ['type' => 'array', 'label' => 'FAQ Items'],
            ],
            'contact' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'success_message' => ['type' => 'textarea', 'required' => true, 'label' => 'Erfolgsmeldung'],
            ],
            default => [],
        };
    }

    /**
     * Validate section content based on section type.
     */
    private function validateSectionContent(Request $request, string $section): array
    {
        $rules = match ($section) {
            'seo' => [
                'content.title' => 'required|string|max:60',
                'content.description' => 'required|string|max:300',
                'content.keywords' => 'nullable|string|max:500',
            ],
            'hero' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'required|string|max:500',
                'content.stats' => 'nullable|array|max:6',
                'content.stats.*.value' => 'required_with:content.stats|string|max:50',
                'content.stats.*.label' => 'required_with:content.stats|string|max:100',
                'content.cta_primary_text' => 'required|string|max:50',
                'content.cta_primary_link' => 'required|string|max:500',
                'content.cta_secondary_text' => 'nullable|string|max:50',
                'content.cta_secondary_link' => 'nullable|string|max:500',
            ],
            'audience' => [
                'content.headline' => 'required|string|max:255',
                'content.verbaende.title' => 'required|string|max:100',
                'content.verbaende.subtitle' => 'nullable|string|max:200',
                'content.verbaende.items' => 'required|array|min:1|max:10',
                'content.verbaende.items.*' => 'required|string|max:200',
                'content.organisationen.title' => 'required|string|max:100',
                'content.organisationen.subtitle' => 'nullable|string|max:200',
                'content.organisationen.items' => 'required|array|min:1|max:10',
                'content.organisationen.items.*' => 'required|string|max:200',
            ],
            'whitelabel' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.items' => 'required|array|min:1|max:10',
                'content.items.*.icon' => 'required|string|max:50',
                'content.items.*.title' => 'required|string|max:100',
                'content.items.*.description' => 'required|string|max:300',
            ],
            'multiclub' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.items' => 'required|array|min:1|max:10',
                'content.items.*.title' => 'required|string|max:100',
                'content.items.*.description' => 'required|string|max:300',
            ],
            'federation' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.items' => 'required|array|min:1|max:10',
                'content.items.*.icon' => 'required|string|max:50',
                'content.items.*.title' => 'required|string|max:100',
                'content.items.*.description' => 'required|string|max:300',
                'content.items.*.features' => 'required|array|min:1',
                'content.items.*.features.*' => 'required|string|max:100',
            ],
            'usecases' => [
                'content.headline' => 'required|string|max:255',
                'content.items' => 'required|array|min:1|max:10',
                'content.items.*.name' => 'required|string|max:100',
                'content.items.*.type' => 'required|string|max:100',
                'content.items.*.stats' => 'nullable|array',
                'content.items.*.challenge' => 'required|string|max:500',
                'content.items.*.solution' => 'required|string|max:500',
                'content.items.*.result' => 'required|string|max:500',
                'content.items.*.quote' => 'nullable|string|max:500',
                'content.items.*.quote_author' => 'nullable|string|max:100',
                'content.items.*.quote_role' => 'nullable|string|max:100',
            ],
            'pricing' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.base_price' => 'required|string|max:50',
                'content.base_price_period' => 'nullable|string|max:50',
                'content.base_price_note' => 'nullable|string|max:200',
                'content.features' => 'required|array|min:1',
                'content.features.*' => 'required|string|max:200',
                'content.comparison' => 'nullable|array',
                'content.cta_text' => 'required|string|max:50',
                'content.cta_link' => 'required|string|max:500',
            ],
            'faq' => [
                'content.headline' => 'required|string|max:255',
                'content.items' => 'required|array|min:1|max:20',
                'content.items.*.question' => 'required|string|max:255',
                'content.items.*.answer' => 'required|string|max:1000',
            ],
            'contact' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.success_message' => 'required|string|max:500',
            ],
            default => [
                'content' => 'required|array',
            ],
        };

        return $request->validate($rules);
    }
}
