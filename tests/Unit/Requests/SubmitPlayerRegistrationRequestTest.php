<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\SubmitPlayerRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class SubmitPlayerRegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_accepts_valid_data()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2000-01-15',
            'email' => 'john.doe@example.com',
            'phone' => '+49123456789',
            'position' => 'PG',
            'height' => 185,
            'experience' => 'Played for 3 years',
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function first_name_and_last_name_are_required()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
    }

    /** @test */
    public function names_must_contain_only_letters_spaces_hyphens()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John123',  // Invalid
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('first_name'));
    }

    /** @test */
    public function birth_date_is_required_and_must_be_in_past()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => now()->addDays(1)->toDateString(),  // Future
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('birth_date'));
    }

    /** @test */
    public function email_is_required_and_must_be_unique()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',  // Duplicate
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('email'));
    }

    /** @test */
    public function phone_is_required_with_valid_format()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => 'invalid',  // Invalid format
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('phone'));
    }

    /** @test */
    public function position_must_be_valid_basketball_position()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'position' => 'INVALID',  // Invalid position
            'gdpr_consent' => 'yes',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('position'));
    }

    /** @test */
    public function height_must_be_between_100_and_250()
    {
        $request = new SubmitPlayerRegistrationRequest();

        // Too small
        $data1 = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'height' => 50,
            'gdpr_consent' => 'yes',
        ];

        $validator1 = Validator::make($data1, $request->rules());
        $this->assertFalse($validator1->passes());
        $this->assertTrue($validator1->errors()->has('height'));

        // Too large
        $data2 = $data1;
        $data2['height'] = 300;
        $data2['email'] = 'test2@example.com';

        $validator2 = Validator::make($data2, $request->rules());
        $this->assertFalse($validator2->passes());
        $this->assertTrue($validator2->errors()->has('height'));
    }

    /** @test */
    public function gdpr_consent_is_required()
    {
        $request = new SubmitPlayerRegistrationRequest();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            // Missing gdpr_consent
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('gdpr_consent'));
    }

    /** @test */
    public function no_authentication_required()
    {
        $request = new SubmitPlayerRegistrationRequest();

        // Public route - always authorized
        $this->assertTrue($request->authorize());
    }
}
