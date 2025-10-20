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
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('pending_team_assignment')->default(false)->after('status')
                ->comment('Whether player is awaiting team assignment by club admin');
            $table->foreignId('registered_via_invitation_id')->nullable()->after('pending_team_assignment')
                ->constrained('player_registration_invitations')->onDelete('set null')
                ->comment('Invitation used for registration');
            $table->timestamp('registration_completed_at')->nullable()->after('registered_via_invitation_id')
                ->comment('When player completed self-registration');

            // Index for pending players query
            $table->index(['pending_team_assignment', 'created_at'], 'idx_pending_players');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_pending_players');
            $table->dropForeign(['registered_via_invitation_id']);
            $table->dropColumn([
                'pending_team_assignment',
                'registered_via_invitation_id',
                'registration_completed_at',
            ]);
        });
    }
};
