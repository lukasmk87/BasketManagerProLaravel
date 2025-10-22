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
        // Get database driver
        $driver = DB::connection()->getDriverName();

        // Skip this migration for SQLite as it has limitations with foreign key column drops
        // This is a schema cleanup migration from a previous refactor and not critical for new installations
        if ($driver === 'sqlite') {
            return;
        }

        // Check if team_id column exists and handle its removal
        if (Schema::hasColumn('players', 'team_id')) {
            Schema::table('players', function (Blueprint $table) use ($driver) {
                // Drop the unique constraint first (from original migration)
                try {
                    $table->dropUnique('unique_jersey_per_team');
                } catch (\Exception $e) {
                    // Constraint might not exist or have different name
                }

                // Drop any indexes that depend on team_id
                try {
                    $table->dropIndex(['team_id', 'status']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['jersey_number', 'team_id']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                // Remove the team_id foreign key constraint (MySQL only)
                // SQLite will handle foreign key removal when dropping the column
                if ($driver === 'mysql') {
                    try {
                        $table->dropForeign('players_team_id_foreign');
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign(['team_id']);
                        } catch (\Exception $e) {
                            // Foreign key might not exist
                        }
                    }
                }

                // Drop the team_id column
                $table->dropColumn('team_id');
            });
        }
        
        // Drop other team-specific columns that are now in pivot table
        $columnsToRemove = [
            'jersey_number',
            'primary_position', 
            'secondary_positions',
            'is_starter',
            'is_captain',
            'contract_start',
            'contract_end',
            'registration_number',
            'is_registered',
            'registered_at',
            'games_played',
            'games_started',
            'minutes_played',
            'points_scored'
        ];
        
        foreach ($columnsToRemove as $column) {
            if (Schema::hasColumn('players', $column)) {
                Schema::table('players', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get database driver
        $driver = DB::connection()->getDriverName();

        // Skip this migration rollback for SQLite
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('players', function (Blueprint $table) {
            // Restore the columns
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('jersey_number')->nullable();
            $table->enum('primary_position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable();
            $table->json('secondary_positions')->nullable();
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_captain')->default(false);
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('registration_number')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('minutes_played')->default(0);
            $table->integer('points_scored')->default(0);
        });
    }
};