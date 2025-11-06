<?php

namespace Database\Seeders;

use App\Models\LandingPageContent;
use App\Services\LandingPageService;
use Illuminate\Database\Seeder;

class LandingPageContentSeeder extends Seeder
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {
    }

    /**
     * Run the database seeds.
     *
     * Seed global default content for all landing page sections.
     * These will be used as fallback when no tenant-specific content exists.
     */
    public function run(): void
    {
        $this->command->info('Seeding landing page default content...');

        $sections = LandingPageContent::SECTIONS;

        foreach ($sections as $section) {
            // Get default content from service
            $defaultContent = $this->getDefaultContentForSection($section);

            // Create or update global content (tenant_id = null)
            LandingPageContent::updateOrCreate(
                [
                    'section' => $section,
                    'tenant_id' => null,
                ],
                [
                    'content' => $defaultContent,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );

            $this->command->info("  ✓ Seeded section: {$section}");
        }

        $this->command->info('Landing page content seeded successfully!');
    }

    /**
     * Get default content for a specific section.
     * This mirrors the structure in LandingPageService::getDefaultContent()
     */
    private function getDefaultContentForSection(string $section): array
    {
        return match ($section) {
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

            default => [],
        };
    }
}
