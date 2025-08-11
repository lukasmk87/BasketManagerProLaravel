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
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            
            // Basic model information
            $table->string('name');
            $table->string('type'); // 'player_performance', 'injury_risk', 'game_outcome', 'shot_success'
            $table->string('algorithm'); // 'random_forest', 'linear_regression', 'neural_network', etc.
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            
            // Model configuration
            $table->json('parameters')->nullable(); // Hyperparameters
            $table->json('features')->nullable(); // Input features used
            $table->json('target_variables')->nullable(); // What the model predicts
            $table->json('preprocessing_config')->nullable(); // Data preprocessing settings
            
            // Training information
            $table->integer('training_samples')->nullable();
            $table->integer('validation_samples')->nullable();
            $table->integer('test_samples')->nullable();
            $table->json('training_metrics')->nullable(); // accuracy, precision, recall, f1, etc.
            $table->json('validation_metrics')->nullable();
            $table->json('test_metrics')->nullable();
            
            // Model performance and reliability
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->decimal('auc_score', 5, 4)->nullable(); // Area Under Curve for classification
            $table->decimal('rmse', 10, 6)->nullable(); // Root Mean Square Error for regression
            $table->decimal('mae', 10, 6)->nullable(); // Mean Absolute Error
            
            // Model storage and deployment
            $table->string('file_path')->nullable(); // Path to serialized model file
            $table->bigInteger('file_size')->nullable(); // Size in bytes
            $table->string('storage_format')->default('pickle'); // 'pickle', 'joblib', 'onnx', 'tensorflow'
            $table->json('dependencies')->nullable(); // Required libraries and versions
            
            // Status and lifecycle
            $table->enum('status', [
                'training',
                'trained',
                'validating', 
                'validated',
                'deployed',
                'deprecated',
                'failed',
                'archived'
            ])->default('training');
            
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_retrain')->default(false);
            $table->timestamp('last_trained_at')->nullable();
            $table->timestamp('last_prediction_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            
            // Feature importance and interpretability
            $table->json('feature_importance')->nullable(); // Feature importance scores
            $table->json('model_interpretation')->nullable(); // SHAP values, permutation importance
            $table->text('interpretability_notes')->nullable();
            
            // Performance monitoring
            $table->integer('prediction_count')->default(0);
            $table->integer('successful_predictions')->default(0);
            $table->integer('failed_predictions')->default(0);
            $table->decimal('average_prediction_time', 8, 3)->nullable(); // in milliseconds
            $table->decimal('drift_score', 5, 4)->nullable(); // Data drift detection score
            
            // Basketball-specific metadata
            $table->json('applicable_positions')->nullable(); // For which player positions
            $table->json('applicable_scenarios')->nullable(); // Game situations where model applies
            $table->integer('min_games_required')->nullable(); // Minimum games needed for prediction
            $table->integer('optimal_sample_size')->nullable();
            
            // Data quality requirements
            $table->json('data_requirements')->nullable(); // Required data quality standards
            $table->decimal('min_data_completeness', 5, 4)->default(0.8000); // Minimum data completeness
            $table->integer('min_historical_days')->default(30); // Minimum historical data period
            
            // Audit and compliance
            $table->json('training_log')->nullable(); // Detailed training process log
            $table->json('validation_results')->nullable(); // Cross-validation results
            $table->text('bias_assessment')->nullable(); // Bias and fairness analysis
            $table->text('ethical_considerations')->nullable();
            
            // Relationships
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->foreignId('parent_model_id')->nullable()->constrained('ml_models'); // For model versioning
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['is_active', 'status']);
            $table->index('last_trained_at');
            $table->index('accuracy');
            $table->index(['type', 'is_active', 'accuracy']);
            
            // Constraints
            $table->unique(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_models');
    }
};