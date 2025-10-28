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
        Schema::create('club_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('metric'); // e.g., 'max_teams', 'max_players', 'max_games_per_month'
            $table->bigInteger('usage_count')->default(0);
            $table->timestamp('period_start'); // Start of tracking period (monthly reset)
            $table->timestamp('period_end')->nullable(); // End of period (for historical records)
            $table->timestamp('last_tracked_at')->nullable();
            $table->json('metadata')->nullable(); // Additional context (e.g., breakdown by team)
            $table->timestamps();

            // Indexes for performance
            $table->index('club_id');
            $table->index('tenant_id');
            $table->index('metric');
            $table->index('period_start');
            $table->index('last_tracked_at');

            // Unique constraint: One record per club-metric-period combination
            $table->unique(['club_id', 'metric', 'period_start'], 'club_metric_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_usages');
    }
};
