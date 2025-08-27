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
        Schema::table('gym_time_slots', function (Blueprint $table) {
            $table->boolean('supports_parallel_bookings')->default(true)->after('allows_substitution')
                ->comment('Erlaubt parallele Buchungen für diesen Zeitslot');
            
            $table->index(['supports_parallel_bookings', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_time_slots', function (Blueprint $table) {
            $table->dropIndex(['supports_parallel_bookings', 'status']);
            $table->dropColumn('supports_parallel_bookings');
        });
    }
};