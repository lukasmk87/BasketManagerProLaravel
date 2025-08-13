<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_emergency_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users');
            
            // Access Control
            $table->string('access_key', 64)->unique();
            $table->enum('access_type', ['emergency_only', 'full_contacts', 'medical_info', 'custom'])->default('emergency_only');
            $table->json('permissions')->nullable(); // What data can be accessed
            
            // Validity
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable(); // Limit number of uses
            $table->integer('current_uses')->default(0);
            
            // Usage Tracking
            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->text('last_used_user_agent')->nullable();
            $table->json('usage_log')->nullable(); // Detailed usage history
            
            // Emergency Context
            $table->string('emergency_contact_person')->nullable(); // Who to call if this is used
            $table->string('emergency_contact_phone')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->json('venue_information')->nullable(); // Where this QR code is displayed
            
            // Security Features
            $table->boolean('requires_reason')->default(false); // Must provide reason for access
            $table->boolean('send_notifications')->default(true); // Notify on use
            $table->json('notification_recipients')->nullable(); // Who gets notified
            $table->boolean('log_detailed_access')->default(true);
            
            // QR Code Information
            $table->string('qr_code_url')->nullable();
            $table->string('qr_code_filename')->nullable();
            $table->json('qr_code_metadata')->nullable(); // Size, format, etc.
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'is_active']);
            $table->index(['access_key', 'expires_at']);
            $table->index('last_used_at');
            $table->index(['expires_at', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_emergency_access');
    }
};