<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drill_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            
            // Rating Information
            $table->integer('rating'); // 1-5 stars
            $table->text('comment')->nullable();
            $table->text('pros')->nullable(); // What they liked
            $table->text('cons')->nullable(); // What could be improved
            
            // Context of Rating
            $table->enum('context', [
                'after_training', 'planning_session', 'drill_review',
                'team_evaluation', 'general_review'
            ])->nullable();
            
            $table->foreignId('team_id')->nullable()->constrained(); // Team context
            $table->string('age_group_used')->nullable(); // Age group when used
            $table->integer('session_count')->nullable(); // How many times used
            
            // Usefulness Ratings
            $table->integer('effectiveness_rating')->nullable(); // 1-10
            $table->integer('engagement_rating')->nullable(); // 1-10
            $table->integer('difficulty_rating')->nullable(); // 1-10
            $table->boolean('would_recommend')->default(true);
            
            // Modification Suggestions
            $table->text('suggested_modifications')->nullable();
            $table->json('improvement_suggestions')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['drill_id', 'rating']);
            $table->index(['user_id', 'rating']);
            $table->unique(['drill_id', 'user_id']); // One rating per user per drill
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drill_ratings');
    }
};