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
        Schema::table('users', function (Blueprint $table) {
            // Subscription information
            $table->string('subscription_tier')->default('free')->after('is_active');
            $table->timestamp('api_quota_reset_at')->nullable()->after('subscription_tier');
            $table->integer('current_api_usage')->default(0)->after('api_quota_reset_at');
            
            // API access
            $table->string('api_key_hash')->nullable()->after('current_api_usage');
            $table->boolean('api_access_enabled')->default(true)->after('api_key_hash');
            $table->timestamp('api_key_last_used_at')->nullable()->after('api_access_enabled');
            
            // Rate limiting cache
            $table->json('rate_limit_cache')->nullable()->after('api_key_last_used_at');
            
            // Indexes for performance
            $table->index(['subscription_tier'], 'users_subscription_tier_idx');
            $table->index(['api_quota_reset_at'], 'users_quota_reset_idx');
            $table->index(['api_key_hash'], 'users_api_key_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_subscription_tier_idx');
            $table->dropIndex('users_quota_reset_idx');
            $table->dropIndex('users_api_key_idx');
            
            $table->dropColumn([
                'subscription_tier',
                'api_quota_reset_at',
                'current_api_usage',
                'api_key_hash',
                'api_access_enabled',
                'api_key_last_used_at',
                'rate_limit_cache'
            ]);
        });
    }
};
