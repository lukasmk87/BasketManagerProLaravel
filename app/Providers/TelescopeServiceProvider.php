<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only register Telescope in local environment
        if ($this->app->environment('local')) {
            parent::register();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only boot Telescope in local environment
        if ($this->app->environment('local')) {
            parent::boot();

            // Configure Telescope
            Telescope::night();

            $this->hideSensitiveRequestDetails();

            Telescope::filter(function (IncomingEntry $entry) {
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
        if (!$this->app->environment('local')) {
            return;
        }

        $this->gate();

        Telescope::auth(function ($request) {
            return $this->app->environment('local') ||
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
                // Add admin emails here if needed in non-local environments
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