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
        Schema::create('club_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Membership information
            $table->enum('role', [
                'owner', 'admin', 'manager', 'coach', 'assistant_coach',
                'player', 'parent', 'volunteer', 'sponsor', 'member'
            ])->default('member');
            
            $table->date('joined_at');
            $table->date('membership_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            
            // Membership details
            $table->string('membership_number')->nullable();
            $table->enum('membership_type', [
                'full', 'associate', 'honorary', 'temporary', 'trial'
            ])->default('full');
            
            // Financial information
            $table->decimal('membership_fee_paid', 8, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->enum('payment_status', ['paid', 'pending', 'overdue', 'exempt'])->default('pending');
            
            // Permissions within club
            $table->json('permissions')->nullable();
            $table->json('restricted_areas')->nullable();
            
            // Contact preferences for this club
            $table->boolean('receive_newsletters')->default(true);
            $table->boolean('receive_game_notifications')->default(true);
            $table->boolean('receive_emergency_alerts')->default(true);
            
            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - user can have only one active membership per club
            $table->unique(['club_id', 'user_id']);
            
            // Indexes
            $table->index(['club_id', 'role']);
            $table->index(['user_id', 'is_active']);
            $table->index('membership_expires_at');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_user');
    }
};