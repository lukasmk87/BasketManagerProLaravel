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
        Schema::table('gym_halls', function (Blueprint $table) {
            // Referenz zur Ausweichhalle (selbstreferenzierend)
            $table->foreignId('fallback_gym_hall_id')
                ->nullable()
                ->constrained('gym_halls')
                ->onDelete('set null')
                ->after('metadata');

            // Vordefinierter Ausweichtag
            $table->enum('fallback_day_of_week', [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
            ])->nullable()->after('fallback_gym_hall_id');

            // Vordefinierte Ausweichzeit
            $table->time('fallback_start_time')->nullable()->after('fallback_day_of_week');
            $table->time('fallback_end_time')->nullable()->after('fallback_start_time');

            // Index für schnelle Abfragen
            $table->index(['fallback_gym_hall_id', 'fallback_day_of_week'], 'gym_halls_fallback_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_halls', function (Blueprint $table) {
            $table->dropForeign(['fallback_gym_hall_id']);
            $table->dropIndex('gym_halls_fallback_idx');
            $table->dropColumn([
                'fallback_gym_hall_id',
                'fallback_day_of_week',
                'fallback_start_time',
                'fallback_end_time',
            ]);
        });
    }
};
