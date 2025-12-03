<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_session_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');

            // Order within training session
            $table->integer('order')->default(0);

            // Notes for this play in this training
            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['training_session_id', 'play_id']);

            // Index for ordering
            $table->index(['training_session_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_session_plays');
    }
};
