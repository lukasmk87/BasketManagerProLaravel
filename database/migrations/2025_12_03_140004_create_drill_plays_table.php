<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drill_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');

            // Order within drill
            $table->integer('order')->default(0);

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['drill_id', 'play_id']);

            // Index for ordering
            $table->index(['drill_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drill_plays');
    }
};
