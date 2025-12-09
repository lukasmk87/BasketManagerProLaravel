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
        Schema::create('stripe_connect_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('club_id');

            // Stripe IDs
            $table->string('stripe_payment_intent_id');
            $table->string('stripe_transfer_id')->nullable();
            $table->string('stripe_charge_id')->nullable();

            // Amounts (in cents)
            $table->integer('gross_amount');
            $table->integer('application_fee_amount');
            $table->integer('net_amount');
            $table->string('currency', 3)->default('EUR');

            // Status
            $table->enum('status', [
                'pending',
                'succeeded',
                'failed',
                'refunded',
                'partially_refunded'
            ])->default('pending');

            // Metadata
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('club_id')
                ->references('id')
                ->on('clubs')
                ->onDelete('cascade');

            $table->index(['tenant_id', 'status'], 'idx_connect_transfers_tenant_status');
            $table->index('stripe_payment_intent_id', 'idx_connect_transfers_payment_intent');
            $table->index('stripe_transfer_id', 'idx_connect_transfers_transfer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_connect_transfers');
    }
};
