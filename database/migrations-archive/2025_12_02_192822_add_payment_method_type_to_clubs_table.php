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
        Schema::table('clubs', function (Blueprint $table) {
            // Zahlungsart: stripe (Kreditkarte/SEPA) oder invoice (Rechnung)
            $table->enum('payment_method_type', ['stripe', 'invoice'])
                ->default('stripe')
                ->after('payment_method_id');

            // Zusätzliche Rechnungsdaten für Invoice-Zahlung
            $table->string('invoice_billing_name')->nullable()->after('billing_address');
            $table->string('invoice_vat_number')->nullable()->after('invoice_billing_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method_type',
                'invoice_billing_name',
                'invoice_vat_number',
            ]);
        });
    }
};
