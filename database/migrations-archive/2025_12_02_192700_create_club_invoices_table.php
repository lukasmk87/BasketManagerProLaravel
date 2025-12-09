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
        Schema::create('club_invoices', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->unsignedBigInteger('club_id');
            $table->char('club_subscription_plan_id', 36)->nullable();

            // Rechnungsnummer
            $table->string('invoice_number')->unique();

            // Status-Workflow
            $table->enum('status', [
                'draft',      // Entwurf
                'sent',       // Versendet
                'paid',       // Bezahlt
                'overdue',    // Überfällig
                'cancelled',  // Storniert
            ])->default('draft');

            // Beträge
            $table->decimal('net_amount', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(19.00);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('gross_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Rechnungsdetails
            $table->string('billing_period')->nullable();
            $table->text('description')->nullable();
            $table->json('line_items')->nullable();

            // Empfänger-Daten (Snapshot zum Zeitpunkt der Rechnung)
            $table->string('billing_name');
            $table->string('billing_email');
            $table->json('billing_address')->nullable();
            $table->string('vat_number')->nullable();

            // Zahlungsinformationen
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();

            // Mahnwesen
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent_at')->nullable();

            // PDF Speicherort
            $table->string('pdf_path')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->foreign('club_subscription_plan_id')->references('id')->on('club_subscription_plans')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['club_id', 'status']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_invoices');
    }
};
