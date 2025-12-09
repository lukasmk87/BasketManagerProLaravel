<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('requesting_team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->text('purpose')->nullable(); // Grund der Anfrage
            $table->integer('expected_participants')->nullable();
            $table->json('requested_equipment')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'pending',      // Wartend auf Antwort
                'approved',     // Genehmigt
                'rejected',     // Abgelehnt
                'cancelled',    // Storniert vom Antragsteller
                'expired'       // Abgelaufen
            ])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('auto_approved')->default(false);
            $table->json('approval_conditions')->nullable(); // Bedingungen für Genehmigung
            $table->json('notifications_sent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_booking_id', 'status']);
            $table->index(['requesting_team_id', 'status']);
            $table->index(['requested_by_user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_booking_requests');
    }
};