<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Club Operations Tables Migration
 *
 * Includes: club_invitations, club_transfers, club_transfer_rollback_data, club_transfer_logs
 */
return new class extends Migration
{
    public function up(): void
    {
        // Club Invitations
        Schema::create('club_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invitation_token', 32)->unique();
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('default_role', ['member', 'player', 'parent', 'volunteer', 'sponsor'])->default('member');
            $table->string('qr_code_path')->nullable();
            $table->json('qr_code_metadata')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('max_uses')->default(100);
            $table->unsignedInteger('current_uses')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'expires_at'], 'idx_club_inv_active_expires');
        });

        // Club Transfers
        Schema::create('club_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignUuid('source_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('target_tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'rolled_back'])->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('can_rollback')->default(true);
            $table->timestamp('rollback_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_tenant_id', 'status']);
            $table->index(['target_tenant_id', 'status']);
            $table->index(['created_at', 'status']);
        });

        // Club Transfer Rollback Data
        Schema::create('club_transfer_rollback_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('club_transfer_id')->constrained('club_transfers')->onDelete('cascade');
            $table->string('table_name')->index();
            $table->string('record_id')->index();
            $table->json('record_data');
            $table->enum('operation_type', ['update', 'delete', 'create'])->default('update');
            $table->timestamps();

            $table->index(['club_transfer_id', 'table_name']);
            $table->index(['table_name', 'record_id']);
        });

        // Club Transfer Logs
        Schema::create('club_transfer_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('club_transfer_id')->constrained('club_transfers')->onDelete('cascade');
            $table->string('step')->index();
            $table->enum('status', ['started', 'in_progress', 'completed', 'failed', 'skipped'])->default('started')->index();
            $table->text('message');
            $table->json('data')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['club_transfer_id', 'created_at']);
            $table->index(['club_transfer_id', 'step', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_transfer_logs');
        Schema::dropIfExists('club_transfer_rollback_data');
        Schema::dropIfExists('club_transfers');
        Schema::dropIfExists('club_invitations');
    }
};
