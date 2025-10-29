<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SecurityMonitoringService;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityMonitoringService $securityService;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(SecurityMonitoringService::class);
        $this->testUser = User::factory()->create();
    }

    /** @test */
    public function can_detect_emergency_access_anomaly()
    {
        // Create a mock request
        $request = Request::create('/emergency/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'Test User Agent'
        ]);

        // Act as authenticated user
        $this->actingAs($this->testUser);

        // Detect security event
        $securityEvent = $this->securityService->detectSecurityEvent(
            $request,
            'emergency_access_anomaly',
            [
                'access_key' => 'key',
                'anomaly_type' => 'high_frequency_access',
                'access_count' => 15,
            ]
        );

        // Assert security event was created
        $this->assertInstanceOf(SecurityEvent::class, $securityEvent);
        $this->assertEquals('emergency_access_anomaly', $securityEvent->event_type);
        $this->assertEquals('192.168.1.100', $securityEvent->source_ip);
        $this->assertEquals($this->testUser->id, $securityEvent->user_id);
        $this->assertStringContains('Emergency access anomaly detected', $securityEvent->description);
        $this->assertArrayHasKey('access_key', $securityEvent->event_data);
        $this->assertEquals('key', $securityEvent->event_data['access_key']);
    }

    /** @test */
    public function can_detect_gdpr_compliance_violation()
    {
        $request = Request::create('/gdpr/test', 'POST');
        $this->actingAs($this->testUser);

        // Monitor GDPR compliance violation
        $this->securityService->monitorGDPRCompliance($this->testUser, 'data_processing', [
            'violation_type' => 'processing_without_consent',
            'request_id' => 'DSR-2025-0001',
        ]);

        // Assert security event was created
        $securityEvent = SecurityEvent::where('event_type', 'gdpr_compliance_violation')->first();
        $this->assertNotNull($securityEvent);
        $this->assertEquals('gdpr_compliance_violation', $securityEvent->event_type);
        $this->assertEquals($this->testUser->id, $securityEvent->user_id);
        $this->assertArrayHasKey('violation_type', $securityEvent->event_data);
    }

    /** @test */
    public function can_monitor_emergency_access_patterns()
    {
        $request = Request::create('/emergency/access', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1'
        ]);

        // Clear cache to start fresh
        Cache::flush();

        // Monitor emergency access multiple times to trigger anomaly
        for ($i = 0; $i < 12; $i++) {
            $this->securityService->monitorEmergencyAccess($request, 'access_key', [
                'action' => 'emergency_access_granted',
                'team_id' => 1,
            ]);
        }

        // Assert security event was created for high frequency access
        $securityEvent = SecurityEvent::where('event_type', 'emergency_access_anomaly')
            ->where('source_ip', '10.0.0.1')
            ->first();
        
        $this->assertNotNull($securityEvent);
        $this->assertArrayHasKey('anomaly_type', $securityEvent->event_data);
        $this->assertEquals('high_frequency_access', $securityEvent->event_data['anomaly_type']);
    }

    /** @test */
    public function can_monitor_authentication_failures()
    {
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.1'
        ]);

        // Clear cache to start fresh
        Cache::flush();

        // Simulate multiple authentication failures
        for ($i = 0; $i < 6; $i++) {
            $this->securityService->monitorAuthenticationFailures($request, 'test@example.com');
        }

        // Assert security event was created
        $securityEvent = SecurityEvent::where('event_type', 'authentication_failure')
            ->where('source_ip', '203.0.113.1')
            ->first();

        $this->assertNotNull($securityEvent);
        $this->assertArrayHasKey('failure_count', $securityEvent->event_data);
        $this->assertGreaterThanOrEqual(5, $securityEvent->event_data['failure_count']);
    }

    /** @test */
    public function can_generate_security_report()
    {
        // Create some test security events
        SecurityEvent::create([
            'event_type' => 'emergency_access_anomaly',
            'severity' => 'high',
            'status' => 'active',
            'description' => 'Test emergency event',
            'occurred_at' => now()->subDays(1),
            'source_ip' => '192.168.1.1',
        ]);

        SecurityEvent::create([
            'event_type' => 'gdpr_compliance_violation',
            'severity' => 'critical',
            'status' => 'active',
            'description' => 'Test GDPR event',
            'occurred_at' => now()->subDays(2),
            'source_ip' => '192.168.1.2',
        ]);

        // Generate report
        $report = $this->securityService->generateSecurityReport(['timeframe' => '7 days']);

        // Assert report structure
        $this->assertArrayHasKey('report_metadata', $report);
        $this->assertArrayHasKey('severity_breakdown', $report);
        $this->assertArrayHasKey('event_type_breakdown', $report);
        $this->assertArrayHasKey('critical_events', $report);
        $this->assertArrayHasKey('trends', $report);
        $this->assertArrayHasKey('recommendations', $report);

        // Assert report contains expected data
        $this->assertEquals(2, $report['report_metadata']['total_events']);
        $this->assertArrayHasKey('high', $report['severity_breakdown']);
        $this->assertArrayHasKey('critical', $report['severity_breakdown']);
    }

    /** @test */
    public function security_event_severity_calculation()
    {
        $request = Request::create('/test', 'GET');

        // Test critical severity
        $criticalEvent = $this->securityService->detectSecurityEvent(
            $request,
            'privilege_escalation',
            ['escalation_type' => 'admin_takeover']
        );

        $this->assertEquals('critical', $criticalEvent->severity);

        // Test high severity
        $highEvent = $this->securityService->detectSecurityEvent(
            $request,
            'emergency_access_misuse',
            ['misuse_type' => 'unauthorized_access']
        );

        $this->assertEquals('high', $highEvent->severity);
    }

    /** @test */
    public function security_event_confidence_scoring()
    {
        $request = Request::create('/test', 'GET');

        // Test high confidence event
        $event = $this->securityService->detectSecurityEvent(
            $request,
            'brute_force_attempt',
            ['failure_count' => 25]
        );

        $this->assertGreaterThan(0.9, $event->confidence_score);
        $this->assertLessThanOrEqual(1.0, $event->confidence_score);
    }

    /** @test */
    public function automated_security_actions()
    {
        $request = Request::create('/test', 'GET');

        // Test critical event triggers automated actions
        $criticalEvent = $this->securityService->detectSecurityEvent(
            $request,
            'privilege_escalation',
            ['escalation_type' => 'critical_breach']
        );

        $this->assertNotEmpty($criticalEvent->automated_actions);
        $this->assertContains('immediate_notification_sent', $criticalEvent->automated_actions);
    }

    /** @test */
    public function security_event_basic_functionality()
    {
        // Create test events
        $emergencyEvent = SecurityEvent::create([
            'event_type' => 'emergency_access_anomaly',
            'severity' => 'critical',
            'status' => 'active',
            'description' => 'Test emergency event',
            'occurred_at' => now(),
            'source_ip' => '192.168.1.1',
        ]);

        $gdprEvent = SecurityEvent::create([
            'event_type' => 'gdpr_compliance_violation',
            'severity' => 'high',
            'status' => 'resolved',
            'description' => 'Test GDPR event',
            'occurred_at' => now(),
            'source_ip' => '192.168.1.2',
            'resolved_at' => now(),
        ]);

        // Test scopes
        $criticalEvents = SecurityEvent::critical()->get();
        $this->assertCount(1, $criticalEvents);

        $unresolvedEvents = SecurityEvent::unresolved()->get();
        $this->assertCount(1, $unresolvedEvents);

        $emergencyEvents = SecurityEvent::emergencyRelated()->get();
        $this->assertCount(1, $emergencyEvents);

        $gdprEvents = SecurityEvent::gdprRelated()->get();
        $this->assertCount(1, $gdprEvents);

        // Test helper methods
        $this->assertTrue($emergencyEvent->isCritical());
        $this->assertFalse($emergencyEvent->isResolved());
        $this->assertTrue($emergencyEvent->isEmergencyRelated());
        $this->assertFalse($emergencyEvent->isGdprRelated());

        $this->assertFalse($gdprEvent->isCritical());
        $this->assertTrue($gdprEvent->isResolved());
        $this->assertFalse($gdprEvent->isEmergencyRelated());
        $this->assertTrue($gdprEvent->isGdprRelated());
    }
}
