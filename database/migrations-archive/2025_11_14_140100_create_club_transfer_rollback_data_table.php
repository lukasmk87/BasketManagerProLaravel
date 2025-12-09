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
        Schema::create('club_transfer_rollback_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('club_transfer_id')->constrained('club_transfers')->onDelete('cascade');

            // Which table this rollback data belongs to
            $table->string('table_name')->index();

            // Original record ID (UUID or INT)
            $table->string('record_id')->index();

            // Snapshot of original record data (JSON)
            $table->json('record_data')->comment('Complete snapshot of the record before transfer');

            // Operation type for clarity
            $table->enum('operation_type', [
                'update',    // Record was updated (e.g., club.tenant_id changed)
                'delete',    // Record was deleted (e.g., club_user memberships)
                'create'     // Record was created (rare, but for completeness)
            ])->default('update');

            $table->timestamps();

            // Composite index for efficient rollback queries
            $table->index(['club_transfer_id', 'table_name']);
            $table->index(['table_name', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_transfer_rollback_data');
    }
};
