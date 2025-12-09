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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->string('name', 20); // z.B. "2024/25"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->json('settings')->nullable(); // Für flexible Konfiguration
            $table->timestamps();
            $table->softDeletes();

            // Indizes für Performance
            $table->index(['club_id', 'status']);
            $table->index(['club_id', 'is_current']);
            $table->unique(['club_id', 'name']); // Eine Saison "2024/25" pro Club
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
