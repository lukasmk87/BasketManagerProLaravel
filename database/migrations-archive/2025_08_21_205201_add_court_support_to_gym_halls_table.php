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
            $table->enum('hall_type', ['single', 'double', 'triple', 'multi'])->default('single')->after('name');
            $table->integer('court_count')->default(1)->after('hall_type');
            $table->json('court_configuration')->nullable()->after('court_count');
            $table->boolean('supports_parallel_bookings')->default(false)->after('court_configuration');
            $table->integer('min_booking_duration_minutes')->default(30)->after('supports_parallel_bookings');
            $table->integer('booking_increment_minutes')->default(30)->after('min_booking_duration_minutes');
            
            $table->index(['hall_type', 'supports_parallel_bookings']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_halls', function (Blueprint $table) {
            $table->dropIndex(['hall_type', 'supports_parallel_bookings']);
            $table->dropColumn([
                'hall_type',
                'court_count', 
                'court_configuration',
                'supports_parallel_bookings',
                'min_booking_duration_minutes',
                'booking_increment_minutes'
            ]);
        });
    }
};
