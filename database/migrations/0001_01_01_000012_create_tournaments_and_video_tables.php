<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Tournaments and Video Migration
 *
 * Includes: tournaments, tournament_teams, tournament_brackets, tournament_games,
 * tournament_officials, tournament_awards, video_files, video_annotations, video_analysis_sessions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tournaments
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('club_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin', 'swiss', 'group_stage'])->default('single_elimination');
            $table->enum('status', ['draft', 'registration', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->integer('max_teams')->nullable();
            $table->json('rules')->nullable();
            $table->json('schedule')->nullable();
            $table->json('prizes')->nullable();
            $table->decimal('entry_fee', 8, 2)->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Tournament Teams
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('external_team_name')->nullable();
            $table->integer('seed')->nullable();
            $table->enum('status', ['registered', 'confirmed', 'eliminated', 'winner'])->default('registered');
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id']);
        });

        // Tournament Brackets
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->string('round_name');
            $table->integer('round_number');
            $table->integer('match_number');
            $table->foreignId('team1_id')->nullable()->constrained('tournament_teams')->onDelete('set null');
            $table->foreignId('team2_id')->nullable()->constrained('tournament_teams')->onDelete('set null');
            $table->foreignId('winner_id')->nullable()->constrained('tournament_teams')->onDelete('set null');
            $table->foreignId('game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->timestamps();
        });

        // Tournament Games (additional metadata)
        Schema::create('tournament_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('bracket_id')->nullable()->constrained('tournament_brackets')->onDelete('set null');
            $table->string('round')->nullable();
            $table->integer('game_number')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'game_id']);
        });

        // Tournament Officials
        Schema::create('tournament_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['director', 'referee', 'scorekeeper', 'coordinator'])->default('referee');
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id', 'role']);
        });

        // Tournament Awards
        Schema::create('tournament_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->string('award_type');
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Video Files
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('game_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->enum('status', ['uploading', 'processing', 'ready', 'failed'])->default('uploading');
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Video Annotations
        Schema::create('video_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained('video_files')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->integer('timestamp_seconds');
            $table->integer('duration_seconds')->nullable();
            $table->text('content');
            $table->enum('type', ['note', 'highlight', 'mistake', 'tactical', 'other'])->default('note');
            $table->json('tags')->nullable();
            $table->json('drawing_data')->nullable();
            $table->timestamps();

            $table->index(['video_file_id', 'timestamp_seconds']);
        });

        // Video Analysis Sessions
        Schema::create('video_analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained('video_files')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed'])->default('draft');
            $table->json('participants')->nullable();
            $table->json('focus_points')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_analysis_sessions');
        Schema::dropIfExists('video_annotations');
        Schema::dropIfExists('video_files');
        Schema::dropIfExists('tournament_awards');
        Schema::dropIfExists('tournament_officials');
        Schema::dropIfExists('tournament_games');
        Schema::dropIfExists('tournament_brackets');
        Schema::dropIfExists('tournament_teams');
        Schema::dropIfExists('tournaments');
    }
};
