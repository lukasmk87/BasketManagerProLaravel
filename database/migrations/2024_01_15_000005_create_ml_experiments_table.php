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
        Schema::create('ml_experiments', function (Blueprint $table) {
            $table->id();
            
            // Experiment identification
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('experiment_type'); // 'model_comparison', 'hyperparameter_tuning', 'feature_selection'
            $table->string('objective'); // 'maximize_accuracy', 'minimize_rmse', 'optimize_f1'
            $table->json('hypothesis')->nullable(); // What we're testing
            
            // Experimental setup
            $table->json('experimental_design')->nullable(); // Study design details
            $table->json('success_criteria')->nullable(); // How to measure success
            $table->json('baseline_metrics')->nullable(); // Baseline to compare against
            $table->integer('target_sample_size')->nullable();
            $table->decimal('statistical_power', 5, 4)->nullable(); // Desired statistical power
            $table->decimal('significance_level', 5, 4)->default(0.0500); // Alpha level
            
            // Models being tested
            $table->json('model_configurations')->nullable(); // Different models/configs tested
            $table->json('hyperparameter_space')->nullable(); // Parameter ranges explored
            $table->json('feature_sets')->nullable(); // Different feature combinations
            $table->json('data_splits')->nullable(); // Train/validation/test splits
            
            // Experiment execution
            $table->enum('status', [
                'planned',
                'running',
                'completed',
                'failed',
                'cancelled',
                'analyzing'
            ])->default('planned');
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('iterations_completed')->default(0);
            $table->integer('total_iterations')->nullable();
            
            // Resource usage
            $table->json('computational_resources')->nullable(); // CPU, GPU, memory used
            $table->integer('total_compute_hours')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable(); // Resource cost
            $table->json('infrastructure_details')->nullable(); // Where experiments ran
            
            // Results and analysis
            $table->json('results_summary')->nullable(); // Key findings
            $table->json('model_performances')->nullable(); // Performance of each model
            $table->json('statistical_tests')->nullable(); // Significance tests performed
            $table->json('confidence_intervals')->nullable(); // Statistical confidence intervals
            $table->text('conclusions')->nullable(); // Main conclusions drawn
            
            // Best model identification
            $table->foreignId('best_model_id')->nullable()->constrained('ml_models');
            $table->json('best_model_metrics')->nullable(); // Performance of best model
            $table->json('winning_configuration')->nullable(); // Best hyperparameters/setup
            $table->decimal('improvement_over_baseline', 10, 6)->nullable(); // % improvement
            $table->boolean('statistically_significant')->nullable();
            
            // Basketball-specific experiment context
            $table->string('basketball_domain')->nullable(); // 'player_analysis', 'team_strategy', etc.
            $table->json('applicable_positions')->nullable(); // Player positions relevant to
            $table->json('game_contexts')->nullable(); // Game situations this applies to
            $table->string('season_focus')->nullable(); // Season data was focused on
            
            // Data and feature analysis
            $table->json('feature_importance_analysis')->nullable(); // Which features mattered most
            $table->json('data_quality_impact')->nullable(); // How data quality affected results
            $table->json('sample_size_analysis')->nullable(); // Impact of different sample sizes
            $table->json('bias_analysis')->nullable(); // Bias detection and mitigation
            
            // Cross-validation and robustness
            $table->json('cross_validation_results')->nullable(); // CV performance metrics
            $table->integer('cv_folds')->default(5); // Number of CV folds used
            $table->json('robustness_tests')->nullable(); // Sensitivity analysis results
            $table->json('out_of_sample_performance')->nullable(); // Performance on unseen data
            
            // Experiment tracking and versioning
            $table->string('version', 20)->default('1.0.0');
            $table->foreignId('parent_experiment_id')->nullable()->constrained('ml_experiments');
            $table->json('version_changes')->nullable(); // What changed from parent
            $table->json('experiment_artifacts')->nullable(); // Generated files, plots, etc.
            
            // Reproducibility
            $table->json('environment_config')->nullable(); // Software versions, seeds, etc.
            $table->integer('random_seed')->nullable();
            $table->string('code_version')->nullable(); // Git commit or version
            $table->json('dependency_versions')->nullable(); // Library versions used
            $table->text('reproduction_notes')->nullable();
            
            // Business impact and implementation
            $table->json('business_metrics')->nullable(); // Real-world impact measures
            $table->decimal('expected_roi', 10, 2)->nullable(); // Return on investment
            $table->json('implementation_plan')->nullable(); // How to deploy findings
            $table->enum('implementation_status', [
                'not_planned',
                'planned',
                'in_progress',
                'implemented',
                'rolled_back'
            ])->default('not_planned');
            
            // Quality assurance
            $table->enum('review_status', [
                'unreviewed',
                'under_review',
                'approved',
                'rejected',
                'needs_revision'
            ])->default('unreviewed');
            
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->json('peer_review_feedback')->nullable();
            
            // Documentation and sharing
            $table->text('methodology_notes')->nullable(); // Detailed methodology
            $table->json('visualizations')->nullable(); // Charts, graphs, plots created
            $table->json('publication_references')->nullable(); // Related papers, articles
            $table->boolean('is_publishable')->default(false);
            $table->enum('sharing_level', [
                'private',
                'team_only',
                'organization',
                'public'
            ])->default('team_only');
            
            // Error tracking and debugging
            $table->integer('error_count')->default(0);
            $table->json('error_log')->nullable(); // Errors encountered
            $table->text('debugging_notes')->nullable();
            $table->json('performance_issues')->nullable(); // Performance bottlenecks
            
            // Follow-up and iteration
            $table->json('future_work_suggestions')->nullable(); // Ideas for next experiments
            $table->json('limitations_identified')->nullable(); // Known limitations
            $table->boolean('requires_followup')->default(false);
            $table->json('followup_experiments')->nullable(); // Planned next experiments
            
            // Collaboration
            $table->json('team_members')->nullable(); // Who worked on this experiment
            $table->json('stakeholder_feedback')->nullable(); // Feedback from business stakeholders
            $table->json('expert_validation')->nullable(); // Validation from domain experts
            
            // Audit and governance
            $table->boolean('follows_ml_guidelines')->default(true); // Follows best practices
            $table->json('ethical_considerations')->nullable(); // Ethics review
            $table->json('compliance_checks')->nullable(); // Regulatory compliance
            $table->text('risk_assessment')->nullable();
            
            // Relationships
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['experiment_type', 'status']);
            $table->index(['started_at', 'completed_at']);
            $table->index(['basketball_domain', 'season_focus']);
            $table->index(['review_status', 'implementation_status']);
            $table->index(['is_publishable', 'sharing_level']);
            $table->index(['created_by_user_id', 'status']);
            $table->index(['best_model_id', 'statistically_significant']);
            $table->index(['requires_followup', 'status']);
            
            // Unique constraint for experiment identity
            $table->unique(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_experiments');
    }
};