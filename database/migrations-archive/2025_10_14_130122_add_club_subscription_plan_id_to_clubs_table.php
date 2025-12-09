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
        Schema::table('clubs', function (Blueprint $table) {
            $table->foreignUuid('club_subscription_plan_id')
                ->nullable()
                ->after('uuid')
                ->constrained('club_subscription_plans')
                ->onDelete('set null');

            $table->index('club_subscription_plan_id', 'idx_subscription_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['club_subscription_plan_id']);
            $table->dropIndex('idx_subscription_plan');
            $table->dropColumn('club_subscription_plan_id');
        });
    }
};
