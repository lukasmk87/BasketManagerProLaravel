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
        Schema::create('enterprise_page_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('section'); // seo, hero, audience, whitelabel, multiclub, federation, usecases, pricing, faq, contact
            $table->string('locale', 2)->default('de');
            $table->json('content');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'section', 'locale']);
            $table->index(['section', 'is_published']);
            $table->unique(['tenant_id', 'section', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_page_contents');
    }
};
