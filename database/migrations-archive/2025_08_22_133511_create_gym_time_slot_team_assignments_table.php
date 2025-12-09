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
        Schema::create('gym_time_slot_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('gym_time_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance (shortened names for MySQL)
            $table->index(['gym_time_slot_id', 'day_of_week', 'start_time'], 'idx_slot_day_start');
            $table->index(['team_id', 'day_of_week'], 'idx_team_day');
            $table->index(['status', 'valid_from', 'valid_until'], 'idx_status_dates');
            
            // Unique constraint to prevent overlapping assignments
            $table->unique(['gym_time_slot_id', 'team_id', 'day_of_week', 'start_time'], 'unique_team_time_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_time_slot_team_assignments');
    }
};
