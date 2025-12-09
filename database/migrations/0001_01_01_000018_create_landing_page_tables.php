<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Landing Page and Legal Tables Migration
 *
 * Includes: landing_page_contents (with locale support from the start), legal_pages
 */
return new class extends Migration
{
    public function up(): void
    {
        // Landing Page Contents (with locale from start - no separate migration needed)
        Schema::create('landing_page_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('section');
            $table->string('locale', 5)->default('de');
            $table->json('content');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'section']);
            $table->index(['section', 'is_published']);
            $table->unique(['tenant_id', 'section', 'locale']);
        });

        // Legal Pages
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content');
            $table->text('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
        Schema::dropIfExists('landing_page_contents');
    }
};
