<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            
            // Basic Information
            $table->string('name');
            $table->text('description');
            $table->text('objectives'); // What this drill aims to achieve
            $table->text('instructions'); // Step-by-step instructions
            
            // Classification
            $table->enum('category', [
                'ball_handling', 'shooting', 'passing', 'defense', 'rebounding',
                'conditioning', 'agility', 'footwork', 'team_offense', 'team_defense',
                'transition', 'set_plays', 'scrimmage', 'warm_up', 'cool_down'
            ]);
            
            $table->enum('sub_category', [
                'fundamental', 'advanced', 'position_specific', 'game_situation',
                'individual', 'small_group', 'team', 'competitive'
            ])->nullable();
            
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->enum('age_group', ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'all']);
            
            // Logistics
            $table->integer('min_players')->default(1);
            $table->integer('max_players')->nullable();
            $table->integer('optimal_players')->nullable();
            $table->integer('estimated_duration')->default(10); // minutes
            $table->decimal('space_required', 5, 2)->nullable(); // square meters
            
            // Equipment
            $table->json('required_equipment')->nullable(); // balls, cones, etc.
            $table->json('optional_equipment')->nullable();
            $table->boolean('requires_full_court')->default(false);
            $table->boolean('requires_half_court')->default(false);
            
            // Variations and Progressions
            $table->text('variations')->nullable();
            $table->text('progressions')->nullable(); // How to make it harder
            $table->text('regressions')->nullable(); // How to make it easier
            $table->json('coaching_points')->nullable(); // Key points to emphasize
            
            // Metrics and Evaluation
            $table->json('measurable_outcomes')->nullable(); // What can be measured
            $table->json('success_criteria')->nullable();
            $table->boolean('is_competitive')->default(false);
            $table->text('scoring_system')->nullable();
            
            // Media and Diagrams
            $table->string('diagram_path')->nullable();
            $table->json('diagram_annotations')->nullable();
            $table->boolean('has_video')->default(false);
            $table->integer('video_duration')->nullable(); // seconds
            
            // Usage and Popularity
            $table->integer('usage_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            
            // Tags and Search
            $table->json('tags')->nullable();
            $table->text('search_keywords')->nullable();
            $table->string('source')->nullable(); // Where this drill comes from
            $table->string('author')->nullable(); // Original author/coach
            
            // Status and Approval
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived']);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category', 'difficulty_level']);
            $table->index(['age_group', 'status']);
            $table->index(['is_public', 'status']);
            $table->index('usage_count');
            
            // Fulltext index only for MySQL/PostgreSQL, not SQLite
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name', 'description', 'search_keywords']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drills');
    }
};