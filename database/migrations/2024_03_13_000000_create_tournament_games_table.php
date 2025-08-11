<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('tournament_bracket_id')->nullable()->constrained();
            $table->foreignId('base_game_id')->constrained('games'); // Reference to main games table
            
            // Tournament-specific game information
            $table->string('tournament_game_number')->nullable(); // "Game 1", "Game 2A", etc.
            $table->integer('importance_level')->default(1); // 1-5, 5 being final
            $table->boolean('is_featured_game')->default(false);
            
            // Extended Game Rules for Tournament
            $table->json('special_rules')->nullable(); // Tournament-specific rule modifications
            $table->boolean('mercy_rule_enabled')->default(true);
            $table->integer('mercy_rule_points')->default(30);
            $table->integer('mercy_rule_period')->default(4);
            
            // Media and Broadcasting
            $table->boolean('livestream_scheduled')->default(false);
            $table->string('livestream_url')->nullable();
            $table->boolean('recording_enabled')->default(false);
            $table->json('media_assignments')->nullable(); // Photographers, videographers
            
            // Officials Assignment
            $table->foreignId('head_referee_id')->nullable()->constrained('users');
            $table->foreignId('assistant_referee_id')->nullable()->constrained('users');
            $table->foreignId('scorekeeper_id')->nullable()->constrained('users');
            $table->foreignId('timekeeper_id')->nullable()->constrained('users');
            $table->decimal('officials_fee', 6, 2)->nullable();
            
            // Statistics Tracking
            $table->boolean('detailed_stats_enabled')->default(true);
            $table->json('stat_categories')->nullable(); // Which stats to track
            $table->foreignId('stats_recorder_id')->nullable()->constrained('users');
            
            // Spectator Information
            $table->integer('expected_spectators')->nullable();
            $table->integer('actual_spectators')->nullable();
            $table->decimal('ticket_sales', 8, 2)->default(0);
            $table->integer('tickets_sold')->default(0);
            
            // Game Atmosphere
            $table->integer('atmosphere_rating')->nullable(); // 1-10
            $table->text('atmosphere_notes')->nullable();
            $table->json('special_events')->nullable(); // Halftime shows, ceremonies, etc.
            
            // Post-Game
            $table->text('game_recap')->nullable();
            $table->json('player_of_game')->nullable(); // One per team or overall
            $table->json('key_moments')->nullable(); // Important plays, momentum changes
            $table->decimal('game_rating', 3, 2)->nullable(); // Overall game quality 1-10
            
            // Tournament Progression Impact
            $table->json('advancement_implications')->nullable(); // What this result means
            $table->boolean('elimination_game')->default(false);
            $table->boolean('championship_implications')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'importance_level']);
            $table->index(['livestream_scheduled']);
            $table->index(['is_featured_game']);
            $table->index(['elimination_game']);
            $table->index(['championship_implications']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_games');
    }
};