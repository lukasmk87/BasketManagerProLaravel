<?php

namespace App\Http\Controllers\Traits;

use App\Services\ApiResponseOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * Optimizes API Responses Trait
 * 
 * Stellt Methoden zur API Response Optimization in Controllern bereit
 */
trait OptimizesApiResponses
{
    /**
     * Optimierte JSON Response für Collections
     *
     * @param Collection $data
     * @param string $entityType
     * @param Request|null $request
     * @param array $additionalOptions
     * @return JsonResponse
     */
    protected function optimizedCollectionResponse(
        Collection $data, 
        string $entityType, 
        ?Request $request = null,
        array $additionalOptions = []
    ): JsonResponse {
        $request = $request ?: request();
        
        // Parse Request-Parameter
        $requestedFields = $this->parseFieldsParameter($request);
        $useAliases = $request->boolean('aliases', false);
        $useCompression = $request->boolean('compress', true);

        $optimizationService = app(ApiResponseOptimizationService::class);
        
        // Cache-Key generieren
        $cacheKey = $this->generateCacheKey($request, $entityType, $data->count());
        
        // Versuche gecachte Response zu verwenden
        if ($useCompression && $request->boolean('cache', true)) {
            $cachedResponse = $optimizationService->getCachedOptimizedResponse($cacheKey);
            if ($cachedResponse) {
                return response()->json($cachedResponse)
                    ->header('X-Cache-Hit', 'true');
            }
        }

        // Optimiere Response
        $optimizedData = $optimizationService->optimizeCollectionResponse(
            $data, 
            $entityType, 
            $requestedFields, 
            $useAliases
        );

        // Zusätzliche Optionen anwenden
        $finalData = array_merge($optimizedData, $additionalOptions);

        // Response cachen
        if ($useCompression && $request->boolean('cache', true)) {
            $optimizationService->cacheOptimizedResponse($cacheKey, $finalData, 300);
        }

        $response = response()->json($finalData);

        // Performance Headers hinzufügen
        $response->header('X-Optimized', 'true');
        $response->header('X-Fields-Selected', count($requestedFields ?: []));
        $response->header('X-Aliases-Used', $useAliases ? 'true' : 'false');

        return $response;
    }

    /**
     * Optimierte Shot Chart Response
     *
     * @param Collection $shots
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function optimizedShotChartResponse(Collection $shots, ?Request $request = null): JsonResponse
    {
        $request = $request ?: request();
        
        $compression = $request->get('compression', 'medium');
        $includeMetadata = $request->boolean('metadata', true);

        $optimizationService = app(ApiResponseOptimizationService::class);
        
        $optimizedData = $optimizationService->optimizeShotChartResponse($shots, [
            'compression' => $compression,
            'include_metadata' => $includeMetadata
        ]);

        $response = response()->json($optimizedData);
        
        $response->header('X-Shot-Chart-Optimized', 'true');
        $response->header('X-Compression-Level', $compression);
        $response->header('X-Total-Shots', $shots->count());

        return $response;
    }

    /**
     * Optimierte Statistics Response
     *
     * @param Collection $statistics
     * @param Request|null $request
     * @param array $groupBy
     * @return JsonResponse
     */
    protected function optimizedStatisticsResponse(
        Collection $statistics, 
        ?Request $request = null,
        array $groupBy = []
    ): JsonResponse {
        $request = $request ?: request();
        
        $precision = (int) $request->get('precision', 2);
        $includeCalculated = $request->boolean('calculated', true);
        $groupBy = $request->array('group_by') ?: $groupBy;

        $optimizationService = app(ApiResponseOptimizationService::class);
        
        $optimizedData = $optimizationService->optimizeStatisticsResponse($statistics, $groupBy, [
            'precision' => $precision,
            'include_calculated' => $includeCalculated
        ]);

        $response = response()->json($optimizedData);
        
        $response->header('X-Statistics-Optimized', 'true');
        $response->header('X-Precision', $precision);
        $response->header('X-Calculated-Fields', $includeCalculated ? 'true' : 'false');

        return $response;
    }

