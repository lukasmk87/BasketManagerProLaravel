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
        Schema::table('gym_time_slot_team_assignments', function (Blueprint $table) {
            // Add court_id as optional foreign key to gym_courts table
            $table->foreignId('gym_court_id')
                ->nullable()
                ->after('team_id')
                ->constrained('gym_courts')
                ->onDelete('set null');
            
            // Add index for performance
            $table->index(['gym_court_id', 'day_of_week', 'start_time'], 'idx_court_day_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_time_slot_team_assignments', function (Blueprint $table) {
            // Drop index first, then foreign key constraint
            $table->dropIndex('idx_court_day_start');
            $table->dropForeign(['gym_court_id']);
            $table->dropColumn('gym_court_id');
        });
    }
};