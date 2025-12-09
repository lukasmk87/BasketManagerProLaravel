<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            
            // Award Information
            $table->string('award_name');
            $table->text('description')->nullable();
            $table->enum('award_type', [
                'team_award', 'individual_award', 'special_recognition',
                'statistical_award', 'sportsmanship_award'
            ]);
            $table->enum('award_category', [
                'champion', 'runner_up', 'third_place', 'mvp', 'best_player',
                'top_scorer', 'best_defense', 'most_rebounds', 'most_assists',
                'most_steals', 'most_blocks', 'best_coach', 'sportsmanship',
                'most_improved', 'rookie_of_tournament', 'all_tournament_team'
            ]);
            
            // Recipient Information
            $table->foreignId('recipient_team_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('recipient_player_id')->nullable()->constrained('users'); // Player as user
            $table->foreignId('recipient_coach_id')->nullable()->constrained('users');
            $table->string('recipient_name')->nullable(); // For non-user recipients
            
            // Award Details
            $table->json('criteria')->nullable(); // What qualifies for this award
            $table->json('statistics')->nullable(); // Supporting statistics
            $table->decimal('statistical_value', 10, 2)->nullable(); // Points, rebounds, etc.
            $table->string('statistical_unit')->nullable(); // 'per game', 'total', etc.
            
            // Selection Process
            $table->enum('selection_method', [
                'automatic', 'committee_vote', 'fan_vote', 'peer_vote', 'statistical'
            ]);
            $table->json('voting_details')->nullable(); // Vote counts, percentages
            $table->foreignId('selected_by_user_id')->nullable()->constrained('users');
            $table->timestamp('selected_at')->nullable();
            
            // Award Presentation
            $table->boolean('presented')->default(false);
            $table->timestamp('presentation_date')->nullable();
            $table->string('presentation_ceremony')->nullable();
            $table->text('presentation_notes')->nullable();
            
            // Physical Award
            $table->enum('award_format', ['trophy', 'medal', 'certificate', 'plaque', 'other']);
            $table->string('award_sponsor')->nullable();
            $table->decimal('award_value', 8, 2)->nullable();
            $table->text('engraving_text')->nullable();
            
            // Media and Recognition
            $table->string('photo_path')->nullable();
            $table->text('press_release')->nullable();
            $table->json('social_media_posts')->nullable();
            $table->boolean('featured_on_website')->default(true);
            
            // Historical Context
            $table->boolean('record_setting')->default(false);
            $table->text('record_details')->nullable();
            $table->json('comparison_data')->nullable(); // How it compares to previous years
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'award_category']);
            $table->index(['tournament_id', 'award_type']);
            $table->index(['recipient_team_id']);
            $table->index(['recipient_player_id']);
            $table->index(['selected_at']);
            $table->index(['presentation_date']);
            $table->index(['record_setting']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_awards');
    }
};