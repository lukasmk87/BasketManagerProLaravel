<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_id')->unique(); // Human-readable ID like "EMG-2024-001"
            
            // Incident Details
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->foreignId('training_session_id')->nullable()->constrained('training_sessions')->onDelete('set null');
            
            // Incident Information
            $table->enum('incident_type', [
                'injury', 'medical_emergency', 'accident', 'missing_person',
                'behavioral_incident', 'facility_emergency', 'weather_emergency', 'other'
            ]);
            $table->enum('severity', ['minor', 'moderate', 'severe', 'critical']);
            $table->text('description');
            $table->datetime('occurred_at');
            $table->string('location');
            $table->json('coordinates')->nullable(); // GPS coordinates
            
            // Response Information
            $table->foreignId('reported_by_user_id')->constrained('users');
            $table->datetime('reported_at');
            $table->json('contacts_notified')->nullable(); // Which emergency contacts were called
            $table->json('response_actions')->nullable(); // Actions taken
            $table->json('personnel_involved')->nullable(); // Staff/volunteers involved
            
            // Medical Information (if applicable)
            $table->boolean('medical_attention_required')->default(false);
            $table->boolean('ambulance_called')->default(false);
            $table->string('hospital_name')->nullable();
            $table->text('medical_notes')->nullable(); // Will be encrypted
            $table->json('vital_signs')->nullable(); // If recorded
            
            // Follow-up
            $table->enum('status', ['active', 'resolved', 'investigating', 'closed'])->default('active');
            $table->text('resolution_notes')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users');
            
            // Documentation
            $table->json('photos')->nullable(); // Photo paths
            $table->json('documents')->nullable(); // Document paths
            $table->json('witness_statements')->nullable();
            
            // Legal & Insurance
            $table->boolean('insurance_claim_filed')->default(false);
            $table->string('insurance_claim_number')->nullable();
            $table->boolean('legal_action_required')->default(false);
            $table->text('legal_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['player_id', 'occurred_at']);
            $table->index(['team_id', 'incident_type']);
            $table->index(['severity', 'status']);
            $table->index('occurred_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_incidents');
    }
};