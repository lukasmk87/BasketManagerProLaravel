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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Free", "Basic", "Professional", "Enterprise"
            $table->string('slug')->unique(); // e.g., "free", "basic", "professional", "enterprise"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0); // Monthly price
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_period')->default('monthly'); // monthly, yearly
            $table->string('stripe_price_id')->nullable(); // Stripe Price ID
            $table->string('stripe_product_id')->nullable(); // Stripe Product ID
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false); // For custom enterprise plans
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable(); // JSON array of feature slugs
            $table->json('limits')->nullable(); // JSON object of limits
            $table->json('metadata')->nullable(); // Additional custom data
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
