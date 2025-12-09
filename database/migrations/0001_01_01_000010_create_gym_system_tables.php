<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Gym System Migration
 *
 * This migration consolidates all gym-related tables (16 original migrations → 1):
 * - gym_halls (with court_support, hall_number, fallback_hall)
 * - gym_time_slots (with custom_times, flexible_booking, parallel_bookings, all nullable fixes)
 * - gym_bookings (with court_support, game_support)
 * - gym_courts (with sort_order, is_main_court)
 * - gym_booking_requests
 * - gym_hall_courts
 * - gym_booking_courts
 * - gym_time_slot_team_assignments
 */
return new class extends Migration
{
    public function up(): void
    {
        // Gym Halls
        Schema::create('gym_halls', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('hall_number')->nullable();
            $table->text('description')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('capacity')->nullable();
            $table->json('facilities')->nullable();
            $table->json('equipment')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_hours')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_key')->default(false);
            $table->text('access_instructions')->nullable();
            $table->text('special_rules')->nullable();
            $table->json('metadata')->nullable();

            // Court support
            $table->enum('hall_type', ['single', 'double', 'triple', 'multi'])->default('single');
            $table->integer('court_count')->default(1);
            $table->json('court_configuration')->nullable();
            $table->boolean('supports_parallel_bookings')->default(false);
            $table->integer('min_booking_duration_minutes')->default(30);
            $table->integer('booking_increment_minutes')->default(30);

            // Fallback hall configuration
            $table->foreignId('fallback_gym_hall_id')->nullable();
            $table->enum('fallback_day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->time('fallback_start_time')->nullable();
            $table->time('fallback_end_time')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'is_active']);
            $table->index('slug');
            $table->index('hall_number');
            $table->index(['hall_type', 'supports_parallel_bookings']);
            $table->index(['fallback_gym_hall_id', 'fallback_day_of_week']);
        });

        // Self-referencing FK for fallback_gym_hall_id
        Schema::table('gym_halls', function (Blueprint $table) {
            $table->foreign('fallback_gym_hall_id')->references('id')->on('gym_halls')->onDelete('set null');
        });

        // FK from games to gym_halls
        Schema::table('games', function (Blueprint $table) {
            $table->foreign('gym_hall_id')->references('id')->on('gym_halls')->onDelete('set null');
        });

        // Gym Courts
        Schema::create('gym_courts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_hall_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('court_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main_court')->default(false);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['gym_hall_id', 'court_number']);
            $table->index(['gym_hall_id', 'is_active']);
            $table->index('sort_order');
            $table->index(['gym_hall_id', 'is_main_court']);
        });

        // Gym Time Slots (all fields nullable as needed for custom times)
        Schema::create('gym_time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_hall_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();

            // Time fields (nullable for custom_times support)
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();

            // Custom times support
            $table->json('custom_times')->nullable();
            $table->boolean('uses_custom_times')->default(false);

            // Recurrence
            $table->enum('recurrence_type', ['weekly', 'biweekly', 'monthly', 'once'])->default('weekly');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_recurring')->default(true);

            // Status and type
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('slot_type', ['training', 'game', 'event', 'maintenance'])->default('training');
            $table->integer('max_participants')->nullable();
            $table->boolean('allows_substitution')->default(true);
            $table->json('excluded_dates')->nullable();
            $table->string('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->decimal('cost_per_hour', 8, 2)->nullable();
            $table->json('required_equipment')->nullable();
            $table->json('metadata')->nullable();

            // Flexible booking support
            $table->json('time_slot_segments')->nullable();
            $table->json('preferred_courts')->nullable();
            $table->integer('min_booking_duration_minutes')->default(30);
            $table->integer('booking_increment_minutes')->default(30);
            $table->boolean('allows_partial_court')->default(false);
            $table->boolean('supports_30_min_slots')->default(true);
            $table->boolean('supports_parallel_bookings')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_hall_id', 'day_of_week', 'start_time']);
            $table->index(['team_id', 'status']);
            $table->index(['valid_from', 'valid_until']);
            $table->index(['supports_30_min_slots', 'allows_partial_court']);
            $table->index(['supports_parallel_bookings', 'status']);
        });

        // Gym Time Slot Team Assignments
        Schema::create('gym_time_slot_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_time_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('court_id')->nullable()->constrained('gym_courts')->onDelete('set null');
            $table->boolean('is_primary')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique(['gym_time_slot_id', 'team_id']);
            $table->index(['team_id', 'is_primary']);
        });

        // Gym Bookings
        Schema::create('gym_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_time_slot_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->integer('priority')->default(0);

            $table->enum('status', [
                'pending', 'confirmed', 'cancelled', 'completed',
                'released', 'no_show', 'substituted', 'expired'
            ])->default('pending');
            $table->enum('booking_type', ['regular', 'substitute', 'adhoc', 'event'])->default('regular');

            // Team substitution
            $table->foreignId('original_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('substitute_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->text('release_reason')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Timestamps
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();

            // Cost and payment
            $table->decimal('cost', 8, 2)->nullable();
            $table->boolean('payment_required')->default(false);
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'waived'])->default('pending');

            // Participants
            $table->integer('participants_count')->nullable();
            $table->json('participant_list')->nullable();
            $table->text('special_requirements')->nullable();
            $table->json('notifications_sent')->nullable();
            $table->json('metadata')->nullable();

            // Court support
            $table->json('court_ids')->nullable();
            $table->boolean('is_partial_court')->default(false);
            $table->decimal('court_percentage', 5, 2)->default(100.00);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_time_slot_id', 'booking_date']);
            $table->index(['team_id', 'booking_date']);
            $table->index(['status', 'booking_date']);
            $table->index(['booking_date', 'priority']);
        });

        // Gym Booking Courts (pivot table)
        Schema::create('gym_booking_courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('gym_court_id')->constrained('gym_courts')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['gym_booking_id', 'gym_court_id']);
        });

        // Gym Booking Requests
        Schema::create('gym_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_hall_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->date('requested_date');
            $table->time('requested_start_time');
            $table->time('requested_end_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_hall_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index(['requested_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['gym_hall_id']);
        });

        Schema::dropIfExists('gym_booking_requests');
        Schema::dropIfExists('gym_booking_courts');
        Schema::dropIfExists('gym_bookings');
        Schema::dropIfExists('gym_time_slot_team_assignments');
        Schema::dropIfExists('gym_time_slots');
        Schema::dropIfExists('gym_courts');
        Schema::dropIfExists('gym_halls');
    }
};
