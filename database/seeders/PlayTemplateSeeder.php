<?php

namespace Database\Seeders;

use App\Models\Play;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlayTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = $this->getTemplates();

        // Get the first super admin user for created_by_user_id
        $superAdmin = \App\Models\User::role('super_admin')->first();
        $createdByUserId = $superAdmin?->id ?? 1;

        foreach ($templates as $index => $template) {
            Play::updateOrCreate(
                [
                    'name' => $template['name'],
                    'is_system_template' => true,
                    'tenant_id' => null,
                ],
                [
                    'uuid' => $template['uuid'] ?? Str::uuid(),
                    'created_by_user_id' => $createdByUserId,
                    'description' => $template['description'],
                    'court_type' => $template['court_type'],
                    'category' => $template['category'],
                    'tags' => $template['tags'],
                    'play_data' => $template['play_data'],
                    'animation_data' => $template['animation_data'] ?? null,
                    'is_public' => true,
                    'is_featured' => $template['is_featured'] ?? false,
                    'is_system_template' => true,
                    'template_order' => $index + 1,
                    'status' => 'published',
                    'usage_count' => 0,
                ]
            );
        }

        $this->command->info('Created ' . count($templates) . ' play templates.');
    }

    /**
     * Get the template definitions.
     */
    private function getTemplates(): array
    {
        return [
            // === OFFENSE TEMPLATES ===
            [
                'name' => 'Motion Offense - Basic',
                'description' => 'Grundlegende Motion Offense mit 5-Out Formation. Spieler bewegen sich kontinuierlich und suchen offene Würfe.',
                'court_type' => 'half_horizontal',
                'category' => 'offense',
                'tags' => ['motion', 'grundlagen', '5-out', 'anfänger'],
                'is_featured' => true,
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 200, 'y' => 180, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 600, 'y' => 180, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 150, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 650, 'y' => 350, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],
            [
                'name' => 'Pick & Roll - Ball Handler',
                'description' => 'Klassischer Pick & Roll mit Fokus auf dem Ballführer. Screener rollt zum Korb.',
                'court_type' => 'half_horizontal',
                'category' => 'offense',
                'tags' => ['pick-and-roll', 'screen', 'klassiker', 'ballhandler'],
                'is_featured' => true,
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 350, 'y' => 200, 'number' => '5', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 150, 'y' => 180, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 650, 'y' => 180, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 600, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                    ],
                    'paths' => [
                        ['id' => 'path1', 'type' => 'movement', 'points' => [[350, 200], [400, 150]], 'fromPlayer' => 'p2'],
                        ['id' => 'path2', 'type' => 'movement', 'points' => [[350, 200], [400, 350]], 'fromPlayer' => 'p2'],
                    ],
                    'shapes' => [
                        ['id' => 's1', 'type' => 'screen', 'x' => 350, 'y' => 150, 'rotation' => 0],
                    ],
                ]),
            ],
            [
                'name' => 'Pick & Roll - Roll Man',
                'description' => 'Pick & Roll mit Fokus auf dem Screener. Big Man rollt zum Korb für einen einfachen Wurf.',
                'court_type' => 'half_horizontal',
                'category' => 'offense',
                'tags' => ['pick-and-roll', 'screen', 'big-man', 'roll'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 350, 'y' => 180, 'number' => '5', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 150, 'y' => 200, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 650, 'y' => 200, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 400, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                    ],
                ]),
            ],
            [
                'name' => 'Give & Go',
                'description' => 'Einfacher Give & Go Spielzug. Pass und Cut zum Korb für einen Return-Pass.',
                'court_type' => 'half_horizontal',
                'category' => 'offense',
                'tags' => ['give-and-go', 'cut', 'anfänger', 'grundlagen'],
                'is_featured' => true,
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 250, 'y' => 200, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 550, 'y' => 200, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 200, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 600, 'y' => 350, 'number' => '5', 'team' => 'offense'],
                    ],
                    'paths' => [
                        ['id' => 'path1', 'type' => 'pass', 'points' => [[400, 100], [250, 200]]],
                        ['id' => 'path2', 'type' => 'movement', 'points' => [[400, 100], [400, 350]], 'fromPlayer' => 'p1'],
                    ],
                ]),
            ],
            [
                'name' => 'Flex Offense - Entry',
                'description' => 'Flex Offense Einstieg mit Down-Screen und Flex-Cut. Kontinuierliche Bewegung.',
                'court_type' => 'half_horizontal',
                'category' => 'offense',
                'tags' => ['flex', 'screen', 'fortgeschritten', 'kontinuierlich'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 200, 'y' => 200, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 600, 'y' => 200, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 250, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 550, 'y' => 350, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],

            // === DEFENSE TEMPLATES ===
            [
                'name' => 'Mann-gegen-Mann Grundstellung',
                'description' => 'Grundlegende Mann-gegen-Mann Verteidigung. Jeder Spieler deckt seinen direkten Gegenspieler.',
                'court_type' => 'half_horizontal',
                'category' => 'man_to_man',
                'tags' => ['mann-gegen-mann', 'grundlagen', 'defense', 'anfänger'],
                'is_featured' => true,
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 130, 'number' => '1', 'team' => 'defense'],
                        ['id' => 'p2', 'x' => 220, 'y' => 200, 'number' => '2', 'team' => 'defense'],
                        ['id' => 'p3', 'x' => 580, 'y' => 200, 'number' => '3', 'team' => 'defense'],
                        ['id' => 'p4', 'x' => 300, 'y' => 320, 'number' => '4', 'team' => 'defense'],
                        ['id' => 'p5', 'x' => 500, 'y' => 320, 'number' => '5', 'team' => 'defense'],
                    ],
                ]),
            ],
            [
                'name' => '2-3 Zonenverteidigung',
                'description' => '2-3 Zone mit zwei Spielern oben und drei Spielern unten. Gut gegen Außenwürfe.',
                'court_type' => 'half_horizontal',
                'category' => 'zone',
                'tags' => ['zone', '2-3', 'defense', 'team'],
                'is_featured' => true,
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 300, 'y' => 150, 'number' => '1', 'team' => 'defense'],
                        ['id' => 'p2', 'x' => 500, 'y' => 150, 'number' => '2', 'team' => 'defense'],
                        ['id' => 'p3', 'x' => 200, 'y' => 300, 'number' => '3', 'team' => 'defense'],
                        ['id' => 'p4', 'x' => 400, 'y' => 350, 'number' => '4', 'team' => 'defense'],
                        ['id' => 'p5', 'x' => 600, 'y' => 300, 'number' => '5', 'team' => 'defense'],
                    ],
                ]),
            ],
            [
                'name' => '3-2 Zonenverteidigung',
                'description' => '3-2 Zone mit drei Spielern oben und zwei Spielern unten. Gut gegen Drives zum Korb.',
                'court_type' => 'half_horizontal',
                'category' => 'zone',
                'tags' => ['zone', '3-2', 'defense', 'team'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 130, 'number' => '1', 'team' => 'defense'],
                        ['id' => 'p2', 'x' => 220, 'y' => 180, 'number' => '2', 'team' => 'defense'],
                        ['id' => 'p3', 'x' => 580, 'y' => 180, 'number' => '3', 'team' => 'defense'],
                        ['id' => 'p4', 'x' => 280, 'y' => 330, 'number' => '4', 'team' => 'defense'],
                        ['id' => 'p5', 'x' => 520, 'y' => 330, 'number' => '5', 'team' => 'defense'],
                    ],
                ]),
            ],
            [
                'name' => '1-3-1 Zonenverteidigung',
                'description' => '1-3-1 Zone mit einem Point-Defender, drei in der Mitte und einem unter dem Korb.',
                'court_type' => 'half_horizontal',
                'category' => 'zone',
                'tags' => ['zone', '1-3-1', 'defense', 'trap', 'fortgeschritten'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 100, 'number' => '1', 'team' => 'defense'],
                        ['id' => 'p2', 'x' => 200, 'y' => 220, 'number' => '2', 'team' => 'defense'],
                        ['id' => 'p3', 'x' => 400, 'y' => 250, 'number' => '3', 'team' => 'defense'],
                        ['id' => 'p4', 'x' => 600, 'y' => 220, 'number' => '4', 'team' => 'defense'],
                        ['id' => 'p5', 'x' => 400, 'y' => 380, 'number' => '5', 'team' => 'defense'],
                    ],
                ]),
            ],

            // === FAST BREAK TEMPLATES ===
            [
                'name' => '3-gegen-2 Fast Break',
                'description' => 'Schnellangriff mit 3 gegen 2 Überzahl. Fülle die Lanes und suche den offenen Mann.',
                'court_type' => 'full',
                'category' => 'fast_break',
                'tags' => ['fastbreak', 'überzahl', '3-2', 'schnell'],
                'is_featured' => true,
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 300, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 150, 'y' => 250, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 650, 'y' => 250, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 350, 'y' => 180, 'number' => 'X', 'team' => 'defense'],
                        ['id' => 'p5', 'x' => 450, 'y' => 180, 'number' => 'X', 'team' => 'defense'],
                    ],
                ]),
            ],
            [
                'name' => '2-gegen-1 Fast Break',
                'description' => 'Schnellangriff mit 2 gegen 1 Überzahl. Einfacher Spielzug für schnelle Punkte.',
                'court_type' => 'full',
                'category' => 'fast_break',
                'tags' => ['fastbreak', 'überzahl', '2-1', 'schnell', 'anfänger'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 350, 'y' => 280, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 450, 'y' => 280, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 400, 'y' => 180, 'number' => 'X', 'team' => 'defense'],
                    ],
                ]),
            ],
            [
                'name' => 'Primary Break - 4 Out',
                'description' => 'Primärer Schnellangriff mit 4 Spielern außen. Trailer füllt die Zone.',
                'court_type' => 'full',
                'category' => 'fast_break',
                'tags' => ['fastbreak', 'primary-break', '4-out', 'struktur'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 350, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 150, 'y' => 200, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 650, 'y' => 200, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 200, 'y' => 100, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 600, 'y' => 100, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],

            // === INBOUND TEMPLATES ===
            [
                'name' => 'Baseline Out of Bounds - Stack',
                'description' => 'Einwurf unter dem Korb mit Stack-Formation. Spieler brechen in verschiedene Richtungen aus.',
                'court_type' => 'half_horizontal',
                'category' => 'inbound',
                'tags' => ['einwurf', 'baseline', 'stack', 'out-of-bounds'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 450, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 400, 'y' => 320, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 400, 'y' => 280, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 400, 'y' => 240, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 400, 'y' => 200, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],
            [
                'name' => 'Sideline Out of Bounds - Box',
                'description' => 'Einwurf von der Seitenlinie mit Box-Formation. Vielseitige Optionen.',
                'court_type' => 'half_horizontal',
                'category' => 'inbound',
                'tags' => ['einwurf', 'sideline', 'box', 'out-of-bounds'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 100, 'y' => 200, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 300, 'y' => 250, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 300, 'y' => 350, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 450, 'y' => 250, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 450, 'y' => 350, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],
            [
                'name' => 'Full Court Inbound',
                'description' => 'Einwurf über das ganze Feld. Schneller Angriff aus der eigenen Hälfte.',
                'court_type' => 'full',
                'category' => 'inbound',
                'tags' => ['einwurf', 'full-court', 'schnell', 'langpass'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 780, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 300, 'y' => 600, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 500, 'y' => 600, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 400, 'y' => 400, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 400, 'y' => 200, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],

            // === PRESS BREAK TEMPLATES ===
            [
                'name' => '1-4 Press Break',
                'description' => 'Press Break gegen Ganzfeldpresse mit 1-4 Formation. Sicherer Ballvortrag.',
                'court_type' => 'full',
                'category' => 'press_break',
                'tags' => ['press-break', '1-4', 'ballvortrag', 'ganzfeld'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 700, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 150, 'y' => 500, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 300, 'y' => 500, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 500, 'y' => 500, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 650, 'y' => 500, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],
            [
                'name' => '2-2-1 Press Break',
                'description' => 'Press Break mit 2-2-1 Formation. Outlet-Optionen und schneller Übergang.',
                'court_type' => 'full',
                'category' => 'press_break',
                'tags' => ['press-break', '2-2-1', 'ballvortrag', 'outlet'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 300, 'y' => 700, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 500, 'y' => 700, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 250, 'y' => 500, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 550, 'y' => 500, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 400, 'y' => 300, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],

            // === TRANSITION TEMPLATES ===
            [
                'name' => 'Transition Offense',
                'description' => 'Schneller Übergang von Defense zu Offense. Fülle die Lanes und suche den Vorteil.',
                'court_type' => 'full',
                'category' => 'transition',
                'tags' => ['transition', 'übergang', 'schnell', 'lanes'],
                'play_data' => $this->createFullCourtPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 400, 'y' => 400, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 150, 'y' => 300, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 650, 'y' => 300, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 300, 'y' => 200, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 500, 'y' => 200, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],

            // === SPECIAL TEMPLATES ===
            [
                'name' => 'Last-Second-Play',
                'description' => 'Spielzug für die letzten Sekunden. Schneller Wurf mit mehreren Optionen.',
                'court_type' => 'half_horizontal',
                'category' => 'special',
                'tags' => ['special', 'last-second', 'clutch', 'wichtig'],
                'play_data' => $this->createPlayData([
                    'players' => [
                        ['id' => 'p1', 'x' => 100, 'y' => 250, 'number' => '1', 'team' => 'offense'],
                        ['id' => 'p2', 'x' => 350, 'y' => 200, 'number' => '2', 'team' => 'offense'],
                        ['id' => 'p3', 'x' => 450, 'y' => 200, 'number' => '3', 'team' => 'offense'],
                        ['id' => 'p4', 'x' => 350, 'y' => 350, 'number' => '4', 'team' => 'offense'],
                        ['id' => 'p5', 'x' => 450, 'y' => 350, 'number' => '5', 'team' => 'offense'],
                    ],
                ]),
            ],
        ];
    }

    /**
     * Create play data structure for half court.
     */
    private function createPlayData(array $elements): array
    {
        return [
            'version' => '1.2',
            'court' => [
                'type' => 'half_horizontal',
                'backgroundColor' => '#1a5f2a',
                'lineColor' => '#ffffff',
            ],
            'elements' => [
                'players' => array_map(function ($player) {
                    return [
                        'id' => $player['id'],
                        'x' => $player['x'],
                        'y' => $player['y'],
                        'number' => $player['number'],
                        'team' => $player['team'],
                        'zIndex' => 10,
                    ];
                }, $elements['players'] ?? []),
                'paths' => $elements['paths'] ?? [],
                'shapes' => $elements['shapes'] ?? [],
                'annotations' => $elements['annotations'] ?? [],
                'freehandPaths' => [],
                'circles' => [],
                'rectangles' => [],
                'arrows' => [],
            ],
            'ball' => null,
            'teamColors' => [
                'offense' => '#2563eb',
                'defense' => '#dc2626',
            ],
            'grid' => [
                'enabled' => false,
                'size' => 20,
            ],
        ];
    }

    /**
     * Create play data structure for full court.
     */
    private function createFullCourtPlayData(array $elements): array
    {
        $data = $this->createPlayData($elements);
        $data['court']['type'] = 'full';
        return $data;
    }
}
