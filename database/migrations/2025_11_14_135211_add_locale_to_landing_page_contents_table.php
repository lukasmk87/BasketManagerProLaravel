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
        Schema::table('landing_page_contents', function (Blueprint $table) {
            // Drop existing unique constraint
            $table->dropUnique(['tenant_id', 'section']);

            // Add locale column
            $table->string('locale', 5)->default('de')->after('section');

            // Add new unique constraint including locale
            $table->unique(['tenant_id', 'section', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_contents', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique(['tenant_id', 'section', 'locale']);

            // Drop locale column
            $table->dropColumn('locale');

            // Restore original unique constraint
            $table->unique(['tenant_id', 'section']);
        });
    }
};
