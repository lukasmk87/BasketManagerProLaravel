<?php

namespace App\Exceptions;

use Exception;

class FeatureNotAvailableException extends Exception
{
    protected $feature;
    protected $requiredTier;

    public function __construct($message = "Feature not available", $feature = null, $requiredTier = null, $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->feature = $feature;
        $this->requiredTier = $requiredTier;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Log feature access attempts for analytics
        logger()->info('Feature access denied', [
            'tenant_id' => app('tenant')?->id,
            'feature' => $this->feature,
            'required_tier' => $this->requiredTier,
            'current_tier' => app('tenant')?->subscription_tier,
            'user_id' => auth()->id(),
            'route' => request()->route()?->getName(),
        ]);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'feature_not_available',
                'message' => $this->getMessage(),
                'feature' => $this->feature,
                'required_tier' => $this->requiredTier,
                'upgrade_url' => route('subscription.plans'),
            ], $this->getCode());
        }

        return response()->view('errors.feature-locked', [
            'message' => $this->getMessage(),
            'feature' => $this->feature,
            'required_tier' => $this->requiredTier
        ], $this->getCode());
    }
}