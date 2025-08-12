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
        Schema::create('api_usage_tracking', function (Blueprint $table) {
            $table->id();
            
            // User/request identification
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('api_key_hash')->nullable(); // For API key based requests
            $table->string('ip_address', 45); // IPv4/IPv6 support
            $table->string('user_agent')->nullable();
            
            // Request details
            $table->string('method', 10); // GET, POST, PUT, DELETE, etc.
            $table->string('endpoint', 500); // Full endpoint path
            $table->string('route_name')->nullable(); // Laravel route name
            $table->string('api_version', 10)->default('4.0');
            
            // Usage metrics
            $table->integer('request_count')->default(1);
            $table->decimal('cost_weight', 5, 2)->default(1.00); // Cost multiplier for this request type
            $table->integer('response_time_ms')->nullable();
            $table->integer('response_status');
            $table->bigInteger('response_size_bytes')->nullable();
            
            // Time windows for sliding rate limiting
            $table->timestamp('window_start'); // Start of the current time window
            $table->string('window_type', 20)->default('hourly'); // hourly, daily, monthly
            $table->timestamp('request_timestamp')->useCurrent();
            
            // Rate limiting tracking
            $table->integer('requests_in_window')->default(1);
            $table->decimal('cost_in_window', 8, 2)->default(1.00);
            $table->boolean('exceeded_limit')->default(false);
            $table->string('limit_type_hit')->nullable(); // 'requests', 'cost', 'burst'
            
            // Geographical and context
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->json('headers')->nullable(); // Important request headers
            
            // Billing and analytics
            $table->decimal('billable_cost', 8, 4)->default(0); // For overage billing
            $table->string('subscription_tier', 20)->nullable();
            $table->boolean('is_overage')->default(false);
            
            $table->timestamps();
            
            // Critical indexes for performance
            $table->index(['user_id', 'window_start', 'window_type'], 'user_window_idx');
            $table->index(['ip_address', 'window_start', 'window_type'], 'ip_window_idx');
            $table->index(['api_key_hash', 'window_start'], 'api_key_window_idx');
            $table->index(['endpoint', 'created_at'], 'endpoint_time_idx');
            $table->index(['request_timestamp'], 'request_time_idx');
            $table->index(['subscription_tier', 'created_at'], 'tier_time_idx');
            $table->index(['exceeded_limit', 'created_at'], 'limit_exceeded_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_tracking');
    }
};
