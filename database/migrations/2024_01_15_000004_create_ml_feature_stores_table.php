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
        Schema::create('ml_feature_stores', function (Blueprint $table) {
            $table->id();
            
            // Feature identification
            $table->string('feature_name');
            $table->string('feature_group'); // 'player_stats', 'team_metrics', 'game_context'
            $table->text('description')->nullable();
            $table->string('data_type'); // 'numerical', 'categorical', 'boolean', 'datetime'
            $table->string('calculation_method')->nullable(); // How feature is computed
            
            // Feature entity (what this feature describes)
            $table->morphs('entity'); // player, team, game, etc.
            $table->json('entity_metadata')->nullable(); // Additional entity information
            
            // Feature values and statistics
            $table->json('current_value')->nullable(); // Latest feature value
            $table->json('historical_values')->nullable(); // Time series of values
            $table->decimal('min_value', 15, 6)->nullable();
            $table->decimal('max_value', 15, 6)->nullable();
            $table->decimal('mean_value', 15, 6)->nullable();
            $table->decimal('median_value', 15, 6)->nullable();
            $table->decimal('std_deviation', 15, 6)->nullable();
            
            // Feature temporal context
            $table->timestamp('calculation_date'); // When feature was calculated
            $table->timestamp('effective_date'); // What date/time this feature represents
            $table->string('time_period'); // 'game', 'week', 'month', 'season', 'career'
            $table->integer('lookback_days')->nullable(); // Days of history used
            $table->string('aggregation_method')->nullable(); // 'sum', 'avg', 'max', 'latest'
            
            // Basketball-specific context
            $table->string('season')->nullable(); // e.g., '2023-2024'
            $table->enum('competition_level', [
                'youth',
                'high_school', 
                'college',
                'professional',
                'international'
            ])->nullable();
            
            $table->enum('situation_context', [
                'overall',
                'home_games',
                'away_games',
                'clutch_time',
                'playoffs',
                'vs_strong_defense',
                'vs_weak_defense'
            ])->default('overall');
            
            // Feature engineering metadata
            $table->json('dependencies')->nullable(); // Other features this depends on
            $table->json('transformation_pipeline')->nullable(); // Processing steps applied
            $table->boolean('is_derived')->default(false); // Calculated from other features
            $table->json('derivation_formula')->nullable(); // How derived features are calculated
            $table->string('source_system'); // Where raw data came from
            
            // Feature quality and reliability
            $table->decimal('completeness_score', 5, 4)->default(1.0000); // How complete the data is
            $table->decimal('reliability_score', 5, 4)->nullable(); // How reliable/consistent
            $table->integer('sample_size')->nullable(); // Number of observations used
            $table->decimal('confidence_interval_lower', 15, 6)->nullable();
            $table->decimal('confidence_interval_upper', 15, 6)->nullable();
            
            // Feature validation
            $table->enum('validation_status', [
                'valid',
                'invalid',
                'suspicious',
                'pending_review'
            ])->default('valid');
            
            $table->json('validation_rules')->nullable(); // Rules applied for validation
            $table->json('validation_results')->nullable(); // Validation check results
            $table->text('validation_notes')->nullable();
            
            // Feature usage in models
            $table->json('model_usage')->nullable(); // Which models use this feature
            $table->decimal('average_importance', 5, 4)->nullable(); // Average importance across models
            $table->decimal('max_importance', 5, 4)->nullable(); // Highest importance in any model
            $table->integer('usage_frequency')->default(0); // How often used
            
            // Performance impact tracking
            $table->json('prediction_correlation')->nullable(); // Correlation with target variables
            $table->decimal('information_gain', 10, 6)->nullable(); // Information theory metric
            $table->decimal('mutual_information', 10, 6)->nullable();
            $table->json('feature_interaction_effects')->nullable(); // Interactions with other features
            
            // Basketball domain knowledge
            $table->enum('basketball_category', [
                'offensive_stats',
                'defensive_stats',
                'shooting_metrics',
                'passing_metrics',
                'rebounding_stats',
                'efficiency_metrics',
                'advanced_analytics',
                'contextual_factors',
                'physical_metrics',
                'team_dynamics'
            ])->nullable();
            
            $table->json('position_relevance')->nullable(); // Relevance to different positions
            $table->boolean('is_rate_stat')->default(false); // Is this a per-game/per-minute stat
            $table->boolean('is_cumulative')->default(false); // Accumulates over time
            
            // Data drift and monitoring
            $table->decimal('drift_score', 5, 4)->nullable(); // Statistical drift detection
            $table->json('drift_analysis')->nullable(); // Detailed drift analysis
            $table->timestamp('last_drift_check')->nullable();
            $table->boolean('drift_alert_triggered')->default(false);
            
            // Feature versioning
            $table->string('version', 20)->default('1.0.0');
            $table->foreignId('parent_feature_id')->nullable()->constrained('ml_feature_stores');
            $table->json('version_changes')->nullable(); // What changed between versions
            $table->boolean('is_deprecated')->default(false);
            $table->timestamp('deprecated_at')->nullable();
            
            // Access control and governance
            $table->enum('access_level', [
                'public',
                'team_only',
                'coaching_staff',
                'admin_only'
            ])->default('team_only');
            
            $table->boolean('is_sensitive')->default(false); // Contains sensitive information
            $table->json('privacy_tags')->nullable(); // Data privacy classifications
            $table->text('usage_restrictions')->nullable();
            
            // Computation and refresh
            $table->enum('refresh_frequency', [
                'real_time',
                'hourly',
                'daily', 
                'weekly',
                'monthly',
                'seasonal',
                'manual'
            ])->default('daily');
            
            $table->timestamp('next_refresh_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->integer('refresh_duration_ms')->nullable(); // Time taken to compute
            $table->boolean('auto_refresh_enabled')->default(true);
            
            // Error handling and debugging
            $table->integer('computation_errors')->default(0);
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->json('debug_information')->nullable();
            
            // Business context
            $table->text('business_definition')->nullable(); // What this feature means
            $table->json('use_cases')->nullable(); // How this feature is used
            $table->decimal('business_value_score', 5, 4)->nullable(); // Business importance
            $table->text('interpretation_guide')->nullable(); // How to interpret values
            
            // Status and lifecycle
            $table->enum('status', [
                'active',
                'inactive',
                'experimental',
                'deprecated',
                'archived'
            ])->default('active');
            
            $table->boolean('is_featured')->default(false); // Important/spotlight feature
            $table->integer('priority')->default(50); // Processing priority (1-100)
            
            // Audit and tracking
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->json('change_history')->nullable(); // Track all changes
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['feature_name', 'entity_type', 'entity_id']);
            $table->index(['feature_group', 'status']);
            $table->index(['calculation_date', 'effective_date']);
            $table->index(['season', 'situation_context']);
            $table->index(['basketball_category', 'status']);
            $table->index(['refresh_frequency', 'next_refresh_at']);
            $table->index(['entity_type', 'entity_id', 'calculation_date']);
            $table->index(['validation_status', 'completeness_score']);
            $table->index(['average_importance', 'usage_frequency']);
            $table->index(['is_featured', 'priority', 'status']);
            
            // Unique constraint for feature identity
            $table->unique([
                'feature_name', 
                'entity_type', 
                'entity_id', 
                'calculation_date',
                'time_period'
            ], 'unique_feature_instance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_feature_stores');
    }
};