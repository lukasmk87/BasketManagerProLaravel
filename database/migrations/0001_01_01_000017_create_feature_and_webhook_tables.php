<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Feature Flags and Webhook Tables Migration
 *
 * Includes: feature_flags, webhook_events
 */
return new class extends Migration
{
    public function up(): void
    {
        // Feature Flags
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('club_id')->nullable()->index();
            $table->string('feature_key')->index();
            $table->string('feature_name')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('is_beta_opt_in')->default(false);
            $table->integer('rollout_percentage')->nullable();
            $table->json('rollout_rules')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->unsignedBigInteger('enabled_by')->nullable();
            $table->unsignedBigInteger('disabled_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->unique(['tenant_id', 'club_id', 'feature_key'], 'feature_flags_unique');
            $table->index(['feature_key', 'is_enabled']);
            $table->index(['tenant_id', 'feature_key']);
            $table->index(['club_id', 'feature_key']);
        });

        // Webhook Events
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique()->index();
            $table->string('event_type')->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->enum('status', ['pending', 'queued', 'processing', 'processed', 'failed'])->default('pending')->index();
            $table->json('payload');
            $table->boolean('livemode')->default(false);
            $table->string('api_version')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['status', 'created_at']);
            $table->index(['event_type', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['livemode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('feature_flags');
    }
};
