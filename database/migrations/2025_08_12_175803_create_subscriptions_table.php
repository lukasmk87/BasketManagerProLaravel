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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Subscription details
            $table->string('tier')->default('free'); // free, basic, premium, enterprise, unlimited
            $table->string('plan_name')->nullable(); // Human readable plan name
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->decimal('annual_price', 8, 2)->default(0);
            
            // Subscription status and timing
            $table->string('status')->default('active'); // active, paused, cancelled, expired
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly, annual
            
            // Rate limiting specifics
            $table->integer('api_requests_limit')->default(1000); // Requests per hour
            $table->integer('burst_limit')->default(100); // Burst requests per minute
            $table->integer('concurrent_requests_limit')->default(10);
            $table->json('feature_limits')->nullable(); // JSON with specific feature limits
            
            // Billing and payment
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->timestamp('last_payment_at')->nullable();
            
            // Usage and overage
            $table->decimal('current_overage_cost', 8, 2)->default(0);
            $table->json('overage_rates')->nullable(); // Per-request overage costs
            $table->boolean('overage_allowed')->default(false);
            
            // Administrative
            $table->string('pending_tier_change')->nullable();
            $table->timestamp('tier_change_date')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('metadata')->nullable(); // Additional custom data
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['tier', 'status']);
            $table->index(['expires_at']);
            $table->index(['next_billing_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
