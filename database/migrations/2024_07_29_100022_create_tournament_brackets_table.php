<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained(); // Will be created when game is scheduled
            
            // Bracket Structure
            $table->string('bracket_type')->default('main'); // main, consolation, third_place
            $table->integer('round'); // 1 = First round, 2 = Second round, etc.
            $table->string('round_name'); // "Round of 16", "Quarterfinal", etc.
            $table->integer('position_in_round'); // Position within the round
            $table->integer('total_rounds'); // Total rounds in this bracket
            
            // Team Progression
            $table->foreignId('team1_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('team2_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('winner_team_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('loser_team_id')->nullable()->constrained('tournament_teams');
            
            // Advancement Rules
            $table->foreignId('winner_advances_to')->nullable()->constrained('tournament_brackets');
            $table->foreignId('loser_advances_to')->nullable()->constrained('tournament_brackets');
            
            // Game Details
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('court')->nullable();
            $table->foreignId('primary_referee_id')->nullable()->constrained('users');
            $table->foreignId('secondary_referee_id')->nullable()->constrained('users');
            $table->string('scorekeeper')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'scheduled', 'in_progress', 'completed', 'bye', 'forfeit', 'cancelled'
            ])->default('pending');
            
            // Seeding Information
            $table->integer('team1_seed')->nullable();
            $table->integer('team2_seed')->nullable();
            $table->string('matchup_description')->nullable(); // "Winner of Game 1 vs Winner of Game 2"
            
            // Results
            $table->integer('team1_score')->nullable();
            $table->integer('team2_score')->nullable();
            $table->json('score_by_period')->nullable();
            $table->boolean('overtime')->default(false);
            $table->integer('overtime_periods')->default(0);
            $table->text('game_notes')->nullable();
            
            // Forfeit Information
            $table->foreignId('forfeit_team_id')->nullable()->constrained('tournament_teams');
            $table->text('forfeit_reason')->nullable();
            
            // Timing
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->integer('actual_duration')->nullable(); // minutes
            
            // Group Stage specific
            $table->string('group_name')->nullable();
            $table->integer('group_round')->nullable();
            
            // Swiss System specific
            $table->integer('swiss_round')->nullable();
            $table->decimal('swiss_rating_change', 8, 2)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'bracket_type', 'round']);
            $table->index(['tournament_id', 'status']);
            $table->index(['scheduled_at']);
            $table->index(['team1_id', 'team2_id']);
            $table->index(['winner_team_id']);
            $table->index(['group_name', 'group_round']);
            $table->index(['swiss_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_brackets');
    }
};