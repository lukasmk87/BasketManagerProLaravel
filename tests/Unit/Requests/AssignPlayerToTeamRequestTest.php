<?php

namespace Tests\Unit\Requests;

use Tests\BasketballTestCase;
use App\Http\Requests\AssignPlayerToTeamRequest;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\Player;
use App\Models\PlayerRegistrationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class AssignPlayerToTeamRequestTest extends BasketballTestCase
{
    use RefreshDatabase;

    private User $clubAdmin;
    private User $trainer;
    private Club $club;
    private BasketballTeam $team;
    private Player $pendingPlayer;
    private PlayerRegistrationInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->club = Club::factory()->create();

        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'max_players' => 20,
        ]);

        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);

        $this->invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
        ]);

        $playerUser = User::factory()->create();
        $this->pendingPlayer = Player::factory()->create([
            'user_id' => $playerUser->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $this->invitation->id,
        ]);
    }

    /** @test */
    public function it_accepts_valid_data()
    {
        $request = new AssignPlayerToTeamRequest();

        $data = [
            'player_id' => $this->pendingPlayer->id,
            'team_id' => $this->team->id,
            'jersey_number' => 23,
            'position' => 'SF',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function player_id_and_team_id_are_required()
    {
        $request = new AssignPlayerToTeamRequest();

        $data = [
            'jersey_number' => 23,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('player_id'));
        $this->assertTrue($validator->errors()->has('team_id'));
    }

    /** @test */
    public function player_id_and_team_id_must_exist()
    {
        $request = new AssignPlayerToTeamRequest();

        $data = [
            'player_id' => 999999,  // Non-existent
            'team_id' => 888888,  // Non-existent
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('player_id'));
        $this->assertTrue($validator->errors()->has('team_id'));
    }

    /** @test */
    public function jersey_number_must_be_between_0_and_99()
    {
        $request = new AssignPlayerToTeamRequest();

        // Too large
        $data = [
            'player_id' => $this->pendingPlayer->id,
            'team_id' => $this->team->id,
            'jersey_number' => 100,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('jersey_number'));
    }

    /** @test */
    public function position_must_be_valid_basketball_position()
    {
        $request = new AssignPlayerToTeamRequest();

        $data = [
            'player_id' => $this->pendingPlayer->id,
            'team_id' => $this->team->id,
            'position' => 'INVALID',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('position'));
    }

    /** @test */
    public function club_admin_can_authorize()
    {
        $request = new AssignPlayerToTeamRequest();
        $request->setUserResolver(fn() => $this->clubAdmin);
        $request->merge([
            'player_id' => $this->pendingPlayer->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function trainer_cannot_authorize()
    {
        $request = new AssignPlayerToTeamRequest();
        $request->setUserResolver(fn() => $this->trainer);
        $request->merge([
            'player_id' => $this->pendingPlayer->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertFalse($request->authorize());
    }
}
