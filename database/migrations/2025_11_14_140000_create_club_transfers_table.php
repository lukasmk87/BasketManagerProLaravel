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
        Schema::create('club_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignUuid('source_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('target_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('initiated_by')->constrained('users')->onDelete('cascade');

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'rolled_back'
            ])->default('pending')->index();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();

            // JSON metadata for transfer details
            $table->json('metadata')->nullable()->comment('Contains transfer configuration, impact analysis, errors');

            // Rollback window (24h default)
            $table->boolean('can_rollback')->default(true);
            $table->timestamp('rollback_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['source_tenant_id', 'status']);
            $table->index(['target_tenant_id', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_transfers');
    }
};
