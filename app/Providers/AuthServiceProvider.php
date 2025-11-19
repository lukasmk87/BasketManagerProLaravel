<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Drill;
use App\Policies\DrillPolicy;
use App\Models\Team;
use App\Policies\TeamPolicy;
use App\Models\Player;
use App\Policies\PlayerPolicy;
use App\Models\Game;
use App\Policies\GamePolicy;
use App\Models\Club;
use App\Policies\ClubPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\GymHall;
use App\Policies\GymHallPolicy;
use App\Models\EmergencyContact;
use App\Policies\EmergencyContactPolicy;
use App\Models\TrainingSession;
use App\Policies\TrainingSessionPolicy;
use App\Models\ClubSubscriptionPlan;
use App\Policies\ClubSubscriptionPlanPolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;
use App\Models\ClubTransfer;
use App\Policies\ClubTransferPolicy;
use App\Models\Season;
use App\Policies\SeasonPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Drill::class => DrillPolicy::class,
        Team::class => TeamPolicy::class,
        Player::class => PlayerPolicy::class,
        Game::class => GamePolicy::class,
        Club::class => ClubPolicy::class,
        User::class => UserPolicy::class,
        GymHall::class => GymHallPolicy::class,
        EmergencyContact::class => EmergencyContactPolicy::class,
        TrainingSession::class => TrainingSessionPolicy::class,
        ClubSubscriptionPlan::class => ClubSubscriptionPlanPolicy::class,
        Role::class => RolePolicy::class,
        ClubTransfer::class => ClubTransferPolicy::class,
        Season::class => SeasonPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Skip during installation to prevent database access before migrations
        if (!file_exists(storage_path('installed')) || file_exists(storage_path('installing'))) {
            return;
        }

        $this->registerPolicies();

        // Super Admins bypass all authorization checks
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true; // Auto-authorize all actions
            }
            return null; // Continue with normal authorization flow
        });

        // Define additional gates for drill management
        Gate::define('view-drills', function ($user) {
            return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']) ||
                   $user->can('view drills');
        });

        Gate::define('create-drills', function ($user) {
            return $user->hasRole(['trainer', 'club_admin', 'admin', 'super_admin']) ||
                   $user->can('create drills');
        });

        Gate::define('review-drills', function ($user) {
            return $user->hasRole(['club_admin', 'admin', 'super_admin']) ||
                   $user->can('review drills');
        });

        Gate::define('manage-drill-library', function ($user) {
            return $user->hasRole(['admin', 'super_admin']) ||
                   $user->can('manage drill library');
        });
    }
}