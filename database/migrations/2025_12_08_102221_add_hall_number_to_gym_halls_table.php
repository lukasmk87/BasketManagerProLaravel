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
        Schema::table('gym_halls', function (Blueprint $table) {
            $table->string('hall_number')->nullable()->after('name');
            $table->index('hall_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_halls', function (Blueprint $table) {
            $table->dropIndex(['hall_number']);
            $table->dropColumn('hall_number');
        });
    }
};
