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
            $table->json('court_ids')->nullable()->after('gym_time_slot_id');
            $table->boolean('is_partial_court')->default(false)->after('court_ids');
            $table->decimal('court_percentage', 5, 2)->default(100.00)->after('is_partial_court');
            
            // Remove the unique constraint that prevents multiple bookings per slot per date
            $table->dropUnique('unique_slot_date');
        });
        
        // Create pivot table for many-to-many relationship between bookings and courts
        Schema::create('gym_booking_courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('gym_hall_court_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['gym_booking_id', 'gym_hall_court_id'], 'unique_booking_court');
            $table->index(['gym_hall_court_id', 'gym_booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_booking_courts');
        
        Schema::table('gym_bookings', function (Blueprint $table) {
            $table->dropColumn(['court_ids', 'is_partial_court', 'court_percentage']);
            
            // Re-add the unique constraint
            $table->unique(['gym_time_slot_id', 'booking_date'], 'unique_slot_date');
        });
    }
};
