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
        Schema::create('game_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('availability_status', [
                'available',      // Verfügbar
                'unavailable',    // Nicht verfügbar
                'maybe',          // Unsicher
                'injured',        // Verletzt
                'suspended'       // Gesperrt
            ])->default('available');
            
            $table->enum('registration_status', [
                'pending',        // Wartet auf Bestätigung
                'confirmed',      // Bestätigt für das Spiel
                'declined',       // Abgelehnt/nicht ausgewählt
                'cancelled'       // Spieler hat abgesagt
            ])->default('pending');
            
            $table->timestamp('registered_at');
            $table->timestamp('response_deadline')->nullable();
            $table->text('player_notes')->nullable();
            $table->text('unavailability_reason')->nullable();
            $table->boolean('is_late_registration')->default(false);
            
            // Trainer-spezifische Felder
            $table->text('trainer_notes')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - ein Spieler kann sich nur einmal pro Spiel anmelden
            $table->unique(['game_id', 'player_id'], 'unique_game_registration');
            
            // Indexes
            $table->index(['game_id', 'availability_status']);
            $table->index(['game_id', 'registration_status']);
            $table->index(['player_id', 'registered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_registrations');
    }
};