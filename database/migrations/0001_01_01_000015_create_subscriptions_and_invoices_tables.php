<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Subscriptions and Invoices Migration
 *
 * Includes: subscriptions, subscription_items (Cashier), subscription_mrr_snapshots,
 * club_subscription_events, club_subscription_cohorts, tenant_usages, club_usages,
 * invoices, invoice_requests (polymorphic - no legacy tables)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stripe Customer columns for Tenants (Cashier)
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->index()->after('currency');
            }
            if (!Schema::hasColumn('tenants', 'pm_type')) {
                $table->string('pm_type')->nullable()->after('stripe_id');
            }
            if (!Schema::hasColumn('tenants', 'pm_last_four')) {
                $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            }
            if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
                // trial_ends_at already exists, skip
            }
        });

        // Subscriptions (Cashier)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'stripe_status']);
        });

        // Subscription Items (Cashier)
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('stripe_id')->unique();
            $table->string('stripe_product');
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);
        });

        // Tenant Usages
        Schema::create('tenant_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('metric');
            $table->integer('value')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->unique(['tenant_id', 'metric', 'period_start']);
        });

        // Club Usages
        Schema::create('club_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('metric');
            $table->integer('value')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->unique(['club_id', 'metric', 'period_start']);
        });

        // Subscription MRR Snapshots
        Schema::create('subscription_mrr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->date('snapshot_date');
            $table->enum('snapshot_type', ['daily', 'monthly'])->default('daily');
            $table->decimal('club_mrr', 10, 2)->default(0);
            $table->integer('club_count')->default(0);
            $table->decimal('tenant_mrr', 10, 2)->default(0);
            $table->decimal('total_mrr', 10, 2)->default(0);
            $table->decimal('mrr_growth', 10, 2)->default(0);
            $table->decimal('mrr_growth_rate', 8, 4)->default(0);
            $table->decimal('new_business_mrr', 10, 2)->default(0);
            $table->decimal('expansion_mrr', 10, 2)->default(0);
            $table->decimal('contraction_mrr', 10, 2)->default(0);
            $table->decimal('churned_mrr', 10, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'snapshot_date', 'snapshot_type'], 'unique_tenant_snapshot');
        });

        // Club Subscription Events
        Schema::create('club_subscription_events', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'subscription_created', 'subscription_canceled', 'subscription_renewed',
                'plan_upgraded', 'plan_downgraded',
                'trial_started', 'trial_converted', 'trial_expired',
                'payment_succeeded', 'payment_failed', 'payment_recovered'
            ]);
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_event_id')->nullable();
            $table->uuid('old_plan_id')->nullable();
            $table->uuid('new_plan_id')->nullable();
            $table->decimal('mrr_change', 10, 2)->default(0);
            $table->enum('cancellation_reason', [
                'voluntary', 'payment_failed', 'trial_expired',
                'downgrade_to_free', 'other'
            ])->nullable();
            $table->text('cancellation_feedback')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('event_date');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['club_id', 'event_date']);
            $table->index(['tenant_id', 'event_date']);
            $table->index('event_type');
            $table->index('stripe_event_id');
        });

        // Club Subscription Cohorts
        Schema::create('club_subscription_cohorts', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->date('cohort_month');
            $table->integer('cohort_size')->default(0);
            $table->decimal('retention_month_1', 5, 2)->nullable();
            $table->decimal('retention_month_2', 5, 2)->nullable();
            $table->decimal('retention_month_3', 5, 2)->nullable();
            $table->decimal('retention_month_6', 5, 2)->nullable();
            $table->decimal('retention_month_12', 5, 2)->nullable();
            $table->decimal('cumulative_revenue', 12, 2)->default(0);
            $table->decimal('avg_ltv', 10, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'cohort_month']);
        });

        // Invoices (polymorphic - for both clubs and tenants)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->morphs('invoiceable');
            // Manual morphs with shorter index name (nullableMorphs name was too long)
            $table->string('subscription_plan_type')->nullable();
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->index(['subscription_plan_type', 'subscription_plan_id'], 'idx_inv_sub_plan');
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->decimal('net_amount', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(19.00);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('gross_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_period')->nullable();
            $table->text('description')->nullable();
            $table->json('line_items')->nullable();
            $table->string('billing_name');
            $table->string('billing_email');
            $table->json('billing_address')->nullable();
            $table->string('vat_number')->nullable();
            $table->boolean('is_small_business')->default(false);
            $table->enum('payment_method', ['bank_transfer', 'stripe'])->default('bank_transfer');
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_hosted_invoice_url')->nullable();
            $table->string('stripe_invoice_pdf')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['status', 'due_date']);
        });

        // Invoice Requests (polymorphic)
        Schema::create('invoice_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->morphs('requestable');
            // Manual morphs with shorter index name (nullableMorphs name was too long)
            $table->string('subscription_plan_type')->nullable();
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->index(['subscription_plan_type', 'subscription_plan_id'], 'idx_invreq_sub_plan');
            $table->string('billing_name');
            $table->string('billing_email');
            $table->json('billing_address')->nullable();
            $table->string('vat_number')->nullable();
            $table->enum('billing_interval', ['monthly', 'yearly'])->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['status', 'created_at']);
        });

        // Club Transactions
        Schema::create('club_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->enum('type', ['subscription', 'one_time', 'refund', 'adjustment'])->default('subscription');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['club_id', 'created_at']);
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_transactions');
        Schema::dropIfExists('invoice_requests');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('club_subscription_cohorts');
        Schema::dropIfExists('club_subscription_events');
        Schema::dropIfExists('subscription_mrr_snapshots');
        Schema::dropIfExists('club_usages');
        Schema::dropIfExists('tenant_usages');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');

        Schema::table('tenants', function (Blueprint $table) {
            $columns = ['stripe_id', 'pm_type', 'pm_last_four'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
