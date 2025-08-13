<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SetupRowLevelSecurityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:setup-rls 
                          {--force : Force setup even if RLS is already enabled}
                          {--dry-run : Show what would be executed without running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Row Level Security policies for multi-tenant architecture';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('database.default') !== 'pgsql') {
            $this->warn('Row Level Security is only supported for PostgreSQL databases');
            $this->info('For MySQL, tenant isolation is handled via Eloquent scopes');
            return 0;
        }

        $this->info('Setting up Row Level Security for multi-tenant architecture...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Check if RLS is already enabled
            if (!$this->option('force') && $this->isRLSEnabled()) {
                $this->warn('Row Level Security appears to already be enabled');
                if (!$this->confirm('Do you want to continue anyway?')) {
                    return 0;
                }
            }

            $this->setupRowLevelSecurity();
            $this->setupAdminRole();
            $this->createPerformanceIndexes();
            
            $this->info('✅ Row Level Security setup completed successfully!');
            $this->newLine();
            $this->info('Next steps:');
            $this->line('• Test tenant isolation with: php artisan tenant:test-isolation');
            $this->line('• Create admin user with: php artisan tenant:create-admin');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to setup Row Level Security: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if RLS is already enabled.
     */
    private function isRLSEnabled(): bool
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM pg_policies 
                WHERE schemaname = 'public' 
                AND policyname LIKE 'tenant_%_policy'
            ");
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Setup Row Level Security policies.
     */
    private function setupRowLevelSecurity(): void
    {
        $this->info('📋 Setting up Row Level Security policies...');
        
        $sqlFile = database_path('sql/row_level_security_policies.sql');
        
        if (!File::exists($sqlFile)) {
            throw new \Exception("RLS SQL file not found: {$sqlFile}");
        }
        
        $sql = File::get($sqlFile);
        
        if ($this->option('dry-run')) {
            $this->info('Would execute SQL from: ' . $sqlFile);
            return;
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || str_starts_with($statement, '--')) {
                continue;
            }
            
            try {
                DB::statement($statement);
                $this->line('✓ Executed: ' . Str::limit($statement, 60));
            } catch (\Exception $e) {
                $this->warn('⚠ Failed: ' . $e->getMessage());
                $this->line('Statement: ' . Str::limit($statement, 100));
            }
        }
    }

    /**
     * Setup admin role for bypassing RLS.
     */
    private function setupAdminRole(): void
    {
        $this->info('👤 Setting up admin role...');
        
        if ($this->option('dry-run')) {
            $this->info('Would create basketmanager_admin role');
            return;
        }
        
        try {
            // Check if role exists
            $roleExists = DB::select("
                SELECT 1 FROM pg_roles WHERE rolname = 'basketmanager_admin'
            ");
            
            if (empty($roleExists)) {
                DB::statement('CREATE ROLE basketmanager_admin');
                $this->line('✓ Created basketmanager_admin role');
            } else {
                $this->line('✓ basketmanager_admin role already exists');
            }
            
            // Grant permissions
            DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO basketmanager_admin');
            DB::statement('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO basketmanager_admin');
            
            $this->line('✓ Granted permissions to admin role');
            
        } catch (\Exception $e) {
            $this->warn('⚠ Failed to setup admin role: ' . $e->getMessage());
        }
    }

    /**
     * Create performance indexes.
     */
    private function createPerformanceIndexes(): void
    {
        $this->info('🚀 Creating performance indexes...');
        
        $tables = [
            'users', 'teams', 'players', 'games', 'tournaments',
            'training_sessions', 'clubs', 'seasons', 'game_actions',
            'game_statistics', 'training_drills', 'media', 'emergency_contacts'
        ];
        
        if ($this->option('dry-run')) {
            $this->info('Would create tenant_id indexes on: ' . implode(', ', $tables));
            return;
        }
        
        foreach ($tables as $table) {
            try {
                $indexName = "idx_{$table}_tenant_id";
                
                // Check if table and column exist
                $tableExists = DB::select("
                    SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = 'public' AND table_name = ?
                ", [$table]);
                
                if (empty($tableExists)) {
                    $this->line("⚠ Skipping {$table} - table does not exist");
                    continue;
                }
                
                $columnExists = DB::select("
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = ? AND column_name = 'tenant_id'
                ", [$table]);
                
                if (empty($columnExists)) {
                    $this->line("⚠ Skipping {$table} - tenant_id column does not exist");
                    continue;
                }
                
                // Check if index already exists
                $indexExists = DB::select("
                    SELECT 1 FROM pg_indexes 
                    WHERE schemaname = 'public' 
                    AND tablename = ? AND indexname = ?
                ", [$table, $indexName]);
                
                if (!empty($indexExists)) {
                    $this->line("✓ Index {$indexName} already exists");
                    continue;
                }
                
                DB::statement("CREATE INDEX CONCURRENTLY {$indexName} ON {$table}(tenant_id)");
                $this->line("✓ Created index {$indexName}");
                
            } catch (\Exception $e) {
                $this->warn("⚠ Failed to create index for {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Test tenant isolation.
     */
    private function testTenantIsolation(): void
    {
        $this->info('🧪 Testing tenant isolation...');
        
        try {
            // Create test tenant context
            $testTenantId = '550e8400-e29b-41d4-a716-446655440000';
            DB::statement('SET basketmanager.current_tenant_id = ?', [$testTenantId]);
            
            // Test query with RLS
            $count = DB::table('users')->count();
            $this->line("✓ Query executed with RLS - returned {$count} users for tenant");
            
            // Reset tenant context
            DB::statement('SET basketmanager.current_tenant_id = DEFAULT');
            
        } catch (\Exception $e) {
            $this->warn('⚠ Tenant isolation test failed: ' . $e->getMessage());
        }
    }
}