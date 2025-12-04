<?php

namespace Database\Seeders;

use App\Models\TacticCategory;
use Illuminate\Database\Seeder;

class TacticCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Play-Kategorien (type: play)
            [
                'name' => 'Offense',
                'slug' => 'offense',
                'type' => 'play',
                'description' => 'Angriffsspielzüge',
                'color' => '#3B82F6',
                'icon' => 'arrow-right',
                'sort_order' => 1,
                'is_system' => true,
            ],
            [
                'name' => 'Press Break',
                'slug' => 'press_break',
                'type' => 'play',
                'description' => 'Pressing durchbrechen',
                'color' => '#8B5CF6',
                'icon' => 'shield-exclamation',
                'sort_order' => 3,
                'is_system' => true,
            ],
            [
                'name' => 'Einwurf',
                'slug' => 'inbound',
                'type' => 'play',
                'description' => 'Out of Bounds Spielzüge',
                'color' => '#EC4899',
                'icon' => 'arrow-up-on-square',
                'sort_order' => 4,
                'is_system' => true,
            ],
            [
                'name' => 'Fast Break',
                'slug' => 'fast_break',
                'type' => 'play',
                'description' => 'Schnelle Angriffe',
                'color' => '#F97316',
                'icon' => 'bolt',
                'sort_order' => 5,
                'is_system' => true,
            ],
            [
                'name' => 'Zone',
                'slug' => 'zone',
                'type' => 'play',
                'description' => 'Zonenverteidigung und -angriff',
                'color' => '#14B8A6',
                'icon' => 'squares-2x2',
                'sort_order' => 6,
                'is_system' => true,
            ],
            [
                'name' => 'Mann-gegen-Mann',
                'slug' => 'man_to_man',
                'type' => 'play',
                'description' => 'Mann-gegen-Mann Verteidigung',
                'color' => '#6366F1',
                'icon' => 'users',
                'sort_order' => 7,
                'is_system' => true,
            ],
            [
                'name' => 'Spezial',
                'slug' => 'special',
                'type' => 'play',
                'description' => 'Spezielle Spielzüge',
                'color' => '#A855F7',
                'icon' => 'star',
                'sort_order' => 9,
                'is_system' => true,
            ],

            // Drill-Kategorien (type: drill)
            [
                'name' => 'Ballhandling',
                'slug' => 'ball_handling',
                'type' => 'drill',
                'description' => 'Ballführung und Dribbeln',
                'color' => '#F59E0B',
                'icon' => 'hand-raised',
                'sort_order' => 10,
                'is_system' => true,
            ],
            [
                'name' => 'Wurf',
                'slug' => 'shooting',
                'type' => 'drill',
                'description' => 'Wurfübungen',
                'color' => '#EF4444',
                'icon' => 'cursor-arrow-rays',
                'sort_order' => 11,
                'is_system' => true,
            ],
            [
                'name' => 'Passen',
                'slug' => 'passing',
                'type' => 'drill',
                'description' => 'Passübungen',
                'color' => '#10B981',
                'icon' => 'arrows-right-left',
                'sort_order' => 12,
                'is_system' => true,
            ],
            [
                'name' => 'Rebound',
                'slug' => 'rebounding',
                'type' => 'drill',
                'description' => 'Reboundübungen',
                'color' => '#6B7280',
                'icon' => 'arrow-path',
                'sort_order' => 14,
                'is_system' => true,
            ],
            [
                'name' => 'Kondition',
                'slug' => 'conditioning',
                'type' => 'drill',
                'description' => 'Konditionstraining',
                'color' => '#DC2626',
                'icon' => 'heart',
                'sort_order' => 15,
                'is_system' => true,
            ],
            [
                'name' => 'Beweglichkeit',
                'slug' => 'agility',
                'type' => 'drill',
                'description' => 'Agilität und Schnelligkeit',
                'color' => '#0EA5E9',
                'icon' => 'sparkles',
                'sort_order' => 16,
                'is_system' => true,
            ],
            [
                'name' => 'Beinarbeit',
                'slug' => 'footwork',
                'type' => 'drill',
                'description' => 'Fußarbeit und Positionierung',
                'color' => '#84CC16',
                'icon' => 'arrows-pointing-out',
                'sort_order' => 17,
                'is_system' => true,
            ],
            [
                'name' => 'Team-Offense',
                'slug' => 'team_offense',
                'type' => 'drill',
                'description' => 'Teambezogene Angriffsübungen',
                'color' => '#3B82F6',
                'icon' => 'user-group',
                'sort_order' => 18,
                'is_system' => true,
            ],
            [
                'name' => 'Team-Defense',
                'slug' => 'team_defense',
                'type' => 'drill',
                'description' => 'Teambezogene Verteidigungsübungen',
                'color' => '#EF4444',
                'icon' => 'shield-check',
                'sort_order' => 19,
                'is_system' => true,
            ],
            [
                'name' => 'Spielzüge',
                'slug' => 'set_plays',
                'type' => 'drill',
                'description' => 'Eingespielte Spielzüge üben',
                'color' => '#8B5CF6',
                'icon' => 'clipboard-document-list',
                'sort_order' => 21,
                'is_system' => true,
            ],
            [
                'name' => 'Scrimmage',
                'slug' => 'scrimmage',
                'type' => 'drill',
                'description' => 'Trainingsspiele',
                'color' => '#059669',
                'icon' => 'play',
                'sort_order' => 22,
                'is_system' => true,
            ],
            [
                'name' => 'Aufwärmen',
                'slug' => 'warm_up',
                'type' => 'drill',
                'description' => 'Aufwärmübungen',
                'color' => '#FBBF24',
                'icon' => 'fire',
                'sort_order' => 23,
                'is_system' => true,
            ],
            [
                'name' => 'Abwärmen',
                'slug' => 'cool_down',
                'type' => 'drill',
                'description' => 'Cool-down Übungen',
                'color' => '#06B6D4',
                'icon' => 'cloud',
                'sort_order' => 24,
                'is_system' => true,
            ],

            // Gemeinsame Kategorien (type: both)
            [
                'name' => 'Defense',
                'slug' => 'defense',
                'type' => 'both',
                'description' => 'Verteidigung - Spielzüge und Übungen',
                'color' => '#EF4444',
                'icon' => 'shield-check',
                'sort_order' => 2,
                'is_system' => true,
            ],
            [
                'name' => 'Transition',
                'slug' => 'transition',
                'type' => 'both',
                'description' => 'Umschaltspiel - Spielzüge und Übungen',
                'color' => '#22C55E',
                'icon' => 'arrows-up-down',
                'sort_order' => 8,
                'is_system' => true,
            ],
        ];

        foreach ($categories as $category) {
            TacticCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
