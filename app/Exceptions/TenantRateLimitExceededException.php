<?php

namespace App\Exceptions;

use Exception;

class TenantRateLimitExceededException extends Exception
{
    protected $retryAfter;

    public function __construct($message = "Rate limit exceeded for tenant", $retryAfter = 60, $code = 429, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'rate_limit_exceeded',
                'message' => $this->getMessage(),
                'retry_after' => $this->retryAfter,
            ], $this->getCode())
            ->header('Retry-After', $this->retryAfter)
            ->header('X-RateLimit-Limit', config('tenants.rate_limits.default', 1000))
            ->header('X-RateLimit-Remaining', 0);
        }

        return response()->view('errors.rate-limit', [
            'message' => $this->getMessage(),
            'retry_after' => $this->retryAfter
        ], $this->getCode())
        ->header('Retry-After', $this->retryAfter);
    }
}