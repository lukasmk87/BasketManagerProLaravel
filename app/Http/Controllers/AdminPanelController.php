<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            'database_name' => config('database.connections.'.config('database.default').'.database'),
            'tables_count' => DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [config('database.connections.'.config('database.default').'.database')])[0]->count ?? 0,
        ];

        // Cache information
        $cacheInfo = [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
        ];

        // Queue information
        $queueInfo = [
            'driver' => config('queue.default'),
            'connection' => config('queue.connections.'.config('queue.default').'.connection') ?? config('queue.default'),
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
     * Show the create user form.
     */
    public function createUser(Request $request): Response
    {
        $this->authorize('create users');

        // Super-Admins see all clubs from all tenants, regular admins see only their tenant's clubs
        $clubs = auth()->user()->hasRole('super_admin')
            ? Club::allTenants()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'tenant_id'])
            : Club::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/CreateUser', [
            'roles' => Role::all(),
            'clubs' => $clubs,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function storeUser(Request $request)
    {
        $this->authorize('create users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'is_active' => 'boolean',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'clubs' => 'nullable|array',
            'clubs.*' => 'exists:clubs,id',
            'send_credentials_email' => 'boolean',
        ]);

        // Store the plain password before hashing
        $plainPassword = $validated['password'];

        $userService = app(\App\Services\UserService::class);
        $user = $userService->createUser($validated);

        // Attach user to clubs if provided
        if (isset($validated['clubs']) && count($validated['clubs']) > 0) {
            // Determine pivot role based on Spatie roles
            $pivotRole = in_array('club_admin', $validated['roles']) ? 'admin' : 'member';

            foreach ($validated['clubs'] as $clubId) {
                $user->clubs()->attach($clubId, [
                    'role' => $pivotRole,
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
            }
        }

        // Send credentials email if requested
        if ($request->boolean('send_credentials_email')) {
            $user->notify(new \App\Notifications\NewUserCreatedNotification(
                $plainPassword,
                auth()->user()->name
            ));
        }

        return redirect()->route('admin.users')
            ->with('success', 'Benutzer wurde erfolgreich erstellt.'.
                ($request->boolean('send_credentials_email') ? ' Eine E-Mail mit den Zugangsdaten wurde versendet.' : ''));
    }

    /**
     * Show the edit user form.
     */
    public function editUser(Request $request, User $user): Response
    {
        $this->authorize('edit users');

        // Super-Admins see all clubs from all tenants, regular admins see only their tenant's clubs
        $clubs = auth()->user()->hasRole('super_admin')
            ? Club::allTenants()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'tenant_id'])
            : Club::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/EditUser', [
            'user' => $user->load(['roles', 'clubs']),
            'roles' => Role::all(),
            'clubs' => $clubs,
        ]);
    }

    /**
     * Update a user.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->authorize('edit users');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
            'clubs' => 'nullable|array',
            'clubs.*' => 'exists:clubs,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        // Update roles if provided
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        // Update club assignments if provided (only for Super Admins)
        if (isset($validated['clubs']) && auth()->user()->hasRole('super_admin')) {
            // Determine pivot role based on Spatie roles
            $pivotRole = in_array('club_admin', $validated['roles'] ?? []) ? 'admin' : 'member';

            // Sync clubs with appropriate pivot role
            $clubData = [];
            foreach ($validated['clubs'] as $clubId) {
                $clubData[$clubId] = [
                    'role' => $pivotRole,
                    'joined_at' => $user->clubs()->where('club_id', $clubId)->exists()
                        ? $user->clubs()->where('club_id', $clubId)->first()->pivot->joined_at
                        : now(),
                    'is_active' => true,
                ];
            }
            $user->clubs()->sync($clubData);
        }

        return redirect()->route('admin.users')
            ->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    /**
     * Delete a user.
     */
    public function destroyUser(Request $request, User $user)
    {
        // Log the deletion attempt
        Log::info('User deletion attempt', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'target_user_name' => $user->name,
            'target_user_roles' => $user->roles->pluck('name')->toArray(),
            'requesting_user_id' => auth()->id(),
            'requesting_user_email' => auth()->user()->email,
            'requesting_user_roles' => auth()->user()->roles->pluck('name')->toArray(),
        ]);

        // Use Policy-based authorization (checks permissions AND business rules)
        try {
            $this->authorize('delete', $user);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('User deletion denied by policy', [
                'target_user_id' => $user->id,
                'requesting_user_id' => auth()->id(),
                'policy_message' => $e->getMessage(),
            ]);

            return redirect()->route('admin.users')
                ->with('error', 'Sie haben keine Berechtigung, diesen Benutzer zu löschen. '.
                    'Mögliche Gründe: Sie können sich nicht selbst löschen, oder Sie haben nicht die erforderlichen Berechtigungen.');
        }

        // Use UserService for intelligent soft/hard delete
        $userService = app(\App\Services\UserService::class);

        try {
            // Store user info before deletion
            $userName = $user->name;
            $userEmail = $user->email;
            $userId = $user->id;

            $userService->deleteUser($user);

            Log::info('User deleted successfully', [
                'user_id' => $userId,
                'user_email' => $userEmail,
                'user_name' => $userName,
                'deleted_by_user_id' => auth()->id(),
            ]);

            return redirect()->route('admin.users')
                ->with('success', "Benutzer \"{$userName}\" wurde erfolgreich gelöscht.");
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'target_user_id' => $user->id,
                'requesting_user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.users')
                ->with('error', 'Fehler beim Löschen des Benutzers: '.$e->getMessage().
                    ' Bitte überprüfen Sie die Server-Logs für weitere Details.');
        }
    }

    /**
     * Send password reset link to user.
     */
    public function sendPasswordResetLink(Request $request, User $user)
    {
        $this->authorize('edit users');

        // Use UserService to send password reset
        $userService = app(\App\Services\UserService::class);

        try {
            $userService->sendPasswordReset($user);

            return back()->with('success',
                'Passwort-Reset-Link wurde an '.$user->email.' gesendet.');
        } catch (\Exception $e) {
            return back()->with('error',
                'Fehler beim Senden des Passwort-Reset-Links: '.$e->getMessage());
        }
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

        return round($bytes, $precision).' '.$units[$i];
    }
}
