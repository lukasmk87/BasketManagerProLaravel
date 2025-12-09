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
        Schema::create('stripe_connect_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(); // null = Platform-wide defaults

            // Fee Configuration
            $table->decimal('application_fee_percent', 5, 2)->default(2.50);
            $table->decimal('application_fee_fixed', 10, 2)->default(0.00);
            $table->string('fee_currency', 3)->default('EUR');

            // Payout Settings
            $table->enum('payout_schedule', ['daily', 'weekly', 'monthly', 'manual'])->default('daily');
            $table->unsignedTinyInteger('payout_delay_days')->default(7);

            // Feature Flags
            $table->boolean('allow_direct_charges')->default(true);
            $table->boolean('allow_destination_charges')->default(true);
            $table->boolean('require_onboarding_complete')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->unique('tenant_id');
        });

        // Insert platform-wide defaults
        DB::table('stripe_connect_settings')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => null,
            'application_fee_percent' => 2.50,
            'application_fee_fixed' => 0.00,
            'fee_currency' => 'EUR',
            'payout_schedule' => 'daily',
            'payout_delay_days' => 7,
            'allow_direct_charges' => true,
            'allow_destination_charges' => true,
            'require_onboarding_complete' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_connect_settings');
    }
};
