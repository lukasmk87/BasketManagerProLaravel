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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();

            // User who owns this preference
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Polymorphic relation to notifiable (Club, Team, Tenant, etc.)
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();

            // Notification channel: email, push, sms, database
            $table->enum('channel', ['email', 'push', 'sms', 'database'])->default('email');

            // Event type for this preference
            // Subscription events: payment_succeeded, payment_failed, subscription_canceled,
            // subscription_welcome, invoice_created, invoice_finalized, payment_action_required,
            // trial_ending, subscription_renewed, plan_upgraded, plan_downgraded
            // Analytics events: high_churn_alert, analytics_report
            $table->string('event_type'); // e.g., 'payment_succeeded', 'high_churn_alert'

            // Enable/Disable this notification
            $table->boolean('is_enabled')->default(true);

            // Additional settings (JSON)
            $table->json('settings')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'channel', 'event_type'], 'idx_user_channel_event');
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifiable');
            $table->index('event_type');

            // Unique constraint: one preference per user/channel/event/notifiable combination
            $table->unique(['user_id', 'channel', 'event_type', 'notifiable_type', 'notifiable_id'], 'unique_preference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
