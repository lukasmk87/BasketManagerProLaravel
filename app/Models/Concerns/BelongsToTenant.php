<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Add global scope for tenant isolation
        static::addGlobalScope(new TenantScope);
        
        // Automatically set tenant_id when creating new models
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenant = app('tenant', null);
                if ($tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the qualified tenant column name.
     */
    public function getQualifiedTenantColumn(): string
    {
        return $this->qualifyColumn('tenant_id');
    }

    /**
     * Get the tenant column name.
     */
    public function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Determine if the model belongs to the current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        $currentTenant = app('tenant', null);
        
        if (!$currentTenant) {
            return false;
        }
        
        return $this->tenant_id === $currentTenant->id;
    }

    /**
     * Determine if the model belongs to a specific tenant.
     */
    public function belongsToTenant($tenant): bool
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        
        return $this->tenant_id === $tenantId;
    }

    /**
     * Scope a query to exclude the current tenant.
     */
    public function scopeExcludeCurrentTenant($query)
    {
        $currentTenant = app('tenant', null);
        
        if ($currentTenant) {
            return $query->where('tenant_id', '!=', $currentTenant->id);
        }
        
        return $query;
    }

    /**
     * Scope a query to only include models from a specific tenant.
     */
    public function scopeForTenant($query, $tenant)
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        
        return $query->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to include models from all tenants.
     */
    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Create a new model instance for the current tenant.
     */
    public static function createForTenant(array $attributes = [])
    {
        $tenant = app('tenant', null);
        
        if ($tenant) {
            $attributes['tenant_id'] = $tenant->id;
        }
        
        return static::create($attributes);
    }

    /**
     * Make a new model instance for the current tenant.
     */
    public static function makeForTenant(array $attributes = [])
    {
        $tenant = app('tenant', null);
        
        if ($tenant) {
            $attributes['tenant_id'] = $tenant->id;
        }
        
        return new static($attributes);
    }
}