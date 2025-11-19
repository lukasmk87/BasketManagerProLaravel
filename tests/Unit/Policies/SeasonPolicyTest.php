<?php

namespace Tests\Unit\Policies;

use Tests\BasketballTestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Season;
use App\Models\BasketballTeam;
use App\Policies\SeasonPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SeasonPolicyTest extends BasketballTestCase
{
    use RefreshDatabase;

    private SeasonPolicy $policy;
    private User $superAdmin;
    private User $admin;
    private User $clubAdmin;
    private User $trainer;
    private User $assistantCoach;
    private User $player;
    private Club $club;
    private Club $otherClub;
    private Season $draftSeason;
    private Season $activeSeason;
    private Season $completedSeason;
    private BasketballTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SeasonPolicy();

        // Create users with different roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->assistantCoach = User::factory()->create();
        $this->assistantCoach->assignRole('assistant_coach');

        $this->player = User::factory()->create();
        $this->player->assignRole('player');

        // Create clubs
        $this->club = Club::factory()->create();
        $this->otherClub = Club::factory()->create();

        // Attach clubAdmin to club
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);

        // Create team and attach trainer and assistant coach
        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
        ]);

        $this->trainer->coachedTeams()->attach($this->team->id, ['role' => 'head_coach']);
        $this->assistantCoach->coachedTeams()->attach($this->team->id, ['role' => 'assistant_coach']);

        // Create seasons with different statuses
        $this->draftSeason = Season::create([
            'club_id' => $this->club->id,
            'name' => '2024/25 Draft',
            'start_date' => Carbon::now()->addMonths(1),
            'end_date' => Carbon::now()->addMonths(12),
            'status' => 'draft',
            'is_current' => false,
        ]);

        $this->activeSeason = Season::create([
            'club_id' => $this->club->id,
            'name' => '2023/24 Active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'status' => 'active',
            'is_current' => true,
        ]);

        $this->completedSeason = Season::create([
            'club_id' => $this->club->id,
            'name' => '2022/23 Completed',
            'start_date' => Carbon::now()->subMonths(15),
            'end_date' => Carbon::now()->subMonths(3),
            'status' => 'completed',
            'is_current' => false,
        ]);
    }

    /** @test */
    public function viewAny_allows_super_admin_and_admin()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    /** @test */
    public function viewAny_allows_club_admin_trainer_and_assistant_coach()
    {
        $this->assertTrue($this->policy->viewAny($this->clubAdmin));
        $this->assertTrue($this->policy->viewAny($this->trainer));
        $this->assertTrue($this->policy->viewAny($this->assistantCoach));
    }

    /** @test */
    public function viewAny_denies_player()
    {
        $this->assertFalse($this->policy->viewAny($this->player));
    }

    /** @test */
    public function view_allows_super_admin_and_admin_for_any_season()
    {
        $this->assertTrue($this->policy->view($this->superAdmin, $this->activeSeason));
        $this->assertTrue($this->policy->view($this->admin, $this->activeSeason));
    }

    /** @test */
    public function view_allows_club_admin_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->view($this->clubAdmin, $this->activeSeason));
    }

    /** @test */
    public function view_denies_club_admin_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Season',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(12),
            'status' => 'active',
        ]);

        $this->assertFalse($this->policy->view($this->clubAdmin, $otherSeason));
    }

    /** @test */
    public function view_allows_trainer_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->view($this->trainer, $this->activeSeason));
    }

    /** @test */
    public function view_allows_assistant_coach_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->view($this->assistantCoach, $this->activeSeason));
    }

    /** @test */
    public function view_denies_trainer_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Season',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(12),
            'status' => 'active',
        ]);

        $this->assertFalse($this->policy->view($this->trainer, $otherSeason));
    }

    /** @test */
    public function view_denies_player()
    {
        $this->assertFalse($this->policy->view($this->player, $this->activeSeason));
    }

    /** @test */
    public function create_allows_super_admin_and_admin()
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
        $this->assertTrue($this->policy->create($this->admin));
    }

    /** @test */
    public function create_allows_club_admin()
    {
        $this->assertTrue($this->policy->create($this->clubAdmin));
    }

    /** @test */
    public function create_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->create($this->trainer));
        $this->assertFalse($this->policy->create($this->assistantCoach));
    }

    /** @test */
    public function create_denies_player()
    {
        $this->assertFalse($this->policy->create($this->player));
    }

    /** @test */
    public function update_allows_super_admin_and_admin_for_any_season()
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->activeSeason));
        $this->assertTrue($this->policy->update($this->admin, $this->activeSeason));
    }

    /** @test */
    public function update_allows_club_admin_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->update($this->clubAdmin, $this->activeSeason));
    }

    /** @test */
    public function update_denies_club_admin_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Season',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(12),
            'status' => 'active',
        ]);

        $this->assertFalse($this->policy->update($this->clubAdmin, $otherSeason));
    }

    /** @test */
    public function update_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->update($this->trainer, $this->activeSeason));
        $this->assertFalse($this->policy->update($this->assistantCoach, $this->activeSeason));
    }

    /** @test */
    public function delete_allows_super_admin_and_admin_for_non_active_seasons()
    {
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->draftSeason));
        $this->assertTrue($this->policy->delete($this->admin, $this->completedSeason));
    }

    /** @test */
    public function delete_denies_active_seasons()
    {
        $this->assertFalse($this->policy->delete($this->superAdmin, $this->activeSeason));
        $this->assertFalse($this->policy->delete($this->admin, $this->activeSeason));
        $this->assertFalse($this->policy->delete($this->clubAdmin, $this->activeSeason));
    }

    /** @test */
    public function delete_allows_club_admin_for_their_club_non_active_seasons()
    {
        $this->assertTrue($this->policy->delete($this->clubAdmin, $this->draftSeason));
        $this->assertTrue($this->policy->delete($this->clubAdmin, $this->completedSeason));
    }

    /** @test */
    public function delete_denies_club_admin_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Season',
            'start_date' => Carbon::now()->subMonths(12),
            'end_date' => Carbon::now()->subMonths(1),
            'status' => 'completed',
        ]);

        $this->assertFalse($this->policy->delete($this->clubAdmin, $otherSeason));
    }

    /** @test */
    public function delete_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->delete($this->trainer, $this->completedSeason));
        $this->assertFalse($this->policy->delete($this->assistantCoach, $this->completedSeason));
    }

    /** @test */
    public function complete_allows_super_admin_and_admin_for_active_seasons()
    {
        $this->assertTrue($this->policy->complete($this->superAdmin, $this->activeSeason));
        $this->assertTrue($this->policy->complete($this->admin, $this->activeSeason));
    }

    /** @test */
    public function complete_denies_non_active_seasons()
    {
        $this->assertFalse($this->policy->complete($this->superAdmin, $this->draftSeason));
        $this->assertFalse($this->policy->complete($this->admin, $this->completedSeason));
    }

    /** @test */
    public function complete_allows_club_admin_for_their_club_active_seasons()
    {
        $this->assertTrue($this->policy->complete($this->clubAdmin, $this->activeSeason));
    }

    /** @test */
    public function complete_denies_club_admin_for_other_club_seasons()
    {
        $otherActiveSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'status' => 'active',
        ]);

        $this->assertFalse($this->policy->complete($this->clubAdmin, $otherActiveSeason));
    }

    /** @test */
    public function complete_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->complete($this->trainer, $this->activeSeason));
        $this->assertFalse($this->policy->complete($this->assistantCoach, $this->activeSeason));
    }

    /** @test */
    public function activate_allows_super_admin_and_admin_for_draft_seasons()
    {
        $this->assertTrue($this->policy->activate($this->superAdmin, $this->draftSeason));
        $this->assertTrue($this->policy->activate($this->admin, $this->draftSeason));
    }

    /** @test */
    public function activate_denies_non_draft_seasons()
    {
        $this->assertFalse($this->policy->activate($this->superAdmin, $this->activeSeason));
        $this->assertFalse($this->policy->activate($this->admin, $this->completedSeason));
    }

    /** @test */
    public function activate_allows_club_admin_for_their_club_draft_seasons()
    {
        $this->assertTrue($this->policy->activate($this->clubAdmin, $this->draftSeason));
    }

    /** @test */
    public function activate_denies_club_admin_for_other_club_seasons()
    {
        $otherDraftSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Draft',
            'start_date' => Carbon::now()->addMonths(1),
            'end_date' => Carbon::now()->addMonths(13),
            'status' => 'draft',
        ]);

        $this->assertFalse($this->policy->activate($this->clubAdmin, $otherDraftSeason));
    }

    /** @test */
    public function activate_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->activate($this->trainer, $this->draftSeason));
        $this->assertFalse($this->policy->activate($this->assistantCoach, $this->draftSeason));
    }

    /** @test */
    public function startNew_allows_super_admin_and_admin()
    {
        $this->assertTrue($this->policy->startNew($this->superAdmin));
        $this->assertTrue($this->policy->startNew($this->admin));
    }

    /** @test */
    public function startNew_allows_club_admin()
    {
        $this->assertTrue($this->policy->startNew($this->clubAdmin));
    }

    /** @test */
    public function startNew_denies_trainer_and_assistant_coach()
    {
        $this->assertFalse($this->policy->startNew($this->trainer));
        $this->assertFalse($this->policy->startNew($this->assistantCoach));
    }

    /** @test */
    public function startNew_denies_player()
    {
        $this->assertFalse($this->policy->startNew($this->player));
    }

    /** @test */
    public function exportStatistics_allows_super_admin_and_admin_for_any_season()
    {
        $this->assertTrue($this->policy->exportStatistics($this->superAdmin, $this->completedSeason));
        $this->assertTrue($this->policy->exportStatistics($this->admin, $this->completedSeason));
    }

    /** @test */
    public function exportStatistics_allows_club_admin_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->exportStatistics($this->clubAdmin, $this->completedSeason));
    }

    /** @test */
    public function exportStatistics_denies_club_admin_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Completed',
            'start_date' => Carbon::now()->subMonths(15),
            'end_date' => Carbon::now()->subMonths(3),
            'status' => 'completed',
        ]);

        $this->assertFalse($this->policy->exportStatistics($this->clubAdmin, $otherSeason));
    }

    /** @test */
    public function exportStatistics_allows_trainer_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->exportStatistics($this->trainer, $this->completedSeason));
    }

    /** @test */
    public function exportStatistics_allows_assistant_coach_for_their_club_seasons()
    {
        $this->assertTrue($this->policy->exportStatistics($this->assistantCoach, $this->completedSeason));
    }

    /** @test */
    public function exportStatistics_denies_trainer_for_other_club_seasons()
    {
        $otherSeason = Season::create([
            'club_id' => $this->otherClub->id,
            'name' => 'Other Completed',
            'start_date' => Carbon::now()->subMonths(15),
            'end_date' => Carbon::now()->subMonths(3),
            'status' => 'completed',
        ]);

        $this->assertFalse($this->policy->exportStatistics($this->trainer, $otherSeason));
    }

    /** @test */
    public function exportStatistics_denies_player()
    {
        $this->assertFalse($this->policy->exportStatistics($this->player, $this->completedSeason));
    }

    /** @test */
    public function compareSeasons_allows_super_admin_and_admin()
    {
        $this->assertTrue($this->policy->compareSeasons($this->superAdmin));
        $this->assertTrue($this->policy->compareSeasons($this->admin));
    }

    /** @test */
    public function compareSeasons_allows_club_admin_trainer_and_assistant_coach()
    {
        $this->assertTrue($this->policy->compareSeasons($this->clubAdmin));
        $this->assertTrue($this->policy->compareSeasons($this->trainer));
        $this->assertTrue($this->policy->compareSeasons($this->assistantCoach));
    }

    /** @test */
    public function compareSeasons_denies_player()
    {
        $this->assertFalse($this->policy->compareSeasons($this->player));
    }
}
