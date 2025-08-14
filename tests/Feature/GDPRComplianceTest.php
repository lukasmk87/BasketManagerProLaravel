<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Player;
use App\Models\EmergencyContact;
use App\Models\GdprDataSubjectRequest;
use App\Models\GdprConsentRecord;
use App\Models\GdprDataProcessingRecord;
use App\Services\GDPRComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GDPRComplianceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $gdprService;
    protected $user;
    protected $player;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->gdprService = app(GDPRComplianceService::class);
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create();
        
        Storage::fake('gdpr-exports');
    }

    /** @test */
    public function gdpr_dashboard_loads_successfully()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('gdpr.dashboard'));
        
        $response->assertStatus(200)
                ->assertViewIs('gdpr.dashboard');
    }

    /** @test */
    public function data_subject_can_request_data_export()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.request-export'), [
            'data_types' => ['personal_data', 'emergency_contacts'],
            'format' => 'json',
            'reason' => 'Personal copy of my data'
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_export',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function data_subject_can_request_data_deletion()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.request-deletion'), [
            'reason' => 'I no longer want to use the service',
            'data_retention_exceptions' => ['legal_obligations']
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_deletion',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function data_subject_can_request_data_rectification()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.request-rectification'), [
            'data_corrections' => [
                'email' => 'newemail@example.com',
                'name' => 'Updated Name'
            ],
            'reason' => 'Personal information has changed'
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_rectification',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function data_export_creates_comprehensive_json_export()
    {
        // Create emergency contact for the user's player
        $emergencyContact = EmergencyContact::factory()->create([
            'player_id' => $this->player->id
        ]);
        
        $exportData = $this->gdprService->exportUserData($this->user, ['json']);
        
        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('personal_data', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertEquals($this->user->email, $exportData['personal_data']['email']);
        $this->assertArrayHasKey('export_generated_at', $exportData['metadata']);
    }

    /** @test */
    public function data_export_creates_csv_format()
    {
        $exportData = $this->gdprService->exportUserData($this->user, ['csv']);
        
        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('csv_files', $exportData);
        $this->assertArrayHasKey('users.csv', $exportData['csv_files']);
    }

    /** @test */
    public function data_export_handles_large_datasets()
    {
        // Create multiple emergency contacts to test large dataset handling
        EmergencyContact::factory()->count(100)->create([
            'player_id' => $this->player->id
        ]);
        
        $exportData = $this->gdprService->exportUserData($this->user, ['json']);
        
        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('personal_data', $exportData);
        // Test should complete without timeout or memory issues
    }

    /** @test */
    public function data_anonymization_preserves_relationships_but_removes_pii()
    {
        $emergencyContact = EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'contact_name' => 'John Doe',
            'phone_number' => '+49123456789',
            'email' => 'john@example.com'
        ]);
        
        $this->gdprService->anonymizeUserData($this->user);
        
        $emergencyContact->refresh();
        $this->assertEquals('Anonymized Contact', $emergencyContact->contact_name);
        $this->assertNull($emergencyContact->phone_number);
        $this->assertNull($emergencyContact->email);
        $this->assertFalse($emergencyContact->is_active);
    }

    /** @test */
    public function consent_management_records_consent_properly()
    {
        $consentData = [
            'processing_purpose' => 'emergency_contacts',
            'legal_basis' => 'consent',
            'data_categories' => ['contact_information', 'medical_data'],
            'consent_text' => 'I consent to processing my emergency contact data'
        ];
        
        $consent = $this->gdprService->recordConsent($this->user, $consentData);
        
        $this->assertInstanceOf(GdprConsentRecord::class, $consent);
        $this->assertTrue($consent->consent_given);
        $this->assertEquals('emergency_contacts', $consent->processing_purpose);
        $this->assertNotNull($consent->consent_given_at);
    }

    /** @test */
    public function consent_can_be_withdrawn()
    {
        $consentData = [
            'processing_purpose' => 'emergency_contacts',
            'legal_basis' => 'consent',
            'data_categories' => ['contact_information']
        ];
        
        $consent = $this->gdprService->recordConsent($this->user, $consentData);
        $this->assertTrue($consent->consent_given);
        
        $withdrawal = $this->gdprService->withdrawConsent($this->user, 'emergency_contacts', 'No longer needed');
        
        $consent->refresh();
        $this->assertFalse($consent->consent_given);
        $this->assertNotNull($consent->consent_withdrawn_at);
        $this->assertEquals('No longer needed', $consent->withdrawal_reason);
    }

    /** @test */
    public function processing_activities_are_recorded()
    {
        $processingData = [
            'activity_name' => 'Emergency Contact Management',
            'processing_purpose' => 'emergency_contacts',
            'legal_basis' => ['consent', 'vital_interests'],
            'data_categories' => ['contact_information', 'medical_data'],
            'data_subjects' => ['players', 'emergency_contacts'],
            'retention_period_months' => 24,
            'is_automated_processing' => false
        ];
        
        $record = $this->gdprService->recordProcessingActivity($processingData);
        
        $this->assertInstanceOf(GdprDataProcessingRecord::class, $record);
        $this->assertEquals('Emergency Contact Management', $record->activity_name);
        $this->assertEquals('emergency_contacts', $record->processing_purpose);
        $this->assertIsArray($record->legal_basis);
    }

    /** @test */
    public function data_breach_notification_works()
    {
        Queue::fake();
        
        $breachData = [
            'breach_type' => 'confidentiality_breach',
            'severity' => 'high',
            'affected_data_categories' => ['emergency_contacts', 'personal_data'],
            'affected_data_subjects_count' => 50,
            'breach_description' => 'Unauthorized access to emergency contact database',
            'containment_measures' => 'Access revoked, passwords reset',
            'likely_consequences' => 'Potential privacy violations',
            'measures_to_address' => 'Additional security measures implemented'
        ];
        
        $notification = $this->gdprService->reportDataBreach($breachData);
        
        $this->assertIsArray($notification);
        $this->assertEquals('high', $notification['severity']);
        
        // Should queue notification job if severity is high
        Queue::assertPushed(\App\Jobs\SendDataBreachNotification::class);
    }

    /** @test */
    public function gdpr_compliance_audit_generates_comprehensive_report()
    {
        // Create test data for audit
        GdprConsentRecord::factory()->count(5)->create();
        GdprDataProcessingRecord::factory()->count(3)->create();
        
        $auditReport = $this->gdprService->generateComplianceAudit();
        
        $this->assertIsArray($auditReport);
        $this->assertArrayHasKey('consent_records', $auditReport);
        $this->assertArrayHasKey('processing_activities', $auditReport);
        $this->assertArrayHasKey('data_subject_requests', $auditReport);
        $this->assertArrayHasKey('compliance_score', $auditReport);
        $this->assertArrayHasKey('recommendations', $auditReport);
        
        $this->assertGreaterThanOrEqual(0, $auditReport['compliance_score']);
        $this->assertLessThanOrEqual(100, $auditReport['compliance_score']);
    }

    /** @test */
    public function data_retention_policy_enforcement_works()
    {
        // Create old consent record
        $oldConsent = GdprConsentRecord::factory()->create([
            'consent_given_at' => now()->subYears(3),
            'consent_withdrawn_at' => now()->subYears(2)
        ]);
        
        // Create recent consent record
        $recentConsent = GdprConsentRecord::factory()->create([
            'consent_given_at' => now()->subMonths(6)
        ]);
        
        $cleanedUp = $this->gdprService->enforceDataRetentionPolicies();
        
        $this->assertGreaterThan(0, $cleanedUp['records_processed']);
        
        // Old consent should be anonymized/deleted based on retention rules
        $this->assertDatabaseMissing('gdpr_consent_records', [
            'id' => $oldConsent->id,
            'consent_text' => $oldConsent->consent_text
        ]);
        
        // Recent consent should remain
        $this->assertDatabaseHas('gdpr_consent_records', [
            'id' => $recentConsent->id
        ]);
    }

    /** @test */
    public function automated_data_deletion_respects_legal_holds()
    {
        // Create data subject request for deletion
        $deletionRequest = GdprDataSubjectRequest::factory()->create([
            'user_id' => $this->user->id,
            'request_type' => 'data_deletion',
            'status' => 'approved'
        ]);
        
        // Add legal hold
        $this->gdprService->addLegalHold($this->user, 'ongoing_litigation', 'Court case pending');
        
        $result = $this->gdprService->processAutomatedDeletion($deletionRequest);
        
        $this->assertFalse($result['deleted']);
        $this->assertStringContains('legal hold', $result['reason']);
        
        $deletionRequest->refresh();
        $this->assertEquals('on_hold', $deletionRequest->status);
    }

    /** @test */
    public function cross_border_data_transfer_logging_works()
    {
        $transferData = [
            'data_categories' => ['emergency_contacts'],
            'destination_country' => 'United States',
            'legal_basis' => 'adequacy_decision',
            'transfer_mechanism' => 'standard_contractual_clauses',
            'recipient_organization' => 'Emergency Services Provider',
            'data_subjects_count' => 25,
            'transfer_purpose' => 'Emergency response coordination'
        ];
        
        $transfer = $this->gdprService->logDataTransfer($transferData);
        
        $this->assertIsArray($transfer);
        $this->assertEquals('United States', $transfer['destination_country']);
        $this->assertEquals('emergency_contacts', $transfer['data_categories'][0]);
        
        // Should be logged in processing records
        $this->assertDatabaseHas('gdpr_data_processing_records', [
            'activity_name' => 'International Data Transfer',
            'processing_purpose' => 'data_transfer'
        ]);
    }

    /** @test */
    public function privacy_impact_assessment_generates_report()
    {
        $assessmentData = [
            'processing_activity' => 'Emergency Contact System',
            'data_categories' => ['contact_information', 'medical_data', 'location_data'],
            'processing_methods' => ['automated_processing', 'profiling'],
            'risk_factors' => ['sensitive_data', 'vulnerable_subjects', 'cross_border_transfer']
        ];
        
        $pia = $this->gdprService->generatePrivacyImpactAssessment($assessmentData);
        
        $this->assertIsArray($pia);
        $this->assertArrayHasKey('risk_score', $pia);
        $this->assertArrayHasKey('risk_assessment', $pia);
        $this->assertArrayHasKey('mitigation_measures', $pia);
        $this->assertArrayHasKey('dpo_consultation_required', $pia);
        
        $this->assertGreaterThanOrEqual(0, $pia['risk_score']);
        $this->assertLessThanOrEqual(10, $pia['risk_score']);
    }

    /** @test */
    public function gdpr_article_15_right_of_access_works()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.exercise-right'), [
            'right_type' => 'access',
            'specific_data' => ['personal_information', 'processing_activities'],
            'reason' => 'Want to see what data you have about me'
        ]);
        
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
        
        $request = GdprDataSubjectRequest::where('user_id', $this->user->id)
                                        ->where('request_type', 'data_access')
                                        ->first();
        
        $this->assertNotNull($request);
        $this->assertEquals('pending', $request->status);
    }

    /** @test */
    public function gdpr_article_16_right_of_rectification_works()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.exercise-right'), [
            'right_type' => 'rectification',
            'incorrect_data' => [
                'field' => 'email',
                'current_value' => $this->user->email,
                'correct_value' => 'corrected@example.com'
            ],
            'reason' => 'Email address has changed'
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_rectification',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function gdpr_article_17_right_of_erasure_works()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.exercise-right'), [
            'right_type' => 'erasure',
            'erasure_grounds' => 'consent_withdrawn',
            'reason' => 'I withdraw my consent for data processing'
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_deletion',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function gdpr_article_20_right_of_portability_works()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('data-subject.exercise-right'), [
            'right_type' => 'portability',
            'data_format' => 'json',
            'transfer_to' => 'another_service@example.com',
            'reason' => 'Moving to another service provider'
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gdpr_data_subject_requests', [
            'user_id' => $this->user->id,
            'request_type' => 'data_portability',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function gdpr_request_processing_respects_time_limits()
    {
        $request = GdprDataSubjectRequest::factory()->create([
            'user_id' => $this->user->id,
            'request_type' => 'data_export',
            'created_at' => now()->subDays(25), // Created 25 days ago
            'status' => 'pending'
        ]);
        
        $overdueRequests = $this->gdprService->getOverdueRequests();
        
        $this->assertCount(1, $overdueRequests);
        $this->assertEquals($request->id, $overdueRequests[0]->id);
    }

    /** @test */
    public function gdpr_compliance_handles_child_data_specially()
    {
        // Create user under 16 (GDPR child)
        $childUser = User::factory()->create([
            'date_of_birth' => now()->subYears(14)
        ]);
        
        $consentData = [
            'processing_purpose' => 'emergency_contacts',
            'legal_basis' => 'consent',
            'data_categories' => ['contact_information'],
            'parental_consent' => true,
            'parent_guardian_id' => $this->user->id
        ];
        
        $consent = $this->gdprService->recordConsent($childUser, $consentData);
        
        $this->assertInstanceOf(GdprConsentRecord::class, $consent);
        $this->assertTrue($consent->requires_parental_consent);
        $this->assertEquals($this->user->id, $consent->parent_guardian_id);
    }

    /** @test */
    public function gdpr_data_minimization_principle_is_enforced()
    {
        $processingData = [
            'activity_name' => 'Basic Contact Management',
            'processing_purpose' => 'emergency_contacts',
            'legal_basis' => ['legitimate_interests'],
            'data_categories' => ['contact_information', 'biometric_data', 'financial_data'], // Excessive data
            'necessity_justification' => 'Need contact info for emergencies'
        ];
        
        $validation = $this->gdprService->validateDataMinimization($processingData);
        
        $this->assertFalse($validation['compliant']);
        $this->assertStringContains('excessive data categories', $validation['issues'][0]);
        $this->assertContains('biometric_data', $validation['unnecessary_categories']);
        $this->assertContains('financial_data', $validation['unnecessary_categories']);
    }

    /** @test */
    public function gdpr_generates_processing_record_for_article_30()
    {
        $record = $this->gdprService->generateArticle30Record();
        
        $this->assertIsArray($record);
        $this->assertArrayHasKey('controller_details', $record);
        $this->assertArrayHasKey('processing_activities', $record);
        $this->assertArrayHasKey('data_categories', $record);
        $this->assertArrayHasKey('retention_periods', $record);
        $this->assertArrayHasKey('technical_organizational_measures', $record);
        
        // Should include emergency contact processing
        $emergencyProcessing = collect($record['processing_activities'])
            ->firstWhere('purpose', 'emergency_contacts');
        
        $this->assertNotNull($emergencyProcessing);
        $this->assertArrayHasKey('legal_basis', $emergencyProcessing);
        $this->assertArrayHasKey('data_categories', $emergencyProcessing);
    }
}