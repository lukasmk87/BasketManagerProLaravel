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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation to notifiable (Club, Tenant, etc.)
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');

            // Notification type (Mail class name)
            $table->string('notification_type'); // e.g., 'PaymentSuccessfulMail', 'HighChurnAlertMail'

            // Notification channel
            $table->enum('channel', ['email', 'push', 'sms', 'database'])->default('email');

            // Recipient information
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Email details
            $table->string('subject')->nullable();
            $table->text('body_preview')->nullable(); // First 200 chars of email body

            // Status tracking
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'opened', 'clicked', 'bounced', 'complained'])->default('queued');

            // Timestamps
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Failure information
            $table->text('failed_reason')->nullable();
            $table->integer('retry_count')->default(0);

            // Additional metadata (JSON)
            // Can store: invoice_id, stripe_event_id, churn_rate, analytics_period, etc.
            $table->json('metadata')->nullable();

            // External tracking (e.g., SendGrid message ID)
            $table->string('external_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifiable');
            $table->index('notification_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index('recipient_email');
            $table->index('external_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
