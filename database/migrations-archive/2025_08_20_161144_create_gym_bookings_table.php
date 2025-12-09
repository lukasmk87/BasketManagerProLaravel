<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_time_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('booked_by_user_id')->constrained('users')->onDelete('cascade');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->enum('status', [
                'reserved',     // Ursprüngliche Team-Reservierung
                'released',     // Vom Team freigegeben
                'requested',    // Von anderem Team angefragt
                'confirmed',    // Buchung bestätigt
                'cancelled',    // Storniert
                'completed',    // Abgeschlossen
                'no_show'      // Team nicht erschienen
            ])->default('reserved');
            $table->enum('booking_type', ['regular', 'substitute', 'additional'])->default('regular');
            $table->foreignId('original_team_id')->nullable()->constrained('teams')->onDelete('set null'); // Team das freigegeben hat
            $table->foreignId('substitute_team_id')->nullable()->constrained('teams')->onDelete('set null'); // Team das gebucht hat
            $table->text('release_reason')->nullable();
            $table->text('booking_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->boolean('payment_required')->default(false);
            $table->enum('payment_status', ['not_required', 'pending', 'paid', 'refunded'])->default('not_required');
            $table->integer('participants_count')->nullable();
            $table->json('participant_list')->nullable();
            $table->text('special_requirements')->nullable();
            $table->json('notifications_sent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_time_slot_id', 'booking_date']);
            $table->index(['team_id', 'status']);
            $table->index(['booked_by_user_id', 'status']);
            $table->index(['booking_date', 'start_time']);
            $table->unique(['gym_time_slot_id', 'booking_date'], 'unique_slot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_bookings');
    }
};