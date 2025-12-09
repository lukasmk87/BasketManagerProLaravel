<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Media and Notifications Migration
 *
 * Includes: media (Spatie), notification_logs, notification_preferences
 */
return new class extends Migration
{
    public function up(): void
    {
        // Media (Spatie Media Library)
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });

        // Notification Logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notification_type');
            $table->enum('channel', ['email', 'push', 'sms', 'database'])->default('email');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject')->nullable();
            $table->text('body_preview')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'opened', 'clicked', 'bounced', 'complained'])->default('queued');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifiable');
            $table->index('notification_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index('recipient_email');
            $table->index('external_id');
            $table->index('created_at');
        });

        // Notification Preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->enum('channel', ['email', 'push', 'sms', 'database'])->default('email');
            $table->string('event_type');
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'event_type'], 'idx_user_channel_event');
            $table->index(['notifiable_type', 'notifiable_id'], 'idx_notifiable_pref');
            $table->index('event_type');
            $table->unique(['user_id', 'channel', 'event_type', 'notifiable_type', 'notifiable_id'], 'unique_preference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('media');
    }
};
