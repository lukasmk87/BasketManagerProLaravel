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
        Schema::create('dbb_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->enum('entity_type', ['player', 'team', 'game', 'club', 'league'])->index();
            $table->string('entity_id')->index(); // Local entity ID
            $table->string('dbb_id')->nullable()->index(); // DBB system ID
            $table->enum('dbb_type', [
                'player_license',
                'team_registration', 
                'game_result',
                'club_membership',
                'league_participation'
            ])->index();
            $table->json('dbb_data')->nullable(); // Additional DBB data
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
            $table->unique(['tenant_id', 'entity_type', 'entity_id', 'dbb_type'], 'dbb_integrations_unique');
            
            // Composite indexes for performance
            $table->index(['tenant_id', 'sync_status']);
            $table->index(['entity_type', 'sync_status']);
            $table->index(['dbb_type', 'sync_status']);
            $table->index(['sync_status', 'last_sync_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dbb_integrations');
    }
};
