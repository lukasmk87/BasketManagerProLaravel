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
        Schema::create('player_registration_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invitation_token', 32)->unique()->comment('Unique token for public registration link');

            // Relationships
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_team_id')->nullable()->constrained('teams')->onDelete('set null')->comment('Suggested team for registration');

            // QR Code
            $table->string('qr_code_path')->nullable()->comment('Path to generated QR code file');
            $table->json('qr_code_metadata')->nullable()->comment('QR code format, size, etc.');

            // Configuration
            $table->timestamp('expires_at')->comment('Token expiration date');
            $table->unsignedInteger('max_registrations')->default(50)->comment('Maximum number of registrations allowed');
            $table->unsignedInteger('current_registrations')->default(0)->comment('Current number of registrations');
            $table->boolean('is_active')->default(true)->comment('Whether invitation is active');

            // Additional settings
            $table->json('settings')->nullable()->comment('Additional configuration options');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            // Note: club_id, created_by_user_id, target_team_id, and invitation_token already have indexes
            // (from foreignId() and unique() constraints respectively)
            // Composite index on is_active + expires_at (covers expires_at queries too)
            // Using table-specific name for SQLite compatibility (global index namespace)
            $table->index(['is_active', 'expires_at'], 'idx_player_reg_active_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_registration_invitations');
    }
};
