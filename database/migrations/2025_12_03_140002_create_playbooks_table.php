<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playbooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();

            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();

            // Classification
            $table->enum('category', ['game', 'practice', 'situational'])->default('practice');

            // Default playbook for team
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'category']);
            $table->index(['team_id', 'is_default']);
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playbooks');
    }
};
