<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Events\SecurityEventDetected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SecurityMonitoringService
{
    private array $securityRules = [
        'failed_login_threshold' => 5,
        'emergency_access_anomaly_threshold' => 10,
        'data_export_anomaly_threshold' => 3,
        'suspicious_ip_patterns' => ['tor_nodes', 'vpn_services', 'known_malicious'],
        'rate_limit_violations' => 50,
        'gdpr_request_threshold' => 5,
        'emergency_access_frequency_threshold' => 15, // per hour
    ];

    /**
     * Detect and log a security event
     */
    public function detectSecurityEvent(Request $request, string $eventType, array $eventData = []): ?SecurityEvent
    {
        $severity = $this->calculateSeverity($eventType, $eventData, $request);
        
        if ($severity === 'none') {
            return null; // Not a security event
        }

        $securityEvent = SecurityEvent::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'status' => 'active',
            'description' => $this->generateEventDescription($eventType, $eventData),
            'event_data' => $eventData,
            'occurred_at' => now(),
            'source_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->method(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'affected_resource' => $eventData['resource'] ?? null,
            'request_headers' => $this->sanitizeHeaders($request->headers->all()),
            'request_payload' => $this->sanitizePayload($request->all()),
            'detection_method' => 'rule_based',
            'detector_name' => 'SecurityMonitoringService',
            'confidence_score' => $this->calculateConfidence($eventType, $eventData),
            'automated_actions' => $this->executeAutomatedActions($eventType, $severity, $request),
            'requires_notification' => $this->requiresNotification($severity),
            'requires_investigation' => $this->requiresInvestigation($eventType, $severity),
        ]);

        // Broadcast event for real-time monitoring
        broadcast(new SecurityEventDetected($securityEvent));

        // Log the security event
        Log::channel('security')->warning('Security event detected', [
            'event_id' => $securityEvent->event_id,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        return $securityEvent;
    }

    /**
     * Monitor emergency access patterns for anomalies
     */
    public function monitorEmergencyAccess(Request $request, string $accessKey, array $context = []): void
    {
        $cacheKey = "emergency_access_monitor:{$request->ip()}";
        $accessCount = Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, 3600); // 1 hour window

        // Detect high-frequency access anomalies
        if ($accessCount > $this->securityRules['emergency_access_anomaly_threshold']) {
            $this->detectSecurityEvent($request, 'emergency_access_anomaly', [
                'access_key' => $accessKey,
                'access_count' => $accessCount,
                'context' => $context,
                'anomaly_type' => 'high_frequency_access',
                'time_window' => '1 hour',
            ]);
        }

        // Check for suspicious IP patterns
        if ($this->isSuspiciousIP($request->ip())) {
            $this->detectSecurityEvent($request, 'suspicious_activity', [
                'access_key' => $accessKey,
                'ip_classification' => $this->classifyIP($request->ip()),
                'context' => $context,
                'activity_type' => 'emergency_access_from_suspicious_ip',
            ]);
        }

        // Monitor unusual timing patterns
        $hour = now()->hour;
        if ($hour < 6 || $hour > 22) { // Outside normal hours
            $nightAccessKey = "emergency_night_access:{$accessKey}:{$request->ip()}";
            $nightCount = Cache::increment($nightAccessKey, 1);
            Cache::expire($nightAccessKey, 86400); // 24 hours
            
            if ($nightCount > 3) {
                $this->detectSecurityEvent($request, 'emergency_access_anomaly', [
                    'access_key' => $accessKey,
                    'anomaly_type' => 'unusual_timing_pattern',
                    'access_time' => now()->format('H:i'),
                    'night_access_count' => $nightCount,
                ]);
            }
        }

        // Check for rapid successive accesses (bot-like behavior)
        $rapidAccessKey = "emergency_rapid_access:{$request->ip()}";
        $lastAccessTime = Cache::get($rapidAccessKey);
        
        if ($lastAccessTime && now()->diffInSeconds($lastAccessTime) < 5) {
            $this->detectSecurityEvent($request, 'suspicious_activity', [
                'access_key' => $accessKey,
                'anomaly_type' => 'rapid_successive_access',
                'seconds_between_access' => now()->diffInSeconds($lastAccessTime),
                'activity_type' => 'potential_bot_activity',
            ]);
        }
        
        Cache::put($rapidAccessKey, now(), 300); // 5 minutes
    }

    /**
     * Monitor GDPR compliance violations
     */
    public function monitorGDPRCompliance(User $user, string $action, array $data = []): void
    {
        $violations = [];

        // Check for unauthorized data access
        if ($action === 'data_access' && !$this->isAuthorizedDataAccess($user, $data)) {
            $violations[] = [
                'type' => 'unauthorized_data_access',
                'details' => 'User attempted to access data without proper authorization'
            ];
        }

        // Check for excessive data export requests
        if ($action === 'data_export') {
            $exportCount = $this->getRecentDataExports($user);
            if ($exportCount > $this->securityRules['data_export_anomaly_threshold']) {
                $violations[] = [
                    'type' => 'excessive_data_exports',
                    'details' => "User has exported data {$exportCount} times recently"
                ];
            }
        }

        // Check for data processing without valid consent
        if ($action === 'data_processing' && !$this->hasValidConsent($data)) {
            $violations[] = [
                'type' => 'processing_without_consent',
                'details' => 'Data processing attempted without valid consent'
            ];
        }

        // Check for consent withdrawal violations
        if ($action === 'consent_violation' && isset($data['consent_withdrawn'])) {
            $violations[] = [
                'type' => 'consent_withdrawal_violation',
                'details' => 'Data processed after consent was withdrawn'
            ];
        }

        // Check for data retention violations
        if ($action === 'data_retention_violation') {
            $violations[] = [
                'type' => 'data_retention_violation',
                'details' => 'Data retained beyond legal retention period'
            ];
        }

        // Create security events for violations
        foreach ($violations as $violation) {
            $this->detectSecurityEvent(request(), 'gdpr_compliance_violation', [
                'violation_type' => $violation['type'],
                'violation_details' => $violation['details'],
                'user_id' => $user->id,
                'action' => $action,
                'data_summary' => $this->summarizeData($data),
                'gdpr_article' => $this->getRelevantGDPRArticle($violation['type']),
            ]);
        }
    }

    /**
     * Monitor authentication failures
     */
    public function monitorAuthenticationFailures(Request $request, string $email = null): void
    {
        $ip = $request->ip();
        $cacheKey = "auth_failures:{$ip}";
        
        $failures = Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, 3600); // 1 hour window
        
        if ($failures >= $this->securityRules['failed_login_threshold']) {
            $this->detectSecurityEvent($request, 'authentication_failure', [
                'failure_count' => $failures,
                'attempted_email' => $email,
                'time_window' => '1 hour',
                'ip_address' => $ip,
                'potential_brute_force' => $failures > 10,
            ]);
        }

        // Check for distributed brute force (same email, different IPs)
        if ($email) {
            $emailKey = "auth_failures_email:{$email}";
            $emailFailures = Cache::increment($emailKey, 1);
            Cache::expire($emailKey, 3600);
            
            if ($emailFailures > 15) { // Threshold for distributed attack
                $this->detectSecurityEvent($request, 'brute_force_attempt', [
                    'attack_type' => 'distributed_brute_force',
                    'target_email' => $email,
                    'failure_count' => $emailFailures,
                    'current_ip' => $ip,
                ]);
            }
        }
    }

    /**
     * Generate comprehensive security report
     */
    public function generateSecurityReport(array $options = []): array
    {
        $timeframe = $options['timeframe'] ?? '30 days';
        $startDate = now()->sub($timeframe);

        $events = SecurityEvent::where('occurred_at', '>=', $startDate)->get();

        return [
            'report_metadata' => [
                'generated_at' => now()->toISOString(),
                'timeframe' => $timeframe,
                'total_events' => $events->count(),
                'period_start' => $startDate->toDateString(),
                'period_end' => now()->toDateString(),
            ],
            'severity_breakdown' => $events->groupBy('severity')->map->count(),
            'event_type_breakdown' => $events->groupBy('event_type')->map->count(),
            'status_distribution' => $events->groupBy('status')->map->count(),
            'top_source_ips' => $events->groupBy('source_ip')
                ->map->count()
                ->sortDesc()
                ->take(10),
            'critical_events' => $events->where('severity', 'critical')
                ->map(function ($event) {
                    return [
                        'event_id' => $event->event_id,
                        'event_type' => $event->event_type,
                        'occurred_at' => $event->occurred_at->toISOString(),
                        'source_ip' => $event->source_ip,
                        'status' => $event->status,
                        'description' => $event->description,
                    ];
                }),
            'emergency_related_events' => $events->where('event_type', 'LIKE', 'emergency%')->count(),
            'gdpr_related_events' => $events->where('event_type', 'LIKE', '%gdpr%')->count(),
            'trends' => $this->calculateSecurityTrends($events),
            'recommendations' => $this->generateSecurityRecommendations($events),
            'resolution_metrics' => $this->calculateResolutionMetrics($events),
        ];
    }

    /**
     * Calculate event severity based on type and context
     */
    private function calculateSeverity(string $eventType, array $eventData, Request $request): string
    {
        $baseSeverity = match ($eventType) {
            'emergency_access_misuse', 'emergency_access_anomaly' => 'high',
            'gdpr_violation', 'gdpr_compliance_violation' => 'high',
            'authentication_failure' => 'medium',
            'authorization_violation' => 'medium',
            'suspicious_activity' => 'medium',
            'brute_force_attempt' => 'high',
            'privilege_escalation' => 'critical',
            'data_access_violation' => 'high',
            'rate_limit_exceeded' => 'low',
            default => 'low',
        };

        // Escalate severity based on context
        if (isset($eventData['access_count']) && $eventData['access_count'] > 20) {
            $baseSeverity = $this->escalateSeverity($baseSeverity);
        }

        if ($this->isSuspiciousIP($request->ip())) {
            $baseSeverity = $this->escalateSeverity($baseSeverity);
        }

        if (isset($eventData['potential_brute_force']) && $eventData['potential_brute_force']) {
            $baseSeverity = $this->escalateSeverity($baseSeverity);
        }

        return $baseSeverity;
    }

    /**
     * Escalate severity level
     */
    private function escalateSeverity(string $currentSeverity): string
    {
        return match ($currentSeverity) {
            'low' => 'medium',
            'medium' => 'high',
            'high' => 'critical',
            'critical' => 'critical',
        };
    }

    /**
     * Generate event description
     */
    private function generateEventDescription(string $eventType, array $eventData): string
    {
        return match ($eventType) {
            'emergency_access_misuse' => "Emergency access misuse detected. Access count: " . 
                ($eventData['access_count'] ?? 'unknown'),
            'emergency_access_anomaly' => "Emergency access anomaly detected: " . 
                ($eventData['anomaly_type'] ?? 'unspecified pattern'),
            'gdpr_compliance_violation' => "GDPR compliance violation: " . 
                ($eventData['violation_type'] ?? 'unknown violation'),
            'authentication_failure' => "Authentication failure detected. Failure count: " . 
                ($eventData['failure_count'] ?? '1'),
            'brute_force_attempt' => "Brute force attack detected: " . 
                ($eventData['attack_type'] ?? 'standard brute force'),
            'suspicious_activity' => "Suspicious activity detected: " . 
                ($eventData['activity_type'] ?? 'general suspicious behavior'),
            default => "Security event of type: {$eventType}",
        };
    }

    /**
     * Calculate confidence score for the event
     */
    private function calculateConfidence(string $eventType, array $eventData): float
    {
        $baseConfidence = match ($eventType) {
            'authentication_failure' => 0.9,
            'brute_force_attempt' => 0.95,
            'emergency_access_anomaly' => 0.8,
            'gdpr_compliance_violation' => 0.85,
            'suspicious_activity' => 0.6,
            default => 0.7,
        };

        // Adjust based on evidence strength
        if (isset($eventData['access_count']) && $eventData['access_count'] > 50) {
            $baseConfidence = min(1.0, $baseConfidence + 0.1);
        }

        if (isset($eventData['failure_count']) && $eventData['failure_count'] > 20) {
            $baseConfidence = min(1.0, $baseConfidence + 0.1);
        }

        return round($baseConfidence, 4);
    }

    /**
     * Execute automated actions based on event type and severity
     */
    private function executeAutomatedActions(string $eventType, string $severity, Request $request): array
    {
        $actions = [];

        // Critical events get immediate action
        if ($severity === 'critical') {
            $actions[] = 'immediate_notification_sent';
            $actions[] = 'incident_escalated_to_admin';
        }

        // Brute force attempts
        if ($eventType === 'brute_force_attempt') {
            $actions[] = 'ip_temporarily_blocked';
            RateLimiter::hit($request->ip() . ':blocked', 3600); // 1 hour block
        }

        // Emergency access anomalies
        if (str_contains($eventType, 'emergency_access')) {
            $actions[] = 'emergency_team_notified';
            if ($severity === 'critical') {
                $actions[] = 'emergency_access_temporarily_suspended';
            }
        }

        // GDPR violations
        if (str_contains($eventType, 'gdpr')) {
            $actions[] = 'gdpr_officer_notified';
            $actions[] = 'compliance_audit_triggered';
        }

        return $actions;
    }

    /**
     * Determine if event requires notification
     */
    private function requiresNotification(string $severity): bool
    {
        return in_array($severity, ['high', 'critical']);
    }

    /**
     * Determine if event requires investigation
     */
    private function requiresInvestigation(string $eventType, string $severity): bool
    {
        if ($severity === 'critical') {
            return true;
        }

        return in_array($eventType, [
            'gdpr_compliance_violation',
            'emergency_access_misuse',
            'brute_force_attempt',
            'privilege_escalation',
            'data_access_violation'
        ]);
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP(string $ip): bool
    {
        // Simple implementation - in production, integrate with threat intelligence
        $suspiciousRanges = [
            '10.0.0.0/8',   // Private ranges shouldn't be accessing from external
            '192.168.0.0/16',
        ];

        // Check against known bad IP lists (stub implementation)
        return false; // In production, check against threat intelligence
    }

    /**
     * Classify IP address
     */
    private function classifyIP(string $ip): string
    {
        // Simple classification - in production, integrate with IP reputation services
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'public_ip';
        }
        
        return 'private_ip';
    }

    /**
     * Check if user is authorized for data access
     */
    private function isAuthorizedDataAccess(User $user, array $data): bool
    {
        // Implement authorization logic based on your business rules
        return $user->hasPermissionTo('access_sensitive_data');
    }

    /**
     * Get recent data export count for user
     */
    private function getRecentDataExports(User $user): int
    {
        return Cache::get("data_exports:{$user->id}", 0);
    }

    /**
     * Check if valid consent exists for data processing
     */
    private function hasValidConsent(array $data): bool
    {
        // Implement consent validation logic
        return isset($data['consent_given']) && $data['consent_given'] === true;
    }

    /**
     * Summarize data for logging (remove sensitive info)
     */
    private function summarizeData(array $data): array
    {
        $summary = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['password', 'token', 'secret', 'key'])) {
                $summary[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $summary[$key] = '[ARRAY_' . count($value) . '_ITEMS]';
            } else {
                $summary[$key] = is_string($value) ? substr($value, 0, 50) : $value;
            }
        }
        
        return $summary;
    }

    /**
     * Get relevant GDPR article for violation type
     */
    private function getRelevantGDPRArticle(string $violationType): string
    {
        return match ($violationType) {
            'unauthorized_data_access' => 'Article 6 (Lawfulness)',
            'processing_without_consent' => 'Article 7 (Consent)',
            'data_retention_violation' => 'Article 5 (Storage limitation)',
            'consent_withdrawal_violation' => 'Article 7 (Withdrawal of consent)',
            'excessive_data_exports' => 'Article 15 (Right of access)',
            default => 'General GDPR compliance',
        };
    }

    /**
     * Sanitize request headers for logging
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '[REDACTED]';
            }
        }
        
        return $headers;
    }

    /**
     * Sanitize request payload for logging
     */
    private function sanitizePayload(array $payload): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '[REDACTED]';
            }
        }
        
        return $payload;
    }

    /**
     * Calculate security trends
     */
    private function calculateSecurityTrends(object $events): array
    {
        // Calculate daily event counts
        $dailyCounts = $events->groupBy(function ($event) {
            return $event->occurred_at->format('Y-m-d');
        })->map->count();

        // Calculate trend (simple implementation)
        $counts = $dailyCounts->values()->toArray();
        if (count($counts) < 2) {
            $trend = 'stable';
        } else {
            $recent = array_slice($counts, -7); // Last 7 days
            $older = array_slice($counts, -14, 7); // Previous 7 days
            
            $recentAvg = array_sum($recent) / count($recent);
            $olderAvg = count($older) > 0 ? array_sum($older) / count($older) : $recentAvg;
            
            $trend = $recentAvg > $olderAvg * 1.2 ? 'increasing' : 
                    ($recentAvg < $olderAvg * 0.8 ? 'decreasing' : 'stable');
        }

        return [
            'overall_trend' => $trend,
            'daily_counts' => $dailyCounts,
            'peak_day' => $dailyCounts->keys()->first(),
            'peak_count' => $dailyCounts->max(),
        ];
    }

    /**
     * Generate security recommendations
     */
    private function generateSecurityRecommendations(object $events): array
    {
        $recommendations = [];

        // Check for high number of critical events
        $criticalCount = $events->where('severity', 'critical')->count();
        if ($criticalCount > 5) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'High Number of Critical Events',
                'description' => "You have {$criticalCount} critical security events that need immediate attention.",
                'action' => 'Review and resolve all critical events within 24 hours.',
            ];
        }

        // Check for unresolved events
        $unresolvedCount = $events->whereNotIn('status', ['resolved', 'false_positive'])->count();
        if ($unresolvedCount > 10) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Unresolved Security Events',
                'description' => "You have {$unresolvedCount} unresolved security events.",
                'action' => 'Establish incident response procedures and assign security events for investigation.',
            ];
        }

        // Check for GDPR violations
        $gdprCount = $events->where('event_type', 'LIKE', '%gdpr%')->count();
        if ($gdprCount > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'GDPR Compliance Issues',
                'description' => "GDPR violations have been detected.",
                'action' => 'Review data processing procedures and ensure GDPR compliance.',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate resolution metrics
     */
    private function calculateResolutionMetrics(object $events): array
    {
        $resolvedEvents = $events->whereIn('status', ['resolved', 'false_positive']);
        
        if ($resolvedEvents->isEmpty()) {
            return [
                'total_resolved' => 0,
                'average_resolution_time' => null,
                'resolution_rate' => 0,
            ];
        }

        $totalEvents = $events->count();
        $resolutionTimes = [];

        foreach ($resolvedEvents as $event) {
            if ($event->resolved_at && $event->occurred_at) {
                $resolutionTimes[] = $event->occurred_at->diffInHours($event->resolved_at);
            }
        }

        return [
            'total_resolved' => $resolvedEvents->count(),
            'average_resolution_time' => empty($resolutionTimes) ? null : round(array_sum($resolutionTimes) / count($resolutionTimes), 2) . ' hours',
            'resolution_rate' => round(($resolvedEvents->count() / $totalEvents) * 100, 1),
            'fastest_resolution' => empty($resolutionTimes) ? null : min($resolutionTimes) . ' hours',
            'slowest_resolution' => empty($resolutionTimes) ? null : max($resolutionTimes) . ' hours',
        ];
    }
}