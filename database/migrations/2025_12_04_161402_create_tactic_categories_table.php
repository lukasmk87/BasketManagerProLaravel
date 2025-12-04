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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tactic_categories');
    }
};
