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
        Schema::table('gym_courts', function (Blueprint $table) {
            // Add main court flag
            $table->boolean('is_main_court')
                ->default(false)
                ->after('is_active');
            
            // Add index for performance when searching for main courts
            $table->index(['gym_hall_id', 'is_main_court'], 'idx_gym_hall_main_court');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_courts', function (Blueprint $table) {
            $table->dropIndex('idx_gym_hall_main_court');
            $table->dropColumn('is_main_court');
        });
    }
};