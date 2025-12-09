<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fügt optionale Billing-Kontakt-Felder zu Tenants hinzu.
     * Diese ermöglichen einen separaten Rechnungsempfänger (z.B. Buchhaltung).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Optionaler Billing-Kontakt (Fallback auf billing_email/billing_name)
            $table->string('billing_contact_name')->nullable()->after('billing_address');
            $table->string('billing_contact_email')->nullable()->after('billing_contact_name');

            // Zahlungsmethode für Tenant-Subscriptions
            $table->enum('preferred_payment_method', ['stripe', 'bank_transfer'])
                ->default('stripe')
                ->after('billing_contact_email');

            // Flag ob Tenant per Rechnung zahlt (anstatt Stripe)
            $table->boolean('pays_via_invoice')->default(false)->after('preferred_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'billing_contact_name',
                'billing_contact_email',
                'preferred_payment_method',
                'pays_via_invoice',
            ]);
        });
    }
};
