<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only register Telescope in specific environments
        if ($this->app->environment(['local', 'development'])) {
            parent::register();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only boot Telescope in specific environments
        if ($this->app->environment(['local', 'development'])) {
            parent::boot();

            // Configure Telescope
            Telescope::night();

            $this->hideSensitiveRequestDetails();

            Telescope::filter(function ($entry) {
                if ($this->app->environment('local')) {
                    return true;
                }

                return $entry->isReportableException() ||
                       $entry->isFailedRequest() ||
                       $entry->isFailedJob() ||
                       $entry->isSlowQuery() ||
                       $entry->hasMonitoredTag();
            });
        }
    }

    /**
     * Configure the Telescope authorization services.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(function ($request) {
            return app()->environment('local') ||
                   Gate::allows('viewTelescope', [$request->user()]);
        });
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                // Add admin emails here
            ]);
        });
    }

    /**
     * Hide sensitive request details from Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }
}