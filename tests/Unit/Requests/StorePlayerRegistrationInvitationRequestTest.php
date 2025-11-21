<?php

namespace Tests\Unit\Requests;

use Tests\BasketballTestCase;
use App\Http\Requests\StorePlayerRegistrationInvitationRequest;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StorePlayerRegistrationInvitationRequestTest extends BasketballTestCase
{
    use RefreshDatabase;

    private User $clubAdmin;
    private User $trainer;
    private User $player;
    private Club $club;
    private Club $otherClub;
    private BasketballTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->player = User::factory()->create();
        $this->player->assignRole('player');

        $this->club = Club::factory()->create();
        $this->otherClub = Club::factory()->create();

        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
        ]);

        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);
        $this->trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);
    }

    /** @test */
    public function it_accepts_valid_data()
    {
        $request = new StorePlayerRegistrationInvitationRequest();
        $request->setUserResolver(fn() => $this->clubAdmin);

        $data = [
            'club_id' => $this->club->id,
            'target_team_id' => $this->team->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'max_registrations' => 50,
            'qr_size' => 300,
            'settings' => ['test' => 'value'],
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function club_id_is_required()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'target_team_id' => $this->team->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('club_id'));
    }

    /** @test */
    public function club_id_must_exist()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'club_id' => 999999,  // Non-existent
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('club_id'));
    }

    /** @test */
    public function target_team_id_must_exist_if_provided()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'club_id' => $this->club->id,
            'target_team_id' => 999999,  // Non-existent
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('target_team_id'));
    }

    /** @test */
    public function expires_at_is_required()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'club_id' => $this->club->id,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('expires_at'));
    }

    /** @test */
    public function expires_at_must_be_in_future()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'club_id' => $this->club->id,
            'expires_at' => now()->subDays(1)->toDateTimeString(),  // Past
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('expires_at'));
    }

    /** @test */
    public function expires_at_cannot_be_more_than_one_year()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        $data = [
            'club_id' => $this->club->id,
            'expires_at' => now()->addYears(2)->toDateTimeString(),  // Too far
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('expires_at'));
    }

    /** @test */
    public function max_registrations_must_be_between_1_and_500()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        // Test too small
        $data1 = [
            'club_id' => $this->club->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'max_registrations' => 0,
        ];

        $validator1 = Validator::make($data1, $request->rules());
        $this->assertFalse($validator1->passes());
        $this->assertTrue($validator1->errors()->has('max_registrations'));

        // Test too large
        $data2 = [
            'club_id' => $this->club->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'max_registrations' => 501,
        ];

        $validator2 = Validator::make($data2, $request->rules());
        $this->assertFalse($validator2->passes());
        $this->assertTrue($validator2->errors()->has('max_registrations'));
    }

    /** @test */
    public function qr_size_must_be_between_100_and_1000()
    {
        $request = new StorePlayerRegistrationInvitationRequest();

        // Test too small
        $data1 = [
            'club_id' => $this->club->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'qr_size' => 50,
        ];

        $validator1 = Validator::make($data1, $request->rules());
        $this->assertFalse($validator1->passes());
        $this->assertTrue($validator1->errors()->has('qr_size'));

        // Test too large
        $data2 = [
            'club_id' => $this->club->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'qr_size' => 1500,
        ];

        $validator2 = Validator::make($data2, $request->rules());
        $this->assertFalse($validator2->errors()->has('qr_size'));
    }

    /** @test */
    public function trainer_can_authorize_for_their_club()
    {
        $request = new StorePlayerRegistrationInvitationRequest();
        $request->setUserResolver(fn() => $this->trainer);
        $request->merge(['club_id' => $this->club->id]);

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function trainer_cannot_authorize_for_other_club()
    {
        $request = new StorePlayerRegistrationInvitationRequest();
        $request->setUserResolver(fn() => $this->trainer);
        $request->merge(['club_id' => $this->otherClub->id]);

        $this->assertFalse($request->authorize());
    }

    /** @test */
    public function club_admin_can_authorize_for_their_club()
    {
        $request = new StorePlayerRegistrationInvitationRequest();
        $request->setUserResolver(fn() => $this->clubAdmin);
        $request->merge(['club_id' => $this->club->id]);

        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function player_cannot_authorize()
    {
        $this->player->givePermissionTo('create player invitations');

        $request = new StorePlayerRegistrationInvitationRequest();
        $request->setUserResolver(fn() => $this->player);
        $request->merge(['club_id' => $this->club->id]);

        $this->assertFalse($request->authorize());
    }
}
