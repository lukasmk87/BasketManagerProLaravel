<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing columns to gym_booking_requests table to match GymBookingRequest model.
     * The original migration created a simpler schema; this aligns it with the model.
     */
    public function up(): void
    {
        Schema::table('gym_booking_requests', function (Blueprint $table) {
            // Add gym_booking_id FK (model expects this for booking relationship)
            $table->foreignId('gym_booking_id')->nullable()->after('uuid')
                  ->constrained('gym_bookings')->onDelete('cascade');

            // Add requesting_team_id (model uses this, different from existing team_id)
            $table->foreignId('requesting_team_id')->nullable()->after('gym_booking_id')
                  ->constrained('teams')->onDelete('cascade');

            // Add message field (existing 'reason' column can stay for compatibility)
            $table->text('message')->nullable()->after('requested_by_user_id');

            // Add purpose field
            $table->string('purpose')->nullable()->after('message');

            // Add expected_participants
            $table->integer('expected_participants')->nullable()->after('purpose');

            // Add requested_equipment (JSON)
            $table->json('requested_equipment')->nullable()->after('expected_participants');

            // Add priority enum
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('requested_equipment');

            // Add expires_at timestamp
            $table->timestamp('expires_at')->nullable()->after('status');

            // Add reviewed_by_user_id FK
            $table->foreignId('reviewed_by_user_id')->nullable()->after('expires_at')
                  ->constrained('users')->onDelete('set null');

            // Add reviewed_at timestamp
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');

            // Add review_notes
            $table->text('review_notes')->nullable()->after('reviewed_at');

            // Add rejection_reason
            $table->text('rejection_reason')->nullable()->after('review_notes');

            // Add auto_approved flag
            $table->boolean('auto_approved')->default(false)->after('rejection_reason');

            // Add approval_conditions (JSON)
            $table->json('approval_conditions')->nullable()->after('auto_approved');

            // Add notifications_sent (JSON)
            $table->json('notifications_sent')->nullable()->after('approval_conditions');

            // Add metadata (JSON)
            $table->json('metadata')->nullable()->after('notifications_sent');
        });

        // Add index for gym_booking_id lookups
        Schema::table('gym_booking_requests', function (Blueprint $table) {
            $table->index(['gym_booking_id', 'status']);
            $table->index(['requesting_team_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_booking_requests', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['gym_booking_id', 'status']);
            $table->dropIndex(['requesting_team_id', 'status']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('gym_booking_requests', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['gym_booking_id']);
            $table->dropForeign(['requesting_team_id']);
            $table->dropForeign(['reviewed_by_user_id']);

            // Drop columns
            $table->dropColumn([
                'gym_booking_id',
                'requesting_team_id',
                'message',
                'purpose',
                'expected_participants',
                'requested_equipment',
                'priority',
                'expires_at',
                'reviewed_by_user_id',
                'reviewed_at',
                'review_notes',
                'rejection_reason',
                'auto_approved',
                'approval_conditions',
                'notifications_sent',
                'metadata',
            ]);
        });
    }
};
