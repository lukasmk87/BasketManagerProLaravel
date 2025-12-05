<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migriert bestehende ClubInvoice-Daten in die neue polymorphe invoices Tabelle.
     */
    public function up(): void
    {
        // Prüfe ob club_invoices existiert und Daten hat
        if (!Schema::hasTable('club_invoices')) {
            return;
        }

        $clubInvoices = DB::table('club_invoices')->get();

        foreach ($clubInvoices as $clubInvoice) {
            DB::table('invoices')->insert([
                'id' => $clubInvoice->id,
                'tenant_id' => $clubInvoice->tenant_id,
                'invoiceable_type' => 'App\\Models\\Club',
                'invoiceable_id' => $clubInvoice->club_id,
                'subscription_plan_type' => $clubInvoice->club_subscription_plan_id
                    ? 'App\\Models\\ClubSubscriptionPlan'
                    : null,
                'subscription_plan_id' => $clubInvoice->club_subscription_plan_id,
                'payment_method' => 'bank_transfer',
                'stripe_invoice_id' => null,
                'stripe_payment_intent_id' => null,
                'stripe_hosted_invoice_url' => null,
                'stripe_invoice_pdf' => null,
                'invoice_number' => $clubInvoice->invoice_number,
                'status' => $clubInvoice->status,
                'net_amount' => $clubInvoice->net_amount,
                'tax_rate' => $clubInvoice->tax_rate,
                'tax_amount' => $clubInvoice->tax_amount,
                'gross_amount' => $clubInvoice->gross_amount,
                'is_small_business' => $clubInvoice->is_small_business ?? false,
                'currency' => $clubInvoice->currency,
                'billing_period' => $clubInvoice->billing_period,
                'description' => $clubInvoice->description,
                'line_items' => $clubInvoice->line_items,
                'billing_name' => $clubInvoice->billing_name,
                'billing_email' => $clubInvoice->billing_email,
                'billing_address' => $clubInvoice->billing_address,
                'vat_number' => $clubInvoice->vat_number,
                'issue_date' => $clubInvoice->issue_date,
                'due_date' => $clubInvoice->due_date,
                'paid_at' => $clubInvoice->paid_at,
                'payment_reference' => $clubInvoice->payment_reference,
                'payment_notes' => $clubInvoice->payment_notes,
                'reminder_count' => $clubInvoice->reminder_count,
                'last_reminder_sent_at' => $clubInvoice->last_reminder_sent_at,
                'pdf_path' => $clubInvoice->pdf_path,
                'created_by' => $clubInvoice->created_by,
                'updated_by' => $clubInvoice->updated_by,
                'created_at' => $clubInvoice->created_at,
                'updated_at' => $clubInvoice->updated_at,
                'deleted_at' => $clubInvoice->deleted_at,
            ]);
        }

        // Migriere auch club_invoice_requests wenn vorhanden
        if (Schema::hasTable('club_invoice_requests')) {
            $clubInvoiceRequests = DB::table('club_invoice_requests')->get();

            foreach ($clubInvoiceRequests as $request) {
                DB::table('invoice_requests')->insert([
                    'id' => $request->id,
                    'tenant_id' => $request->tenant_id,
                    'requestable_type' => 'App\\Models\\Club',
                    'requestable_id' => $request->club_id,
                    'status' => $request->status,
                    'billing_name' => $request->billing_name,
                    'billing_email' => $request->billing_email,
                    'billing_address' => $request->billing_address,
                    'vat_number' => $request->vat_number,
                    'notes' => $request->notes ?? null,
                    'requested_by' => $request->requested_by ?? null,
                    'processed_by' => $request->processed_by ?? null,
                    'processed_at' => $request->processed_at ?? null,
                    'rejection_reason' => $request->rejection_reason ?? null,
                    'admin_notes' => $request->admin_notes ?? null,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                    'deleted_at' => $request->deleted_at ?? null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lösche migrierte Daten
        DB::table('invoices')->where('invoiceable_type', 'App\\Models\\Club')->delete();
        DB::table('invoice_requests')->where('requestable_type', 'App\\Models\\Club')->delete();
    }
};
