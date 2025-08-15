<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Club;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminPanelController extends Controller
{
    /**
     * Show the admin settings panel.
     */
    public function settings(Request $request): Response
    {
        $this->authorize('access admin panel');

        // Get system statistics
        $systemStats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_clubs' => Club::count(),
            'active_clubs' => Club::where('is_active', true)->count(),
            'total_teams' => Team::count(),
            'active_teams' => Team::where('is_active', true)->count(),
            'total_players' => Player::count(),
            'active_players' => Player::where('status', 'active')->count(),
            'total_games' => Game::count(),
            'games_this_month' => Game::whereMonth('scheduled_at', now()->month)
                                    ->whereYear('scheduled_at', now()->year)
                                    ->count(),
        ];

        // Get recent activities
        $recentActivities = DB::table('activity_log')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->select(
                'activity_log.description',
                'activity_log.created_at',
                'users.name as user_name',
                'activity_log.subject_type',
                'activity_log.subject_id'
            )
            ->orderBy('activity_log.created_at', 'desc')
            ->limit(20)
            ->get();

        // Get system settings (these would typically be stored in a settings table)
        $settings = [
            'app_name' => config('app.name'),
            'app_timezone' => config('app.timezone'),
            'app_locale' => config('app.locale'),
            'app_debug' => config('app.debug'),
            'registration_enabled' => true, // This would come from settings
            'email_verification_required' => true, // This would come from settings
            'maintenance_mode' => app()->isDownForMaintenance(),
        ];

        return Inertia::render('Admin/Settings', [
            'system_stats' => $systemStats,
            'recent_activities' => $recentActivities,
            'settings' => $settings,
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all()->groupBy(function ($permission) {
                return explode(' ', $permission->name)[1] ?? 'other';
            }),
        ]);
    }

    /**
     * Update system settings.
     */
    public function updateSettings(Request $request)
    {
        $this->authorize('manage system settings');

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_timezone' => 'required|string|max:255',
            'app_locale' => 'required|string|max:10',
            'registration_enabled' => 'boolean',
            'email_verification_required' => 'boolean',
        ]);

        // In a real application, you would save these to a settings table
        // For now, we'll just clear relevant caches
        Cache::forget('app_settings');

        return redirect()->route('admin.settings')
            ->with('success', 'Einstellungen wurden erfolgreich aktualisiert.');
    }

    /**
     * Show user management panel.
     */
    public function users(Request $request): Response
    {
        $this->authorize('view users');

        $users = User::with(['roles', 'clubs'])
            ->withCount(['clubs', 'coachedTeams'])
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                return $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status !== null, function ($query) use ($request) {
                return $query->where('is_active', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $roleStats = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name', 'roles.id')
            ->get()
            ->pluck('count', 'name');

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::all(),
            'role_stats' => $roleStats,
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show system information panel.
     */
    public function system(Request $request): Response
    {
        $this->authorize('view system statistics');

        // System information
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        // Database information
        $databaseInfo = [
            'connection' => config('database.default'),
            'database_name' => config('database.connections.' . config('database.default') . '.database'),
            'tables_count' => DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.' . config('database.default') . '.database')])[0]->count ?? 0,
        ];

        // Cache information
        $cacheInfo = [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
        ];

        // Queue information
        $queueInfo = [
            'driver' => config('queue.default'),
            'connection' => config('queue.connections.' . config('queue.default') . '.connection') ?? config('queue.default'),
        ];

        // Storage information
        $storagePath = storage_path();
        $storageInfo = [
            'path' => $storagePath,
            'total_space' => $this->formatBytes(disk_total_space($storagePath)),
            'free_space' => $this->formatBytes(disk_free_space($storagePath)),
            'used_space' => $this->formatBytes(disk_total_space($storagePath) - disk_free_space($storagePath)),
            'usage_percentage' => round(((disk_total_space($storagePath) - disk_free_space($storagePath)) / disk_total_space($storagePath)) * 100, 1),
        ];

        return Inertia::render('Admin/System', [
            'system_info' => $systemInfo,
            'database_info' => $databaseInfo,
            'cache_info' => $cacheInfo,
            'queue_info' => $queueInfo,
            'storage_info' => $storageInfo,
        ]);
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}