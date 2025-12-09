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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique()->index();
            $table->string('event_type')->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->enum('status', ['pending', 'queued', 'processing', 'processed', 'failed'])->default('pending')->index();
            $table->json('payload');
            $table->boolean('livemode')->default(false);
            $table->string('api_version')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Composite indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['event_type', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['livemode', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
