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
            $table->json('custom_times')->nullable()->after('day_of_week');
            $table->boolean('uses_custom_times')->default(false)->after('custom_times');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_time_slots', function (Blueprint $table) {
            $table->dropColumn(['custom_times', 'uses_custom_times']);
        });
    }
};
