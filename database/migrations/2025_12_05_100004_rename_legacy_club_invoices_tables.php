<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Benennt die alten ClubInvoice-Tabellen in Legacy-Tabellen um.
     * Diese werden 30 Tage aufbewahrt für Rollback-Sicherheit.
     */
    public function up(): void
    {
        if (Schema::hasTable('club_invoices')) {
            Schema::rename('club_invoices', 'club_invoices_legacy');
        }

        if (Schema::hasTable('club_invoice_requests')) {
            Schema::rename('club_invoice_requests', 'club_invoice_requests_legacy');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('club_invoices_legacy')) {
            Schema::rename('club_invoices_legacy', 'club_invoices');
        }

        if (Schema::hasTable('club_invoice_requests_legacy')) {
            Schema::rename('club_invoice_requests_legacy', 'club_invoice_requests');
        }
    }
};
