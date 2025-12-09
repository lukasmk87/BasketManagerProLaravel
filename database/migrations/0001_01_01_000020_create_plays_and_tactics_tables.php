<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Plays and Tactics Tables Migration
 *
 * Includes: tactic_categories, plays (with template fields), playbooks (with status),
 * playbook_plays, drill_plays, game_playbooks, training_session_plays, play_favorites
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tactic Categories
        Schema::create('tactic_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['play', 'drill', 'both'])->default('both');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->unique(['slug', 'tenant_id']);
            $table->index(['tenant_id', 'type']);
            $table->index('sort_order');
        });

        // Plays (with all enhancements from the start)
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('category_id')->nullable()->constrained('tactic_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('court_type', ['half_horizontal', 'full', 'half_vertical'])->default('half_horizontal');
            $table->json('play_data');
            $table->json('animation_data')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('category', [
                'offense', 'defense', 'press_break', 'inbound',
                'fast_break', 'zone', 'man_to_man', 'transition', 'special'
            ])->default('offense');
            $table->json('tags')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_system_template')->default(false);
            $table->integer('template_order')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'category', 'status']);
            $table->index(['created_by_user_id', 'status']);
            $table->index('is_public');
            $table->index('usage_count');
            $table->index('category_id');
            $table->index(['is_featured', 'status']);
            $table->index(['is_system_template', 'category']);
        });

        // Playbooks (with status from the start)
        Schema::create('playbooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tenant_id')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['game', 'practice', 'situational'])->default('practice');
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'category']);
            $table->index(['team_id', 'is_default']);
            $table->index('created_by_user_id');
        });

        // Playbook-Plays Pivot
        Schema::create('playbook_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['playbook_id', 'play_id']);
            $table->index(['playbook_id', 'order']);
        });

        // Drill-Plays Pivot
        Schema::create('drill_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['drill_id', 'play_id']);
            $table->index(['drill_id', 'order']);
        });

        // Game-Playbooks Pivot
        Schema::create('game_playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('playbook_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['game_id', 'playbook_id']);
        });

        // Training Session-Plays Pivot
        Schema::create('training_session_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'play_id']);
            $table->index(['training_session_id', 'order']);
        });

        // Play Favorites
        Schema::create('play_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->enum('favorite_type', ['personal', 'team_specific', 'training', 'game_prep'])->default('personal');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->json('use_cases')->nullable();
            $table->string('category_override')->nullable();
            $table->integer('personal_priority')->default(5);
            $table->boolean('is_quick_access')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'favorite_type']);
            $table->index(['play_id', 'user_id']);
            $table->unique(['play_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_favorites');
        Schema::dropIfExists('training_session_plays');
        Schema::dropIfExists('game_playbooks');
        Schema::dropIfExists('drill_plays');
        Schema::dropIfExists('playbook_plays');
        Schema::dropIfExists('playbooks');
        Schema::dropIfExists('plays');
        Schema::dropIfExists('tactic_categories');
    }
};
