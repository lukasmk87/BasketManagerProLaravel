<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing team_id data from players table to player_team pivot table
        DB::statement('
            INSERT INTO player_team (
                player_id, 
                team_id, 
                jersey_number,
                primary_position,
                secondary_positions,
                is_active,
                is_starter,
                is_captain,
                status,
                joined_at,
                contract_start,
                contract_end,
                registration_number,
                is_registered,
                registered_at,
                games_played,
                games_started,
                minutes_played,
                points_scored,
                notes,
                created_at,
                updated_at
            )
            SELECT 
                p.id as player_id,
                p.team_id,
                p.jersey_number,
                p.primary_position,
                p.secondary_positions,
                CASE WHEN p.status = "active" THEN 1 ELSE 0 END as is_active,
                p.is_starter,
                p.is_captain,
                p.status,
                COALESCE(p.registered_at, p.created_at) as joined_at,
                p.contract_start,
                p.contract_end,
                p.registration_number,
                p.is_registered,
                p.registered_at,
                p.games_played,
                p.games_started,
                p.minutes_played,
                p.points_scored,
                NULL as notes,
                p.created_at,
                p.updated_at
            FROM players p 
            WHERE p.team_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all data from player_team table (will restore from players.team_id)
        DB::table('player_team')->truncate();
    }
};