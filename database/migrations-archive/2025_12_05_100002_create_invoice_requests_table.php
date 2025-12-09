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
        Schema::create('invoice_requests', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);

            // Polymorphe Relation (Club oder Tenant, der Invoice-Zahlung anfordert)
            $table->string('requestable_type');  // 'App\Models\Club' oder 'App\Models\Tenant'
            $table->string('requestable_id');    // UUID (Tenant) oder BigInt (Club)

            // Status-Workflow
            $table->enum('status', [
                'pending',   // Ausstehend
                'approved',  // Genehmigt
                'rejected',  // Abgelehnt
            ])->default('pending');

            // Billing-Daten für die Rechnung
            $table->string('billing_name');
            $table->string('billing_email');
            $table->json('billing_address')->nullable();
            $table->string('vat_number')->nullable();

            // Optionale Notizen
            $table->text('notes')->nullable();

            // Processing
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['requestable_type', 'requestable_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_requests');
    }
};
