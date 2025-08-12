<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drill_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Favorite Information
            $table->text('notes')->nullable(); // Why they favorited it
            $table->json('tags')->nullable(); // Personal tags for organization
            $table->enum('favorite_type', [
                'personal', 'team_specific', 'age_group', 'situational'
            ])->default('personal');
            
            // Usage Context
            $table->foreignId('team_id')->nullable()->constrained(); // For team-specific favorites
            $table->string('intended_age_group')->nullable();
            $table->json('use_cases')->nullable(); // When to use this drill
            
            // Organization
            $table->string('category_override')->nullable(); // Personal categorization
            $table->integer('personal_priority')->default(1); // 1-10 priority
            $table->boolean('is_quick_access')->default(false); // Show in quick menu
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'favorite_type']);
            $table->index(['drill_id', 'user_id']);
            $table->unique(['drill_id', 'user_id']); // One favorite per user per drill
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drill_favorites');
    }
};