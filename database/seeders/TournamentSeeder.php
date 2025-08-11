<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use App\Models\Club;
use App\Models\Team;
use App\Models\User;
use App\Services\BracketGeneratorService;
use Carbon\Carbon;

class TournamentSeeder extends Seeder
{
    private BracketGeneratorService $bracketGenerator;

    public function __construct()
    {
        $this->bracketGenerator = app(BracketGeneratorService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have clubs and teams to work with
        $this->createSampleClubs();
        $this->createSampleTeams();
        
        // Create diverse tournament scenarios
        $this->createSingleEliminationTournament();
        $this->createDoubleEliminationTournament();
        $this->createRoundRobinTournament();
        $this->createSwissSystemTournament();
        $this->createGroupStageKnockoutTournament();
        $this->createLadderTournament();
        
        // Create tournaments in different states
        $this->createCompletedTournament();
        $this->createInProgressTournament();
        $this->createUpcomingTournament();
        $this->createRegistrationOpenTournament();
        
        $this->command->info('Tournament seeder completed successfully.');
    }

    /**
     * Create sample clubs for tournament hosting.
     */
    private function createSampleClubs(): void
    {
        $clubs = [
            [
                'name' => 'Basketball Club München',
                'short_name' => 'BCM',
                'city' => 'München',
                'founded_year' => 1985
            ],
            [
                'name' => 'Berlin Ballers',
                'short_name' => 'BB',
                'city' => 'Berlin',
                'founded_year' => 1992
            ],
            [
                'name' => 'Hamburg Hawks',
                'short_name' => 'HH',
                'city' => 'Hamburg',
                'founded_year' => 1978
            ],
            [
                'name' => 'Cologne Cardinals',
                'short_name' => 'CC',
                'city' => 'Köln',
                'founded_year' => 2001
            ]
        ];

        foreach ($clubs as $clubData) {
            Club::firstOrCreate(
                ['name' => $clubData['name']],
                $clubData
            );
        }
    }

    /**
     * Create sample teams for tournaments.
     */
    private function createSampleTeams(): void
    {
        $clubs = Club::all();
        $teamNames = [
            // Professional sounding teams
            'Lions', 'Eagles', 'Tigers', 'Panthers', 'Wolves', 'Bears', 
            'Thunder', 'Lightning', 'Storm', 'Fire', 'Warriors', 'Knights',
            'Dragons', 'Phoenix', 'Falcons', 'Hawks', 'Sharks', 'Rebels',
            'Legends', 'Champions', 'Titans', 'Giants', 'Rockets', 'Comets',
            
            // German-style names
            'Bären', 'Löwen', 'Adler', 'Drachen', 'Blitz', 'Sturm',
            'Wikinger', 'Gladiatoren', 'Spartiaten', 'Könige', 'Helden',
            'Kämpfer', 'Jäger', 'Ranger', 'Bomber', 'Crusher'
        ];

        $categories = ['U12', 'U14', 'U16', 'U18', 'adult'];
        $genders = ['male', 'female', 'mixed'];

        foreach ($clubs as $club) {
            for ($i = 0; $i < rand(8, 12); $i++) {
                $teamName = $teamNames[array_rand($teamNames)];
                $category = $categories[array_rand($categories)];
                $gender = $genders[array_rand($genders)];
                
                Team::firstOrCreate([
                    'club_id' => $club->id,
                    'name' => $club->short_name . ' ' . $teamName . ' ' . $category . ' ' . ucfirst($gender),
                    'category' => $category,
                    'gender' => $gender
                ], [
                    'short_name' => $club->short_name . $teamName[0] . $category,
                    'season' => '2024/2025',
                    'founded_year' => rand(2010, 2024),
                    'is_active' => true
                ]);
            }
        }
    }

    /**
     * Create single elimination tournament.
     */
    private function createSingleEliminationTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Frühjahrs Cup 2024',
            'description' => 'Das prestigeträchtige Frühjahrsturnier für U16 Mannschaften mit Single-Elimination Format.',
            'type' => 'single_elimination',
            'category' => 'U16',
            'gender' => 'mixed',
            'status' => 'registration_open',
            'club_id' => Club::first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(30),
            'end_date' => Carbon::now()->addDays(32),
            'registration_start' => Carbon::now()->subDays(14),
            'registration_end' => Carbon::now()->addDays(25),
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 16,
            'entry_fee' => 150.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Olympiahalle München',
            'venue_address' => 'Spiridon-Louis-Ring 21, 80809 München',
            'available_courts' => 3,
            
            // Game Rules
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            'game_rules' => [
                'fouls_to_bonus' => 7,
                'technical_foul_shots' => 1,
                'flagrant_foul_ejection' => true,
                'mercy_rule' => 30,
                'timeouts_per_half' => 3,
                'substitution_rules' => 'unlimited'
            ],
            
            // Tournament Structure
            'third_place_game' => true,
            'advancement_rules' => [
                'tie_breaker' => 'head_to_head',
                'wildcard_advancement' => false
            ],
            
            // Prizes
            'prizes' => [
                '1st' => 'Pokal + 500€',
                '2nd' => 'Pokal + 300€',
                '3rd' => 'Pokal + 150€',
                'mvp' => 'MVP Trophäe'
            ],
            'total_prize_money' => 950.00,
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => 5.00,
            'photography_allowed' => true,
            'livestream_enabled' => true,
            'livestream_url' => 'https://stream.example.com/fruehjahrs-cup',
            
            // Contact
            'contact_email' => 'turnier@bcm.de',
            'contact_phone' => '+49 89 1234567',
            'special_instructions' => 'Bitte bringen Sie offizielle Trikots mit. Aufwärmen 30 Minuten vor Spielbeginn.',
        ]);

        // Register 12 teams
        $teams = Team::where('category', 'U16')->take(12)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(1, 14)),
                'contact_person' => 'Coach ' . fake()->lastName,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
                'special_requirements' => rand(0, 1) ? 'Keine besonderen Anforderungen' : null,
            ]);
        }

        // Generate brackets
        $this->bracketGenerator->generateTournamentBrackets($tournament);
    }

    /**
     * Create double elimination tournament.
     */
    private function createDoubleEliminationTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Stadtmeisterschaft Berlin 2024',
            'description' => 'Offene Stadtmeisterschaft mit Double-Elimination für maximale Chancengleichheit.',
            'type' => 'double_elimination',
            'category' => 'adult',
            'gender' => 'male',
            'status' => 'upcoming',
            'club_id' => Club::where('city', 'Berlin')->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(45),
            'end_date' => Carbon::now()->addDays(48),
            'registration_start' => Carbon::now()->subDays(30),
            'registration_end' => Carbon::now()->addDays(35),
            'daily_start_time' => '08:00',
            'daily_end_time' => '20:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 16,
            'entry_fee' => 300.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Max-Schmeling-Halle',
            'venue_address' => 'Am Falkplatz, 10437 Berlin',
            'additional_venues' => ['Sporthalle Wedding', 'Arena Treptow'],
            'available_courts' => 4,
            
            // Game Rules
            'game_duration' => 48,
            'periods' => 4,
            'period_length' => 12,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            'game_rules' => [
                'fouls_to_bonus' => 5,
                'technical_foul_shots' => 2,
                'flagrant_foul_ejection' => true,
                'mercy_rule' => null,
                'timeouts_per_half' => 4,
                'substitution_rules' => 'unlimited',
                'three_point_line' => 'FIBA'
            ],
            
            // Tournament Structure
            'third_place_game' => false,
            'seeding_rules' => [
                'method' => 'random_draw',
                'protected_seeds' => 4
            ],
            
            // Prizes
            'prizes' => [
                '1st' => 'Meisterschaftspokal + 2000€',
                '2nd' => 'Silberpokal + 1000€',
                '3rd' => 'Bronzepokal + 500€',
                'mvp' => 'MVP Award + Basketballschuhe'
            ],
            'total_prize_money' => 3500.00,
            
            // Settings
            'is_public' => true,
            'requires_approval' => true,
            'allows_spectators' => true,
            'spectator_fee' => 10.00,
            'photography_allowed' => true,
            'livestream_enabled' => true,
            'livestream_url' => 'https://berlin-basketball.stream',
            
            // Contact
            'contact_email' => 'meisterschaft@berlin-ballers.de',
            'contact_phone' => '+49 30 9876543',
            'covid_requirements' => '3G-Regel erforderlich, Masken in Innenräumen empfohlen.',
        ]);

        // Register 14 teams
        $teams = Team::where('category', 'adult')->where('gender', 'male')->take(14)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(5, 25)),
                'contact_person' => 'Manager ' . fake()->name,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => rand(0, 1),
                'payment_status' => rand(0, 1) ? 'paid' : 'pending',
                'travel_information' => [
                    'needs_accommodation' => rand(0, 1),
                    'arrival_date' => Carbon::now()->addDays(44)->format('Y-m-d'),
                    'departure_date' => Carbon::now()->addDays(49)->format('Y-m-d'),
                ],
            ]);
        }

        // Generate brackets for upcoming tournament
        $this->bracketGenerator->generateTournamentBrackets($tournament);
    }

    /**
     * Create round robin tournament.
     */
    private function createRoundRobinTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Hamburg Liga Meisterschaft 2024',
            'description' => 'Jeder gegen Jeden - die fairste Art, den Champion zu ermitteln.',
            'type' => 'round_robin',
            'category' => 'U18',
            'gender' => 'female',
            'status' => 'registration_open',
            'club_id' => Club::where('city', 'Hamburg')->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(20),
            'end_date' => Carbon::now()->addDays(22),
            'registration_start' => Carbon::now()->subDays(10),
            'registration_end' => Carbon::now()->addDays(15),
            'daily_start_time' => '10:00',
            'daily_end_time' => '16:00',
            
            // Teams
            'min_teams' => 6,
            'max_teams' => 8,
            'entry_fee' => 120.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Sporthalle Alsterdorf',
            'venue_address' => 'Alsterdorfer Str. 123, 22297 Hamburg',
            'available_courts' => 2,
            
            // Game Rules
            'game_duration' => 32,
            'periods' => 4,
            'period_length' => 8,
            'overtime_enabled' => true,
            'overtime_length' => 4,
            'shot_clock_enabled' => false,
            'game_rules' => [
                'fouls_to_bonus' => 8,
                'technical_foul_shots' => 1,
                'mercy_rule' => 25,
                'timeouts_per_half' => 2,
                'substitution_rules' => 'unlimited',
                'press_rule' => 'no_press_after_15_point_lead'
            ],
            
            // Tournament Structure
            'groups_count' => 1,
            'advancement_rules' => [
                'tie_breaker' => 'point_differential',
                'head_to_head_priority' => true
            ],
            
            // Prizes
            'prizes' => [
                '1st' => 'Liga-Pokal + Medaillen für alle Spieler',
                '2nd' => 'Vize-Pokal + Medaillen',
                '3rd' => 'Bronze-Pokal + Medaillen',
                'fair_play' => 'Fair Play Award'
            ],
            'awards' => [
                'top_scorer',
                'best_defensive_player', 
                'most_improved_player',
                'team_spirit_award'
            ],
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => null,
            'photography_allowed' => true,
            'livestream_enabled' => false,
            
            // Contact
            'contact_email' => 'liga@hamburg-hawks.de',
            'contact_phone' => '+49 40 5555666',
        ]);

        // Register 7 teams
        $teams = Team::where('category', 'U18')->where('gender', 'female')->take(7)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(1, 8)),
                'contact_person' => 'Trainer ' . fake()->firstName,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
                'equipment_needs' => [
                    'uniform_colors' => ['Rot', 'Weiß'],
                    'warmup_time_needed' => 20,
                ],
                'team_experience_level' => 'competitive',
            ]);
        }

        // Generate round robin brackets
        $this->bracketGenerator->generateTournamentBrackets($tournament);
    }

    /**
     * Create swiss system tournament.
     */
    private function createSwissSystemTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'NRW Open Championship',
            'description' => 'Großes Swiss-System Turnier für alle Altersklassen mit optimaler Paarungslogik.',
            'type' => 'swiss_system',
            'category' => 'mixed',
            'gender' => 'mixed',
            'status' => 'upcoming',
            'club_id' => Club::where('city', 'Köln')->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(60),
            'end_date' => Carbon::now()->addDays(62),
            'registration_start' => Carbon::now()->subDays(20),
            'registration_end' => Carbon::now()->addDays(50),
            'daily_start_time' => '09:00',
            'daily_end_time' => '19:00',
            
            // Teams
            'min_teams' => 16,
            'max_teams' => 32,
            'entry_fee' => 180.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Lanxess Arena',
            'venue_address' => 'Willy-Brandt-Platz 3, 50679 Köln',
            'additional_venues' => ['Sporthalle Deutz', 'Basketball Center Ehrenfeld'],
            'available_courts' => 6,
            
            // Game Rules
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 30,
            'game_rules' => [
                'fouls_to_bonus' => 6,
                'technical_foul_shots' => 2,
                'flagrant_foul_ejection' => true,
                'timeouts_per_half' => 3,
                'substitution_rules' => 'unlimited',
                'age_eligibility_check' => true
            ],
            
            // Tournament Structure
            'seeding_rules' => [
                'method' => 'rating_based',
                'initial_rating' => 1200,
                'k_factor' => 32
            ],
            'advancement_rules' => [
                'rounds' => 6,
                'pairing_method' => 'swiss_perfect',
                'avoid_repeat_opponents' => true,
                'color_balancing' => true
            ],
            
            // Prizes
            'prizes' => [
                '1st' => 'Hauptpreis 3000€ + Trophäe',
                '2nd' => '2000€ + Pokal',
                '3rd' => '1000€ + Pokal',
                'age_group_winners' => '200€ pro Altersklasse',
                'participation' => 'Teilnahme-Zertifikat'
            ],
            'total_prize_money' => 8000.00,
            
            // Settings
            'is_public' => true,
            'requires_approval' => true,
            'allows_spectators' => true,
            'spectator_fee' => 15.00,
            'photography_allowed' => true,
            'livestream_enabled' => true,
            'livestream_url' => 'https://nrw-open.basketball-stream.de',
            
            // Media
            'social_media_links' => [
                ['platform' => 'Instagram', 'url' => 'https://instagram.com/nrw_open_basketball'],
                ['platform' => 'Facebook', 'url' => 'https://facebook.com/nrwopen'],
                ['platform' => 'YouTube', 'url' => 'https://youtube.com/nrwbasketball']
            ],
            
            // Contact
            'contact_email' => 'info@nrw-open.de',
            'contact_phone' => '+49 221 7777888',
            'special_instructions' => 'Online-Check-In 2 Stunden vor erstem Spiel. Offizielle FIBA-Regeln.',
        ]);

        // Register 24 teams from different categories
        $teams = Team::whereIn('category', ['U14', 'U16', 'U18', 'adult'])->take(24)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(3, 18)),
                'contact_person' => fake()->name,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => rand(0, 4) > 0, // 80% confirmed
                'payment_status' => rand(0, 3) > 0 ? 'paid' : 'pending', // 75% paid
                'travel_information' => [
                    'needs_accommodation' => rand(0, 1),
                    'transportation_method' => rand(0, 1) ? 'Bus' : 'PKW',
                ],
                'previous_tournaments' => [
                    [
                        'tournament_name' => 'Regionalmeisterschaft 2023',
                        'year' => 2023,
                        'result' => 'Viertelfinale'
                    ]
                ],
                'tournament_goals' => rand(0, 1) ? 'Top 8 erreichen' : 'Erfahrung sammeln',
                'expected_placement' => ['champion', 'top_3', 'top_half', 'participation'][rand(0, 3)],
            ]);
        }
    }

    /**
     * Create group stage knockout tournament.
     */
    private function createGroupStageKnockoutTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Champions Cup 2024',
            'description' => 'Premiumturnier mit Gruppenphase und anschließender K.O.-Phase nach Europa-League-System.',
            'type' => 'group_stage_knockout',
            'category' => 'adult',
            'gender' => 'mixed',
            'status' => 'upcoming',
            'club_id' => Club::first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(75),
            'end_date' => Carbon::now()->addDays(78),
            'registration_start' => Carbon::now()->subDays(45),
            'registration_end' => Carbon::now()->addDays(65),
            'daily_start_time' => '08:30',
            'daily_end_time' => '21:00',
            
            // Teams
            'min_teams' => 16,
            'max_teams' => 24,
            'entry_fee' => 400.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Basketball Arena München',
            'venue_address' => 'Olympiapark München',
            'additional_venues' => ['Audi Dome', 'TU München Sporthalle'],
            'available_courts' => 8,
            
            // Game Rules
            'game_duration' => 48,
            'periods' => 4,
            'period_length' => 12,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            'game_rules' => [
                'fouls_to_bonus' => 5,
                'technical_foul_shots' => 2,
                'flagrant_foul_ejection' => true,
                'unsportsmanlike_foul_points' => 1,
                'timeouts_per_half' => 4,
                'timeout_length' => 60,
                'substitution_rules' => 'unlimited',
                'video_replay' => true
            ],
            
            // Tournament Structure
            'groups_count' => 4,
            'seeding_rules' => [
                'method' => 'serpentine_draw',
                'protected_seeds' => 8,
                'regional_separation' => true
            ],
            'advancement_rules' => [
                'group_winners' => 'automatic_quarterfinals',
                'group_runners_up' => 'playoff_for_quarterfinals',
                'group_third_place' => 'eliminated',
                'tie_breaker' => ['head_to_head', 'point_differential', 'points_scored']
            ],
            'third_place_game' => true,
            
            // Prizes
            'prizes' => [
                '1st' => 'Champions Cup + 5000€',
                '2nd' => 'Runners-up Trophäe + 3000€',
                '3rd' => 'Bronze Medal + 1500€',
                'group_winners' => '500€ pro Gruppensieger',
                'mvp_final' => 'BMW i3 (Leasing 1 Jahr)',
                'all_tournament_team' => 'All-Star Trikot + Ausrüstung'
            ],
            'awards' => [
                'mvp_tournament',
                'top_scorer', 
                'best_defensive_player',
                'most_valuable_young_player',
                'coach_of_tournament',
                'fair_play_award',
                'fan_favorite_team'
            ],
            'total_prize_money' => 15000.00,
            
            // Settings
            'is_public' => true,
            'requires_approval' => true,
            'allows_spectators' => true,
            'spectator_fee' => 25.00,
            'photography_allowed' => true,
            'livestream_enabled' => true,
            'livestream_url' => 'https://champions-cup.sport1.de/live',
            
            // Media
            'social_media_links' => [
                ['platform' => 'Instagram', 'url' => 'https://instagram.com/champions_cup_basketball'],
                ['platform' => 'TikTok', 'url' => 'https://tiktok.com/@championscup'],
                ['platform' => 'Twitch', 'url' => 'https://twitch.tv/championscup_live']
            ],
            
            // Contact
            'contact_email' => 'organization@champions-cup.de',
            'contact_phone' => '+49 89 2020 3030',
            'special_instructions' => 'Professionelles Turnier mit TV-Übertragung. Dresscode für Trainer erforderlich. Anti-Doping-Kontrollen möglich.',
            'covid_requirements' => 'Aktuelle Corona-Bestimmungen gemäß Landesverordnung Bayern.',
        ]);

        // Register 20 premium teams
        $teams = Team::where('category', 'adult')->take(20)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(10, 40)),
                'contact_person' => 'Team Manager ' . fake()->name,
                'contact_email' => fake()->companyEmail,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
                'special_requirements' => 'VIP Betreuung erwünscht',
                'travel_information' => [
                    'needs_accommodation' => true,
                    'accommodation_nights' => 4,
                    'arrival_date' => Carbon::now()->addDays(74)->format('Y-m-d'),
                    'departure_date' => Carbon::now()->addDays(79)->format('Y-m-d'),
                    'transportation_method' => 'Flugzeug',
                    'special_needs' => 'Transfer vom Flughafen'
                ],
                'equipment_needs' => [
                    'uniform_colors' => ['Primärfarbe', 'Auswärtsfarbe'],
                    'warmup_time_needed' => 45,
                    'special_equipment' => 'Professionelle Basketbälle'
                ],
                'medical_information' => [
                    'team_doctor' => 'Dr. ' . fake()->lastName,
                    'team_doctor_phone' => fake()->phoneNumber,
                    'medical_insurance' => 'Premium Sportversicherung',
                ],
                'previous_tournaments' => [
                    [
                        'tournament_name' => 'Champions Cup 2023',
                        'year' => 2023,
                        'result' => 'Halbfinale'
                    ],
                    [
                        'tournament_name' => 'Deutsche Meisterschaft',
                        'year' => 2023,
                        'result' => 'Finale'
                    ]
                ],
                'tournament_goals' => 'Titelverteidigung und internationale Anerkennung',
                'expected_placement' => 'champion',
                'media_consent' => true,
                'photo_consent' => true,
                'interview_consent' => true,
            ]);
        }
    }

    /**
     * Create ladder tournament.
     */
    private function createLadderTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Basketball Ladder Liga 2024',
            'description' => 'Kontinuierliche Ladder-Liga über die gesamte Saison mit flexiblen Herausforderungen.',
            'type' => 'ladder',
            'category' => 'adult',
            'gender' => 'mixed',
            'status' => 'in_progress',
            'club_id' => Club::inRandomOrder()->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule (Season-long)
            'start_date' => Carbon::now()->subDays(60),
            'end_date' => Carbon::now()->addDays(150),
            'registration_start' => Carbon::now()->subDays(90),
            'registration_end' => Carbon::now()->addDays(30), // Rolling registration
            'daily_start_time' => '18:00',
            'daily_end_time' => '22:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 50,
            'entry_fee' => 50.00, // Monthly fee
            'currency' => 'EUR',
            
            // Venue (Multiple locations)
            'primary_venue' => 'Various Basketball Courts',
            'venue_address' => 'Flexible locations across the city',
            'additional_venues' => [
                'Universitätssporthalle',
                'Stadtpark Basketball Court',
                'Vereinshalle Nord',
                'Community Center Süd'
            ],
            'available_courts' => 12,
            
            // Game Rules
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => false,
            'game_rules' => [
                'fouls_to_bonus' => 7,
                'technical_foul_shots' => 1,
                'timeouts_per_half' => 2,
                'substitution_rules' => 'unlimited',
                'challenge_rules' => true,
                'flexible_scheduling' => true,
                'makeup_games_allowed' => true
            ],
            
            // Tournament Structure
            'advancement_rules' => [
                'ranking_system' => 'elo_rating',
                'initial_rating' => 1000,
                'challenge_range' => 3, // Can challenge teams within 3 positions
                'games_per_month_minimum' => 2,
                'activity_requirement' => true,
                'playoff_qualification' => 'top_8',
                'season_end_playoff' => true
            ],
            
            // Prizes
            'prizes' => [
                'season_champion' => 'Liga-Pokal + 1000€',
                'monthly_leader' => '100€ pro Monat',
                'most_active_team' => 'Activity Award + Ausrüstung',
                'biggest_climb' => 'Improvement Award',
                'playoff_champion' => 'Playoff-Trophäe + 500€'
            ],
            'total_prize_money' => 2500.00,
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => null,
            'photography_allowed' => true,
            'livestream_enabled' => false,
            
            // Contact
            'contact_email' => 'ladder@basketball-liga.de',
            'contact_phone' => '+49 123 4567890',
            'special_instructions' => 'Flexible Terminplanung über App. Teams können sich gegenseitig herausfordern.',
        ]);

        // Register 15 teams with ongoing activity
        $teams = Team::where('category', 'adult')->skip(20)->take(15)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(30, 80)),
                'contact_person' => 'Kapitän ' . fake()->name,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
                'special_requirements' => 'Abendspiele bevorzugt',
                'team_experience_level' => ['recreational', 'competitive'][rand(0, 1)],
                'tournament_goals' => 'Spaß am Spiel und Verbesserung',
                'expected_placement' => 'top_half',
            ]);
        }

        // Generate initial ladder brackets
        $this->bracketGenerator->generateTournamentBrackets($tournament);
    }

    /**
     * Create completed tournament with full results.
     */
    private function createCompletedTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Winter Cup 2023 - Completed',
            'description' => 'Abgeschlossenes Winterturnier mit vollständigen Ergebnissen.',
            'type' => 'single_elimination',
            'category' => 'U14',
            'gender' => 'male',
            'status' => 'completed',
            'club_id' => Club::first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule (Past dates)
            'start_date' => Carbon::now()->subDays(120),
            'end_date' => Carbon::now()->subDays(118),
            'registration_start' => Carbon::now()->subDays(140),
            'registration_end' => Carbon::now()->subDays(125),
            'daily_start_time' => '10:00',
            'daily_end_time' => '17:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 8,
            'entry_fee' => 100.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Wintersporthalle',
            'venue_address' => 'Sportplatz 1, 12345 Stadt',
            'available_courts' => 2,
            
            // Game Rules
            'game_duration' => 32,
            'periods' => 4,
            'period_length' => 8,
            'overtime_enabled' => true,
            'overtime_length' => 4,
            'shot_clock_enabled' => false,
            
            // Tournament Structure
            'third_place_game' => true,
            
            // Completed tournament specific
            'tournament_completed_at' => Carbon::now()->subDays(118),
            'final_standings' => [
                '1st' => 'BCM Lions U14',
                '2nd' => 'Berlin Eagles U14', 
                '3rd' => 'Hamburg Hawks U14',
                '4th' => 'Cologne Cardinals U14'
            ],
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => 3.00,
            'photography_allowed' => true,
            
            // Contact
            'contact_email' => 'winter@tournament.de',
            'contact_phone' => '+49 111 222333',
        ]);

        // Register 8 teams
        $teams = Team::where('category', 'U14')->where('gender', 'male')->take(8)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(125, 135)),
                'contact_person' => 'Coach ' . fake()->lastName,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
                'final_placement' => $index < 4 ? $index + 1 : null, // Top 4 get placement
            ]);
        }

        // Generate and complete brackets with results
        $this->bracketGenerator->generateTournamentBrackets($tournament);
        $this->simulateCompletedTournamentResults($tournament);
    }

    /**
     * Create in-progress tournament.
     */
    private function createInProgressTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Aktuelle Stadtmeisterschaft 2024',
            'description' => 'Laufendes Turnier mit aktuellen Spielen und Live-Ergebnissen.',
            'type' => 'double_elimination',
            'category' => 'U16',
            'gender' => 'mixed',
            'status' => 'in_progress',
            'club_id' => Club::inRandomOrder()->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule (Currently running)
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->addDays(2),
            'registration_start' => Carbon::now()->subDays(30),
            'registration_end' => Carbon::now()->subDays(5),
            'daily_start_time' => '09:00',
            'daily_end_time' => '18:00',
            
            // Teams
            'min_teams' => 12,
            'max_teams' => 12,
            'entry_fee' => 200.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Zentrale Sporthalle',
            'venue_address' => 'Hauptstraße 100, 54321 Stadt',
            'available_courts' => 3,
            
            // Game Rules
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => 8.00,
            'photography_allowed' => true,
            'livestream_enabled' => true,
            'livestream_url' => 'https://live.tournament.stream',
            
            // Contact
            'contact_email' => 'live@stadtmeisterschaft.de',
            'contact_phone' => '+49 999 888777',
        ]);

        // Register 12 teams
        $teams = Team::where('category', 'U16')->take(12)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(5, 25)),
                'contact_person' => fake()->name,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => true,
                'payment_status' => 'paid',
            ]);
        }

        // Generate brackets and simulate some completed games
        $this->bracketGenerator->generateTournamentBrackets($tournament);
        $this->simulateInProgressTournamentResults($tournament);
    }

    /**
     * Create upcoming tournament.
     */
    private function createUpcomingTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Sommer-Festival Basketball 2024',
            'description' => 'Großes Sommerfest mit Basketball-Turnier für die ganze Familie.',
            'type' => 'round_robin',
            'category' => 'mixed',
            'gender' => 'mixed',
            'status' => 'upcoming',
            'club_id' => Club::inRandomOrder()->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule (Future)
            'start_date' => Carbon::now()->addDays(90),
            'end_date' => Carbon::now()->addDays(92),
            'registration_start' => Carbon::now()->addDays(10),
            'registration_end' => Carbon::now()->addDays(80),
            'daily_start_time' => '10:00',
            'daily_end_time' => '20:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 12,
            'entry_fee' => 80.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Sommerfestplatz',
            'venue_address' => 'Festwiese 1, 98765 Sommerstadt',
            'available_courts' => 4,
            
            // Game Rules
            'game_duration' => 30,
            'periods' => 3,
            'period_length' => 10,
            'overtime_enabled' => false,
            'shot_clock_enabled' => false,
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => null,
            'photography_allowed' => true,
            
            // Contact
            'contact_email' => 'sommer@festival.de',
            'contact_phone' => '+49 555 666777',
            'special_instructions' => 'Familienfest mit Grillstation und Kinderbetreuung verfügbar.',
        ]);
    }

    /**
     * Create registration open tournament.
     */
    private function createRegistrationOpenTournament(): void
    {
        $tournament = Tournament::create([
            'name' => 'Herbst-Challenge 2024',
            'description' => 'Herausforderung für alle Teams - Anmeldung jetzt geöffnet!',
            'type' => 'single_elimination',
            'category' => 'U18',
            'gender' => 'male',
            'status' => 'registration_open',
            'club_id' => Club::inRandomOrder()->first()->id,
            'organizer_id' => User::first()->id,
            
            // Schedule
            'start_date' => Carbon::now()->addDays(40),
            'end_date' => Carbon::now()->addDays(42),
            'registration_start' => Carbon::now()->subDays(5),
            'registration_end' => Carbon::now()->addDays(30),
            'daily_start_time' => '09:00',
            'daily_end_time' => '17:00',
            
            // Teams
            'min_teams' => 8,
            'max_teams' => 16,
            'entry_fee' => 150.00,
            'currency' => 'EUR',
            
            // Venue
            'primary_venue' => 'Herbsthalle',
            'venue_address' => 'Am Herbstpark 5, 13579 Herbststadt',
            'available_courts' => 2,
            
            // Game Rules
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 30,
            
            // Settings
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => 5.00,
            'photography_allowed' => true,
            
            // Contact
            'contact_email' => 'herbst@challenge.de',
            'contact_phone' => '+49 444 555666',
        ]);

        // Register some teams (partially filled)
        $teams = Team::where('category', 'U18')->where('gender', 'male')->take(6)->get();
        foreach ($teams as $index => $team) {
            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'seed' => $index + 1,
                'registration_date' => Carbon::now()->subDays(rand(1, 4)),
                'contact_person' => fake()->name,
                'contact_email' => fake()->email,
                'contact_phone' => fake()->phoneNumber,
                'roster_confirmed' => rand(0, 1),
                'payment_status' => rand(0, 2) > 0 ? 'paid' : 'pending',
            ]);
        }
    }

    /**
     * Simulate completed tournament results.
     */
    private function simulateCompletedTournamentResults($tournament): void
    {
        $brackets = $tournament->brackets()->orderBy('round')->get();
        
        foreach ($brackets as $bracket) {
            if ($bracket->has_both_teams) {
                $bracket->update([
                    'status' => 'completed',
                    'scheduled_at' => Carbon::now()->subDays(rand(118, 120))->addHours(rand(8, 16)),
                    'actual_start_time' => Carbon::now()->subDays(rand(118, 120))->addHours(rand(8, 16)),
                    'actual_duration' => rand(35, 45),
                    'team1_score' => rand(40, 85),
                    'team2_score' => rand(40, 85),
                    'winner_team_id' => rand(0, 1) ? $bracket->team1_id : $bracket->team2_id,
                    'game_notes' => 'Spannendes Spiel mit guter Atmosphäre.',
                ]);
                
                // Set loser_team_id
                $bracket->update([
                    'loser_team_id' => $bracket->winner_team_id === $bracket->team1_id ? 
                                      $bracket->team2_id : $bracket->team1_id
                ]);
                
                // Ensure winner has higher score
                if ($bracket->winner_team_id === $bracket->team1_id && $bracket->team1_score < $bracket->team2_score) {
                    $bracket->update(['team1_score' => $bracket->team2_score + rand(1, 10)]);
                } elseif ($bracket->winner_team_id === $bracket->team2_id && $bracket->team2_score < $bracket->team1_score) {
                    $bracket->update(['team2_score' => $bracket->team1_score + rand(1, 10)]);
                }
            }
        }
    }

    /**
     * Simulate in-progress tournament results.
     */
    private function simulateInProgressTournamentResults($tournament): void
    {
        $brackets = $tournament->brackets()->orderBy('round')->get();
        $completed = 0;
        $total = $brackets->count();
        
        foreach ($brackets as $index => $bracket) {
            if ($bracket->has_both_teams && $completed < $total * 0.6) { // Complete 60% of games
                $bracket->update([
                    'status' => 'completed',
                    'scheduled_at' => Carbon::now()->subDays(rand(0, 2))->addHours(rand(9, 17)),
                    'actual_start_time' => Carbon::now()->subDays(rand(0, 2))->addHours(rand(9, 17)),
                    'actual_duration' => rand(42, 52),
                    'team1_score' => rand(50, 90),
                    'team2_score' => rand(50, 90),
                    'winner_team_id' => rand(0, 1) ? $bracket->team1_id : $bracket->team2_id,
                ]);
                
                $bracket->update([
                    'loser_team_id' => $bracket->winner_team_id === $bracket->team1_id ? 
                                      $bracket->team2_id : $bracket->team1_id
                ]);
                
                $completed++;
            } elseif ($bracket->has_both_teams && $completed < $total * 0.8) { // 20% in progress
                $bracket->update([
                    'status' => 'in_progress',
                    'scheduled_at' => Carbon::now()->addHours(rand(1, 8)),
                    'actual_start_time' => Carbon::now()->subMinutes(rand(10, 30)),
                ]);
                
                $completed++;
            } else { // Rest scheduled
                $bracket->update([
                    'status' => 'scheduled',
                    'scheduled_at' => Carbon::now()->addHours(rand(8, 48)),
                ]);
            }
        }
    }
}