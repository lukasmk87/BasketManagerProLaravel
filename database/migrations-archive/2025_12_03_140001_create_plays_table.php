<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');

            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();

            // Court Configuration
            $table->enum('court_type', ['half_horizontal', 'full', 'half_vertical'])->default('half_horizontal');

            // Play Data (JSON structure for positions, lines, shapes)
            $table->json('play_data');

            // Animation Data (JSON for keyframes)
            $table->json('animation_data')->nullable();

            // Thumbnail for preview
            $table->string('thumbnail_path')->nullable();

            // Classification
            $table->enum('category', [
                'offense',
                'defense',
                'press_break',
                'inbound',
                'fast_break',
                'zone',
                'man_to_man',
                'transition',
                'special',
            ])->default('offense');

            // Tags for search
            $table->json('tags')->nullable();

            // Visibility and Status
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Usage tracking
            $table->integer('usage_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'category', 'status']);
            $table->index(['created_by_user_id', 'status']);
            $table->index('is_public');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plays');
    }
};
