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
        Schema::table('drills', function (Blueprint $table) {
            $table->json('drill_data')->nullable()->after('instructions');
            $table->json('animation_data')->nullable()->after('drill_data');
            $table->string('thumbnail_path')->nullable()->after('animation_data');
            $table->enum('court_type', ['half_horizontal', 'full', 'half_vertical'])
                  ->default('half_horizontal')->after('thumbnail_path');
            $table->uuid('tenant_id')->nullable()->after('id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drills', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'category_id', 'status']);
            $table->dropColumn(['drill_data', 'animation_data', 'thumbnail_path', 'court_type', 'tenant_id']);
        });
    }
};
