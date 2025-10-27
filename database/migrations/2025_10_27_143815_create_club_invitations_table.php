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
        Schema::create('club_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invitation_token', 32)->unique()->comment('Unique token for public registration link');

            // Relationships
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');

            // Default club role for invited members
            $table->enum('default_role', [
                'member', 'player', 'parent', 'volunteer', 'sponsor'
            ])->default('member')->comment('Default role assigned to users who register via this invitation');

            // QR Code
            $table->string('qr_code_path')->nullable()->comment('Path to generated QR code file');
            $table->json('qr_code_metadata')->nullable()->comment('QR code format, size, etc.');

            // Configuration
            $table->timestamp('expires_at')->comment('Invitation expiration date');
            $table->unsignedInteger('max_uses')->default(100)->comment('Maximum number of uses allowed');
            $table->unsignedInteger('current_uses')->default(0)->comment('Current number of uses');
            $table->boolean('is_active')->default(true)->comment('Whether invitation is active');

            // Additional settings
            $table->json('settings')->nullable()->comment('Additional configuration options');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('club_id', 'idx_club_id');
            $table->index('created_by_user_id', 'idx_created_by');
            $table->index('invitation_token', 'idx_token');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['is_active', 'expires_at'], 'idx_active_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_invitations');
    }
};
