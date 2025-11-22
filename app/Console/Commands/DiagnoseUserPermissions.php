<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DiagnoseUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:user-permissions
                            {--permission=create players : The permission to check}
                            {--show-all : Show all users, not just those missing the permission}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose user permissions and role assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissionName = $this->option('permission');
        $showAll = $this->option('show-all');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  USER PERMISSIONS DIAGNOSTIC REPORT');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Check if permission exists
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            $this->error("❌ Permission '{$permissionName}' does NOT exist!");
            $this->newLine();
            $this->warn('Available permissions:');
            Permission::orderBy('name')->get()->each(function ($p) {
                $this->line("  - {$p->name}");
            });
            return 1;
        }

        $this->info("✅ Checking permission: '{$permissionName}' (ID: {$permission->id})");
        $this->newLine();

        // Show which roles have this permission
        $this->info('📋 Roles with this permission:');
        $rolesWithPermission = Role::whereHas('permissions', function ($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->get();

        if ($rolesWithPermission->isEmpty()) {
            $this->warn('  ⚠️  No roles have this permission assigned!');
        } else {
            foreach ($rolesWithPermission as $role) {
                $userCount = $role->users()->count();
                $this->line("  ✅ {$role->name} ({$userCount} users)");
            }
        }
        $this->newLine();

        // Analyze users
        $users = User::with('roles.permissions', 'permissions')->get();
        $totalUsers = $users->count();
        $usersWithPermission = 0;
        $usersWithoutPermission = 0;

        $this->info("📊 Analyzing {$totalUsers} users...");
        $this->newLine();

        $usersData = [];

        foreach ($users as $user) {
            $hasPermission = $user->can($permissionName);
            $roles = $user->roles->pluck('name')->toArray();
            $directPermissions = $user->permissions->pluck('name')->toArray();

            if ($hasPermission) {
                $usersWithPermission++;
            } else {
                $usersWithoutPermission++;
            }

            $usersData[] = [
                'user' => $user,
                'hasPermission' => $hasPermission,
                'roles' => $roles,
                'directPermissions' => $directPermissions,
            ];
        }

        // Summary statistics
        $this->info('📈 Summary Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Users', $totalUsers],
                ['Users WITH permission', $usersWithPermission],
                ['Users WITHOUT permission', $usersWithoutPermission],
                ['Permission coverage', sprintf('%.1f%%', ($usersWithPermission / max($totalUsers, 1)) * 100)],
            ]
        );
        $this->newLine();

        // Show users with permission (if --show-all)
        if ($showAll && $usersWithPermission > 0) {
            $this->info('✅ Users WITH permission:');
            $this->newLine();

            $tableData = [];
            foreach ($usersData as $data) {
                if ($data['hasPermission']) {
                    $tableData[] = [
                        $data['user']->id,
                        $data['user']->name,
                        $data['user']->email,
                        empty($data['roles']) ? '❌ No role' : implode(', ', $data['roles']),
                        empty($data['directPermissions']) ? '-' : implode(', ', $data['directPermissions']),
                    ];
                }
            }

            $this->table(
                ['ID', 'Name', 'Email', 'Roles', 'Direct Permissions'],
                $tableData
            );
            $this->newLine();
        }

        // Show users WITHOUT permission (always)
        if ($usersWithoutPermission > 0) {
            $this->warn("⚠️  Users WITHOUT '{$permissionName}' permission:");
            $this->newLine();

            $tableData = [];
            $recommendations = [];

            foreach ($usersData as $data) {
                if (!$data['hasPermission']) {
                    $user = $data['user'];
                    $roles = $data['roles'];

                    // Generate recommendation
                    if (empty($roles)) {
                        $recommendation = '➡️  Assign role: club_admin OR trainer';
                    } elseif (in_array('assistant_coach', $roles) || in_array('team_manager', $roles)) {
                        $recommendation = '➡️  Consider upgrading to: trainer OR club_admin';
                    } elseif (in_array('player', $roles) || in_array('parent', $roles) || in_array('guest', $roles)) {
                        $recommendation = '✓ Correct - these roles should NOT have this permission';
                    } else {
                        $recommendation = '⚠️  Check role configuration';
                    }

                    $tableData[] = [
                        $user->id,
                        $user->name,
                        $user->email,
                        empty($roles) ? '❌ No role' : implode(', ', $roles),
                        $recommendation,
                    ];

                    $recommendations[] = [
                        'user_id' => $user->id,
                        'recommendation' => $recommendation,
                    ];
                }
            }

            $this->table(
                ['ID', 'Name', 'Email', 'Current Roles', 'Recommendation'],
                $tableData
            );
            $this->newLine();

            // Action recommendations
            $this->info('🔧 Suggested Actions:');
            $this->newLine();

            $actionableUsers = collect($recommendations)->filter(function ($rec) {
                return str_starts_with($rec['recommendation'], '➡️');
            });

            if ($actionableUsers->isNotEmpty()) {
                $this->warn('Users that need role assignment:');
                foreach ($actionableUsers as $rec) {
                    $user = User::find($rec['user_id']);
                    $this->line("  php artisan tinker --execute=\"User::find({$user->id})->assignRole('club_admin');\"");
                }
                $this->newLine();
            }

            $this->info('To assign Club Admin role manually in Tinker:');
            $this->comment('  $user = User::find(<USER_ID>);');
            $this->comment('  $user->assignRole(\'club_admin\');');
            $this->newLine();

            $this->info('To assign Trainer role manually in Tinker:');
            $this->comment('  $user = User::find(<USER_ID>);');
            $this->comment('  $user->assignRole(\'trainer\');');
        } else {
            $this->info("✅ All users have the '{$permissionName}' permission!");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  END OF REPORT');
        $this->info('═══════════════════════════════════════════════════════════════');

        return 0;
    }
}
