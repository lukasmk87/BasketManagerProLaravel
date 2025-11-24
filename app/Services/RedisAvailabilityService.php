<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Service to detect Redis availability for graceful degradation
 *
 * This service checks if Redis is available and caches the result
 * to avoid repeated connection attempts. Useful for shared hosting
 * environments where Redis may not be available.
 */
class RedisAvailabilityService
{
    /**
     * Cache key for storing Redis availability status
     */
    private const CACHE_KEY = 'system:redis_available';

    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Check if Redis is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // Try to get cached result first (using file cache to avoid circular dependency)
        $cacheFile = storage_path('framework/cache/data/redis_availability');

        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time()) {
                return $cached['available'];
            }
        }

        // Perform actual availability check
        $available = $this->performCheck();

        // Cache the result
        $this->cacheResult($available, $cacheFile);

        return $available;
    }

    /**
     * Perform the actual Redis availability check
     *
     * @return bool
     */
    private function performCheck(): bool
    {
        try {
            // Attempt to connect and ping Redis
            $redis = Redis::connection();
            $redis->ping();

            Log::info('Redis availability check: Available');
            return true;
        } catch (\Exception $e) {
            // Redis is not available
            Log::warning('Redis availability check: Not available', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Cache the availability result
     *
     * @param bool $available
     * @param string $cacheFile
     * @return void
     */
    private function cacheResult(bool $available, string $cacheFile): void
    {
        try {
            $data = [
                'available' => $available,
                'expires_at' => time() + self::CACHE_TTL,
                'checked_at' => now()->toDateTimeString()
            ];

            // Ensure directory exists
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($cacheFile, json_encode($data));
        } catch (\Exception $e) {
            // Silently fail if caching fails - not critical
            Log::debug('Failed to cache Redis availability', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Force a fresh check (bypass cache)
     *
     * @return bool
     */
    public function forceCheck(): bool
    {
        $cacheFile = storage_path('framework/cache/data/redis_availability');
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        return $this->isAvailable();
    }

    /**
     * Get the recommended cache driver based on Redis availability
     *
     * @return string
     */
    public function getRecommendedCacheDriver(): string
    {
        if ($this->isAvailable()) {
            return 'redis';
        }

        // Fallback: database is more reliable than file for multi-process environments
        return 'database';
    }

    /**
     * Get the recommended session driver based on Redis availability
     *
     * @return string
     */
    public function getRecommendedSessionDriver(): string
    {
        if ($this->isAvailable()) {
            return 'redis';
        }

        // Database sessions work well for most applications
        return 'database';
    }

    /**
     * Get the recommended queue connection based on Redis availability
     *
     * @return string
     */
    public function getRecommendedQueueConnection(): string
    {
        if ($this->isAvailable()) {
            return 'redis';
        }

        // Database queue for production without Redis
        return 'database';
    }

    /**
     * Get all recommended drivers at once
     *
     * @return array
     */
    public function getRecommendedDrivers(): array
    {
        $available = $this->isAvailable();

        return [
            'redis_available' => $available,
            'cache' => $available ? 'redis' : 'database',
            'session' => $available ? 'redis' : 'database',
            'queue' => $available ? 'redis' : 'database',
            'checked_at' => now()->toDateTimeString()
        ];
    }
}
