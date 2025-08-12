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
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            
            // Model and prediction identification
            $table->foreignId('ml_model_id')->constrained('ml_models')->onDelete('cascade');
            $table->string('prediction_id')->unique(); // UUID for external tracking
            $table->string('batch_id')->nullable(); // For batch predictions
            
            // Target entity (what we're predicting about)
            $table->morphs('predictable'); // player_id, game_id, team_id, etc.
            $table->string('prediction_type'); // 'performance', 'injury_risk', 'game_outcome', 'shot_success'
            $table->string('prediction_subtype')->nullable(); // 'next_game', 'season_end', 'career'
            
            // Input data and context
            $table->json('input_features'); // The features used for this prediction
            $table->json('feature_metadata')->nullable(); // Metadata about feature quality/age
            $table->timestamp('data_cutoff_date'); // Latest date of input data used
            $table->integer('data_points_used')->nullable(); // Number of historical data points
            
            // Prediction results
            $table->json('prediction_output'); // The actual prediction(s)
            $table->json('prediction_probabilities')->nullable(); // Confidence scores for each class
            $table->decimal('confidence_score', 5, 4)->nullable(); // Overall confidence (0-1)
            $table->decimal('prediction_variance', 10, 6)->nullable(); // Uncertainty measure
            
            // Basketball-specific predictions
            $table->json('performance_metrics')->nullable(); // Predicted stats (points, rebounds, etc.)
            $table->decimal('injury_probability', 5, 4)->nullable(); // Risk of injury (0-1)
            $table->string('injury_type_predicted')->nullable(); // Type of potential injury
            $table->integer('predicted_games_missed')->nullable(); // Due to potential injury
            $table->decimal('win_probability', 5, 4)->nullable(); // For game outcome predictions
            $table->json('shot_predictions')->nullable(); // Shot success rates by zone
            
            // Time context
            $table->timestamp('prediction_for_date')->nullable(); // What date/game this predicts
            $table->integer('days_ahead')->nullable(); // How many days in advance
            $table->string('season')->nullable(); // Which season the prediction is for
            $table->integer('game_number')->nullable(); // Game number in season
            
            // Validation and accuracy tracking
            $table->timestamp('validation_date')->nullable(); // When we can validate this
            $table->json('actual_outcome')->nullable(); // What actually happened
            $table->boolean('prediction_correct')->nullable(); // Was the prediction correct
            $table->decimal('prediction_error', 10, 6)->nullable(); // Absolute error for regression
            $table->decimal('accuracy_score', 5, 4)->nullable(); // Accuracy of this specific prediction
            
            // Model performance at prediction time
            $table->string('model_version'); // Version of model used
            $table->json('model_metrics_snapshot')->nullable(); // Model performance when prediction made
            $table->decimal('model_drift_score', 5, 4)->nullable(); // Data drift at prediction time
            
            // Processing metadata
            $table->decimal('processing_time_ms', 8, 3)->nullable(); // Time to generate prediction
            $table->json('processing_log')->nullable(); // Detailed processing information
            $table->string('processing_node')->nullable(); // Which server/process generated this
            
            // Business context
            $table->string('requested_by_type')->nullable(); // 'coach', 'analyst', 'automated_system'
            $table->string('use_case')->nullable(); // 'lineup_optimization', 'injury_prevention', etc.
            $table->text('business_context')->nullable(); // Why this prediction was requested
            
            // Status and lifecycle
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'validated',
                'expired',
                'failed',
                'cancelled'
            ])->default('pending');
            
            $table->boolean('is_archived')->default(false);
            $table->timestamp('expires_at')->nullable(); // When prediction becomes stale
            
            // Quality assurance
            $table->json('quality_checks')->nullable(); // QA checks performed
            $table->boolean('flagged_for_review')->default(false);
            $table->text('review_notes')->nullable();
            $table->json('outlier_indicators')->nullable(); // Unusual prediction indicators
            
            // Feature importance for this prediction
            $table->json('feature_contributions')->nullable(); // How each feature contributed
            $table->json('explanation')->nullable(); // Human-readable explanation
            $table->text('interpretation_notes')->nullable();
            
            // External integrations
            $table->json('external_factors')->nullable(); // Weather, opponent strength, etc.
            $table->json('contextual_adjustments')->nullable(); // Adjustments made for context
            
            // Performance monitoring
            $table->integer('access_count')->default(0); // How many times this prediction was accessed
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('feedback_received')->nullable(); // Feedback from users about prediction
            
            // Relationships
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users');
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users');
            
            // Timestamps
            $table->timestamps();
            $table->timestamp('predicted_at')->nullable(); // When prediction was generated
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['ml_model_id', 'status']);
            $table->index(['prediction_type', 'created_at']);
            $table->index(['prediction_for_date', 'prediction_type']);
            $table->index(['confidence_score', 'status']);
            $table->index('batch_id');
            $table->index(['season', 'prediction_type']);
            $table->index(['expires_at', 'status']);
            $table->index(['flagged_for_review', 'status']);
            
            // Composite indexes for common queries
            $table->index(['ml_model_id', 'prediction_type', 'predictable_type'], 'idx_ml_pred_model_type_predictable');
            $table->index(['prediction_for_date', 'predictable_type', 'status'], 'idx_ml_pred_date_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};