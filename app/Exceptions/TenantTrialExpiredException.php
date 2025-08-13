<?php

namespace App\Exceptions;

use Exception;

class TenantTrialExpiredException extends Exception
{
    public function __construct($message = "Tenant trial period has expired", $code = 402, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
                'error' => 'trial_expired',
                'message' => $this->getMessage(),
                'upgrade_url' => route('subscription.plans'),
            ], $this->getCode());
        }

        return response()->view('errors.trial-expired', [
            'message' => $this->getMessage()
        ], $this->getCode());
    }
}