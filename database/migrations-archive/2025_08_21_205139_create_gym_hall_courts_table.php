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
        Schema::create('gym_hall_courts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('gym_hall_id')->constrained()->onDelete('cascade');
            $table->string('court_identifier')->comment('z.B. "A", "B", "1", "2"');
            $table->string('court_name')->comment('z.B. "Hauptfeld", "Nebenfeld"');
            $table->enum('court_type', ['full', 'half', 'third'])->default('full');
            $table->integer('max_capacity')->nullable();
            $table->json('equipment')->nullable();
            $table->string('color_code', 7)->default('#3B82F6')->comment('Hex color for UI');
            $table->decimal('width_meters', 5, 2)->nullable();
            $table->decimal('length_meters', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_hall_id', 'is_active']);
            $table->index('sort_order');
            $table->unique(['gym_hall_id', 'court_identifier'], 'unique_court_per_hall');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_hall_courts');
    }
};
