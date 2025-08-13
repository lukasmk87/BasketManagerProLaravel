<?php

namespace App\Http\Middleware;

use App\Services\Stripe\CashierTenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigureTenantStripe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current tenant from the app service container
        $tenant = app('tenant');
        
        if ($tenant) {
            // Configure Cashier/Stripe for the current tenant
            $cashierManager = app(CashierTenantManager::class);
            $cashierManager->setCurrentTenant($tenant);
        }
        
        return $next($request);
    }
}