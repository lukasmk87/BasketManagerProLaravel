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
        // Add win_percentage to teams table
        if (! Schema::hasColumn('teams', 'win_percentage')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->decimal('win_percentage', 5, 2)->nullable()->default(0)->after('is_active')
                    ->comment('Team win percentage (0-100)');
            });
        }

        // Add birth_date to users table
        if (! Schema::hasColumn('users', 'birth_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('birth_date')->nullable()->after('email')
                    ->comment('User birth date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teams', 'win_percentage')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('win_percentage');
            });
        }

        if (Schema::hasColumn('users', 'birth_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('birth_date');
            });
        }
    }
};
