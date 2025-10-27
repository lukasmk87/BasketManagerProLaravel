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
        Schema::table('club_user', function (Blueprint $table) {
            $table->foreignId('registered_via_invitation_id')
                ->nullable()
                ->after('metadata')
                ->constrained('club_invitations')
                ->onDelete('set null')
                ->comment('Club invitation used for registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_user', function (Blueprint $table) {
            $table->dropForeign(['registered_via_invitation_id']);
            $table->dropColumn('registered_via_invitation_id');
        });
    }
};
