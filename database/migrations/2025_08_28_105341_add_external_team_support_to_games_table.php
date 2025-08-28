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
        Schema::table('games', function (Blueprint $table) {
            // Make away_team_id nullable for external teams
            $table->foreignId('away_team_id')->nullable()->change();
            
            // Add fields for external team support
            $table->string('away_team_name')->nullable()->after('away_team_id');
            $table->string('home_team_name')->nullable()->after('away_team_name');
            
            // Add venue code for hall numbers (like 502A160)
            $table->string('venue_code', 50)->nullable()->after('venue_address');
            
            // Add import source tracking
            $table->enum('import_source', ['manual', 'ical', 'api'])->default('manual')->after('venue_code');
            $table->string('external_game_id')->nullable()->after('import_source');
            $table->json('import_metadata')->nullable()->after('external_game_id');
            $table->string('external_url')->nullable()->after('import_metadata');
            
            // Add home/away designation clarity
            $table->boolean('is_home_game')->nullable()->after('external_url');
            
            // Add indexes for performance
            $table->index(['import_source', 'external_game_id']);
            $table->index('venue_code');
            $table->index(['away_team_name', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex(['import_source', 'external_game_id']);
            $table->dropIndex(['venue_code']);
            $table->dropIndex(['away_team_name', 'scheduled_at']);
            
            // Drop new columns
            $table->dropColumn([
                'away_team_name',
                'home_team_name', 
                'venue_code',
                'import_source',
                'external_game_id',
                'import_metadata',
                'external_url',
                'is_home_game'
            ]);
            
            // Make away_team_id required again
            $table->foreignId('away_team_id')->nullable(false)->change();
        });
    }
};
