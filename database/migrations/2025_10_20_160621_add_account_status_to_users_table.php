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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['pending', 'active', 'suspended'])
                ->default('active')
                ->after('email_verified_at')
                ->comment('User account status for registration flow');
            $table->boolean('pending_verification')->default(false)->after('account_status')
                ->comment('Whether user needs email verification');

            // Index for querying pending accounts
            $table->index('account_status', 'idx_account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_account_status');
            $table->dropColumn(['account_status', 'pending_verification']);
        });
    }
};
