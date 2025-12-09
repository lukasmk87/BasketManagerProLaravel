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
        Schema::create('tenant_usages', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('metric'); // e.g., 'api_calls_per_hour', 'storage_gb', 'users', etc.
            $table->bigInteger('usage_count')->default(0);
            $table->timestamp('period_start'); // Start of tracking period (usually month start)
            $table->timestamp('period_end')->nullable(); // End of tracking period
            $table->timestamp('last_tracked_at'); // Last time usage was updated
            $table->json('metadata')->nullable(); // Additional data like breakdown by feature
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index(['tenant_id', 'metric', 'period_start']);
            $table->index(['period_start', 'period_end']);
            $table->index('last_tracked_at');
            
            // Foreign key constraint
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate tracking records
            $table->unique(['tenant_id', 'metric', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usages');
    }
};
