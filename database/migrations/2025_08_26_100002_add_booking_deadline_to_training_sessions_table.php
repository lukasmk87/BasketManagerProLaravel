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
        Schema::table('training_sessions', function (Blueprint $table) {
            // Buchungsfrist in Stunden vor dem Training
            $table->integer('booking_deadline_hours')->default(2)->after('planned_duration');
            
            // Ob Buchungen überhaupt erlaubt sind
            $table->boolean('allow_registrations')->default(true)->after('booking_deadline_hours');
            
            // Warteliste aktiviert
            $table->boolean('enable_waitlist')->default(true)->after('allow_registrations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'booking_deadline_hours',
                'allow_registrations',
                'enable_waitlist'
            ]);
        });
    }
};