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
        Schema::table('plays', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_public');
            $table->boolean('is_system_template')->default(false)->after('is_featured');
            $table->integer('template_order')->nullable()->after('is_system_template');

            // Indexes for template queries
            $table->index(['is_featured', 'status']);
            $table->index(['is_system_template', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'status']);
            $table->dropIndex(['is_system_template', 'category']);

            $table->dropColumn(['is_featured', 'is_system_template', 'template_order']);
        });
    }
};
