<?php

namespace Tests\Unit\Services;

use App\Models\Club;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use App\Models\TournamentAward;
use App\Models\TournamentOfficial;
use App\Models\User;
use App\Observers\TeamObserver;
use App\Services\TournamentService;
use App\Services\BracketGeneratorService;
use App\Services\TournamentProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;
use Mockery;

class TournamentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TournamentService $service;
    protected BracketGeneratorService $bracketGenerator;
    protected TournamentProgressionService $progressionService;
    protected User $organizer;
    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        // Use array cache driver to avoid Redis dependency
        config(['cache.default' => 'array']);
        Cache::flush();

        // Remove TeamObserver to avoid Redis calls
        Team::unsetEventDispatcher();

        $this->bracketGenerator = Mockery::mock(BracketGeneratorService::class);
        $this->progressionService = Mockery::mock(TournamentProgressionService::class);

        $this->service = new TournamentService(
            $this->bracketGenerator,
            $this->progressionService
        );

        $this->organizer = User::factory()->create();
        $this->club = Club::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function create_tournament_creates_tournament_with_draft_status(): void
    {
        $data = [
            'name' => 'Berlin Basketball Cup 2024',
            'description' => 'Annual basketball tournament',
            'type' => 'single_elimination',
            'category' => 'adult',
            'gender' => 'mixed',
            'min_teams' => 4,
            'max_teams' => 16,
            'club_id' => $this->club->id,
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addDays(2),
            'registration_start' => now(),
            'registration_end' => now()->addWeeks(2),
            'daily_start_time' => '09:00',
            'daily_end_time' => '21:00',
            'primary_venue' => 'Test Arena',
        ];

        $tournament = $this->service->createTournament($data, $this->organizer);

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals('draft', $tournament->status);
        $this->assertEquals('Berlin Basketball Cup 2024', $tournament->name);
        $this->assertEquals($this->organizer->id, $tournament->organizer_id);
        $this->assertEquals(0, $tournament->registered_teams);
    }

    /** @test */
    public function update_tournament_updates_allowed_fields(): void
    {
        $tournament = Tournament::factory()->draft()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $updated = $this->service->updateTournament($tournament, [
            'name' => 'Updated Tournament Name',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('Updated Tournament Name', $updated->name);
        $this->assertEquals('Updated description', $updated->description);
    }

    /** @test */
    public function update_tournament_throws_exception_for_restricted_fields_after_draft(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Folgende Felder können nach Anmeldungsstart nicht mehr geändert werden');

        $this->service->updateTournament($tournament, [
            'type' => 'round_robin', // Restricted field
        ]);
    }

    /** @test */
    public function delete_tournament_deletes_draft_tournament(): void
    {
        $tournament = Tournament::factory()->draft()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $result = $this->service->deleteTournament($tournament);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tournaments', ['id' => $tournament->id]);
    }

    /** @test */
    public function delete_tournament_throws_exception_for_in_progress_tournament(): void
    {
        $tournament = Tournament::factory()->inProgress()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Laufende Turniere können nicht gelöscht werden');

        $this->service->deleteTournament($tournament);
    }

    /** @test */
    public function register_team_creates_tournament_team_with_pending_status(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
            'requires_approval' => true, // Requires approval so status stays pending
        ]);
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $tournamentTeam = $this->service->registerTeam($tournament, $team, $user, [
            'contact_person' => 'John Doe',
            'contact_email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(TournamentTeam::class, $tournamentTeam);
        $this->assertEquals($tournament->id, $tournamentTeam->tournament_id);
        $this->assertEquals($team->id, $tournamentTeam->team_id);
        $this->assertEquals('pending', $tournamentTeam->status);
    }

    /** @test */
    public function register_team_throws_exception_when_registration_closed(): void
    {
        $tournament = Tournament::factory()->draft()->create([
            'organizer_id' => $this->organizer->id,
        ]);
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Anmeldung für dieses Turnier ist nicht möglich');

        $this->service->registerTeam($tournament, $team, $user);
    }

    /** @test */
    public function register_team_throws_exception_for_duplicate_registration(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
        ]);
        $team = Team::factory()->create();
        $user = User::factory()->create();

        // First registration
        TournamentTeam::factory()->approved()->create([
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Team ist bereits für dieses Turnier angemeldet');

        $this->service->registerTeam($tournament, $team, $user);
    }

    /** @test */
    public function approve_team_registration_approves_pending_team(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
            'registered_teams' => 0,
        ]);
        $tournamentTeam = TournamentTeam::factory()->pending()->create([
            'tournament_id' => $tournament->id,
        ]);

        $approved = $this->service->approveTeamRegistration($tournamentTeam);

        $this->assertEquals('approved', $approved->status);
        $this->assertNotNull($approved->status_updated_at);

        $tournament->refresh();
        $this->assertEquals(1, $tournament->registered_teams);
    }

    /** @test */
    public function reject_team_registration_rejects_with_reason(): void
    {
        $tournamentTeam = TournamentTeam::factory()->pending()->create();

        $rejected = $this->service->rejectTeamRegistration($tournamentTeam, 'Not eligible');

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals('Not eligible', $rejected->status_reason);
    }

    /** @test */
    public function withdraw_team_withdraws_team(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
            'registered_teams' => 5,
        ]);
        $tournamentTeam = TournamentTeam::factory()->approved()->create([
            'tournament_id' => $tournament->id,
        ]);

        $withdrawn = $this->service->withdrawTeam($tournamentTeam, 'Team withdrew');

        $this->assertEquals('withdrawn', $withdrawn->status);
        $this->assertEquals('Team withdrew', $withdrawn->status_reason);
    }

    /** @test */
    public function open_registration_changes_status_to_registration_open(): void
    {
        $tournament = Tournament::factory()->draft()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $opened = $this->service->openRegistration($tournament);

        $this->assertEquals('registration_open', $opened->status);
    }

    /** @test */
    public function open_registration_throws_exception_for_non_draft(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Anmeldung kann nur aus Entwurf-Status geöffnet werden');

        $this->service->openRegistration($tournament);
    }

    /** @test */
    public function close_registration_changes_status_when_enough_teams(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
            'min_teams' => 4,
            'registered_teams' => 6,
        ]);

        $closed = $this->service->closeRegistration($tournament);

        $this->assertEquals('registration_closed', $closed->status);
    }

    /** @test */
    public function close_registration_throws_exception_when_not_enough_teams(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
            'min_teams' => 8,
            'registered_teams' => 4,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nicht genügend Teams angemeldet');

        $this->service->closeRegistration($tournament);
    }

    /** @test */
    public function start_tournament_throws_exception_when_not_registration_closed(): void
    {
        $tournament = Tournament::factory()->registrationOpen()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Turnier kann nur nach Anmeldeschluss gestartet werden');

        $this->service->startTournament($tournament);
    }

    /** @test */
    public function start_tournament_throws_exception_when_cannot_start(): void
    {
        $tournament = Tournament::factory()->registrationClosed()->create([
            'organizer_id' => $this->organizer->id,
            'min_teams' => 4,
            'registered_teams' => 2, // Not enough teams
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Turnier kann nicht gestartet werden');

        $this->service->startTournament($tournament);
    }

    /** @test */
    public function get_tournament_statistics_returns_stats_array(): void
    {
        $tournament = Tournament::factory()->inProgress()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $stats = $this->service->getTournamentStatistics($tournament);

        $this->assertArrayHasKey('basic_stats', $stats);
        $this->assertArrayHasKey('team_stats', $stats);
        $this->assertArrayHasKey('game_stats', $stats);
        $this->assertArrayHasKey('progression', $stats);
    }

    /** @test */
    public function get_team_standings_returns_sorted_teams(): void
    {
        $tournament = Tournament::factory()->inProgress()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $team1 = TournamentTeam::factory()->approved()->withStats(3, 0)->create([
            'tournament_id' => $tournament->id,
        ]);
        $team2 = TournamentTeam::factory()->approved()->withStats(2, 1)->create([
            'tournament_id' => $tournament->id,
        ]);
        $team3 = TournamentTeam::factory()->approved()->withStats(1, 2)->create([
            'tournament_id' => $tournament->id,
        ]);

        $standings = $this->service->getTeamStandings($tournament);

        $this->assertCount(3, $standings);
        // Teams should be sorted by tournament_points desc
        $this->assertEquals($team1->id, $standings->first()->id);
    }

    /** @test */
    public function get_upcoming_games_returns_scheduled_brackets(): void
    {
        $tournament = Tournament::factory()->inProgress()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        TournamentBracket::factory()->scheduled()->create([
            'tournament_id' => $tournament->id,
            'scheduled_at' => now()->addDay(),
        ]);
        TournamentBracket::factory()->scheduled()->create([
            'tournament_id' => $tournament->id,
            'scheduled_at' => now()->addDays(2),
        ]);
        TournamentBracket::factory()->completed()->create([
            'tournament_id' => $tournament->id,
        ]);

        $upcoming = $this->service->getUpcomingGames($tournament);

        $this->assertCount(2, $upcoming);
    }

    /** @test */
    public function create_award_creates_tournament_award(): void
    {
        $tournament = Tournament::factory()->completed()->create([
            'organizer_id' => $this->organizer->id,
        ]);

        $award = $this->service->createAward($tournament, [
            'award_name' => 'MVP',
            'award_type' => 'individual_award',
            'award_category' => 'mvp',
            'selection_method' => 'committee_vote',
            'award_format' => 'trophy',
        ]);

        $this->assertInstanceOf(TournamentAward::class, $award);
        $this->assertEquals('MVP', $award->award_name);
        $this->assertEquals($tournament->id, $award->tournament_id);
    }

    /** @test */
    public function seed_teams_updates_team_seeds(): void
    {
        $tournament = Tournament::factory()->registrationClosed()->create([
            'organizer_id' => $this->organizer->id,
        ]);
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        TournamentTeam::factory()->approved()->create([
            'tournament_id' => $tournament->id,
            'team_id' => $team1->id,
        ]);
        TournamentTeam::factory()->approved()->create([
            'tournament_id' => $tournament->id,
            'team_id' => $team2->id,
        ]);

        $this->service->seedTeams($tournament, [
            $team1->id => 1,
            $team2->id => 2,
        ]);

        $this->assertEquals(1, TournamentTeam::where('team_id', $team1->id)->first()->seed);
        $this->assertEquals(2, TournamentTeam::where('team_id', $team2->id)->first()->seed);
    }
}
