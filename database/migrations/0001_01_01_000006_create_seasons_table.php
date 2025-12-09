<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Seasons Migration
 *
 * This migration consolidates:
 * - 2025_11_19_083233_create_seasons_table.php
 * - 2025_11_19_083323_create_season_statistics_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->string('name', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'status']);
            $table->index(['club_id', 'is_current']);
            $table->unique(['club_id', 'name']);
        });

        Schema::create('season_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('team_id')->nullable();
            $table->foreignId('player_id')->nullable();
            $table->string('statistic_type');
            $table->json('data');
            $table->timestamps();

            $table->index(['season_id', 'statistic_type']);
            $table->index(['team_id', 'statistic_type']);
            $table->index(['player_id', 'statistic_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_statistics');
        Schema::dropIfExists('seasons');
    }
};