    /**
     * Optimierte Paginated Response
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $entityType
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function optimizedPaginatedResponse(
        $paginator, 
        string $entityType, 
        ?Request $request = null
    ): JsonResponse {
        $request = $request ?: request();
        
        $requestedFields = $this->parseFieldsParameter($request);
        $useAliases = $request->boolean('aliases', false);

        $optimizationService = app(ApiResponseOptimizationService::class);
        
        $optimizedData = $optimizationService->optimizePaginatedResponse($paginator, $entityType, [
            'fields' => $requestedFields,
            'aliases' => $useAliases
        ]);

        $response = response()->json($optimizedData);
        
        $response->header('X-Paginated-Optimized', 'true');
        $response->header('X-Current-Page', $paginator->currentPage());
        $response->header('X-Total-Pages', $paginator->lastPage());

        return $response;
    }

    /**
     * Optimierte Live Game Response
     *
     * @param mixed $liveGame
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function optimizedLiveGameResponse($liveGame, ?Request $request = null): JsonResponse
    {
        $request = $request ?: request();
        
        $includeActions = $request->boolean('include_actions', true);
        $actionsLimit = (int) $request->get('actions_limit', 10);

        $optimizationService = app(ApiResponseOptimizationService::class);
        
        $optimizedData = $optimizationService->optimizeLiveGameResponse($liveGame, [
            'include_recent_actions' => $includeActions,
            'actions_limit' => $actionsLimit
        ]);

        $response = response()->json($optimizedData);
        
        $response->header('X-Live-Game-Optimized', 'true');
        $response->header('X-Include-Actions', $includeActions ? 'true' : 'false');
        $response->header('Cache-Control', 'no-cache, must-revalidate');

        return $response;
    }

    /**
     * Generiere Performance Report
     *
     * @param array $originalData
     * @param array $optimizedData
     * @return array
     */
    protected function generatePerformanceReport(array $originalData, array $optimizedData): array
    {
        $optimizationService = app(ApiResponseOptimizationService::class);
        return $optimizationService->generateOptimizationReport($originalData, $optimizedData);
    }

    /**
     * Parse fields Parameter aus Request
     *
     * @param Request $request
     * @return array
     */
    private function parseFieldsParameter(Request $request): array
    {
        $fields = $request->get('fields', '');
        
        if (empty($fields)) {
            return [];
        }

        // Unterstützung für verschiedene Formate
        if (is_array($fields)) {
            return $fields;
        }

        // Comma-separated string
        if (is_string($fields)) {
            return array_map('trim', explode(',', $fields));
        }

        return [];
    }

    /**
     * Generiere Cache-Key für optimierte Response
     *
     * @param Request $request
     * @param string $entityType
     * @param int $dataCount
     * @return string
     */
    private function generateCacheKey(Request $request, string $entityType, int $dataCount): string
    {
        $params = [
            'path' => $request->path(),
            'type' => $entityType,
            'count' => $dataCount,
            'fields' => $request->get('fields', ''),
            'aliases' => $request->get('aliases', ''),
            'compression' => $request->get('compression', ''),
            'precision' => $request->get('precision', ''),
            'group_by' => $request->get('group_by', '')
        ];

        return 'opt_' . md5(json_encode($params));
    }

    /**
     * Response mit Debug-Informationen
     *
     * @param mixed $data
     * @param array $debugInfo
     * @return JsonResponse
     */
    protected function debugOptimizedResponse($data, array $debugInfo = []): JsonResponse
    {
        $response = [
            'data' => $data,
            'debug' => array_merge([
                'timestamp' => now()->toISOString(),
                'optimization_enabled' => true,
                'request_id' => request()->header('X-Request-ID', uniqid())
            ], $debugInfo)
        ];

        return response()->json($response)
            ->header('X-Debug-Mode', 'true');
    }

    /**
     * Middleware-ähnliche Methode für automatische Optimization
     *
     * @param \Closure $callback
     * @param string $entityType
     * @param array $options
     * @return JsonResponse
     */
    protected function withOptimization(\Closure $callback, string $entityType, array $options = []): JsonResponse
    {
        $data = $callback();
        
        if ($data instanceof Collection) {
            return $this->optimizedCollectionResponse($data, $entityType, null, $options);
        }

        if (is_array($data) && isset($data['data']) && $data['data'] instanceof Collection) {
            return $this->optimizedCollectionResponse($data['data'], $entityType, null, $options);
        }

        // Fallback für andere Datentypen
        return response()->json($data);
    }

    /**
     * Bulk Response Optimization für mehrere Entity-Typen
     *
     * @param array $dataWithTypes
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function optimizedBulkResponse(array $dataWithTypes, ?Request $request = null): JsonResponse
    {
        $request = $request ?: request();
        $optimizationService = app(ApiResponseOptimizationService::class);
        
        $optimizedBulk = [];
        
        foreach ($dataWithTypes as $key => $item) {
            if (!isset($item['data']) || !isset($item['type'])) {
                $optimizedBulk[$key] = $item;
                continue;
            }
            
            $data = $item['data'];
            $entityType = $item['type'];
            
            if ($data instanceof Collection) {
                $optimizedBulk[$key] = $optimizationService->optimizeCollectionResponse(
                    $data,
                    $entityType,
                    $this->parseFieldsParameter($request),
                    $request->boolean('aliases', false)
                );
            } else {
                $optimizedBulk[$key] = $item;
            }
        }

        $response = response()->json($optimizedBulk);
        
        $response->header('X-Bulk-Optimized', 'true');
        $response->header('X-Bulk-Items', count($dataWithTypes));

        return $response;
    }
}