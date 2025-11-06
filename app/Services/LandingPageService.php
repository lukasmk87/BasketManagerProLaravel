<?php

namespace App\Services;

use App\Models\LandingPageContent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LandingPageService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get content for a specific section with fallback hierarchy.
     *
     * Fallback order:
     * 1. Tenant-specific published content
     * 2. Global published content
     * 3. Default hardcoded content
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID (null for global)
     * @return array The section content
     */
    public function getContent(string $section, ?int $tenantId = null): array
    {
        $cacheKey = $this->getCacheKey($section, $tenantId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($section, $tenantId) {
            // Try tenant-specific content first
            if ($tenantId) {
                $content = LandingPageContent::forTenant($tenantId)
                    ->forSection($section)
                    ->published()
                    ->first();

                if ($content) {
                    return $content->content;
                }
            }

            // Try global content
            $globalContent = LandingPageContent::global()
                ->forSection($section)
                ->published()
                ->first();

            if ($globalContent) {
                return $globalContent->content;
            }

            // Fall back to defaults
            return $this->getDefaultContent($section);
        });
    }

    /**
     * Get all published content for all sections.
     *
     * @param int|null $tenantId The tenant ID
     * @return array Array of section => content
     */
    public function getAllContent(?int $tenantId = null): array
    {
        $sections = LandingPageContent::SECTIONS;
        $content = [];

        foreach ($sections as $section) {
            $content[$section] = $this->getContent($section, $tenantId);
        }

        return $content;
    }

    /**
     * Get draft content for editing (not published).
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return LandingPageContent|null
     */
    public function getDraft(string $section, ?int $tenantId = null): ?LandingPageContent
    {
        $query = LandingPageContent::forSection($section);

        if ($tenantId) {
            $query->forTenant($tenantId);
        } else {
            $query->global();
        }

        return $query->first();
    }

    /**
     * Create or update section content.
     *
     * @param string $section The section name
     * @param array $content The content data
     * @param int|null $tenantId The tenant ID
     * @param bool $publish Whether to publish immediately
     * @return LandingPageContent
     */
    public function saveContent(
        string $section,
        array $content,
        ?int $tenantId = null,
        bool $publish = false
    ): LandingPageContent {
        $this->validateSection($section);
        $this->validateContent($section, $content);

        $landingPageContent = LandingPageContent::updateOrCreate(
            [
                'section' => $section,
                'tenant_id' => $tenantId,
            ],
            [
                'content' => $content,
                'is_published' => $publish,
                'published_at' => $publish ? now() : null,
            ]
        );

        // Invalidate cache
        $this->invalidateCache($section, $tenantId);

        Log::info('Landing page content saved', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'published' => $publish,
        ]);

        return $landingPageContent;
    }

    /**
     * Publish section content.
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return bool
     */
    public function publishContent(string $section, ?int $tenantId = null): bool
    {
        $content = $this->getDraft($section, $tenantId);

        if (!$content) {
            Log::warning('Attempted to publish non-existent content', [
                'section' => $section,
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        $result = $content->publish();

        if ($result) {
            $this->invalidateCache($section, $tenantId);

            Log::info('Landing page content published', [
                'section' => $section,
                'tenant_id' => $tenantId,
            ]);
        }

        return $result;
    }

    /**
     * Unpublish section content.
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return bool
     */
    public function unpublishContent(string $section, ?int $tenantId = null): bool
    {
        $content = $this->getDraft($section, $tenantId);

        if (!$content) {
            return false;
        }

        $result = $content->unpublish();

        if ($result) {
            $this->invalidateCache($section, $tenantId);

            Log::info('Landing page content unpublished', [
                'section' => $section,
                'tenant_id' => $tenantId,
            ]);
        }

        return $result;
    }

    /**
     * Delete section content.
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return bool
     */
    public function deleteContent(string $section, ?int $tenantId = null): bool
    {
        $content = $this->getDraft($section, $tenantId);

        if (!$content) {
            return false;
        }

        $result = $content->delete();

        if ($result) {
            $this->invalidateCache($section, $tenantId);

            Log::info('Landing page content deleted', [
                'section' => $section,
                'tenant_id' => $tenantId,
            ]);
        }

        return $result;
    }

    /**
     * Get default hardcoded content for a section.
     *
     * @param string $section The section name
     * @return array
     */
    private function getDefaultContent(string $section): array
    {
        $defaults = [
            'hero' => [
                'headline' => 'Die All-in-One Basketball Vereinsverwaltung',
                'subheadline' => '20% günstiger als die Konkurrenz • DSGVO-konform • Made in Germany',
                'cta_primary_text' => 'Kostenlos testen',
                'cta_primary_link' => '/register',
                'cta_secondary_text' => 'Demo ansehen',
                'cta_secondary_link' => '/demo',
                'stats' => [
                    'clubs' => '500+',
                    'teams' => '2.000+',
                    'players' => '15.000+',
                    'uptime' => '99,9%',
                ],
                'stats_labels' => [
                    'clubs' => 'Vereine vertrauen uns',
                    'teams' => 'Teams im System',
                    'players' => 'Zufriedene Spieler',
                    'uptime' => 'Verfügbarkeit',
                ],
            ],
            'features' => [
                'headline' => 'Alles, was dein Verein braucht',
                'subheadline' => 'Eine Plattform für alle Anforderungen moderner Basketballvereine',
                'items' => [
                    [
                        'icon' => 'chart-bar',
                        'title' => 'Live-Scoring & Statistiken',
                        'description' => 'Erfasse Spielzüge in Echtzeit und generiere automatisch detaillierte Statistiken. FG%, 3P%, PER und mehr.',
                    ],
                    [
                        'icon' => 'users',
                        'title' => 'Team & Spielerverwaltung',
                        'description' => 'Verwalte unbegrenzt Teams und Spieler mit vollständigen Profilen, Positionen, Trikotnummern und Statistiken.',
                    ],
                    [
                        'icon' => 'clipboard-list',
                        'title' => 'Training & Drill Management',
                        'description' => 'Plane Trainingseinheiten, erstelle Drills und tracke Fortschritte. Mit Vorlagen und Bibliothek.',
                    ],
                    [
                        'icon' => 'trophy',
                        'title' => 'Turniere & Ligen',
                        'description' => 'Organisiere Turniere, erstelle Brackets, verwalte Spielpläne und tracke Standings in Echtzeit.',
                    ],
                    [
                        'icon' => 'shield-check',
                        'title' => 'Notfall-Management',
                        'description' => 'QR-Code-basierter Zugriff auf Notfallkontakte. Funktioniert auch offline. DSGVO-konform.',
                    ],
                    [
                        'icon' => 'mobile',
                        'title' => 'Mobile App (PWA)',
                        'description' => 'Voller Funktionsumfang auf allen Geräten. Installierbar, offline-fähig, Push-Benachrichtigungen.',
                    ],
                ],
            ],
            'pricing' => [
                'headline' => 'Transparent und fair',
                'subheadline' => 'Wähle den Plan, der zu deinem Verein passt',
                'items' => [
                    [
                        'name' => 'Starter',
                        'price' => '7,99',
                        'period' => 'Monat',
                        'description' => 'Perfekt für kleine Vereine',
                        'features' => [
                            '2 Teams',
                            '30 Spieler',
                            'Basis-Statistiken',
                            'Team-Verwaltung',
                            '5 GB Speicher',
                        ],
                        'cta_text' => 'Jetzt starten',
                        'cta_link' => '/register?plan=starter',
                        'popular' => false,
                    ],
                    [
                        'name' => 'Club',
                        'price' => '29,99',
                        'period' => 'Monat',
                        'description' => 'Ideal für wachsende Vereine',
                        'features' => [
                            '10 Teams',
                            '150 Spieler',
                            'Live-Scoring',
                            'Erweiterte Stats',
                            '50 GB Speicher',
                            'Training-Management',
                        ],
                        'cta_text' => 'Jetzt starten',
                        'cta_link' => '/register?plan=club',
                        'popular' => true,
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '59,99',
                        'period' => 'Monat',
                        'description' => 'Für professionelle Vereine',
                        'features' => [
                            '50 Teams',
                            '500 Spieler',
                            'Alle Features',
                            'Video-Analyse',
                            '200 GB Speicher',
                            'Turniere & Ligen',
                            'Priority Support',
                        ],
                        'cta_text' => 'Jetzt starten',
                        'cta_link' => '/register?plan=professional',
                        'popular' => false,
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'period' => '',
                        'description' => 'Individuelle Lösungen',
                        'features' => [
                            'Unbegrenzte Teams',
                            'Unbegrenzte Spieler',
                            'White-Label',
                            'Dedizierter Support',
                            'Custom Features',
                            'API-Zugang',
                        ],
                        'cta_text' => 'Kontakt aufnehmen',
                        'cta_link' => '/contact',
                        'popular' => false,
                    ],
                ],
            ],
            'testimonials' => [
                'headline' => 'Was unsere Kunden sagen',
                'items' => [
                    [
                        'name' => 'Michael Schmidt',
                        'role' => 'Vorstand',
                        'club' => 'BBC München',
                        'image' => null,
                        'quote' => 'BasketManager Pro hat unsere Vereinsverwaltung revolutioniert. Die Live-Scoring-Funktion ist einfach genial!',
                        'rating' => 5,
                    ],
                    [
                        'name' => 'Andrea Klein',
                        'role' => 'Trainerin',
                        'club' => 'JSG Rheinland',
                        'image' => null,
                        'quote' => 'Die Training-Management-Features haben mir so viel Zeit gespart. Kann ich nur empfehlen!',
                        'rating' => 5,
                    ],
                    [
                        'name' => 'Thomas Hoffmann',
                        'role' => 'Geschäftsführer',
                        'club' => 'Basketball Löwen Berlin',
                        'image' => null,
                        'quote' => 'Endlich eine deutsche Lösung, die DSGVO-konform ist und alle unsere Anforderungen erfüllt.',
                        'rating' => 5,
                    ],
                ],
            ],
            'faq' => [
                'headline' => 'Häufig gestellte Fragen',
                'items' => [
                    [
                        'question' => 'Ist BasketManager Pro DSGVO-konform?',
                        'answer' => 'Ja, absolut. Wir sind zu 100% DSGVO-konform, hosten ausschließlich in Deutschland und nehmen Datenschutz sehr ernst.',
                    ],
                    [
                        'question' => 'Funktioniert die App offline?',
                        'answer' => 'Ja, die Progressive Web App funktioniert auch ohne Internetverbindung. Daten werden synchronisiert, sobald wieder eine Verbindung besteht.',
                    ],
                    [
                        'question' => 'Kann ich bestehende Daten importieren?',
                        'answer' => 'Ja, wir unterstützen Datenimporte aus verschiedenen Formaten (CSV, Excel). Unser Support-Team hilft gerne bei der Migration.',
                    ],
                    [
                        'question' => 'Gibt es eine Vertragsbindung?',
                        'answer' => 'Nein, alle Pläne sind monatlich kündbar. Du kannst jederzeit upgraden oder downgraden.',
                    ],
                    [
                        'question' => 'Was unterscheidet euch von der Konkurrenz?',
                        'answer' => '20% günstiger, Made in Germany, DSGVO-konform, moderne Technologie, exzellenter Support und speziell für Basketball entwickelt.',
                    ],
                ],
            ],
            'cta' => [
                'headline' => 'Bereit für die Zukunft deines Vereins?',
                'subheadline' => 'Starte noch heute und erlebe, wie einfach Vereinsverwaltung sein kann.',
                'cta_primary_text' => 'Kostenlos testen',
                'cta_primary_link' => '/register',
                'cta_secondary_text' => 'Demo buchen',
                'cta_secondary_link' => '/demo',
            ],
            'footer' => [
                'description' => 'Die professionelle All-in-One Lösung für Basketball-Vereinsverwaltung. Made in Germany.',
                'columns' => [
                    [
                        'title' => 'Über',
                        'links' => [
                            ['label' => 'Über uns', 'url' => '/about'],
                            ['label' => 'Blog', 'url' => '/blog'],
                            ['label' => 'Karriere', 'url' => '/careers'],
                            ['label' => 'Kontakt', 'url' => '/contact'],
                        ],
                    ],
                    [
                        'title' => 'Produkt',
                        'links' => [
                            ['label' => 'Features', 'url' => '/#features'],
                            ['label' => 'Preise', 'url' => '/#pricing'],
                            ['label' => 'Referenzen', 'url' => '/#testimonials'],
                            ['label' => 'API', 'url' => '/api-docs'],
                        ],
                    ],
                    [
                        'title' => 'Support',
                        'links' => [
                            ['label' => 'Hilfe-Center', 'url' => '/help'],
                            ['label' => 'Dokumentation', 'url' => '/docs'],
                            ['label' => 'Status', 'url' => '/status'],
                        ],
                    ],
                    [
                        'title' => 'Rechtliches',
                        'links' => [
                            ['label' => 'Datenschutz', 'url' => '/legal/datenschutz'],
                            ['label' => 'AGB', 'url' => '/legal/agb'],
                            ['label' => 'Impressum', 'url' => '/legal/impressum'],
                            ['label' => 'DSGVO', 'url' => '/legal/gdpr'],
                        ],
                    ],
                ],
            ],
            'seo' => [
                'title' => 'BasketManager Pro - Basketball Vereinsverwaltung',
                'description' => 'Die professionelle All-in-One Lösung für Basketball-Vereinsverwaltung. Live-Scoring, Statistiken, Training-Management und mehr. DSGVO-konform. Made in Germany.',
                'keywords' => 'Basketball, Vereinsverwaltung, Live-Scoring, Statistiken, Training-Management, DSGVO, Deutschland',
                'og_image' => '/images/og-image.jpg',
            ],
        ];

        return $defaults[$section] ?? [];
    }

    /**
     * Validate section name.
     *
     * @param string $section The section name
     * @throws \InvalidArgumentException
     */
    private function validateSection(string $section): void
    {
        if (!in_array($section, LandingPageContent::SECTIONS)) {
            throw new \InvalidArgumentException("Invalid section: {$section}");
        }
    }

    /**
     * Validate content structure for a section.
     *
     * @param string $section The section name
     * @param array $content The content to validate
     * @throws \InvalidArgumentException
     */
    private function validateContent(string $section, array $content): void
    {
        // Basic validation - can be extended
        if (empty($content)) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }

        // Section-specific validation
        switch ($section) {
            case 'hero':
                if (empty($content['headline'])) {
                    throw new \InvalidArgumentException('Hero section requires a headline');
                }
                break;

            case 'seo':
                if (empty($content['title']) || empty($content['description'])) {
                    throw new \InvalidArgumentException('SEO section requires title and description');
                }
                break;

            // Add more validations as needed
        }
    }

    /**
     * Get cache key for a section.
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return string
     */
    private function getCacheKey(string $section, ?int $tenantId): string
    {
        $tenantPart = $tenantId ? "tenant:{$tenantId}" : 'global';
        return "landing_page:{$tenantPart}:{$section}:published";
    }

    /**
     * Invalidate cache for a section.
     *
     * @param string $section The section name
     * @param int|null $tenantId The tenant ID
     * @return void
     */
    private function invalidateCache(string $section, ?int $tenantId): void
    {
        $cacheKey = $this->getCacheKey($section, $tenantId);
        Cache::forget($cacheKey);

        Log::debug('Landing page cache invalidated', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'cache_key' => $cacheKey,
        ]);
    }
}
