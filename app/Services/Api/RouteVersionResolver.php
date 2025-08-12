<?php

namespace App\Services\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use ReflectionClass;

class RouteVersionResolver
{
    protected array $versionNamespaceMap = [
        '1.0' => 'App\Http\Controllers\Api\V1',
        '2.0' => 'App\Http\Controllers\Api\V2',
        '3.0' => 'App\Http\Controllers\Api\V3',
        '4.0' => 'App\Http\Controllers\Api\V4',
    ];

    protected array $fallbackVersions = [
        '4.0' => ['3.0', '2.0', '1.0'],
        '3.0' => ['2.0', '1.0'],
        '2.0' => ['1.0'],
        '1.0' => [],
    ];

    /**
     * Resolve controller class for the given version and controller name
     */
    public function resolveController(string $version, string $controllerName): ?string
    {
        $normalizedVersion = $this->normalizeVersion($version);
        
        // Try exact version match first
        $controllerClass = $this->findControllerInVersion($normalizedVersion, $controllerName);
        
        if ($controllerClass) {
            return $controllerClass;
        }

        // Try fallback versions for backward compatibility
        $fallbacks = $this->fallbackVersions[$normalizedVersion] ?? [];
        
        foreach ($fallbacks as $fallbackVersion) {
            $controllerClass = $this->findControllerInVersion($fallbackVersion, $controllerName);
            
            if ($controllerClass) {
                // Log fallback usage for monitoring
                logger()->info("API Controller fallback used", [
                    'requested_version' => $version,
                    'fallback_version' => $fallbackVersion,
                    'controller' => $controllerName,
                    'resolved_class' => $controllerClass
                ]);
                
                return $controllerClass;
            }
        }

        return null;
    }

    /**
     * Find controller class in specific version namespace
     */
    protected function findControllerInVersion(string $version, string $controllerName): ?string
    {
        $namespace = $this->versionNamespaceMap[$version] ?? null;
        
        if (!$namespace) {
            return null;
        }

        // Ensure controller name ends with 'Controller'
        if (!str_ends_with($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }

        $fullClassName = $namespace . '\\' . $controllerName;

        // Check if class exists
        if (class_exists($fullClassName)) {
            return $fullClassName;
        }

        return null;
    }

    /**
     * Get versioned route middleware stack
     */
    public function getVersionedMiddleware(string $version): array
    {
        $baseMiddleware = ['api', 'api.version'];
        
        // Add version-specific middleware if configured
        $versionConfig = config("api.versions.{$version}", []);
        $versionMiddleware = $versionConfig['middleware'] ?? [];
        
        return array_merge($baseMiddleware, $versionMiddleware);
    }

    /**
     * Check if endpoint exists in specified version
     */
    public function endpointExists(string $version, string $controller, string $method): bool
    {
        $controllerClass = $this->resolveController($version, $controller);
        
        if (!$controllerClass) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($controllerClass);
            return $reflection->hasMethod($method);
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * Get API version from request
     */
    public function getVersionFromRequest(Request $request): string
    {
        return $request->attributes->get('api_version', config('api.default_version', '4.0'));
    }

    /**
     * Generate versioned route name
     */
    public function generateVersionedRouteName(string $version, string $routeName): string
    {
        $majorVersion = explode('.', $version)[0];
        return "api.v{$majorVersion}.{$routeName}";
    }

    /**
     * Get available endpoints for version
     */
    public function getVersionEndpoints(string $version): array
    {
        $versionConfig = config("api.versions.{$version}", []);
        return $versionConfig['endpoints'] ?? [];
    }

    /**
     * Check if version supports specific feature
     */
    public function versionSupportsFeature(string $version, string $feature): bool
    {
        $versionConfig = config("api.versions.{$version}", []);
        $features = $versionConfig['features'] ?? [];
        
        return in_array($feature, $features) || 
               isset($features[$feature]) && $features[$feature] === true;
    }

    /**
     * Get deprecated endpoints for version
     */
    public function getDeprecatedEndpoints(string $version): array
    {
        $versionConfig = config("api.versions.{$version}", []);
        return $versionConfig['deprecated_endpoints'] ?? [];
    }

    /**
     * Check if endpoint is deprecated in version
     */
    public function isEndpointDeprecated(string $version, string $endpoint): bool
    {
        $deprecated = $this->getDeprecatedEndpoints($version);
        
        return in_array($endpoint, $deprecated) ||
               isset($deprecated[$endpoint]);
    }

    /**
     * Get migration information between versions
     */
    public function getMigrationInfo(string $fromVersion, string $toVersion): array
    {
        $migrationKey = "{$fromVersion}_to_{$toVersion}";
        return config("api.migrations.{$migrationKey}", [
            'breaking_changes' => [],
            'new_features' => [],
            'deprecated_features' => [],
            'migration_guide' => null
        ]);
    }

    /**
     * Normalize version number format
     */
    protected function normalizeVersion(string $version): string
    {
        if (!str_contains($version, '.')) {
            return $version . '.0';
        }
        
        return $version;
    }

    /**
     * Register version-specific service bindings
     */
    public function registerVersionBindings(string $version): void
    {
        $versionConfig = config("api.versions.{$version}", []);
        $bindings = $versionConfig['service_bindings'] ?? [];
        
        foreach ($bindings as $abstract => $concrete) {
            App::bind($abstract, $concrete);
        }
    }

    /**
     * Get version changelog
     */
    public function getVersionChangelog(string $version): array
    {
        return config("api.changelogs.{$version}", []);
    }

    /**
     * Get all registered version namespaces
     */
    public function getVersionNamespaces(): array
    {
        return $this->versionNamespaceMap;
    }

    /**
     * Register new version namespace
     */
    public function registerVersionNamespace(string $version, string $namespace): void
    {
        $this->versionNamespaceMap[$version] = $namespace;
    }
}