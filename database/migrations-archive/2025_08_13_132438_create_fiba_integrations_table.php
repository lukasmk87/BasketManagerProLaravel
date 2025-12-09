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
        Schema::create('fiba_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->enum('entity_type', ['player', 'team', 'club', 'competition', 'referee', 'game'])->index();
            $table->string('entity_id')->index(); // Local entity ID
            $table->string('fiba_id')->nullable()->index(); // FIBA system ID
            $table->enum('fiba_type', [
                'player_profile',
                'player_eligibility',
                'team_registration',
                'club_license',
                'competition_registration',
                'referee_certification',
                'game_official'
            ])->index();
            $table->json('fiba_data')->nullable(); // Additional FIBA data
            $table->enum('sync_status', ['pending', 'syncing', 'synced', 'failed', 'expired'])
                ->default('pending')->index();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('sync_attempts')->default(0);
            $table->json('validation_errors')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Unique constraint for entity mappings
            $table->unique(['tenant_id', 'entity_type', 'entity_id', 'fiba_type'], 'fiba_integrations_unique');
            
            // Composite indexes for performance
            $table->index(['tenant_id', 'sync_status']);
            $table->index(['entity_type', 'sync_status']);
            $table->index(['fiba_type', 'sync_status']);
            $table->index(['sync_status', 'last_sync_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiba_integrations');
    }
};
