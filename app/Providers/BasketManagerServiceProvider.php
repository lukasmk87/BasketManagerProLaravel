<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Team;
use App\Models\Player;
use App\Models\Club;
use App\Models\Game;
use App\Models\EmergencyContact;
use App\Policies\UserPolicy;
use App\Policies\TeamPolicy;
use App\Policies\PlayerPolicy;
use App\Policies\ClubPolicy;
use App\Policies\GamePolicy;
use App\Policies\EmergencyContactPolicy;
use App\Services\StatisticsService;
use App\Services\TeamService;
use App\Services\PlayerService;
use App\Services\EmergencyContactService;
use App\Services\TwoFactorAuthService;
use App\Services\LocalizationService;
use App\Observers\UserObserver;
use App\Observers\TeamObserver;
use App\Observers\PlayerObserver;
use App\Observers\GameObserver;
use App\Observers\TrainingSessionObserver;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class BasketManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Core Services
        $this->app->singleton(StatisticsService::class, function ($app) {
            return new StatisticsService(
                $app->make('cache'),
                $app->make('db')
            );
        });

        $this->app->singleton(TeamService::class);
        $this->app->singleton(PlayerService::class);
        $this->app->singleton(EmergencyContactService::class);
        $this->app->singleton(TwoFactorAuthService::class);
        $this->app->singleton(LocalizationService::class);

        // Register Repository Interfaces and Implementations
        $this->registerRepositories();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policies
        $this->registerPolicies();

        // Register Model Observers
        $this->registerObservers();

        // Register Custom Validation Rules
        $this->registerValidationRules();

        // Register Custom Blade Directives
        $this->registerBladeDirectives();

        // Register View Composers
        $this->registerViewComposers();

        // Boot Multi-Language Support
        $this->bootLocalization();

        // Register Custom Commands
        $this->registerCommands();

        // Register Event Listeners
        $this->registerEventListeners();
    }

    /**
     * Register repository interfaces and implementations.
     */
    protected function registerRepositories(): void
    {
        // Repository bindings will be added here when implementing repository pattern
        // $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        // $this->app->bind(PlayerRepositoryInterface::class, PlayerRepository::class);
        // $this->app->bind(GameRepositoryInterface::class, GameRepository::class);
    }

    /**
     * Register model policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
        Gate::policy(Club::class, ClubPolicy::class);
        Gate::policy(Game::class, GamePolicy::class);
        Gate::policy(EmergencyContact::class, EmergencyContactPolicy::class);
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        User::observe(UserObserver::class);
        Team::observe(TeamObserver::class);
        Player::observe(PlayerObserver::class);
        Game::observe(GameObserver::class);
        TrainingSession::observe(TrainingSessionObserver::class);
    }

    /**
     * Register custom validation rules.
     */
    protected function registerValidationRules(): void
    {
        // Basketball Position Validation
        Validator::extend('basketball_position', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['PG', 'SG', 'SF', 'PF', 'C']);
        });

        // Jersey Number Validation
        Validator::extend('jersey_number', function ($attribute, $value, $parameters, $validator) {
            return is_numeric($value) && $value >= 0 && $value <= 99;
        });

        // Current Season Format Validation (YYYY-YY)
        Validator::extend('current_season', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^\d{4}-\d{2}$/', $value);
        });

        // Basketball Category Validation
        Validator::extend('basketball_category', function ($attribute, $value, $parameters, $validator) {
            $categories = ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Herren', 'Damen', 'Senioren', 'Mixed'];
            return in_array($value, $categories);
        });

        // Jersey Number Unique Per Team
        Validator::extend('jersey_unique_per_team', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 1) {
                return false;
            }

            $teamId = $parameters[0];
            $playerId = $parameters[1] ?? null;

            $query = Player::where('team_id', $teamId)->where('jersey_number', $value);
            
            if ($playerId) {
                $query->where('id', '!=', $playerId);
            }

            return !$query->exists();
        });

        // Emergency Relationship Validation
        Validator::extend('emergency_relationship', function ($attribute, $value, $parameters, $validator) {
            $relationships = [
                'parent', 'mother', 'father', 'guardian', 
                'sibling', 'grandparent', 'partner', 'friend', 'other'
            ];
            return in_array($value, $relationships);
        });

        // German Phone Number Validation
        Validator::extend('phone_german', function ($attribute, $value, $parameters, $validator) {
            // Basic German phone number validation
            return preg_match('/^(\+49|0)[1-9][0-9]{1,14}$/', $value);
        });

        // Player Age Category Match
        Validator::extend('player_age_category', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 2) {
                return false;
            }

            $birthDate = Carbon::parse($parameters[0]);
            $category = $parameters[1];
            $age = $birthDate->age;

            $categoryAges = config('basketball.team.categories');
            
            if (!isset($categoryAges[$category])) {
                return false;
            }

            $minAge = $categoryAges[$category]['min_age'];
            $maxAge = $categoryAges[$category]['max_age'];

            return $age >= $minAge && ($maxAge === null || $age <= $maxAge);
        });

        // Game Future Date Validation
        Validator::extend('game_future_date', function ($attribute, $value, $parameters, $validator) {
            return Carbon::parse($value)->isFuture();
        });

        // Different Teams Validation
        Validator::extend('different_teams', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 1) {
                return false;
            }

            return $value !== $parameters[0];
        });
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // @role directive
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @permission directive
        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->can({$permission})): ?>";
        });

        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // @teamAccess directive
        Blade::directive('teamAccess', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasTeamAccess({$expression})): ?>";
        });

        Blade::directive('endTeamAccess', function () {
            return '<?php endif; ?>';
        });

        // @basketballPosition directive
        Blade::directive('basketballPosition', function ($position) {
            return "<?php echo __('basketball.positions.' . {$position}); ?>";
        });

        // @basketballCategory directive
        Blade::directive('basketballCategory', function ($category) {
            return "<?php echo __('basketball.categories.' . {$category}); ?>";
        });

        // @gameStatus directive
        Blade::directive('gameStatus', function ($status) {
            return "<?php echo __('basketball.game_statuses.' . {$status}); ?>";
        });

        // @emergencyRelationship directive
        Blade::directive('emergencyRelationship', function ($relationship) {
            return "<?php echo __('basketball.emergency_relationships.' . {$relationship}); ?>";
        });

        // @currentSeason directive
        Blade::directive('currentSeason', function () {
            return "<?php echo config('basketball.season.current'); ?>";
        });

        // @formatJerseyNumber directive
        Blade::directive('formatJerseyNumber', function ($number) {
            return "<?php echo str_pad({$number}, 2, '0', STR_PAD_LEFT); ?>";
        });

        // @playerAge directive
        Blade::directive('playerAge', function ($birthDate) {
            return "<?php echo \\Carbon\\Carbon::parse({$birthDate})->age; ?>";
        });

        // @gameTime directive for formatting game times
        Blade::directive('gameTime', function ($datetime) {
            return "<?php echo \\Carbon\\Carbon::parse({$datetime})->format(__('common.datetime_format')); ?>";
        });
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Share current season with all views
        View::composer('*', function ($view) {
            $view->with('currentSeason', config('basketball.season.current'));
        });

        // Share basketball positions for form selects
        View::composer(['teams.*', 'players.*'], function ($view) {
            $view->with('basketballPositions', [
                'PG' => __('basketball.positions.PG'),
                'SG' => __('basketball.positions.SG'),
                'SF' => __('basketball.positions.SF'),
                'PF' => __('basketball.positions.PF'),
                'C' => __('basketball.positions.C'),
            ]);
        });

        // Share basketball categories
        View::composer(['teams.*'], function ($view) {
            $categories = [];
            foreach (['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Herren', 'Damen', 'Senioren', 'Mixed'] as $category) {
                $categories[$category] = __('basketball.categories.' . $category);
            }
            $view->with('basketballCategories', $categories);
        });

        // Share emergency relationships
        View::composer(['players.*', 'emergency.*'], function ($view) {
            $relationships = [];
            foreach (['parent', 'mother', 'father', 'guardian', 'sibling', 'grandparent', 'partner', 'friend', 'other'] as $relationship) {
                $relationships[$relationship] = __('basketball.emergency_relationships.' . $relationship);
            }
            $view->with('emergencyRelationships', $relationships);
        });

        // Share available locales
        View::composer('*', function ($view) {
            $view->with('availableLocales', config('localization.supported_locales'));
            $view->with('currentLocale', app()->getLocale());
        });
    }

    /**
     * Boot localization features.
     */
    protected function bootLocalization(): void
    {
        // Set default locale from configuration
        $defaultLocale = config('localization.default_locale', 'de');
        app()->setLocale($defaultLocale);

        // Register custom Carbon locale for dates
        Carbon::setLocale($defaultLocale);

        // Register locale detection middleware if not already registered
        if (!$this->app->has('localization.middleware.registered')) {
            $this->app->instance('localization.middleware.registered', true);
        }
    }

    /**
     * Register custom artisan commands.
     */
    protected function registerCommands(): void
    {
        // Commands will be registered here
        if ($this->app->runningInConsole()) {
            $this->commands([
                // \App\Console\Commands\GenerateSeasonReportCommand::class,
                // \App\Console\Commands\CleanupExpiredEmergencyAccessCommand::class,
                // \App\Console\Commands\RecalculateStatisticsCommand::class,
            ]);
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Event listeners will be registered here
        // Event::listen(GameStarted::class, SendGameNotifications::class);
        // Event::listen(PlayerRegistered::class, GenerateEmergencyQRCode::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            StatisticsService::class,
            TeamService::class,
            PlayerService::class,
            EmergencyContactService::class,
            TwoFactorAuthService::class,
            LocalizationService::class,
        ];
    }
}