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
        Schema::create('training_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'registered',     // Spieler hat sich angemeldet
                'confirmed',      // Trainer hat Teilnahme bestätigt
                'cancelled',      // Spieler hat abgesagt
                'waitlist',       // Auf Warteliste
                'declined'        // Trainer hat abgelehnt
            ])->default('registered');
            
            $table->timestamp('registered_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('registration_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('is_late_registration')->default(false);
            
            // Notizen vom Trainer
            $table->text('trainer_notes')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - ein Spieler kann sich nur einmal pro Training anmelden
            $table->unique(['training_session_id', 'player_id'], 'unique_training_registration');
            
            // Indexes für bessere Performance
            $table->index(['training_session_id', 'status']);
            $table->index(['player_id', 'status']);
            $table->index('registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_registrations');
    }
};