<?php

namespace App\Services;

use App\Models\EnterprisePageContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnterprisePageService
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
     */
    public function getContent(string $section, ?string $tenantId = null, string $locale = 'de'): array
    {
        $cacheKey = $this->getCacheKey($section, $tenantId, $locale);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($section, $tenantId, $locale) {
            // Try tenant-specific content for requested locale
            if ($tenantId) {
                $content = EnterprisePageContent::forTenant($tenantId)
                    ->forSection($section)
                    ->forLocale($locale)
                    ->published()
                    ->first();

                if ($content) {
                    return $content->content;
                }
            }

            // Try global content for requested locale
            $globalContent = EnterprisePageContent::global()
                ->forSection($section)
                ->forLocale($locale)
                ->published()
                ->first();

            if ($globalContent) {
                return $globalContent->content;
            }

            // If not default locale, try fallback to default locale (de)
            if ($locale !== 'de') {
                if ($tenantId) {
                    $content = EnterprisePageContent::forTenant($tenantId)
                        ->forSection($section)
                        ->forLocale('de')
                        ->published()
                        ->first();

                    if ($content) {
                        return $content->content;
                    }
                }

                $globalContent = EnterprisePageContent::global()
                    ->forSection($section)
                    ->forLocale('de')
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
     */
    public function getAllContent(?string $tenantId = null, string $locale = 'de'): array
    {
        $sections = EnterprisePageContent::SECTIONS;
        $content = [];

        foreach ($sections as $section) {
            $content[$section] = $this->getContent($section, $tenantId, $locale);
        }

        return $content;
    }

    /**
     * Get draft content for editing (published or draft).
     */
    public function getDraft(string $section, ?string $tenantId = null, string $locale = 'de'): ?EnterprisePageContent
    {
        $query = EnterprisePageContent::forSection($section)
            ->forLocale($locale);

        if ($tenantId) {
            $query->forTenant($tenantId);
        } else {
            $query->global();
        }

        return $query->first();
    }

    /**
     * Create or update section content.
     */
    public function saveContent(
        string $section,
        array $content,
        ?string $tenantId = null,
        string $locale = 'de',
        bool $publish = false
    ): EnterprisePageContent {
        $this->validateSection($section);
        $this->validateContent($section, $content);

        $enterprisePageContent = EnterprisePageContent::updateOrCreate(
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

        Log::info('Enterprise page content saved', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'locale' => $locale,
            'published' => $publish,
        ]);

        return $enterprisePageContent;
    }

    /**
     * Publish section content.
     */
    public function publishContent(string $section, ?string $tenantId = null, string $locale = 'de'): bool
    {
        $content = $this->getDraft($section, $tenantId, $locale);

        if (!$content) {
            Log::warning('Attempted to publish non-existent enterprise content', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
            return false;
        }

        $result = $content->publish();

        if ($result) {
            $this->invalidateCache($section, $tenantId, $locale);

            Log::info('Enterprise page content published', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
        }

        return $result;
    }

    /**
     * Unpublish section content.
     */
    public function unpublishContent(string $section, ?string $tenantId = null, string $locale = 'de'): bool
    {
        $content = $this->getDraft($section, $tenantId, $locale);

        if (!$content) {
            return false;
        }

        $result = $content->unpublish();

        if ($result) {
            $this->invalidateCache($section, $tenantId, $locale);

            Log::info('Enterprise page content unpublished', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
        }

        return $result;
    }

    /**
     * Copy content from one locale to another.
     */
    public function copyContentToLocale(
        string $section,
        ?string $tenantId = null,
        string $fromLocale = 'de',
        string $toLocale = 'en',
        bool $overwrite = false
    ): ?EnterprisePageContent {
        // Get source content
        $sourceContent = $this->getDraft($section, $tenantId, $fromLocale);

        if (!$sourceContent) {
            Log::warning('Source content not found for locale copy', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'from_locale' => $fromLocale,
            ]);
            return null;
        }

        // Check if target already exists
        $targetContent = $this->getDraft($section, $tenantId, $toLocale);

        if ($targetContent && !$overwrite) {
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

        Log::info('Enterprise page content copied to new locale', [
            'section' => $section,
            'tenant_id' => $tenantId,
            'from_locale' => $fromLocale,
            'to_locale' => $toLocale,
        ]);

        return $copiedContent;
    }

    /**
     * Delete section content.
     */
    public function deleteContent(string $section, ?string $tenantId = null, string $locale = 'de'): bool
    {
        $content = $this->getDraft($section, $tenantId, $locale);

        if (!$content) {
            return false;
        }

        $result = $content->delete();

        if ($result) {
            $this->invalidateCache($section, $tenantId, $locale);

            Log::info('Enterprise page content deleted', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $locale,
            ]);
        }

        return $result;
    }

    /**
     * Get all sections status for a tenant/global.
     */
    public function getSectionsStatus(?string $tenantId = null, string $locale = 'de'): array
    {
        $sections = [];

        foreach (EnterprisePageContent::SECTIONS as $section) {
            $content = $this->getDraft($section, $tenantId, $locale);

            $sections[] = [
                'section' => $section,
                'label' => EnterprisePageContent::getSectionLabel($section),
                'icon' => EnterprisePageContent::getSectionIcon($section),
                'has_content' => $content !== null,
                'is_published' => $content?->is_published ?? false,
                'published_at' => $content?->published_at,
                'updated_at' => $content?->updated_at,
            ];
        }

        return $sections;
    }

    /**
     * Get default hardcoded content for a section.
     */
    private function getDefaultContent(string $section): array
    {
        $defaults = [
            'seo' => [
                'title' => 'Enterprise & White-Label Lösung | ' . app_name(),
                'description' => 'Die White-Label Basketball-Plattform für Verbände und große Vereine. Eigene Marke, DBB/FIBA Integration, unbegrenzte Teams. Ab €499/Monat.',
                'keywords' => 'Basketball Software Enterprise, White Label Basketball, Verband Software, DBB Integration, Basketball Vereinsverwaltung, Multi-Club Management',
            ],
            'hero' => [
                'headline' => 'Ihre Basketball-Plattform. Ihre Marke.',
                'subheadline' => 'Die vollständig anpassbare White-Label-Lösung für Verbände und große Organisationen',
                'stats' => [
                    ['value' => '99,9%', 'label' => 'SLA Garantie'],
                    ['value' => 'Unbegrenzt', 'label' => 'Teams & Spieler'],
                    ['value' => 'Custom', 'label' => 'Domain'],
                    ['value' => '24/7', 'label' => 'Dedizierter Support'],
                ],
                'cta_primary_text' => 'Beratungsgespräch vereinbaren',
                'cta_primary_link' => '#contact',
                'cta_secondary_text' => 'Preise anfragen',
                'cta_secondary_link' => '#pricing',
            ],
            'audience' => [
                'headline' => 'Für wen ist die Enterprise-Lösung?',
                'verbaende' => [
                    'title' => 'Für Verbände',
                    'subtitle' => 'DBB Landesverbände & Regionalverbände',
                    'items' => [
                        'Liga-Management für alle Ligen & Altersklassen',
                        'Zentrale Spieler-Lizenzierung & Eligibility-Prüfung',
                        'Automatische DBB/FIBA Verbands-Integration',
                        'Einheitliche Plattform für alle Vereine',
                        'Verbands-weite Statistiken & Reporting',
                    ],
                ],
                'organisationen' => [
                    'title' => 'Für große Organisationen',
                    'subtitle' => 'Multi-Team Vereine & Akademien',
                    'items' => [
                        'Vollständige Marken-Integration (White-Label)',
                        'Multi-Club-Verwaltung unter einem Dach',
                        'Zentrale Administration mit delegierten Rechten',
                        'Unbegrenzte API-Anbindung für eigene Systeme',
                        'Dedizierte Serverressourcen & Premium-Support',
                    ],
                ],
            ],
            'whitelabel' => [
                'headline' => 'Ihr Branding. Überall.',
                'subheadline' => 'Ihre Marke steht im Vordergrund – von der Domain bis zur E-Mail',
                'items' => [
                    [
                        'icon' => 'globe',
                        'title' => 'Eigene Domain / Subdomain',
                        'description' => 'ihr-verband.de oder verband.basketmanager-pro.com',
                    ],
                    [
                        'icon' => 'photograph',
                        'title' => 'Vollständiges Logo-Branding',
                        'description' => 'Ihr Logo in Navigation, E-Mails, PDF-Exports und Mobile App',
                    ],
                    [
                        'icon' => 'color-swatch',
                        'title' => 'Anpassbare Farbschemata',
                        'description' => 'Ihre Vereinsfarben systemweit in allen Oberflächen',
                    ],
                    [
                        'icon' => 'mail',
                        'title' => 'Gebrandete E-Mail-Kommunikation',
                        'description' => 'Absender: noreply@ihr-verband.de mit Ihrem Design',
                    ],
                    [
                        'icon' => 'template',
                        'title' => 'Personalisierte Landing Pages',
                        'description' => 'Eigene Startseite und Registrierung für Ihre Mitglieder',
                    ],
                    [
                        'icon' => 'document-report',
                        'title' => 'Gebrandete PDF-Exports',
                        'description' => 'Statistiken, Spielberichte und Dokumente mit Ihrem Logo',
                    ],
                ],
            ],
            'multiclub' => [
                'headline' => 'Eine Plattform. Alle Vereine.',
                'subheadline' => 'Zentrale Verwaltung mit maximaler Flexibilität',
                'items' => [
                    [
                        'title' => 'Zentrale Benutzerverwaltung',
                        'description' => 'Single Sign-On für alle Vereine, rollenbasierte Berechtigungen auf Verbands- und Vereinsebene',
                    ],
                    [
                        'title' => 'Vereinsübergreifende Statistiken',
                        'description' => 'Aggregierte Daten über alle Clubs hinweg, Benchmarks und Vergleiche',
                    ],
                    [
                        'title' => 'Delegierte Administration',
                        'description' => 'Vereine verwalten ihre Teams selbstständig innerhalb der Verbandsvorgaben',
                    ],
                    [
                        'title' => 'Verbands-weite Turniere',
                        'description' => 'Meisterschaften, Pokale und Events mit automatischer Meldung aller Vereine',
                    ],
                    [
                        'title' => 'Konsolidiertes Reporting',
                        'description' => 'Ein Dashboard für den kompletten Überblick über alle Aktivitäten',
                    ],
                ],
            ],
            'federation' => [
                'headline' => 'Nahtlose Verbandsintegration',
                'subheadline' => 'Direkte Anbindung an offizielle Verbandssysteme',
                'items' => [
                    [
                        'icon' => 'badge-check',
                        'title' => 'DBB API Integration',
                        'description' => 'Direkte Anbindung an den Deutschen Basketball Bund',
                        'features' => ['Spieler-Lizenzprüfung', 'Liga-Daten Sync', 'Ergebnis-Übermittlung'],
                    ],
                    [
                        'icon' => 'globe-alt',
                        'title' => 'FIBA Europe Integration',
                        'description' => 'Für internationale Wettbewerbe und Turniere',
                        'features' => ['Internationale Eligibility', 'Competition-Registrierung', 'Official Game Data'],
                    ],
                    [
                        'icon' => 'refresh',
                        'title' => 'Automatische Synchronisation',
                        'description' => 'Echtzeit-Updates zwischen Ihrem System und den Verbänden',
                        'features' => ['Spielergebnisse', 'Tabellenstände', 'Team-Registrierungen'],
                    ],
                ],
            ],
            'usecases' => [
                'headline' => 'Erfolgsgeschichten',
                'items' => [
                    [
                        'name' => 'Landesverband NRW',
                        'type' => 'Landesverband',
                        'stats' => [
                            'clubs' => '52 Vereine',
                            'teams' => '200+ Teams',
                            'players' => '3.500 Spieler',
                        ],
                        'challenge' => 'Dezentrale Datenhaltung, manuelle Spielplan-Erstellung, keine einheitliche Statistik-Erfassung über alle Ligen.',
                        'solution' => 'Einführung der Enterprise White-Label Lösung mit zentraler Plattform für alle Vereine, automatischer DBB-Integration und Live-Scoring.',
                        'result' => '70% weniger Verwaltungsaufwand, Echtzeit-Statistiken für alle Ligen, einheitliche Spieler-Datenbank.',
                        'quote' => 'Mit BasketManager Pro haben wir endlich eine einheitliche Plattform für alle unsere Vereine. Die Zeitersparnis ist enorm.',
                        'quote_author' => 'Thomas Weber',
                        'quote_role' => 'Geschäftsführer',
                    ],
                ],
            ],
            'pricing' => [
                'headline' => 'Individuelle Lösungen. Faire Preise.',
                'subheadline' => 'Maßgeschneidert auf Ihre Anforderungen',
                'base_price' => 'Ab €499',
                'base_price_period' => '/Monat',
                'base_price_note' => 'Preis abhängig von Anzahl Vereine/Teams',
                'features' => [
                    'Alles aus Professional, plus:',
                    'Vollständiges White-Label (eigene Domain, Logo, Farben)',
                    'Multi-Club-Management (unbegrenzte Vereine)',
                    'DBB & FIBA Verbands-Integration',
                    'Unbegrenzter API-Zugang',
                    'Dedizierter Support Manager',
                    '99,9% SLA Garantie',
                    'Custom Integrationen',
                    'DSGVO Compliance Tools',
                    'Erweiterte Audit-Logs',
                ],
                'comparison' => [
                    'professional' => [
                        'name' => 'Professional',
                        'price' => '€149/Monat',
                        'teams' => 'Bis zu 50 Teams',
                        'clubs' => 'Bis zu 10 Clubs',
                        'api' => '5.000 API Calls/Stunde',
                        'support' => 'Prioritäts-Support',
                        'whitelabel' => 'Basis-Branding',
                    ],
                    'enterprise' => [
                        'name' => 'Enterprise',
                        'price' => 'Ab €499/Monat',
                        'teams' => 'Unbegrenzt',
                        'clubs' => 'Unbegrenzt',
                        'api' => 'Unbegrenzter API-Zugang',
                        'support' => 'Dedizierter Manager',
                        'whitelabel' => 'Vollständiges White-Label',
                    ],
                ],
                'cta_text' => 'Individuelles Angebot anfragen',
                'cta_link' => '#contact',
            ],
            'faq' => [
                'headline' => 'Häufig gestellte Fragen',
                'items' => [
                    [
                        'question' => 'Wie lange dauert die Einrichtung einer White-Label-Lösung?',
                        'answer' => 'Die typische Einrichtungszeit beträgt 2-4 Wochen, abhängig von Ihren Anpassungswünschen. Dies beinhaltet Domain-Setup, Branding-Konfiguration, Datenmigration und Schulung.',
                    ],
                    [
                        'question' => 'Können wir unsere bestehenden Daten migrieren?',
                        'answer' => 'Ja, wir unterstützen Datenmigration aus verschiedenen Quellen. Unser Team analysiert Ihre bestehenden Datenstrukturen und erstellt einen individuellen Migrationsplan.',
                    ],
                    [
                        'question' => 'Gibt es eine Mindestvertragslaufzeit?',
                        'answer' => 'Enterprise-Verträge haben standardmäßig eine Laufzeit von 12 Monaten. Bei größeren Verbänden sind auch individuelle Konditionen möglich.',
                    ],
                    [
                        'question' => 'Welche SLA-Garantien gibt es?',
                        'answer' => 'Wir garantieren 99,9% Verfügbarkeit mit einer maximalen Response-Zeit von 4 Stunden bei kritischen Issues. Bei Unterschreitung erhalten Sie anteilige Gutschriften.',
                    ],
                    [
                        'question' => 'Wie funktioniert die DBB-/Verbandsintegration?',
                        'answer' => 'Über offizielle APIs verbinden wir Ihre Plattform direkt mit dem DBB und ggf. FIBA. Spieler-Lizenzprüfungen, Ergebnis-Übermittlung und Liga-Daten werden automatisch synchronisiert.',
                    ],
                    [
                        'question' => 'Können wir die API für eigene Entwicklungen nutzen?',
                        'answer' => 'Ja, Enterprise-Kunden erhalten unbegrenzten API-Zugang mit dediziertem API-Key. Wir stellen umfangreiche Dokumentation und Support bei der Integration bereit.',
                    ],
                    [
                        'question' => 'Ist die Lösung DSGVO-konform?',
                        'answer' => 'Absolut. Alle Daten werden auf deutschen Servern gehostet. Wir bieten integrierte Compliance-Tools, Auftragsverarbeitungsverträge (AVV) und regelmäßige Sicherheitsaudits.',
                    ],
                ],
            ],
            'contact' => [
                'headline' => 'Lassen Sie uns sprechen',
                'subheadline' => 'Erfahren Sie, wie wir Ihren Verband oder Ihre Organisation unterstützen können',
                'success_message' => 'Vielen Dank für Ihre Anfrage! Wir melden uns innerhalb von 24 Stunden bei Ihnen.',
            ],
        ];

        return $defaults[$section] ?? [];
    }

    /**
     * Validate section name.
     */
    private function validateSection(string $section): void
    {
        if (!in_array($section, EnterprisePageContent::SECTIONS)) {
            throw new \InvalidArgumentException("Invalid enterprise section: {$section}");
        }
    }

    /**
     * Validate content structure for a section.
     */
    private function validateContent(string $section, array $content): void
    {
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

            case 'audience':
                if (empty($content['headline'])) {
                    throw new \InvalidArgumentException('Audience section requires a headline');
                }
                break;

            case 'pricing':
                if (empty($content['headline'])) {
                    throw new \InvalidArgumentException('Pricing section requires a headline');
                }
                break;

            case 'faq':
                if (empty($content['headline'])) {
                    throw new \InvalidArgumentException('FAQ section requires a headline');
                }
                break;
        }
    }

    /**
     * Get cache key for a section.
     */
    private function getCacheKey(string $section, ?string $tenantId, string $locale = 'de'): string
    {
        $tenantPart = $tenantId ? "tenant:{$tenantId}" : 'global';
        return "enterprise_page:{$tenantPart}:{$section}:{$locale}:published";
    }

    /**
     * Invalidate cache for a section (all locales).
     */
    private function invalidateCache(string $section, ?string $tenantId, ?string $locale = null): void
    {
        $locales = $locale ? [$locale] : ['de', 'en'];

        foreach ($locales as $loc) {
            $cacheKey = $this->getCacheKey($section, $tenantId, $loc);
            Cache::forget($cacheKey);

            Log::debug('Enterprise page cache invalidated', [
                'section' => $section,
                'tenant_id' => $tenantId,
                'locale' => $loc,
                'cache_key' => $cacheKey,
            ]);
        }
    }
}
