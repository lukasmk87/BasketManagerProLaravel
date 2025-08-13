<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsageQuotaExceededException extends Exception
{
    protected $metric;
    protected $current;
    protected $limit;

    public function __construct($message = "Usage quota exceeded", $metric = null, $current = null, $limit = null, $code = 429, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->metric = $metric;
        $this->current = $current;
        $this->limit = $limit;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Log quota exceeded events for monitoring
        logger()->warning('Usage quota exceeded', [
            'tenant_id' => app('tenant')?->id,
            'metric' => $this->metric,
            'current_usage' => $this->current,
            'limit' => $this->limit,
            'subscription_tier' => app('tenant')?->subscription_tier,
            'user_id' => auth()->id(),
            'route' => request()->route()?->getName(),
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'quota_exceeded',
                'message' => $this->getMessage(),
                'metric' => $this->metric,
                'current_usage' => $this->current,
                'limit' => $this->limit,
                'subscription_tier' => app('tenant')?->subscription_tier,
                'upgrade_url' => route('subscription.index'),
            ], $this->getCode());
        }

        return response()->view('errors.quota-exceeded', [
            'exception' => $this,
            'tenant' => app('tenant'),
            'metric' => $this->metric,
            'current' => $this->current,
            'limit' => $this->limit,
        ], $this->getCode());
    }
}