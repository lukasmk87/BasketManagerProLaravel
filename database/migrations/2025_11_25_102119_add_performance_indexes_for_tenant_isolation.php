<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERF-004: Add performance indexes for tenant isolation.
 *
 * This migration adds composite indexes to optimize queries that filter
 * by tenant_id, which is crucial for multi-tenant query performance.
 *
 * @see SECURITY_AND_PERFORMANCE_FIXES.md PERF-004
 */
return new class extends Migration
{
    /**
     * Tables that need tenant_id composite indexes for common query patterns.
     */
    protected array $tenantIndexes = [
        // Core business tables with high query volume
        'clubs' => ['tenant_id', 'created_at'],
        'basketball_teams' => ['tenant_id', 'club_id'],
        'players' => ['tenant_id', 'team_id'],
        'games' => ['tenant_id', 'scheduled_at'],
        'training_sessions' => ['tenant_id', 'scheduled_at'],

        // Subscription and analytics tables
        'subscription_mrr_snapshots' => ['tenant_id', 'snapshot_date'],
        'club_subscription_events' => ['tenant_id', 'event_date'],
        'club_subscription_cohorts' => ['tenant_id', 'cohort_month'],
        'club_usages' => ['tenant_id', 'club_id'],
        'tenant_usages' => ['tenant_id', 'metric'],
        'tenant_plan_customizations' => ['tenant_id', 'subscription_plan_id'],

        // API and tracking tables
        'api_usage_tracking' => ['tenant_id', 'request_timestamp'],
        'webhook_events' => ['tenant_id', 'created_at'],

        // Federation integration tables
        'dbb_integrations' => ['tenant_id', 'entity_type'],
        'fiba_integrations' => ['tenant_id', 'entity_type'],

        // Game registrations
        'game_registrations' => ['game_id', 'player_id'],
        'training_registrations' => ['training_session_id', 'player_id'],
    ];

    /**
     * Single-column indexes for frequently filtered columns.
     */
    protected array $simpleIndexes = [
        // Stripe-related lookups
        'clubs' => ['stripe_customer_id', 'stripe_subscription_id'],

        // Status filters
        'games' => ['status'],
        'training_sessions' => ['status'],
        'webhook_events' => ['status', 'event_type'],

        // Common foreign key lookups
        'players' => ['user_id'],
        'game_registrations' => ['availability_status', 'registration_status'],
        'training_registrations' => ['status'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add composite indexes for tenant + common filter patterns
        foreach ($this->tenantIndexes as $table => $columns) {
            if (Schema::hasTable($table) && $this->allColumnsExist($table, $columns)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $indexName = $this->generateIndexName($table->getTable(), $columns);

                    // Only add if index doesn't exist
                    if (!$this->indexExists($table->getTable(), $indexName)) {
                        $table->index($columns, $indexName);
                    }
                });
            }
        }

        // Add simple indexes for frequently filtered columns
        foreach ($this->simpleIndexes as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $indexName = "{$tableName}_{$column}_idx";

                            if (!$this->indexExists($tableName, $indexName)) {
                                $table->index($column, $indexName);
                            }
                        }
                    }
                });
            }
        }

        // Special: Add unique constraint for webhook event deduplication
        if (Schema::hasTable('webhook_events') && Schema::hasColumn('webhook_events', 'stripe_event_id')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (!$this->indexExists('webhook_events', 'webhook_events_stripe_event_id_unique')) {
                    $table->unique('stripe_event_id', 'webhook_events_stripe_event_id_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite indexes
        foreach ($this->tenantIndexes as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $indexName = $this->generateIndexName($table->getTable(), $columns);
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Index may not exist, ignore
                    }
                });
            }
        }

        // Drop simple indexes
        foreach ($this->simpleIndexes as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                    foreach ($columns as $column) {
                        $indexName = "{$tableName}_{$column}_idx";
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index may not exist, ignore
                        }
                    }
                });
            }
        }

        // Drop unique constraint
        if (Schema::hasTable('webhook_events')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                try {
                    $table->dropUnique('webhook_events_stripe_event_id_unique');
                } catch (\Exception $e) {
                    // Index may not exist, ignore
                }
            });
        }
    }

    /**
     * Generate a consistent index name for composite indexes.
     */
    protected function generateIndexName(string $table, array $columns): string
    {
        return $table . '_' . implode('_', $columns) . '_idx';
    }

    /**
     * Check if all columns exist in the table.
     */
    protected function allColumnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if an index already exists.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        // MySQL check
        if ($connection->getDriverName() === 'mysql') {
            $result = $connection->select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return count($result) > 0;
        }

        // PostgreSQL check
        if ($connection->getDriverName() === 'pgsql') {
            $result = $connection->select(
                "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return count($result) > 0;
        }

        // SQLite check
        if ($connection->getDriverName() === 'sqlite') {
            $result = $connection->select(
                "SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
            return count($result) > 0;
        }

        return false;
    }
};
