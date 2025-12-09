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
        Schema::create('play_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Favorite Information
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->enum('favorite_type', [
                'personal', 'team_specific', 'training', 'game_prep'
            ])->default('personal');

            // Usage Context
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->json('use_cases')->nullable();

            // Organization
            $table->string('category_override')->nullable();
            $table->integer('personal_priority')->default(5);
            $table->boolean('is_quick_access')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'favorite_type']);
            $table->index(['play_id', 'user_id']);
            $table->unique(['play_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_favorites');
    }
};
