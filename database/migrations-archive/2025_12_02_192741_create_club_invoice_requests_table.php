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
        Schema::create('club_invoice_requests', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->unsignedBigInteger('club_id');
            $table->char('club_subscription_plan_id', 36);

            // Rechnungsdaten vom Club Admin
            $table->string('billing_name');
            $table->string('billing_email');
            $table->json('billing_address')->nullable();
            $table->string('vat_number')->nullable();

            // Gewünschter Abrechnungsintervall
            $table->enum('billing_interval', ['monthly', 'yearly'])->default('monthly');

            // Status
            $table->enum('status', [
                'pending',   // Wartet auf Bearbeitung
                'approved',  // Genehmigt
                'rejected',  // Abgelehnt
            ])->default('pending');

            // Admin-Bearbeitung
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Referenz zur erstellten Rechnung (nach Genehmigung)
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->foreign('club_subscription_plan_id')->references('id')->on('club_subscription_plans')->cascadeOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('club_invoices')->nullOnDelete();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_invoice_requests');
    }
};
