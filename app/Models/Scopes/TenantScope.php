<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply tenant scope if tenant is set and model has tenant_id column
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if ($tenant && $this->modelHasTenantColumn($model)) {
            $builder->where($model->getQualifiedTenantColumn(), $tenant->id);
        }
    }

    /**
     * Extend the query builder with tenant-specific methods.
     */
    public function extend(Builder $builder): void
    {
        // Add method to bypass tenant scope
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
        
        // Add method to query specific tenant
        $builder->macro('forTenant', function (Builder $builder, $tenantId) {
            return $builder->withoutGlobalScope($this)
                          ->where($builder->getModel()->getQualifiedTenantColumn(), $tenantId);
        });
        
        // Add method to query all tenants
        $builder->macro('allTenants', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Check if model has tenant_id column.
     */
    private function modelHasTenantColumn(Model $model): bool
    {
        return $model->getConnection()
                    ->getSchemaBuilder()
                    ->hasColumn($model->getTable(), 'tenant_id');
    }
}