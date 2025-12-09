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
        Schema::table('gym_time_slots', function (Blueprint $table) {
            // Make duration_minutes nullable for custom_times usage
            // Custom time slots have different durations per day, so no single duration value
            $table->integer('duration_minutes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_time_slots', function (Blueprint $table) {
            // Revert to NOT NULL (this might fail if there are null values)
            $table->integer('duration_minutes')->nullable(false)->change();
        });
    }
};