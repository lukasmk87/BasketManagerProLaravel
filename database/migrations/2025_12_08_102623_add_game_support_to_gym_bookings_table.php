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
        Schema::table('gym_bookings', function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->after('team_id')->constrained('games')->nullOnDelete();
            $table->integer('priority')->default(0)->after('booking_type'); // 0 = Training, 10 = Spiel
            $table->index(['booking_date', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_date', 'priority']);
            $table->dropForeign(['game_id']);
            $table->dropColumn(['game_id', 'priority']);
        });
    }
};
