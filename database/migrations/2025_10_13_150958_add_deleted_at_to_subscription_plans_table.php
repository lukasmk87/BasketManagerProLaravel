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
        // Only add soft deletes if deleted_at column doesn't exist
        if (!Schema::hasColumn('subscription_plans', 'deleted_at')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop soft deletes if deleted_at column exists
        if (Schema::hasColumn('subscription_plans', 'deleted_at')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
