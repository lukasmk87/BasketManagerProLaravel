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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('domain')->nullable()->unique()->index();
            $table->string('subdomain')->nullable()->unique()->index();
            
            // Contact & Billing
            $table->string('billing_email');
            $table->string('billing_name')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('country_code', 2)->default('DE');
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('locale')->default('de');
            $table->string('currency', 3)->default('EUR');
            
            // Subscription & Limits
            $table->string('subscription_tier')->default('free'); // free, basic, professional, enterprise
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();
            
            // Features & Settings
            $table->json('features')->nullable(); // Custom feature flags
            $table->text('settings')->nullable(); // Tenant-specific settings (encrypted)
            $table->json('branding')->nullable(); // Logo, colors, etc.
            $table->text('security_settings')->nullable(); // 2FA requirements, IP restrictions, etc. (encrypted)
            
            // Limits & Usage
            $table->integer('max_users')->default(10);
            $table->integer('max_teams')->default(5);
            $table->integer('max_storage_gb')->default(10);
            $table->integer('max_api_calls_per_hour')->default(1000);
            $table->integer('current_users_count')->default(0);
            $table->integer('current_teams_count')->default(0);
            $table->decimal('current_storage_gb', 10, 2)->default(0);
            
            // Database & Technical
            $table->string('database_name')->nullable(); // For separate database per tenant
            $table->string('database_host')->nullable();
            $table->string('database_port')->nullable();
            $table->text('database_password')->nullable(); // Encrypted
            $table->string('schema_name')->nullable(); // For PostgreSQL schemas
            
            // API & Integration
            $table->string('api_key')->nullable()->unique(); // Encrypted
            $table->text('api_secret')->nullable(); // Encrypted
            $table->text('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable(); // Encrypted
            $table->json('allowed_domains')->nullable(); // CORS whitelist
            $table->json('blocked_ips')->nullable(); // IP blacklist
            
            // Analytics & Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('total_logins')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('monthly_recurring_revenue', 10, 2)->default(0);
            
            // Compliance & Legal
            $table->boolean('gdpr_accepted')->default(false);
            $table->timestamp('gdpr_accepted_at')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->json('data_retention_policy')->nullable();
            $table->boolean('data_processing_agreement_signed')->default(false);
            
            // Meta
            $table->uuid('created_by')->nullable();
            $table->uuid('onboarded_by')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->text('notes')->nullable(); // Internal notes
            $table->json('tags')->nullable(); // For segmentation
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('subscription_tier');
            $table->index('created_at');
            $table->index(['is_active', 'subscription_tier']);
            $table->index(['domain', 'is_active']);
            $table->index(['subdomain', 'is_active']);
        });
        
        // Add foreign key constraint to users table
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
        
        // Add tenant_id to all relevant tables
        $tables = [
            'teams', 'players', 'games', 'tournaments', 'training_sessions',
            'clubs', 'seasons', 'game_actions', 'game_statistics',
            'training_drills', 'media', 'emergency_contacts'
        ];
        
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->uuid('tenant_id')->nullable()->after('id');
                        $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                        $table->index('tenant_id');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove tenant_id from all tables
        $tables = [
            'users', 'teams', 'players', 'games', 'tournaments', 'training_sessions',
            'clubs', 'seasons', 'game_actions', 'game_statistics',
            'training_drills', 'media', 'emergency_contacts'
        ];
        
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
        
        Schema::dropIfExists('tenants');
    }
};