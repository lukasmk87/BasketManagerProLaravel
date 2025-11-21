<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test updating personal data
     */
    public function test_user_can_update_personal_data(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.personal-data.update'), [
            'phone' => '+49 123 456789',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'address_street' => 'Teststraße 123',
            'address_city' => 'Berlin',
            'address_state' => 'Berlin',
            'address_zip' => '10115',
            'address_country' => 'DE',
            'nationality' => 'DE',
            'bio' => 'This is my bio',
            'occupation' => 'Software Developer',
            'employer' => 'Tech Company GmbH',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->user->refresh();

        $this->assertEquals('+49 123 456789', $this->user->phone);
        $this->assertEquals('1990-01-15', $this->user->date_of_birth);
        $this->assertEquals('male', $this->user->gender);
        $this->assertEquals('Teststraße 123', $this->user->address_street);
        $this->assertEquals('Berlin', $this->user->address_city);
        $this->assertEquals('DE', $this->user->nationality);
        $this->assertEquals('This is my bio', $this->user->bio);
        $this->assertEquals('Software Developer', $this->user->occupation);
    }

    /**
     * Test personal data validation
     */
    public function test_personal_data_validation(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.personal-data.update'), [
            'date_of_birth' => '2050-01-01', // Future date
            'gender' => 'invalid_gender',
            'nationality' => 'INVALID', // Too long
        ]);

        $response->assertSessionHasErrors(['date_of_birth', 'gender', 'nationality']);
    }

    /**
     * Test updating basketball data
     */
    public function test_user_can_update_basketball_data(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.basketball-data.update'), [
            'basketball_experience' => [
                'years' => 10,
                'level_description' => 'Played in regional league for 5 years',
            ],
            'preferred_positions' => ['PG', 'SG'],
            'skill_level' => 'advanced',
            'player_profile_active' => true,
            'coaching_certifications' => [
                ['name' => 'C-Lizenz', 'year' => 2020, 'issuer' => 'DBB'],
                ['name' => 'B-Lizenz', 'year' => 2022, 'issuer' => 'DBB'],
            ],
            'referee_certifications' => [
                ['name' => 'SR-Lizenz Regionalliga', 'year' => 2021, 'issuer' => 'DBB'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->user->refresh();

        $experience = json_decode($this->user->basketball_experience, true);
        $this->assertEquals(10, $experience['years']);

        $positions = json_decode($this->user->preferred_positions, true);
        $this->assertContains('PG', $positions);
        $this->assertContains('SG', $positions);

        $this->assertEquals('advanced', $this->user->skill_level);
        $this->assertTrue($this->user->player_profile_active);

        $coachingCerts = json_decode($this->user->coaching_certifications, true);
        $this->assertCount(2, $coachingCerts);
        $this->assertEquals('C-Lizenz', $coachingCerts[0]['name']);
    }

    /**
     * Test basketball data validation
     */
    public function test_basketball_data_validation(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.basketball-data.update'), [
            'preferred_positions' => ['INVALID_POSITION'],
            'skill_level' => 'invalid_level',
        ]);

        $response->assertSessionHasErrors(['preferred_positions.0', 'skill_level']);
    }

    /**
     * Test updating emergency and medical data
     */
    public function test_user_can_update_emergency_medical_data(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.emergency-medical.update'), [
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '+49 987 654321',
            'emergency_contact_relationship' => 'Father',
            'blood_type' => 'A+',
            'medical_conditions' => [
                ['name' => 'Asthma', 'notes' => 'Mild, controlled with inhaler'],
            ],
            'allergies' => [
                ['name' => 'Peanuts', 'severity' => 'severe', 'notes' => 'Carries EpiPen'],
            ],
            'medications' => [
                ['name' => 'Inhaler', 'dosage' => '100mcg', 'frequency' => 'As needed'],
            ],
            'medical_consent' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->user->refresh();

        $this->assertEquals('John Doe', $this->user->emergency_contact_name);
        $this->assertEquals('+49 987 654321', $this->user->emergency_contact_phone);
        $this->assertEquals('Father', $this->user->emergency_contact_relationship);
        $this->assertEquals('A+', $this->user->blood_type);

        $conditions = json_decode($this->user->medical_conditions, true);
        $this->assertCount(1, $conditions);
        $this->assertEquals('Asthma', $conditions[0]['name']);

        $allergies = json_decode($this->user->allergies, true);
        $this->assertCount(1, $allergies);
        $this->assertEquals('Peanuts', $allergies[0]['name']);
        $this->assertEquals('severe', $allergies[0]['severity']);

        $medications = json_decode($this->user->medications, true);
        $this->assertCount(1, $medications);
        $this->assertEquals('Inhaler', $medications[0]['name']);

        $this->assertTrue($this->user->medical_consent);
        $this->assertNotNull($this->user->medical_consent_date);
    }

    /**
     * Test emergency medical data validation
     */
    public function test_emergency_medical_data_validation(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.emergency-medical.update'), [
            'blood_type' => 'INVALID',
            'allergies' => [
                ['name' => 'Test', 'severity' => 'invalid_severity'],
            ],
        ]);

        $response->assertSessionHasErrors(['blood_type', 'allergies.0.severity']);
    }

    /**
     * Test updating preferences
     */
    public function test_user_can_update_preferences(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.preferences.update'), [
            'language' => 'en',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'date_format' => 'd/m/Y',
            'time_format' => 'h:i A',
            'notification_settings' => [
                'email_notifications' => true,
                'push_notifications' => false,
                'game_reminders' => true,
                'training_reminders' => true,
                'team_announcements' => false,
            ],
            'privacy_settings' => [
                'profile_visible' => true,
                'show_email' => false,
                'show_phone' => false,
                'show_statistics' => true,
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->user->refresh();

        $this->assertEquals('en', $this->user->language);
        $this->assertEquals('en', $this->user->locale);
        $this->assertEquals('Europe/London', $this->user->timezone);
        $this->assertEquals('d/m/Y', $this->user->date_format);
        $this->assertEquals('h:i A', $this->user->time_format);

        $notificationSettings = json_decode($this->user->notification_settings, true);
        $this->assertTrue($notificationSettings['email_notifications']);
        $this->assertFalse($notificationSettings['push_notifications']);

        $privacySettings = json_decode($this->user->privacy_settings, true);
        $this->assertTrue($privacySettings['profile_visible']);
        $this->assertFalse($privacySettings['show_email']);
    }

    /**
     * Test preferences validation
     */
    public function test_preferences_validation(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.preferences.update'), [
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertSessionHasErrors(['timezone']);
    }

    /**
     * Test unauthenticated user cannot update profile
     */
    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->post(route('user.personal-data.update'), [
            'phone' => '+49 123 456789',
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test empty arrays are filtered out correctly
     */
    public function test_empty_arrays_are_filtered(): void
    {
        $response = $this->actingAs($this->user)->post(route('user.basketball-data.update'), [
            'coaching_certifications' => [
                ['name' => 'Valid Cert', 'year' => 2020, 'issuer' => 'DBB'],
                ['name' => '', 'year' => '', 'issuer' => ''], // Empty entry
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->user->refresh();

        $certs = json_decode($this->user->coaching_certifications, true);
        $this->assertCount(1, $certs); // Only non-empty entries
        $this->assertEquals('Valid Cert', $certs[0]['name']);
    }

    /**
     * Test nullable fields can be cleared
     */
    public function test_nullable_fields_can_be_cleared(): void
    {
        $this->user->update([
            'phone' => '+49 123 456789',
            'bio' => 'Old bio',
        ]);

        $response = $this->actingAs($this->user)->post(route('user.personal-data.update'), [
            'phone' => null,
            'bio' => null,
        ]);

        $response->assertSessionHasNoErrors();

        $this->user->refresh();

        $this->assertNull($this->user->phone);
        $this->assertNull($this->user->bio);
    }
}
