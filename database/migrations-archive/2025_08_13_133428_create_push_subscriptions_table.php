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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->index();
            $table->text('endpoint');
            $table->string('p256dh_key');
            $table->string('auth_token');
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('subscription_data')->nullable(); // Additional subscription metadata
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate subscriptions
            $table->unique(['user_id', 'endpoint'], 'user_endpoint_unique');
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};