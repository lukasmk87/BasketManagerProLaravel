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
        Schema::create('club_transfer_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('club_transfer_id')->constrained('club_transfers')->onDelete('cascade');

            // Transfer step/phase identifier
            $table->string('step')->index()->comment('e.g., validation, stripe_cancellation, media_migration, etc.');

            // Status of this step
            $table->enum('status', [
                'started',
                'in_progress',
                'completed',
                'failed',
                'skipped'
            ])->default('started')->index();

            // Log message
            $table->text('message');

            // Additional data (errors, details, metrics)
            $table->json('data')->nullable();

            // Duration tracking
            $table->integer('duration_ms')->nullable()->comment('Step duration in milliseconds');

            $table->timestamps();

            // Indexes for efficient log retrieval
            $table->index(['club_transfer_id', 'created_at']);
            $table->index(['club_transfer_id', 'step', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_transfer_logs');
    }
};
