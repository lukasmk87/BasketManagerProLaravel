<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // Human-readable ID
            
            // Event Classification
            $table->enum('event_type', [
                'authentication_failure', 'authorization_violation', 'data_access_violation',
                'emergency_access_misuse', 'gdpr_violation', 'suspicious_activity',
                'brute_force_attempt', 'rate_limit_exceeded', 'ip_blocked',
                'session_hijack_attempt', 'privilege_escalation', 'data_export_unusual',
                'emergency_access_anomaly', 'gdpr_compliance_violation'
            ]);
            
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['active', 'investigating', 'resolved', 'false_positive']);
            
            // Event Details
            $table->text('description');
            $table->json('event_data')->nullable(); // Detailed event information
            $table->timestamp('occurred_at');
            $table->ipAddress('source_ip');
            $table->string('user_agent')->nullable();
            $table->string('request_uri')->nullable();
            $table->string('request_method', 10)->nullable();
            
            // Context
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('affected_resource')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_payload')->nullable();
            
            // Detection
            $table->string('detection_method')->default('rule_based'); // rule_based, ml_model, manual
            $table->string('detector_name')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0.0000 to 1.0000
            
            // Response
            $table->json('automated_actions')->nullable(); // Actions taken automatically
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            // Follow-up
            $table->boolean('requires_notification')->default(false);
            $table->json('notified_users')->nullable();
            $table->boolean('requires_investigation')->default(false);
            $table->text('investigation_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['event_type', 'severity']);
            $table->index(['occurred_at', 'status']);
            $table->index(['source_ip', 'occurred_at']);
            $table->index(['user_id', 'event_type']);
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
