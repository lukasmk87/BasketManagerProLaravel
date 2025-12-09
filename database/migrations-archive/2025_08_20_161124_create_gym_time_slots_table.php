<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_hall_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->enum('recurrence_type', ['weekly', 'biweekly', 'monthly', 'once'])->default('weekly');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('slot_type', ['training', 'game', 'event', 'maintenance'])->default('training');
            $table->integer('max_participants')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->boolean('allows_substitution')->default(true);
            $table->json('excluded_dates')->nullable(); // Ausnahme-Termine
            $table->string('assigned_by')->nullable(); // Wer hat zugeordnet
            $table->timestamp('assigned_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->decimal('cost_per_hour', 8, 2)->nullable();
            $table->json('required_equipment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_hall_id', 'day_of_week', 'start_time']);
            $table->index(['team_id', 'status']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_time_slots');
    }
};