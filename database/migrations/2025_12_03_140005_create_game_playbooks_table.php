<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('playbook_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['game_id', 'playbook_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_playbooks');
    }
};
