<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\Player;
use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\TrainingSession;
use App\Models\TrainingRegistration;
use App\Models\Tournament;
use App\Models\TournamentAward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * SEC-006: Authorization Policy Tests (TDD)
 *
 * Tests for missing authorization policies:
 * - GameRegistrationPolicy
 * - TrainingRegistrationPolicy
 * - TournamentAwardPolicy
 *
 * @see SECURITY_AND_PERFORMANCE_FIXES.md SEC-006
 */
class SEC006AuthorizationPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected BasketballTeam $team;
    protected User $superAdmin;
    protected User $clubAdmin;
    protected User $trainer;
    protected User $player;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupRolesAndPermissions();
        $this->setupTestData();
    }

    protected function setupRolesAndPermissions(): void
    {
        // Create roles
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'club_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'trainer', 'guard_name' => 'web']);
        Role::create(['name' => 'player', 'guard_name' => 'web']);

        // Create relevant permissions
        Permission::create(['name' => 'manage game registrations', 'guard_name' => 'web']);
        Permission::create(['name' => 'view game registrations', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage training registrations', 'guard_name' => 'web']);
        Permission::create(['name' => 'view training registrations', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage tournament awards', 'guard_name' => 'web']);
        Permission::create(['name' => 'view tournament awards', 'guard_name' => 'web']);
    }

    protected function setupTestData(): void
    {
        // Create tenant and club
        $this->tenant = Tenant::factory()->create();
        $this->club = Club::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create users with different roles
        $this->superAdmin = User::factory()->create(['tenant_id' => null]);
        $this->superAdmin->assignRole('super_admin');

        $this->clubAdmin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->clubAdmin->assignRole('club_admin');
        $this->clubAdmin->clubs()->attach($this->club->id);

        $this->trainer = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->trainer->assignRole('trainer');
        // Associate trainer with team
        $this->team->coaches()->attach($this->trainer->id);

        $this->player = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->player->assignRole('player');

        $this->otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->otherUser->assignRole('player');
    }

    // ========================================
    // GAME REGISTRATION POLICY TESTS
    // ========================================

    /** @test */
    public function super_admin_can_view_any_game_registration(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($this->superAdmin->can('viewAny', GameRegistration::class));
    }

    /** @test */
    public function super_admin_can_view_game_registration(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->superAdmin->can('view', $registration));
    }

    /** @test */
    public function super_admin_can_create_game_registration(): void
    {
        $this->assertTrue($this->superAdmin->can('create', GameRegistration::class));
    }

    /** @test */
    public function super_admin_can_update_game_registration(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->superAdmin->can('update', $registration));
    }

    /** @test */
    public function super_admin_can_delete_game_registration(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->superAdmin->can('delete', $registration));
    }

    /** @test */
    public function club_admin_can_manage_game_registrations_for_their_club(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->clubAdmin->can('view', $registration));
        $this->assertTrue($this->clubAdmin->can('update', $registration));
        $this->assertTrue($this->clubAdmin->can('delete', $registration));
    }

    /** @test */
    public function trainer_can_manage_game_registrations_for_their_team(): void
    {
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->trainer->can('view', $registration));
        $this->assertTrue($this->trainer->can('update', $registration));
    }

    /** @test */
    public function trainer_cannot_manage_game_registrations_for_other_teams(): void
    {
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $game = Game::factory()->create([
            'home_team_id' => $otherTeam->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $otherTeam->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertFalse($this->trainer->can('update', $registration));
        $this->assertFalse($this->trainer->can('delete', $registration));
    }

    /** @test */
    public function player_can_view_and_update_own_game_registration(): void
    {
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->player->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->player->can('view', $registration));
        $this->assertTrue($this->player->can('update', $registration));
    }

    /** @test */
    public function player_cannot_manage_other_players_game_registration(): void
    {
        $otherPlayerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->otherUser->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = GameRegistration::factory()->create([
            'game_id' => $game->id,
            'player_id' => $otherPlayerModel->id,
        ]);

        $this->assertFalse($this->player->can('update', $registration));
        $this->assertFalse($this->player->can('delete', $registration));
    }

    // ========================================
    // TRAINING REGISTRATION POLICY TESTS
    // ========================================

    /** @test */
    public function super_admin_can_view_any_training_registration(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', TrainingRegistration::class));
    }

    /** @test */
    public function super_admin_can_manage_training_registration(): void
    {
        $session = TrainingSession::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->superAdmin->can('view', $registration));
        $this->assertTrue($this->superAdmin->can('update', $registration));
        $this->assertTrue($this->superAdmin->can('delete', $registration));
    }

    /** @test */
    public function club_admin_can_manage_training_registrations_for_their_club(): void
    {
        $session = TrainingSession::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->clubAdmin->can('view', $registration));
        $this->assertTrue($this->clubAdmin->can('update', $registration));
    }

    /** @test */
    public function trainer_can_manage_training_registrations_for_their_team(): void
    {
        $session = TrainingSession::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->trainer->can('view', $registration));
        $this->assertTrue($this->trainer->can('update', $registration));
        $this->assertTrue($this->trainer->can('confirm', $registration));
    }

    /** @test */
    public function trainer_cannot_manage_training_registrations_for_other_teams(): void
    {
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $session = TrainingSession::factory()->create([
            'team_id' => $otherTeam->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $playerModel = Player::factory()->create([
            'team_id' => $otherTeam->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertFalse($this->trainer->can('update', $registration));
        $this->assertFalse($this->trainer->can('confirm', $registration));
    }

    /** @test */
    public function player_can_view_and_update_own_training_registration(): void
    {
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->player->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $session = TrainingSession::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertTrue($this->player->can('view', $registration));
        $this->assertTrue($this->player->can('update', $registration));
    }

    /** @test */
    public function player_cannot_confirm_training_registrations(): void
    {
        $playerModel = Player::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $this->player->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $session = TrainingSession::factory()->create([
            'team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $registration = TrainingRegistration::factory()->create([
            'training_session_id' => $session->id,
            'player_id' => $playerModel->id,
        ]);

        $this->assertFalse($this->player->can('confirm', $registration));
    }

    // ========================================
    // TOURNAMENT AWARD POLICY TESTS
    // ========================================

    /** @test */
    public function super_admin_can_view_any_tournament_award(): void
    {
        $this->assertTrue($this->superAdmin->can('viewAny', TournamentAward::class));
    }

    /** @test */
    public function super_admin_can_manage_tournament_award(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertTrue($this->superAdmin->can('view', $award));
        $this->assertTrue($this->superAdmin->can('create', TournamentAward::class));
        $this->assertTrue($this->superAdmin->can('update', $award));
        $this->assertTrue($this->superAdmin->can('delete', $award));
    }

    /** @test */
    public function club_admin_can_manage_tournament_awards_for_their_club(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertTrue($this->clubAdmin->can('view', $award));
        $this->assertTrue($this->clubAdmin->can('update', $award));
        $this->assertTrue($this->clubAdmin->can('delete', $award));
    }

    /** @test */
    public function club_admin_cannot_manage_tournament_awards_for_other_clubs(): void
    {
        $otherClub = Club::factory()->create(['tenant_id' => $this->tenant->id]);
        $tournament = Tournament::factory()->create([
            'club_id' => $otherClub->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertFalse($this->clubAdmin->can('update', $award));
        $this->assertFalse($this->clubAdmin->can('delete', $award));
    }

    /** @test */
    public function trainer_can_view_tournament_awards_for_tournaments_they_participate_in(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        // Trainer should be able to view awards for tournaments their team participates in
        $this->assertTrue($this->trainer->can('view', $award));
    }

    /** @test */
    public function trainer_cannot_manage_tournament_awards(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        // Trainers should not be able to manage awards (only club admins+)
        $this->assertFalse($this->trainer->can('update', $award));
        $this->assertFalse($this->trainer->can('delete', $award));
    }

    /** @test */
    public function super_admin_can_present_tournament_award(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertTrue($this->superAdmin->can('present', $award));
    }

    /** @test */
    public function club_admin_can_present_tournament_award_for_their_club(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertTrue($this->clubAdmin->can('present', $award));
    }

    /** @test */
    public function trainer_cannot_present_tournament_award(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertFalse($this->trainer->can('present', $award));
    }

    /** @test */
    public function club_admin_can_feature_tournament_award(): void
    {
        $tournament = Tournament::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $award = TournamentAward::factory()->create([
            'tournament_id' => $tournament->id,
        ]);

        $this->assertTrue($this->clubAdmin->can('feature', $award));
    }
}
