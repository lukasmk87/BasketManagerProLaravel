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
        Schema::create('rate_limit_exceptions', function (Blueprint $table) {
            $table->id();
            
            // Target identification
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6 support
            $table->string('api_key_hash')->nullable();
            $table->string('identifier_type')->default('user'); // user, ip, api_key, endpoint
            
            // Exception details
            $table->string('exception_type')->default('increase'); // increase, unlimited, bypass
            $table->string('scope')->default('global'); // global, endpoint_specific
            $table->string('endpoints')->nullable(); // Comma-separated list of endpoints
            
            // Limit overrides
            $table->integer('custom_request_limit')->nullable(); // Override requests per hour
            $table->integer('custom_burst_limit')->nullable(); // Override burst limit
            $table->decimal('custom_cost_multiplier', 5, 2)->nullable(); // Override cost weight
            
            // Temporal constraints
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->integer('duration_hours')->nullable(); // Alternative to expires_at
            
            // Usage tracking
            $table->integer('times_used')->default(0);
            $table->integer('max_uses')->nullable(); // Maximum number of times this exception can be used
            $table->timestamp('last_used_at')->nullable();
            
            // Administrative
            $table->string('reason')->nullable(); // Why was this exception granted
            $table->string('granted_by')->nullable(); // Which admin granted this
            $table->string('status')->default('active'); // active, expired, revoked
            $table->text('notes')->nullable();
            
            // Monitoring and alerts
            $table->boolean('alert_on_use')->default(false); // Send alert when used
            $table->boolean('auto_expire')->default(true); // Auto-expire when expires_at is reached
            $table->json('metadata')->nullable(); // Additional custom data
            
            $table->timestamps();
            
            // Indexes for fast lookups during rate limiting
            $table->index(['user_id', 'status', 'starts_at', 'expires_at'], 'user_active_exceptions');
            $table->index(['ip_address', 'status', 'starts_at', 'expires_at'], 'ip_active_exceptions');
            $table->index(['api_key_hash', 'status'], 'api_key_exceptions');
            $table->index(['expires_at'], 'expiry_cleanup_idx');
            $table->index(['status', 'starts_at'], 'active_exceptions_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_limit_exceptions');
    }
};
