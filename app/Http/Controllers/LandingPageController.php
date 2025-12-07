<?php

namespace App\Http\Controllers;

use App\Models\LandingPageContent;
use App\Services\LandingPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {}

    /**
     * Display a listing of all landing page sections (Admin).
     */
    public function index(Request $request): Response
    {
        // Authorization handled by route middleware: role:admin|super_admin
        $tenantId = $this->getTenantId();
        $currentLocale = $request->query('locale', 'de');

        // Get all sections with their content for current locale
        $sections = [];
        foreach (LandingPageContent::SECTIONS as $section) {
            $draft = $this->landingPageService->getDraft($section, $tenantId, $currentLocale);

            // Get status for both locales
            $draftDe = $this->landingPageService->getDraft($section, $tenantId, 'de');
            $draftEn = $this->landingPageService->getDraft($section, $tenantId, 'en');

            $sections[] = [
                'section' => $section,
                'label' => $this->getSectionLabel($section),
                'description' => $this->getSectionDescription($section),
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

        return Inertia::render('Admin/LandingPage/Index', [
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
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $currentLocale = $request->query('locale', 'de');

        // Get existing draft or create from published/default
        $draft = $this->landingPageService->getDraft($section, $tenantId, $currentLocale);

        // If no draft exists, get published content to use as starting point
        if (! $draft) {
            $publishedContent = $this->landingPageService->getContent($section, $tenantId, $currentLocale);
            $draft = new LandingPageContent([
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $currentLocale,
                'content' => $publishedContent,
                'is_published' => false,
            ]);
        }

        // Check if other locale has content (for copy feature)
        $otherLocale = $currentLocale === 'de' ? 'en' : 'de';
        $otherLocaleDraft = $this->landingPageService->getDraft($section, $tenantId, $otherLocale);

        return Inertia::render('Admin/LandingPage/EditSection', [
            'section' => $section,
            'label' => $this->getSectionLabel($section),
            'description' => $this->getSectionDescription($section),
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
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        // Validate based on section type
        $validated = $this->validateSectionContent($request, $section);

        // Save as draft (not published)
        $this->landingPageService->saveContent(
            $section,
            $validated['content'],
            $tenantId,
            $locale,
            false
        );

        return redirect()->route('admin.landing-page.index', ['locale' => $locale])
            ->with('success', 'Inhalte wurden erfolgreich als Entwurf gespeichert.');
    }

    /**
     * Publish a specific section (Admin).
     */
    public function publish(Request $request, string $section)
    {
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        // If content was sent with the request, save it first before publishing
        if ($request->has('content')) {
            $validated = $this->validateSectionContent($request, $section);
            $this->landingPageService->saveContent(
                $section,
                $validated['content'],
                $tenantId,
                $locale,
                false  // Don't publish here, publishContent() will do it
            );
        }

        $success = $this->landingPageService->publishContent($section, $tenantId, $locale);

        if ($success) {
            return redirect()->route('admin.landing-page.index', ['locale' => $locale])
                ->with('success', 'Inhalte wurden erfolgreich veröffentlicht.');
        }

        return redirect()->route('admin.landing-page.index', ['locale' => $locale])
            ->with('error', 'Fehler beim Veröffentlichen. Bitte stellen Sie sicher, dass Inhalte vorhanden sind.');
    }

    /**
     * Unpublish a specific section (Admin).
     */
    public function unpublish(Request $request, string $section)
    {
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->input('locale', 'de');

        $success = $this->landingPageService->unpublishContent($section, $tenantId, $locale);

        if ($success) {
            return redirect()->route('admin.landing-page.index', ['locale' => $locale])
                ->with('success', 'Veröffentlichung wurde rückgängig gemacht.');
        }

        return redirect()->route('admin.landing-page.index', ['locale' => $locale])
            ->with('error', 'Fehler beim Zurücknehmen der Veröffentlichung.');
    }

    /**
     * Show preview of a specific section (Admin).
     */
    public function preview(Request $request, string $section): Response
    {
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $tenantId = $this->getTenantId();
        $locale = $request->query('locale', 'de');

        // Get draft content
        $draft = $this->landingPageService->getDraft($section, $tenantId, $locale);
        $content = $draft ? $draft->content : $this->landingPageService->getContent($section, $tenantId, $locale);

        // Return preview view
        return Inertia::render('Admin/LandingPage/Preview', [
            'section' => $section,
            'label' => $this->getSectionLabel($section),
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
        // Authorization handled by route middleware: role:admin|super_admin
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            abort(404, 'Section not found');
        }

        $validated = $request->validate([
            'from_locale' => 'required|in:de,en',
            'to_locale' => 'required|in:de,en',
            'overwrite' => 'sometimes|boolean',
        ]);

        $tenantId = $this->getTenantId();

        $result = $this->landingPageService->copyContentToLocale(
            $section,
            $tenantId,
            $validated['from_locale'],
            $validated['to_locale'],
            $validated['overwrite'] ?? false
        );

        if ($result) {
            return redirect()
                ->route('admin.landing-page.edit', ['section' => $section, 'locale' => $validated['to_locale']])
                ->with('success', "Inhalte wurden erfolgreich von {$validated['from_locale']} nach {$validated['to_locale']} kopiert.");
        }

        return redirect()
            ->route('admin.landing-page.edit', ['section' => $section, 'locale' => $validated['to_locale']])
            ->with('error', 'Fehler beim Kopieren der Inhalte. Bitte stellen Sie sicher, dass Quellinhalte vorhanden sind.');
    }

    /**
     * Get locale status for a draft.
     */
    private function getLocaleStatus(?LandingPageContent $draft): string
    {
        if (! $draft) {
            return 'not_configured';
        }

        return $draft->is_published ? 'published' : 'draft';
    }

    /**
     * Get tenant ID for current user.
     * Super admin can manage global content (tenant_id = null).
     * Club admin manages their own tenant content.
     */
    private function getTenantId(): ?int
    {
        $user = Auth::user();

        // Super admin manages global content
        if ($user && $user->hasRole('super_admin')) {
            return null;
        }

        // Club admin manages their tenant content
        // Assuming user has a club relationship or tenant_id
        return $user?->tenant_id ?? $user?->club?->tenant_id ?? null;
    }

    /**
     * Get user-friendly label for section.
     */
    private function getSectionLabel(string $section): string
    {
        return match ($section) {
            'hero' => 'Hero Bereich',
            'features' => 'Features',
            'pricing' => 'Preise',
            'testimonials' => 'Referenzen',
            'faq' => 'FAQ',
            'cta' => 'Call-to-Action',
            'footer' => 'Footer',
            'seo' => 'SEO Metadaten',
            default => ucfirst($section),
        };
    }

    /**
     * Get description for section.
     */
    private function getSectionDescription(string $section): string
    {
        return match ($section) {
            'hero' => 'Hauptbereich mit Headline, Subheadline und Call-to-Actions',
            'features' => 'Feature-Cards mit Icons, Titeln und Beschreibungen',
            'pricing' => 'Preispläne mit Features und Call-to-Actions',
            'testimonials' => 'Kundenstimmen und Referenzen',
            'faq' => 'Häufig gestellte Fragen mit Antworten',
            'cta' => 'Abschließender Call-to-Action Bereich',
            'footer' => 'Footer mit Links und Informationen',
            'seo' => 'Meta-Title, Description und Keywords für Suchmaschinen',
            default => '',
        };
    }

    /**
     * Get JSON schema for section editor.
     */
    private function getSectionSchema(string $section): array
    {
        // Return schema definition for frontend form generation
        // This helps the Vue component know what fields to render
        return match ($section) {
            'hero' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Hauptüberschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => true, 'label' => 'Unterüberschrift'],
                'cta_primary_text' => ['type' => 'text', 'required' => true, 'label' => 'Primärer Button Text'],
                'cta_primary_link' => ['type' => 'url', 'required' => true, 'label' => 'Primärer Button Link'],
                'cta_secondary_text' => ['type' => 'text', 'required' => false, 'label' => 'Sekundärer Button Text'],
                'cta_secondary_link' => ['type' => 'url', 'required' => false, 'label' => 'Sekundärer Button Link'],
                'stats' => ['type' => 'object', 'label' => 'Statistiken'],
            ],
            'seo' => [
                'title' => ['type' => 'text', 'required' => true, 'label' => 'Meta Title', 'maxlength' => 60],
                'description' => ['type' => 'textarea', 'required' => true, 'label' => 'Meta Description', 'maxlength' => 160],
                'keywords' => ['type' => 'text', 'required' => false, 'label' => 'Keywords'],
                'og_image' => ['type' => 'image', 'required' => false, 'label' => 'Open Graph Bild'],
            ],
            'cta' => [
                'headline' => ['type' => 'text', 'required' => true, 'label' => 'Überschrift'],
                'subheadline' => ['type' => 'textarea', 'required' => false, 'label' => 'Unterüberschrift'],
                'cta_primary_text' => ['type' => 'text', 'required' => true, 'label' => 'Primärer Button Text'],
                'cta_primary_link' => ['type' => 'url', 'required' => true, 'label' => 'Primärer Button Link'],
                'cta_secondary_text' => ['type' => 'text', 'required' => false, 'label' => 'Sekundärer Button Text'],
                'cta_secondary_link' => ['type' => 'url', 'required' => false, 'label' => 'Sekundärer Button Link'],
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
            'hero' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'required|string|max:500',
                'content.cta_primary_text' => 'required|string|max:50',
                'content.cta_primary_link' => 'required|string|max:500',
                'content.cta_secondary_text' => 'nullable|string|max:50',
                'content.cta_secondary_link' => 'nullable|string|max:500',
                'content.stats' => 'nullable|array',
            ],
            'seo' => [
                'content.title' => 'required|string|max:60',
                'content.description' => 'required|string|max:160',
                'content.keywords' => 'nullable|string|max:500',
                'content.og_image' => 'nullable|string|max:500',
            ],
            'cta' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.cta_primary_text' => 'required|string|max:50',
                'content.cta_primary_link' => 'required|string|max:500',
                'content.cta_secondary_text' => 'nullable|string|max:50',
                'content.cta_secondary_link' => 'nullable|string|max:500',
            ],
            'faq' => [
                'content.headline' => 'required|string|max:255',
                'content.items' => 'required|array|min:1|max:20',
                'content.items.*.question' => 'required|string|max:255',
                'content.items.*.answer' => 'required|string|max:1000',
            ],
            'features' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.items' => 'required|array|min:1|max:12',
                'content.items.*.icon' => 'required|string|max:50',
                'content.items.*.title' => 'required|string|max:100',
                'content.items.*.description' => 'required|string|max:200',
            ],
            'testimonials' => [
                'content.headline' => 'required|string|max:255',
                'content.items' => 'required|array|min:1|max:10',
                'content.items.*.name' => 'required|string|max:100',
                'content.items.*.role' => 'required|string|max:100',
                'content.items.*.club' => 'required|string|max:100',
                'content.items.*.quote' => 'required|string|max:300',
                'content.items.*.rating' => 'required|integer|min:1|max:5',
                'content.items.*.image' => 'nullable|string|max:500',
            ],
            'pricing' => [
                'content.headline' => 'required|string|max:255',
                'content.subheadline' => 'nullable|string|max:500',
                'content.items' => 'required|array|min:1|max:6',
                'content.items.*.name' => 'required|string|max:100',
                'content.items.*.price' => 'required|string|max:50',
                'content.items.*.period' => 'nullable|string|max:50',
                'content.items.*.description' => 'nullable|string|max:200',
                'content.items.*.features' => 'required|array|min:1',
                'content.items.*.features.*' => 'required|string|max:200',
                'content.items.*.cta_text' => 'required|string|max:50',
                'content.items.*.cta_link' => 'required|string|max:500',
                'content.items.*.popular' => 'boolean',
            ],
            default => [
                'content' => 'required|array',
            ],
        };

        return $request->validate($rules);
    }
}
