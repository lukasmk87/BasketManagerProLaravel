<?php

namespace App\Services;

use App\Models\ClubSubscriptionPlan;
use App\Models\LandingPageContent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
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
     * 1. Tenant-specific published content for requested locale
     * 2. Global published content for requested locale
     * 3. Tenant-specific published content for default locale (de)
     * 4. Global published content for default locale (de)
     * 5. Default hardcoded content
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID (null for global)
     * @param  string  $locale  The locale (default: 'de')
     * @return array The section content
     */
    public function getContent(string $section, ?int $tenantId = null, string $locale = 'de'): array
    {
        $cacheKey = $this->getCacheKey($section, $tenantId, $locale);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($section, $tenantId, $locale) {
            // Try tenant-specific content for requested locale
            if ($tenantId) {
                $content = LandingPageContent::forTenant($tenantId)
                    ->forSection($section)
                    ->where('locale', $locale)
                    ->published()
                    ->first();

                if ($content) {
                    return $content->content;
                }
            }

            // Try global content for requested locale
            $globalContent = LandingPageContent::global()
                ->forSection($section)
                ->where('locale', $locale)
                ->published()
                ->first();

            if ($globalContent) {
                return $globalContent->content;
            }

            // If not default locale, try fallback to default locale (de)
            if ($locale !== 'de') {
                if ($tenantId) {
                    $content = LandingPageContent::forTenant($tenantId)
                        ->forSection($section)
                        ->where('locale', 'de')
                        ->published()
                        ->first();

                    if ($content) {
                        return $content->content;
                    }
                }

                $globalContent = LandingPageContent::global()
                    ->forSection($section)
                    ->where('locale', 'de')
                    ->published()
                    ->first();

                if ($globalContent) {
                    return $globalContent->content;
                }
            }

            // Fall back to defaults
            return $this->getDefaultContent($section);
        });
    }

    /**
     * Get all published content for all sections.
     *
     * @param  int|null  $tenantId  The tenant ID
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
     * Get draft content for editing (published or draft).
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $locale  The locale (default: 'de')
     */
    public function getDraft(string $section, ?int $tenantId = null, string $locale = 'de'): ?LandingPageContent
    {
        $query = LandingPageContent::forSection($section)
            ->where('locale', $locale);

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
     * @param  string  $section  The section name
     * @param  array  $content  The content data
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $locale  The locale (default: 'de')
     * @param  bool  $publish  Whether to publish immediately
     */
    public function saveContent(
        string $section,
        array $content,
        ?int $tenantId = null,
        string $locale = 'de',
        bool $publish = false
    ): LandingPageContent {
        $this->validateSection($section);
        $this->validateContent($section, $content);

        $landingPageContent = LandingPageContent::updateOrCreate(
            [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ],
            [
                'content' => $content,
                'is_published' => $publish,
                'published_at' => $publish ? now() : null,
            ]
        );

        // Invalidate cache for this locale
        $this->invalidateCache($section, $tenantId, $locale);

        Log::info('Landing page content saved', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'locale' => $locale,
            'published' => $publish,
        ]);

        return $landingPageContent;
    }

    /**
     * Publish section content.
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $locale  The locale (default: 'de')
     */
    public function publishContent(string $section, ?int $tenantId = null, string $locale = 'de'): bool
    {
        $content = $this->getDraft($section, $tenantId, $locale);

        if (! $content) {
            Log::warning('Attempted to publish non-existent content', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);

            return false;
        }

        $result = $content->publish();

        if ($result) {
            $this->invalidateCache($section, $tenantId, $locale);

            Log::info('Landing page content published', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
        }

        return $result;
    }

    /**
     * Unpublish section content.
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $locale  The locale (default: 'de')
     */
    public function unpublishContent(string $section, ?int $tenantId = null, string $locale = 'de'): bool
    {
        $content = $this->getDraft($section, $tenantId, $locale);

        if (! $content) {
            return false;
        }

        $result = $content->unpublish();

        if ($result) {
            $this->invalidateCache($section, $tenantId, $locale);

            Log::info('Landing page content unpublished', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
        }

        return $result;
    }

    /**
     * Copy content from one locale to another.
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $fromLocale  Source locale
     * @param  string  $toLocale  Target locale
     * @param  bool  $overwrite  Whether to overwrite existing content
     */
    public function copyContentToLocale(
        string $section,
        ?int $tenantId = null,
        string $fromLocale = 'de',
        string $toLocale = 'en',
        bool $overwrite = false
    ): ?LandingPageContent {
        // Get source content
        $sourceContent = $this->getDraft($section, $tenantId, $fromLocale);

        if (! $sourceContent) {
            Log::warning('Source content not found for locale copy', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'from_locale' => $fromLocale,
            ]);

            return null;
        }

        // Check if target already exists
        $targetContent = $this->getDraft($section, $tenantId, $toLocale);

        if ($targetContent && ! $overwrite) {
            Log::info('Target locale content already exists, skipping copy', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'to_locale' => $toLocale,
            ]);

            return $targetContent;
        }

        // Copy content
        $copiedContent = $this->saveContent(
            $section,
            $sourceContent->content,
            $tenantId,
            $toLocale,
            false // Don't publish automatically
        );

        Log::info('Landing page content copied to new locale', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'from_locale' => $fromLocale,
            'to_locale' => $toLocale,
        ]);

        return $copiedContent;
    }

    /**
     * Delete section content.
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     */
    public function deleteContent(string $section, ?int $tenantId = null): bool
    {
        $content = $this->getDraft($section, $tenantId);

        if (! $content) {
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
     * @param  string  $section  The section name
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
                        'price' => 7.99,
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
                        'price' => 29.99,
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
                        'price' => 59.99,
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
                        'price' => null,
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
     * @param  string  $section  The section name
     *
     * @throws \InvalidArgumentException
     */
    private function validateSection(string $section): void
    {
        if (! in_array($section, LandingPageContent::SECTIONS)) {
            throw new \InvalidArgumentException("Invalid section: {$section}");
        }
    }

    /**
     * Validate content structure for a section.
     *
     * @param  string  $section  The section name
     * @param  array  $content  The content to validate
     *
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
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     */
    private function getCacheKey(string $section, ?int $tenantId, string $locale = 'de'): string
    {
        $tenantPart = $tenantId ? "tenant:{$tenantId}" : 'global';

        return "landing_page:{$tenantPart}:{$section}:{$locale}:published";
    }

    /**
     * Invalidate cache for a section (all locales).
     *
     * @param  string  $section  The section name
     * @param  int|null  $tenantId  The tenant ID
     * @param  string|null  $locale  Specific locale or null for all
     */
    private function invalidateCache(string $section, ?int $tenantId, ?string $locale = null): void
    {
        $locales = $locale ? [$locale] : ['de', 'en'];

        foreach ($locales as $loc) {
            $cacheKey = $this->getCacheKey($section, $tenantId, $loc);
            Cache::forget($cacheKey);

            Log::debug('Landing page cache invalidated', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $loc,
                'cache_key' => $cacheKey,
            ]);
        }
    }

    // =============================
    // FEATURED PLANS METHODS
    // =============================

    /**
     * Get featured subscription plans for the landing page pricing section.
     *
     * @param  int|null  $tenantId  The tenant ID
     */
    public function getFeaturedPlans(?int $tenantId = null): Collection
    {
        $query = ClubSubscriptionPlan::publiclyAvailable()
            ->orderBy('sort_order')
            ->orderBy('price');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Transform subscription plans for landing page display.
     *
     * @param  Collection  $plans  The plans collection
     */
    public function transformPlansForLandingPage(Collection $plans): array
    {
        return $plans->map(function ($plan) {
            // Brutto-Preis berechnen (19% MwSt. für deutschen Markt)
            $taxRate = 0.19;
            $grossPrice = round($plan->price * (1 + $taxRate), 2);

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'formatted_price' => $plan->formatted_price,
                'gross_price' => $grossPrice,
                'formatted_gross_price' => number_format($grossPrice, 2, ',', '.').' €',
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'description' => $plan->description,
                'features' => $this->getLocalizedFeatures($plan->getFeaturesList()),
                'limits' => $plan->getLimitsList(),
                'color' => $plan->color,
                'icon' => $plan->icon,
                'is_default' => $plan->is_default,
                'trial_period_days' => $plan->trial_period_days,
                'cta_text' => 'Jetzt starten',
                'cta_link' => route('register').'?plan='.$plan->slug,
            ];
        })->toArray();
    }

    /**
     * Get pricing content for landing page with fallback to featured plans.
     *
     * @param  int|null  $tenantId  The tenant ID
     * @param  string  $locale  The locale
     */
    public function getPricingContent(?int $tenantId = null, string $locale = 'de'): array
    {
        // Try to get featured plans from database
        $featuredPlans = $this->getFeaturedPlans($tenantId);

        // If we have featured plans, use them
        if ($featuredPlans->isNotEmpty()) {
            $staticContent = $this->getContent('pricing', $tenantId, $locale);

            return [
                'headline' => $staticContent['headline'] ?? 'Transparent und fair',
                'subheadline' => $staticContent['subheadline'] ?? 'Wähle den Plan, der zu deinem Verein passt',
                'plans' => $this->transformPlansForLandingPage($featuredPlans),
                'source' => 'featured_plans',
            ];
        }

        // Fallback to static content from LandingPageContent
        $content = $this->getContent('pricing', $tenantId, $locale);
        $content['source'] = 'static_content';

        return $content;
    }

    /**
     * Get localized feature names.
     *
     * @param  array  $features  The feature slugs
     */
    private function getLocalizedFeatures(array $features): array
    {
        $featureLabels = config('club_plans.available_features', []);

        return array_map(function ($feature) use ($featureLabels) {
            return $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
        }, $features);
    }
}
