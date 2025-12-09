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
            $table->json('time_slot_segments')->nullable()->after('uses_custom_times')
                ->comment('30-Min-Segmente innerhalb der Zeiten');
            $table->json('preferred_courts')->nullable()->after('time_slot_segments')
                ->comment('bevorzugte Courts für dieses Team');
            $table->integer('min_booking_duration_minutes')->default(30)->after('preferred_courts');
            $table->integer('booking_increment_minutes')->default(30)->after('min_booking_duration_minutes');
            $table->boolean('allows_partial_court')->default(false)->after('booking_increment_minutes');
            $table->boolean('supports_30_min_slots')->default(true)->after('allows_partial_court');
            
            $table->index(['supports_30_min_slots', 'allows_partial_court']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_time_slots', function (Blueprint $table) {
            $table->dropIndex(['supports_30_min_slots', 'allows_partial_court']);
            $table->dropColumn([
                'time_slot_segments',
                'preferred_courts',
                'min_booking_duration_minutes',
                'booking_increment_minutes', 
                'allows_partial_court',
                'supports_30_min_slots'
            ]);
        });
    }
};
