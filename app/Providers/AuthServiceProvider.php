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
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

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