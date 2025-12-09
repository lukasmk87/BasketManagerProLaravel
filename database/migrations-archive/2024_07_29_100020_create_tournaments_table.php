<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            
            // Organization and Ownership
            $table->foreignId('organizer_id')->constrained('users'); // Tournament Organizer
            $table->foreignId('club_id')->nullable()->constrained(); // Hosting Club (optional)
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            
            // Tournament Configuration
            $table->enum('type', [
                'single_elimination', 'double_elimination', 'round_robin',
                'swiss_system', 'group_stage_knockout', 'ladder'
            ])->default('single_elimination');
            $table->enum('category', ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'mixed']);
            $table->enum('gender', ['male', 'female', 'mixed'])->default('mixed');
            
            // Schedule
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_start');
            $table->date('registration_end');
            $table->time('daily_start_time')->default('09:00');
            $table->time('daily_end_time')->default('18:00');
            
            // Team Limits
            $table->integer('min_teams')->default(4);
            $table->integer('max_teams')->default(32);
            $table->integer('registered_teams')->default(0);
            $table->decimal('entry_fee', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            
            // Venue Information
            $table->string('primary_venue');
            $table->text('venue_address')->nullable();
            $table->json('additional_venues')->nullable();
            $table->integer('available_courts')->default(1);
            
            // Game Rules and Settings
            $table->json('game_rules')->nullable();
            $table->integer('game_duration')->default(40); // minutes
            $table->integer('periods')->default(4);
            $table->integer('period_length')->default(10); // minutes
            $table->boolean('overtime_enabled')->default(true);
            $table->integer('overtime_length')->default(5);
            $table->boolean('shot_clock_enabled')->default(true);
            $table->integer('shot_clock_seconds')->default(24);
            
            // Tournament Structure
            $table->integer('groups_count')->nullable(); // For group stage tournaments
            $table->json('seeding_rules')->nullable();
            $table->boolean('third_place_game')->default(true);
            $table->json('advancement_rules')->nullable();
            
            // Prizes and Awards
            $table->json('prizes')->nullable(); // 1st, 2nd, 3rd place prizes
            $table->json('awards')->nullable(); // MVP, Best Player, etc.
            $table->decimal('total_prize_money', 10, 2)->nullable();
            
            // Status and Workflow
            $table->enum('status', [
                'draft', 'registration_open', 'registration_closed',
                'in_progress', 'completed', 'cancelled'
            ])->default('draft');
            
            $table->boolean('is_public')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('allows_spectators')->default(true);
            $table->decimal('spectator_fee', 6, 2)->nullable();
            
            // Streaming and Media
            $table->boolean('livestream_enabled')->default(false);
            $table->string('livestream_url')->nullable();
            $table->json('social_media_links')->nullable();
            $table->boolean('photography_allowed')->default(true);
            
            // Contact and Support
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('covid_requirements')->nullable();
            
            // Statistics and Analytics
            $table->integer('total_games')->default(0);
            $table->integer('completed_games')->default(0);
            $table->decimal('average_game_duration', 5, 2)->nullable();
            $table->integer('total_spectators')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'start_date']);
            $table->index(['category', 'gender']);
            $table->index(['registration_start', 'registration_end']);
            $table->index('is_public');
            // Fulltext index only for MySQL/PostgreSQL, not SQLite
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};