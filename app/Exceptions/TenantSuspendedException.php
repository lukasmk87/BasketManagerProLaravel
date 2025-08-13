<?php

namespace App\Exceptions;

use Exception;

class TenantSuspendedException extends Exception
{
    public function __construct($message = "Tenant account is suspended", $code = 403, Exception $previous = null)
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
                'error' => 'tenant_suspended',
                'message' => $this->getMessage(),
            ], $this->getCode());
        }

        return response()->view('errors.tenant-suspended', [
            'message' => $this->getMessage()
        ], $this->getCode());
    }
}