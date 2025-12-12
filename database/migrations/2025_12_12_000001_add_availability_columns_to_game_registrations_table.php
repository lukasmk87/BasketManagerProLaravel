<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds availability tracking columns to game_registrations table
     * to support the RSVP functionality (Zusagen/Absagen/Unsicher).
     */
    public function up(): void
    {
        Schema::table('game_registrations', function (Blueprint $table) {
            // Availability status (player's response)
            $table->string('availability_status')->default('pending')->after('status');

            // Registration status (trainer's confirmation)
            $table->string('registration_status')->default('pending')->after('availability_status');

            // Response deadline
            $table->timestamp('response_deadline')->nullable()->after('registered_at');

            // Player notes (visible to player)
            $table->text('player_notes')->nullable()->after('notes');

            // Reason if unavailable
            $table->string('unavailability_reason')->nullable()->after('player_notes');

            // Late registration flag
            $table->boolean('is_late_registration')->default(false)->after('unavailability_reason');

            // Trainer notes (visible to trainers)
            $table->text('trainer_notes')->nullable()->after('is_late_registration');
        });

        // Make team_id nullable (not all registrations need a team_id)
        Schema::table('game_registrations', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'availability_status',
                'registration_status',
                'response_deadline',
                'player_notes',
                'unavailability_reason',
                'is_late_registration',
                'trainer_notes',
            ]);
        });
    }
};
