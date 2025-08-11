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
        Schema::create('ml_training_data', function (Blueprint $table) {
            $table->id();
            
            // Data identification and versioning
            $table->string('dataset_name'); // Logical name for the dataset
            $table->string('dataset_version', 20)->default('1.0.0');
            $table->string('data_hash', 64)->unique(); // SHA-256 hash of the data for integrity
            $table->string('partition_key')->nullable(); // For data partitioning (date, season, etc.)
            
            // Source and target information
            $table->morphs('source_entity'); // What entity this training data represents
            $table->string('data_type'); // 'player_performance', 'injury_data', 'game_outcome', 'shot_data'
            $table->string('data_subtype')->nullable(); // More specific categorization
            
            // Feature data
            $table->json('features'); // The input features for training
            $table->json('feature_names'); // Names/descriptions of features
            $table->json('feature_types'); // Data types of each feature (numeric, categorical, etc.)
            $table->json('feature_metadata')->nullable(); // Additional metadata about features
            
            // Target/label data
            $table->json('targets'); // The target variables (what we want to predict)
            $table->json('target_names'); // Names of target variables
            $table->json('target_types'); // Data types of targets
            $table->json('target_metadata')->nullable(); // Additional metadata about targets
            
            // Data quality and preprocessing
            $table->decimal('data_completeness', 5, 4)->nullable(); // Percentage of complete data
            $table->json('missing_values_info')->nullable(); // Information about missing values
            $table->json('outlier_info')->nullable(); // Outlier detection results
            $table->json('preprocessing_applied')->nullable(); // What preprocessing was done
            $table->json('normalization_params')->nullable(); // Parameters for data normalization
            
            // Time context
            $table->timestamp('data_start_date'); // Start of data period
            $table->timestamp('data_end_date'); // End of data period
            $table->string('season')->nullable(); // Basketball season
            $table->integer('games_included')->nullable(); // Number of games in dataset
            $table->integer('players_included')->nullable(); // Number of unique players
            
            // Basketball-specific context
            $table->json('team_ids')->nullable(); // Teams included in dataset
            $table->json('player_positions')->nullable(); // Player positions in dataset
            $table->json('game_types')->nullable(); // Types of games (regular, playoff, preseason)
            $table->json('opponent_strength')->nullable(); // Strength of opponents
            $table->json('venue_types')->nullable(); // Home/away/neutral games
            
            // Statistical information
            $table->integer('total_samples'); // Total number of data points
            $table->integer('positive_samples')->nullable(); // For classification problems
            $table->integer('negative_samples')->nullable();
            $table->json('class_distribution')->nullable(); // Distribution of classes
            $table->json('feature_statistics')->nullable(); // Mean, std, min, max for each feature
            $table->json('correlation_matrix')->nullable(); // Feature correlations
            
            // Data partitioning for ML
            $table->enum('partition_type', ['train', 'validation', 'test', 'holdout'])->nullable();
            $table->decimal('train_ratio', 5, 4)->nullable(); // Ratio used for training
            $table->decimal('validation_ratio', 5, 4)->nullable();
            $table->decimal('test_ratio', 5, 4)->nullable();
            $table->string('split_strategy')->nullable(); // How data was split
            
            // Data lineage and provenance
            $table->json('source_tables')->nullable(); // Which database tables data came from
            $table->json('source_queries')->nullable(); // Queries used to extract data
            $table->text('extraction_logic')->nullable(); // Business logic for data extraction
            $table->json('transformation_log')->nullable(); // Log of transformations applied
            
            // Validation and quality assurance
            $table->json('validation_rules')->nullable(); // Rules applied to validate data
            $table->json('validation_results')->nullable(); // Results of validation checks
            $table->boolean('data_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->text('quality_notes')->nullable();
            
            // Usage tracking
            $table->integer('models_trained')->default(0); // How many models used this data
            $table->timestamp('last_used_at')->nullable();
            $table->json('usage_history')->nullable(); // History of model training with this data
            
            // Storage and access
            $table->string('storage_path')->nullable(); // Path to stored data file
            $table->string('storage_format')->default('parquet'); // Format of stored data
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('compression')->nullable(); // Compression method used
            $table->json('access_permissions')->nullable(); // Who can access this data
            
            // Performance and bias considerations
            $table->json('bias_analysis')->nullable(); // Analysis of potential biases
            $table->json('fairness_metrics')->nullable(); // Fairness across different groups
            $table->json('demographic_breakdown')->nullable(); // Breakdown by player demographics
            $table->text('ethical_considerations')->nullable();
            
            // External data integration
            $table->json('external_data_sources')->nullable(); // External APIs, databases used
            $table->json('weather_data')->nullable(); // Weather conditions during games
            $table->json('injury_context')->nullable(); // Injury status during data period
            $table->json('team_dynamics')->nullable(); // Team chemistry, coaching changes
            
            // Status and lifecycle
            $table->enum('status', [
                'collecting',
                'processing',
                'validating',
                'ready',
                'in_use',
                'deprecated',
                'archived',
                'failed'
            ])->default('collecting');
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_synthetic')->default(false); // Is this synthetic/generated data
            $table->timestamp('expires_at')->nullable(); // When data becomes stale
            
            // Compliance and governance
            $table->json('privacy_settings')->nullable(); // Privacy and GDPR compliance
            $table->json('retention_policy')->nullable(); // How long to keep this data
            $table->text('legal_notes')->nullable();
            $table->boolean('anonymized')->default(false);
            
            // Relationships
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->foreignId('parent_dataset_id')->nullable()->constrained('ml_training_data');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['dataset_name', 'dataset_version']);
            $table->index(['data_type', 'status']);
            $table->index(['source_entity_type', 'source_entity_id']);
            $table->index(['data_start_date', 'data_end_date']);
            $table->index(['season', 'data_type']);
            $table->index(['is_active', 'status']);
            $table->index(['partition_type', 'data_type']);
            $table->index('last_used_at');
            $table->index(['total_samples', 'data_type']);
            
            // Composite indexes
            $table->index(['data_type', 'season', 'is_active']);
            $table->index(['dataset_name', 'dataset_version', 'partition_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_training_data');
    }
};