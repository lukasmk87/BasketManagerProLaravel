<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated ML and GDPR Tables Migration
 *
 * Includes: ml_models, ml_predictions, ml_training_data, ml_feature_stores, ml_experiments,
 * emergency_incidents, team_emergency_access, gdpr_data_processing_records,
 * gdpr_consent_records, gdpr_data_subject_requests
 */
return new class extends Migration
{
    public function up(): void
    {
        // ML Models
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('model_path')->nullable();
            $table->json('parameters')->nullable();
            $table->json('metrics')->nullable();
            $table->enum('status', ['training', 'ready', 'deployed', 'deprecated'])->default('training');
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ML Predictions
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ml_model_id')->constrained('ml_models')->onDelete('cascade');
            $table->morphs('predictable');
            $table->string('prediction_type');
            $table->json('input_data')->nullable();
            $table->json('prediction')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('explanation')->nullable();
            $table->timestamp('predicted_at');
            $table->timestamps();
        });

        // ML Training Data
        Schema::create('ml_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ml_model_id')->constrained('ml_models')->onDelete('cascade');
            $table->string('data_type');
            $table->json('features')->nullable();
            $table->json('labels')->nullable();
            $table->enum('split', ['train', 'validation', 'test'])->default('train');
            $table->timestamps();
        });

        // ML Feature Stores
        Schema::create('ml_feature_stores', function (Blueprint $table) {
            $table->id();
            $table->morphs('entity');
            $table->string('feature_name');
            $table->json('feature_value')->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'feature_name']);
        });

        // ML Experiments
        Schema::create('ml_experiments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->json('results')->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Emergency Incidents
        Schema::create('emergency_incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('reported_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['injury', 'medical', 'safety', 'weather', 'other'])->default('other');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->text('outcome')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->timestamp('occurred_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Team Emergency Access
        Schema::create('team_emergency_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('access_token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // GDPR Data Processing Records
        Schema::create('gdpr_data_processing_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('processing_type');
            $table->string('legal_basis');
            $table->text('purpose');
            $table->json('data_categories')->nullable();
            $table->json('recipients')->nullable();
            $table->string('retention_period')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // GDPR Consent Records
        Schema::create('gdpr_consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type');
            $table->boolean('given')->default(false);
            $table->timestamp('given_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
        });

        // GDPR Data Subject Requests
        Schema::create('gdpr_data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('request_type', ['access', 'rectification', 'erasure', 'portability', 'restriction', 'objection'])->default('access');
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->text('response')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_data_subject_requests');
        Schema::dropIfExists('gdpr_consent_records');
        Schema::dropIfExists('gdpr_data_processing_records');
        Schema::dropIfExists('team_emergency_access');
        Schema::dropIfExists('emergency_incidents');
        Schema::dropIfExists('ml_experiments');
        Schema::dropIfExists('ml_feature_stores');
        Schema::dropIfExists('ml_training_data');
        Schema::dropIfExists('ml_predictions');
        Schema::dropIfExists('ml_models');
    }
};
