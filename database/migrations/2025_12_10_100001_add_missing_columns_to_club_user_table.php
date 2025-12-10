<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds missing columns to the club_user pivot table
     * that were present in the original migration but omitted in the refactor.
     */
    public function up(): void
    {
        Schema::table('club_user', function (Blueprint $table) {
            // Membership information
            $table->date('membership_expires_at')->nullable()->after('joined_at');
            $table->boolean('is_active')->default(true)->after('membership_expires_at');
            $table->boolean('is_verified')->default(false)->after('is_active');

            // Membership details
            $table->string('membership_number')->nullable()->after('is_verified');
            $table->enum('membership_type', [
                'full', 'associate', 'honorary', 'temporary', 'trial'
            ])->default('full')->after('membership_number');

            // Financial information
            $table->decimal('membership_fee_paid', 8, 2)->default(0)->after('membership_type');
            $table->date('last_payment_date')->nullable()->after('membership_fee_paid');
            $table->enum('payment_status', ['paid', 'pending', 'overdue', 'exempt'])
                ->default('pending')
                ->after('last_payment_date');

            // Permissions within club
            $table->json('permissions')->nullable()->after('payment_status');
            $table->json('restricted_areas')->nullable()->after('permissions');

            // Contact preferences for this club
            $table->boolean('receive_newsletters')->default(true)->after('restricted_areas');
            $table->boolean('receive_game_notifications')->default(true)->after('receive_newsletters');
            $table->boolean('receive_emergency_alerts')->default(true)->after('receive_game_notifications');

            // Notes and metadata
            $table->text('notes')->nullable()->after('receive_emergency_alerts');
            $table->json('metadata')->nullable()->after('notes');

            // Indexes
            $table->index('membership_expires_at');
            $table->index('payment_status');
            $table->index(['user_id', 'is_active']);
        });

        // Modify the role enum to add missing values
        // Current: admin, trainer, manager, member
        // New: owner, admin, manager, coach, assistant_coach, player, parent, volunteer, sponsor, member
        DB::statement("ALTER TABLE club_user MODIFY COLUMN role ENUM('owner', 'admin', 'manager', 'coach', 'assistant_coach', 'player', 'parent', 'volunteer', 'sponsor', 'member') DEFAULT 'member'");

        // Note: joined_at is already timestamp in the current schema
        // The archive had it as date, but timestamp is more appropriate for tracking exact join times
        // We keep it as timestamp to avoid data loss
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_user', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['membership_expires_at']);

            // Drop columns
            $table->dropColumn([
                'membership_expires_at',
                'is_active',
                'is_verified',
                'membership_number',
                'membership_type',
                'membership_fee_paid',
                'last_payment_date',
                'payment_status',
                'permissions',
                'restricted_areas',
                'receive_newsletters',
                'receive_game_notifications',
                'receive_emergency_alerts',
                'notes',
                'metadata',
            ]);
        });

        // Revert role enum to original 4 values
        DB::statement("ALTER TABLE club_user MODIFY COLUMN role ENUM('admin', 'trainer', 'manager', 'member') DEFAULT 'member'");
    }
};
