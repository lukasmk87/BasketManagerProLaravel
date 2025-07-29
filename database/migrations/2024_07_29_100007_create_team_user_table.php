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
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Role within the team
            $table->enum('role', [
                'head_coach', 'assistant_coach', 'player', 'manager', 
                'trainer', 'scout', 'statistician', 'volunteer'
            ]);
            
            // Dates
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Specific player information (when role is 'player')
            $table->integer('jersey_number')->nullable();
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_captain')->default(false);
            
            // Coach information (when role is coach-related)
            $table->string('coaching_license')->nullable();
            $table->json('coaching_certifications')->nullable();
            $table->text('coaching_specialties')->nullable();
            
            // Manager/Staff information
            $table->json('responsibilities')->nullable();
            $table->json('access_permissions')->nullable();
            
            // Contract information
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->json('contract_terms')->nullable();
            
            // Performance and statistics
            $table->json('performance_metrics')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('performance_rating', 3, 1)->nullable();
            
            // Emergency contact for this team context
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['team_id', 'user_id'], 'unique_team_user');
            $table->unique(['team_id', 'jersey_number'], 'unique_jersey_per_team');
            
            // Indexes
            $table->index(['team_id', 'role']);
            $table->index(['user_id', 'is_active']);
            $table->index('jersey_number');
            $table->index(['is_starter', 'is_captain']);
            $table->index(['contract_start', 'contract_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};