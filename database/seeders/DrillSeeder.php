<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Drill;
use App\Models\User;

class DrillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user as creator
        $creator = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$creator) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Seeding Basketball Drills...');

        // Warm-up Drills
        $this->seedWarmUpDrills($creator);
        
        // Ball Handling Drills
        $this->seedBallHandlingDrills($creator);
        
        // Shooting Drills
        $this->seedShootingDrills($creator);
        
        // Passing Drills
        $this->seedPassingDrills($creator);
        
        // Defense Drills
        $this->seedDefenseDrills($creator);
        
        // Rebounding Drills
        $this->seedReboundingDrills($creator);
        
        // Conditioning Drills
        $this->seedConditioningDrills($creator);
        
        // Team Offense Drills
        $this->seedTeamOffenseDrills($creator);
        
        // Team Defense Drills
        $this->seedTeamDefenseDrills($creator);
        
        // Scrimmage & Game Situations
        $this->seedScrimmageAndGameDrills($creator);
        
        // Cool Down Drills
        $this->seedCoolDownDrills($creator);

        $this->command->info('Basketball Drills seeded successfully!');
    }

    private function seedWarmUpDrills(User $creator): void
    {
        $warmUpDrills = [
            [
                'name' => 'Dynamisches Aufwärmen',
                'description' => 'Vollständige Aufwärmroutine mit dynamischen Bewegungen zur Vorbereitung auf das Training.',
                'objectives' => 'Körper aufwärmen, Beweglichkeit verbessern, Verletzungsrisiko reduzieren',
                'instructions' => '1. 2 Minuten leichtes Joggen\n2. Armkreisen vorwärts/rückwärts (je 10x)\n3. Beinschwingen seitlich (je 10x)\n4. Knieheben (20x)\n5. Fersenkicks (20x)\n6. Seitschritte (2x Platzbreite)\n7. Rückwärtslaufen (1x Platzlänge)',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 10,
                'min_players' => 1,
                'max_players' => 20,
                'optimal_players' => 12,
                'required_equipment' => [],
                'tags' => ['aufwärmen', 'mobilität', 'verletzungsprävention'],
                'coaching_points' => [
                    'Auf saubere Bewegungsausführung achten',
                    'Intensität langsam steigern',
                    'Bei Schmerzen sofort stoppen'
                ],
            ],
            [
                'name' => 'Ball-Aufwärmen',
                'description' => 'Aufwärmprogramm mit Basketball zur Gewöhnung an den Ball.',
                'objectives' => 'Ball-Gefühl entwickeln, Koordination verbessern, für Training vorbereiten',
                'instructions' => '1. Ball um Körper kreisen (je Richtung 10x)\n2. Ball um Beine in Achter-Form (10x)\n3. Ball zwischen Beinen hin und her (20x)\n4. Ball hochwerfen und fangen (10x)\n5. Ball auf Boden prellen und fangen (20x)\n6. Gehen mit Dribbeln (2 Runden)',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 8,
                'min_players' => 1,
                'max_players' => 15,
                'optimal_players' => 10,
                'required_equipment' => ['Basketball'],
                'tags' => ['aufwärmen', 'ball-handling', 'koordination'],
                'coaching_points' => [
                    'Ball kontrolliert führen',
                    'Blick vom Ball lösen',
                    'Tempo langsam steigern'
                ],
            ],
            [
                'name' => 'Gelenksmobilisation',
                'description' => 'Spezielle Mobilisationsübungen für alle wichtigen Gelenke im Basketball.',
                'objectives' => 'Gelenke mobilisieren, Bewegungsumfang verbessern, Körper aktivieren',
                'instructions' => '1. Handgelenke kreisen (je 10x)\n2. Schultern rollen (10x vor/zurück)\n3. Hüftkreise (je 10x)\n4. Sprunggelenke kreisen (je 10x)\n5. Kniebeuge mit Armen hoch (10x)\n6. Ausfallschritt mit Rotation (je 5x)\n7. Seitlicher Ausfallschritt (je 5x)',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 7,
                'min_players' => 1,
                'max_players' => 25,
                'optimal_players' => 15,
                'required_equipment' => [],
                'tags' => ['mobilisation', 'gelenke', 'flexibilität'],
                'coaching_points' => [
                    'Langsam und kontrolliert ausführen',
                    'Vollständigen Bewegungsumfang nutzen',
                    'Atmung nicht vergessen'
                ],
            ],
        ];

        foreach ($warmUpDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'warm_up',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => false,
            ]));
        }
    }

    private function seedBallHandlingDrills(User $creator): void
    {
        $ballHandlingDrills = [
            [
                'name' => 'Stationary Ball Handling',
                'description' => 'Grundlegende Ball-Handling-Übungen im Stand für bessere Ballkontrolle.',
                'objectives' => 'Ballgefühl verbessern, Fingerfertigkeit entwickeln, beidhändige Kontrolle aufbauen',
                'instructions' => '1. Ball um Taille kreisen (10x je Richtung)\n2. Ball um Beine kreisen (10x je Richtung)\n3. Achter um Beine (10x)\n4. Ball zwischen Beinen durchgeben (20x)\n5. Ball über Kopf/unter Beinen wechseln (10x)\n6. Fingertip-Kontrolle (30 Sekunden)',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 10,
                'min_players' => 1,
                'max_players' => 15,
                'optimal_players' => 8,
                'required_equipment' => ['Basketball'],
                'variations' => 'Mit geschlossenen Augen durchführen',
                'progressions' => 'Schneller ausführen, komplexere Bewegungsmuster',
                'tags' => ['ballkontrolle', 'fingerfertigkeit', 'koordination'],
                'coaching_points' => [
                    'Ball mit Fingerspitzen kontrollieren',
                    'Blick nach oben richten',
                    'Flüssige Bewegungen'
                ],
            ],
            [
                'name' => 'Dribbling Fundamentals',
                'description' => 'Grundlegende Dribbel-Techniken für verschiedene Spielsituationen.',
                'objectives' => 'Dribbel-Technik perfektionieren, Ballschutz lernen, Tempo kontrollieren',
                'instructions' => '1. High Dribble (30 Sekunden je Hand)\n2. Low Dribble (30 Sekunden je Hand)\n3. Crossover zwischen Beinen (20x)\n4. Behind-the-back (10x je Richtung)\n5. Between-the-legs (20x)\n6. Hesitation Move (10x)\n7. Kombinationen (2 Minuten)',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 12,
                'min_players' => 1,
                'max_players' => 12,
                'optimal_players' => 6,
                'required_equipment' => ['Basketball'],
                'variations' => 'Mit Verteidiger, in Bewegung',
                'progressions' => 'Schnellere Ausführung, Druck durch Trainer',
                'tags' => ['dribbeln', 'ballschutz', 'technik'],
                'coaching_points' => [
                    'Ball niedrig halten',
                    'Körper als Schutz nutzen',
                    'Kopf oben behalten'
                ],
            ],
            [
                'name' => 'Two-Ball Dribbling',
                'description' => 'Fortgeschrittene Übung mit zwei Bällen zur Verbesserung der Koordination.',
                'objectives' => 'Koordination steigern, schwächere Hand stärken, Konzentration verbessern',
                'instructions' => '1. Beide Bälle gleichzeitig (30 Sekunden)\n2. Alternierend (30 Sekunden)\n3. Ein Ball hoch, einer niedrig (30 Sekunden)\n4. Crossover mit beiden Bällen (20x)\n5. Einer steht, einer dribbelt (je 30 Sekunden)\n6. Im Gehen (2 Minuten)',
                'difficulty_level' => 'advanced',
                'estimated_duration' => 15,
                'min_players' => 1,
                'max_players' => 8,
                'optimal_players' => 4,
                'required_equipment' => ['2 Basketballs pro Spieler'],
                'variations' => 'Mit Richtungswechsel, verschiedene Rhythmen',
                'tags' => ['koordination', 'zwei-ball', 'konzentration'],
                'coaching_points' => [
                    'Gleichmäßigen Rhythmus halten',
                    'Beide Hände gleich stark',
                    'Geduld bei Anfangsschwierigkeiten'
                ],
            ],
            [
                'name' => 'Cone Dribbling',
                'description' => 'Dribbel-Parcours mit Hütchen zur Verbesserung von Ballkontrolle und Wendigkeit.',
                'objectives' => 'Ballkontrolle in Bewegung, Richtungswechsel, Geschicklichkeit',
                'instructions' => '1. Slalom durch Hütchen (je Hand)\n2. Crossover an jedem Hütchen\n3. Behind-the-back um Hütchen\n4. Between-legs an Hütchen\n5. Kombination verschiedener Moves\n6. Tempo-Variationen',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 15,
                'min_players' => 1,
                'max_players' => 6,
                'optimal_players' => 3,
                'required_equipment' => ['Basketballs', '6-8 Hütchen'],
                'requires_half_court' => true,
                'variations' => 'Rückwärts, nur schwächere Hand',
                'tags' => ['wendigkeit', 'ballkontrolle', 'parcours'],
                'coaching_points' => [
                    'Enge Kontrolle am Hütchen',
                    'Körper tief halten',
                    'Explosive Richtungswechsel'
                ],
            ],
        ];

        foreach ($ballHandlingDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'ball_handling',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => rand(0, 1) == 1,
            ]));
        }
    }

    private function seedShootingDrills(User $creator): void
    {
        $shootingDrills = [
            [
                'name' => 'Form Shooting',
                'description' => 'Grundlegende Wurfform aus kurzer Distanz perfektionieren.',
                'objectives' => 'Wurfmechanik verbessern, Muskelgedächtnis aufbauen, Konsistenz entwickeln',
                'instructions' => '1. Position 1m vor Korb\n2. 10 Würfe mit perfekter Form\n3. Auf Handgelenk-Snap achten\n4. Nach Erfolg einen Schritt zurück\n5. Von verschiedenen Positionen\n6. Abschluss: 10 perfekte Würfe in Folge',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 15,
                'min_players' => 1,
                'max_players' => 4,
                'optimal_players' => 2,
                'required_equipment' => ['Basketballs'],
                'requires_half_court' => true,
                'variations' => 'Mit Partner als Rebounder',
                'progressions' => 'Größere Distanz, verschiedene Winkel',
                'tags' => ['wurftechnik', 'form', 'mechanik'],
                'coaching_points' => [
                    'BEEF: Balance, Eyes, Elbow, Follow-through',
                    'Konstanter Bogenflug',
                    'Weiches Handgelenk'
                ],
                'measurable_outcomes' => ['Trefferquote', 'Wurfform-Bewertung'],
                'success_criteria' => ['70% Trefferquote aus kurzer Distanz'],
            ],
            [
                'name' => 'Spot Shooting',
                'description' => 'Stationäres Werfen von verschiedenen Positionen um den Korb.',
                'objectives' => 'Trefferquote aus verschiedenen Winkeln verbessern, Spielsituationen simulieren',
                'instructions' => '1. 5 Positionen um Korb markieren\n2. Je 10 Würfe pro Position\n3. Position 1: Rechter Flügel\n4. Position 2: Rechte Ecke\n5. Position 3: Center/Freiwurf\n6. Position 4: Linke Ecke\n7. Position 5: Linker Flügel\n8. Trefferquote dokumentieren',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 20,
                'min_players' => 1,
                'max_players' => 3,
                'optimal_players' => 2,
                'required_equipment' => ['Basketballs', 'Markierungshütchen'],
                'requires_half_court' => true,
                'variations' => 'Verschiedene Distanzen, Zeitdruck',
                'is_competitive' => true,
                'scoring_system' => 'Punkte pro getroffenen Wurf, Bonus für perfekte Serien',
                'tags' => ['spot-shooting', 'trefferquote', 'konsistenz'],
                'coaching_points' => [
                    'Gleiche Form bei jedem Wurf',
                    'Schnelle Vorbereitung',
                    'Mentale Stärke bei Fehlwürfen'
                ],
                'measurable_outcomes' => ['Trefferquote pro Position', 'Gesamttrefferquote'],
            ],
            [
                'name' => 'Catch and Shoot',
                'description' => 'Wurf direkt nach Pass-Annahme für Spielsituationen.',
                'objectives' => 'Schnelle Wurfvorbereitung, Catch-and-Shoot-Mechanik, Spieltempo',
                'instructions' => '1. Spieler an Dreipunktlinie\n2. Partner passt von verschiedenen Positionen\n3. Sofortiger Wurf nach Ballannahme\n4. Füße richtig setzen\n5. 20 Versuche pro Position\n6. Tempo steigern',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 18,
                'min_players' => 2,
                'max_players' => 6,
                'optimal_players' => 4,
                'required_equipment' => ['Basketballs'],
                'requires_half_court' => true,
                'variations' => 'Mit Verteidiger, verschiedene Pass-Arten',
                'tags' => ['catch-shoot', 'pass-annahme', 'schnelligkeit'],
                'coaching_points' => [
                    'Füße beim Fangen schon setzen',
                    'Ball schnell in Wurfposition',
                    'Rhythmus halten'
                ],
            ],
            [
                'name' => 'Free Throw Routine',
                'description' => 'Systematisches Freiwurf-Training mit festem Ablauf.',
                'objectives' => 'Freiwurf-Konsistenz verbessern, Routine entwickeln, Druck-Situationen meistern',
                'instructions' => '1. Feste Routine entwickeln (Atemzüge, Ball-Bounces)\n2. 10er-Serie: 10 Freiwürfe in Folge\n3. Nach Fehlwurf: Neustart der Serie\n4. Ziel: 3 perfekte 10er-Serien\n5. Simulation von Druck-Situationen\n6. Verschiedene Ermüdungslevel',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 20,
                'min_players' => 1,
                'max_players' => 2,
                'optimal_players' => 1,
                'required_equipment' => ['Basketball'],
                'requires_half_court' => true,
                'is_competitive' => true,
                'scoring_system' => '1 Punkt pro Treffer, Bonus für perfekte Serien',
                'tags' => ['freiwurf', 'routine', 'mental', 'druck'],
                'coaching_points' => [
                    'Immer gleiche Routine',
                    'Positive Visualisierung',
                    'Entspannte Schultern'
                ],
                'measurable_outcomes' => ['Freiwurf-Quote', 'Längste Trefferserie'],
                'success_criteria' => ['80% Freiwurf-Quote'],
            ],
        ];

        foreach ($shootingDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'shooting',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => rand(0, 1) == 1,
            ]));
        }
    }

    private function seedPassingDrills(User $creator): void
    {
        $passingDrills = [
            [
                'name' => 'Partner Passing',
                'description' => 'Grundlegende Pass-Arten zwischen zwei Spielern perfektionieren.',
                'objectives' => 'Pass-Technik verbessern, verschiedene Pass-Arten lernen, Timing entwickeln',
                'instructions' => '1. Chest Pass (20x)\n2. Bounce Pass (20x)\n3. Overhead Pass (20x)\n4. One-hand Push Pass (je 10x)\n5. Behind-the-back Pass (je 10x)\n6. No-look Pass (10x)\n7. Distanz langsam vergrößern',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 15,
                'min_players' => 2,
                'max_players' => 12,
                'optimal_players' => 6,
                'required_equipment' => ['Basketballs'],
                'variations' => 'Mit Bewegung, unter Zeitdruck',
                'progressions' => 'Längere Distanz, schwierigere Pass-Arten',
                'tags' => ['grundlagen', 'pass-technik', 'partner'],
                'coaching_points' => [
                    'Schritt zum Pass',
                    'Ziel genau anvisieren',
                    'Follow-through mit Handgelenk'
                ],
            ],
            [
                'name' => 'Wall Passing',
                'description' => 'Pass-Training gegen die Wand für Reaktion und Hand-Auge-Koordination.',
                'objectives' => 'Reaktionszeit verbessern, Pass-Stärke kontrollieren, Koordination schulen',
                'instructions' => '1. 2m vor Wand positionieren\n2. Chest Pass gegen Wand (30x)\n3. Bounce Pass gegen Wand (30x)\n4. One-hand Pass abwechselnd (40x)\n5. Schnelle Sequenzen\n6. Mit Schritten nach links/rechts',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 10,
                'min_players' => 1,
                'max_players' => 8,
                'optimal_players' => 4,
                'required_equipment' => ['Basketball', 'Wand'],
                'variations' => 'Verschiedene Distanzen, nur schwächere Hand',
                'tags' => ['reaktion', 'koordination', 'solo'],
                'coaching_points' => [
                    'Konstante Pass-Stärke',
                    'Bereit für Rückpass',
                    'Saubere Technik auch bei Tempo'
                ],
            ],
            [
                'name' => '3-Man Weave',
                'description' => 'Klassische Lauf-Pass-Kombination für drei Spieler.',
                'objectives' => 'Pass in Bewegung, Timing, Teamwork, Koordination',
                'instructions' => '1. 3 Spieler an Grundlinie\n2. Mittlerer startet mit Ball\n3. Pass zum Seitenmann\n4. Hinter den Ball laufen\n5. Kontinuierlicher Pass-Wechsel\n6. Abschluss mit Layup\n7. Zurück zur Grundlinie',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 12,
                'min_players' => 3,
                'max_players' => 15,
                'optimal_players' => 6,
                'required_equipment' => ['Basketball'],
                'requires_full_court' => true,
                'variations' => '2-Ball-Weave, ohne Dribbling',
                'tags' => ['bewegung', 'timing', 'teamwork'],
                'coaching_points' => [
                    'Pass und sofort laufen',
                    'Timing ist entscheidend',
                    'Kommunikation wichtig'
                ],
            ],
            [
                'name' => 'Outlet Passing',
                'description' => 'Schnelle Pässe nach Rebound für Fast Break Situationen.',
                'objectives' => 'Fast Break initiieren, lange Pässe, Spielübersicht',
                'instructions' => '1. Rebounder unter dem Korb\n2. Receiver an Mittellinie\n3. Rebound simulieren\n4. Schneller Outlet Pass\n5. Verschiedene Positionen\n6. Beide Seiten trainieren\n7. Unter Zeitdruck',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 12,
                'min_players' => 2,
                'max_players' => 8,
                'optimal_players' => 4,
                'required_equipment' => ['Basketball'],
                'requires_full_court' => true,
                'variations' => 'Mit Verteidiger, verschiedene Receiver-Positionen',
                'tags' => ['outlet', 'fastbreak', 'lange-pässe'],
                'coaching_points' => [
                    'Schnelle Ballverteilung',
                    'Übersicht behalten',
                    'Kraftvolle, präzise Pässe'
                ],
            ],
        ];

        foreach ($passingDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'passing',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => rand(0, 1) == 1,
            ]));
        }
    }

    private function seedDefenseDrills(User $creator): void
    {
        $defenseDrills = [
            [
                'name' => 'Defensive Stance',
                'description' => 'Grundhaltung und Beinarbeit für effektive Verteidigung.',
                'objectives' => 'Defensive Grundhaltung perfektionieren, Balance und Beweglichkeit verbessern',
                'instructions' => '1. Füße schulterbreit, parallel\n2. Knie gebeugt, tiefer Schwerpunkt\n3. Rücken gerade, Kopf hoch\n4. Arme ausgebreitet\n5. Seitschritte ohne Überkreuzen (30 Sekunden)\n6. Vorwärts/rückwärts ohne Stance zu verlieren\n7. Richtungswechsel auf Signal',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 10,
                'min_players' => 1,
                'max_players' => 20,
                'optimal_players' => 12,
                'required_equipment' => [],
                'variations' => 'Mit Ball, gegen Angreifer',
                'tags' => ['stance', 'beinarbeit', 'grundlagen'],
                'coaching_points' => [
                    'Tiefe Haltung halten',
                    'Füße nie überkreuzen',
                    'Balance in allen Bewegungen'
                ],
            ],
            [
                'name' => 'Mirror Drill',
                'description' => 'Verteidiger spiegelt Bewegungen des Angreifers.',
                'objectives' => 'Reaktionszeit verbessern, Antizipation schulen, Defensive Beinarbeit',
                'instructions' => '1. Angreifer und Verteidiger face-to-face\n2. Angreifer bewegt sich seitlich\n3. Verteidiger folgt spiegelbildlich\n4. Keine Überkreuzung der Beine\n5. 30 Sekunden, dann Rollentausch\n6. Tempo langsam steigern',
                'difficulty_level' => 'beginner',
                'estimated_duration' => 8,
                'min_players' => 2,
                'max_players' => 16,
                'optimal_players' => 8,
                'required_equipment' => [],
                'variations' => 'Mit Ball, rückwärts',
                'tags' => ['reaktion', 'spiegeln', 'bewegung'],
                'coaching_points' => [
                    'Augen auf Hüfte des Angreifers',
                    'Distanz konstant halten',
                    'Schnelle Richtungswechsel'
                ],
            ],
            [
                'name' => 'Closeout Drill',
                'description' => 'Schnelles Herantreten an den ballführenden Angreifer.',
                'objectives' => 'Closeout-Technik lernen, Distanz kontrollieren, Balance unter Kontrolle',
                'instructions' => '1. Verteidiger 6m vom Angreifer\n2. Angreifer erhält Pass\n3. Sprint mit kurzen Schritten zum Angreifer\n4. Letzten 2m kontrolliert abbremsen\n5. Defensive Stance einnehmen\n6. Hand am Ball, ohne zu foulen',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 10,
                'min_players' => 2,
                'max_players' => 12,
                'optimal_players' => 6,
                'required_equipment' => ['Basketball'],
                'variations' => 'Angreifer darf dribbeln/werfen',
                'tags' => ['closeout', 'sprint', 'kontrolle'],
                'coaching_points' => [
                    'Kontrollierte Geschwindigkeit',
                    'Balance beim Stoppen',
                    'Hand hoch zum Ball'
                ],
            ],
            [
                'name' => 'Shell Drill 4-on-4',
                'description' => 'Grundlegende Team-Defense mit Rotation und Kommunikation.',
                'objectives' => 'Team-Defense verstehen, Kommunikation, Positionsspiel, Rotation',
                'instructions' => '1. 4 Verteidiger, 4 Angreifer\n2. Angreifer passen den Ball\n3. Defense rotiert entsprechend\n4. Ballverteidiger: Pressure\n5. Help-Defender: Unterstützung\n6. Weak-side: Rotation\n7. Kommunikation konstant',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 15,
                'min_players' => 8,
                'max_players' => 12,
                'optimal_players' => 8,
                'required_equipment' => ['Basketball'],
                'requires_half_court' => true,
                'variations' => '5-on-5, mit Dribbling erlaubt',
                'tags' => ['team-defense', 'rotation', 'kommunikation'],
                'coaching_points' => [
                    'Konstante Kommunikation',
                    'Schnelle Rotation',
                    'Ballverteidiger Pressure'
                ],
            ],
        ];

        foreach ($defenseDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'defense',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => rand(0, 1) == 1,
            ]));
        }
    }

    private function seedConditioningDrills(User $creator): void
    {
        $conditioningDrills = [
            [
                'name' => 'Suicide Sprints',
                'description' => 'Klassische Konditions-Übung mit progressiven Distanzen.',
                'objectives' => 'Ausdauer verbessern, Schnelligkeit steigern, mentale Stärke',
                'instructions' => '1. Start an Grundlinie\n2. Sprint zur Freiwurflinie, zurück\n3. Sprint zur Mittellinie, zurück\n4. Sprint zur anderen Freiwurflinie, zurück\n5. Sprint zur anderen Grundlinie, zurück\n6. 3 Runden mit Pause',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 12,
                'min_players' => 1,
                'max_players' => 15,
                'optimal_players' => 8,
                'required_equipment' => [],
                'requires_full_court' => true,
                'variations' => 'Seitlich, rückwärts, mit Ball',
                'tags' => ['ausdauer', 'sprint', 'kondition'],
                'coaching_points' => [
                    'Maximale Intensität',
                    'Vollständige Distanzen',
                    'Mentale Stärke zeigen'
                ],
                'measurable_outcomes' => ['Zeit pro Runde', 'Herzfrequenz'],
            ],
            [
                'name' => '17s Drill',
                'description' => 'Ausdauerlauf mit festem Zeitlimit für basketballspezifische Kondition.',
                'objectives' => 'Spielspezifische Ausdauer, Tempo-Kontrolle, Recovery-Fähigkeit',
                'instructions' => '1. Lauf von Grundlinie zu Grundlinie\n2. Ziel: unter 17 Sekunden\n3. 10 Sekunden Pause\n4. Insgesamt 10 Runden\n5. Bei verpasster Zeit: Zusatzrunde\n6. Gruppe läuft zusammen',
                'difficulty_level' => 'advanced',
                'estimated_duration' => 15,
                'min_players' => 3,
                'max_players' => 15,
                'optimal_players' => 8,
                'required_equipment' => ['Stoppuhr'],
                'requires_full_court' => true,
                'is_competitive' => true,
                'scoring_system' => 'Punkte für erfolgreiche Runden unter Zeitlimit',
                'tags' => ['ausdauer', 'zeitlimit', 'gruppe'],
                'coaching_points' => [
                    'Konstantes Tempo halten',
                    'Gemeinsam motivieren',
                    'Mentale Stärke'
                ],
            ],
            [
                'name' => 'Defensive Slides',
                'description' => 'Seitliche Bewegungen für defensive Ausdauer und Beinarbeit.',
                'objectives' => 'Defensive Ausdauer, Beinarbeit stärken, Position halten',
                'instructions' => '1. Defensive Stance einnehmen\n2. Seitschritte von Seitenlinie zu Seitenlinie\n3. Füße nicht überkreuzen\n4. 30 Sekunden kontinuierlich\n5. 10 Sekunden Pause\n6. 6 Runden\n7. Richtung bei jeder Runde wechseln',
                'difficulty_level' => 'intermediate',
                'estimated_duration' => 10,
                'min_players' => 1,
                'max_players' => 20,
                'optimal_players' => 12,
                'required_equipment' => [],
                'variations' => 'Mit Verteidiger-Armbewegungen',
                'tags' => ['defense', 'beinarbeit', 'seitlich'],
                'coaching_points' => [
                    'Tiefe Stance beibehalten',
                    'Keine Überkreuzung',
                    'Explosiver Richtungswechsel'
                ],
            ],
        ];

        foreach ($conditioningDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'conditioning',
                'age_group' => 'all',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => rand(0, 1) == 1,
            ]));
        }
    }

    // Continue with other drill categories...
    private function seedTeamOffenseDrills(User $creator): void
    {
        $teamOffenseDrills = [
            [
                'name' => '5-Man Motion Offense',
                'description' => 'Grundlegende Motion Offense mit kontinuierlicher Bewegung aller Spieler.',
                'objectives' => 'Teamplay entwickeln, Spacing verstehen, kontinuierliche Bewegung',
                'instructions' => '1. 5 Spieler in Formation\n2. Pass und Screen away\n3. Kontinuierliche Rotation\n4. Spacing von 4-5 Metern\n5. Backdoor bei Überverteidigung\n6. Abschluss bei freiem Wurf',
                'difficulty_level' => 'advanced',
                'estimated_duration' => 20,
                'min_players' => 5,
                'max_players' => 10,
                'optimal_players' => 5,
                'required_equipment' => ['Basketball'],
                'requires_full_court' => true,
                'variations' => 'Mit/ohne Defense, verschiedene Sets',
                'tags' => ['motion', 'teamwork', 'spacing'],
                'coaching_points' => [
                    'Konstante Bewegung',
                    'Gutes Spacing',
                    'Lesen der Defense'
                ],
            ],
            // Add more team offense drills...
        ];

        foreach ($teamOffenseDrills as $drill) {
            Drill::create(array_merge($drill, [
                'created_by_user_id' => $creator->id,
                'category' => 'team_offense',
                'age_group' => 'adult',
                'status' => 'approved',
                'is_public' => true,
                'is_featured' => true,
            ]));
        }
    }

    // Add other seed methods for remaining categories...
    private function seedReboundingDrills(User $creator): void { /* Add rebounding drills */ }
    private function seedTeamDefenseDrills(User $creator): void { /* Add team defense drills */ }
    private function seedScrimmageAndGameDrills(User $creator): void { /* Add scrimmage drills */ }
    private function seedCoolDownDrills(User $creator): void { /* Add cool down drills */ }
}