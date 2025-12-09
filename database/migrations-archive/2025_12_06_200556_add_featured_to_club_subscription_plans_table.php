<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            $table->boolean('is_featured')
                ->default(false)
                ->after('is_default')
                ->comment('Ob der Plan auf der Landingpage angezeigt und bei Registrierung auswählbar ist');

            $table->index(['tenant_id', 'is_featured', 'is_active'], 'idx_tenant_featured_active');
        });

        // Standard-Pläne (Free, Standard, Premium) als Featured markieren
        DB::table('club_subscription_plans')
            ->whereIn('slug', ['free-club', 'standard-club', 'premium-club'])
            ->update(['is_featured' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            $table->dropIndex('idx_tenant_featured_active');
            $table->dropColumn('is_featured');
        });
    }
};
