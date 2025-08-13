<?php

namespace App\Providers;

use App\Services\FeatureGateService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FeatureGateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FeatureGateService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    /**
     * Register custom Blade directives for feature gates.
     */
    private function registerBladeDirectives(): void
    {
        // @feature directive - check if feature is available
        Blade::directive('feature', function ($expression) {
            return "<?php if(app(\App\Services\FeatureGateService::class)->hasFeature({$expression})): ?>";
        });

        Blade::directive('endfeature', function () {
            return '<?php endif; ?>';
        });

        // @notfeature directive - check if feature is NOT available
        Blade::directive('notfeature', function ($expression) {
            return "<?php if(!app(\App\Services\FeatureGateService::class)->hasFeature({$expression})): ?>";
        });

        Blade::directive('endnotfeature', function () {
            return '<?php endif; ?>';
        });

        // @tier directive - check if tenant is on specific tier or higher
        Blade::directive('tier', function ($expression) {
            return "<?php if(app(\App\Services\FeatureGateService::class)->canUpgradeTo({$expression}) === false): ?>";
        });

        Blade::directive('endtier', function () {
            return '<?php endif; ?>';
        });

        // @usage directive - check if usage is within limits
        Blade::directive('usage', function ($expression) {
            return "<?php if(app(\App\Services\FeatureGateService::class)->canUse({$expression})): ?>";
        });

        Blade::directive('endusage', function () {
            return '<?php endif; ?>';
        });

        // @trial directive - check if tenant is in trial
        Blade::directive('trial', function () {
            return "<?php if(app(\App\Services\FeatureGateService::class)->isInTrial()): ?>";
        });

        Blade::directive('endtrial', function () {
            return '<?php endif; ?>';
        });

        // @subscription directive - check if tenant has active subscription
        Blade::directive('subscription', function () {
            return "<?php if(app(\App\Services\FeatureGateService::class)->hasActiveSubscription()): ?>";
        });

        Blade::directive('endsubscription', function () {
            return '<?php endif; ?>';
        });

        // @upgradebutton directive - show upgrade button with usage stats
        Blade::directive('upgradebutton', function ($expression) {
            $params = $expression ? "($expression)" : '';
            return "<?php echo view('components.upgrade-button', ['usage' => app(\App\Services\FeatureGateService::class)->getAllUsage()])->render(); ?>";
        });

        // @usagestats directive - display usage statistics
        Blade::directive('usagestats', function ($expression) {
            $metric = $expression ?: "'all'";
            return "<?php echo view('components.usage-stats', ['usage' => app(\App\Services\FeatureGateService::class)->getAllUsage(), 'metric' => {$metric}])->render(); ?>";
        });
    }
}
