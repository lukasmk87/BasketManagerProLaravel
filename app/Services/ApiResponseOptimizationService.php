<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * API Response Optimization Service
 * 
 * Optimiert API-Responses für Basketball-spezifische Datenstrukturen
 * - Field Selection/Sparse Fields
 * - Data Transformation
 * - Response Caching
 * - Pagination Optimization
 */
class ApiResponseOptimizationService
{
    /**
     * Standard-Felder für verschiedene Entitäten
     */
    private const DEFAULT_FIELDS = [
        'player' => ['id', 'first_name', 'last_name', 'position', 'jersey_number', 'team_id'],
        'team' => ['id', 'name', 'short_name', 'city', 'league', 'founded_year'],
        'game' => ['id', 'home_team_id', 'away_team_id', 'home_score', 'away_score', 'status', 'played_at'],
        'statistics' => ['player_id', 'points', 'rebounds', 'assists', 'field_goal_percentage'],
        'shot_chart' => ['shot_x', 'shot_y', 'is_successful', 'shot_distance', 'shot_zone', 'period']
    ];

    /**
     * Feld-Aliase für kürzere JSON-Keys
     */
    private const FIELD_ALIASES = [
        'field_goal_percentage' => 'fg_pct',
        'three_point_percentage' => '3p_pct',
        'free_throw_percentage' => 'ft_pct',
        'offensive_rebounds' => 'oreb',
        'defensive_rebounds' => 'dreb',
        'total_rebounds' => 'reb',
        'is_successful' => 'made',
        'shot_distance' => 'dist',
        'recorded_at' => 'time'
    ];

    /**
     * Optimiere Collection-Response mit Field Selection
     *
     * @param Collection $data
     * @param string $entityType
     * @param array $requestedFields
     * @param bool $useAliases
     * @return array
     */
    public function optimizeCollectionResponse(
        Collection $data, 
        string $entityType, 
        array $requestedFields = [], 
        bool $useAliases = false
    ): array {
        // Bestimme zu verwendende Felder
        $fieldsToInclude = $this->determineFields($entityType, $requestedFields);
        
        $optimizedData = $data->map(function ($item) use ($fieldsToInclude, $useAliases) {
            return $this->optimizeItem($item, $fieldsToInclude, $useAliases);
        });

        return [
            'data' => $optimizedData->toArray(),
            'meta' => [
                'total' => $data->count(),
                'fields_included' => $fieldsToInclude,
                'optimization' => [
                    'field_selection' => count($fieldsToInclude) < count($this->getAvailableFields($data->first())),
                    'alias_usage' => $useAliases,
                    'estimated_size_reduction' => $this->estimateSizeReduction($fieldsToInclude, $data->first())
                ]
            ]
        ];
    }

    /**
     * Optimiere einzelnen Item
     *
     * @param mixed $item
     * @param array $fields
     * @param bool $useAliases
     * @return array
     */
    private function optimizeItem($item, array $fields, bool $useAliases): array
    {
        $result = [];
        
        foreach ($fields as $field) {
            if (is_object($item) && isset($item->$field)) {
                $key = $useAliases ? (self::FIELD_ALIASES[$field] ?? $field) : $field;
                $result[$key] = $this->optimizeValue($item->$field);
            } elseif (is_array($item) && isset($item[$field])) {
                $key = $useAliases ? (self::FIELD_ALIASES[$field] ?? $field) : $field;
                $result[$key] = $this->optimizeValue($item[$field]);
            }
        }

        return $result;
    }

    /**
     * Optimiere einzelnen Wert
     *
     * @param mixed $value
     * @return mixed
     */
    private function optimizeValue($value)
    {
        // Timestamps zu Unix Timestamps konvertieren (kürzer als ISO strings)
        if ($value instanceof Carbon) {
            return $value->timestamp;
        }

        // Booleans zu Integers für weniger Bytes
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        // Dezimalzahlen runden
        if (is_float($value)) {
            return round($value, 2);
        }

        // Null-Werte entfernen (sparse fields)
        if ($value === null) {
            return null; // Wird später gefiltert
        }

        return $value;
    }

    /**
     * Basketball Shot Chart Response Optimization
     * Spezielle Optimierung für Shot Chart Daten (meist sehr groß)
     *
     * @param Collection $shots
     * @param array $options
     * @return array
     */
    public function optimizeShotChartResponse(Collection $shots, array $options = []): array
    {
        $compression = $options['compression'] ?? 'medium';
        $includeMetadata = $options['include_metadata'] ?? true;

        $optimizedShots = $shots->map(function ($shot) use ($compression) {
            return $this->compressShotData($shot, $compression);
        });

        $response = [
            'shots' => $optimizedShots->toArray(),
            'compression' => $compression
        ];

        if ($includeMetadata) {
            $response['metadata'] = $this->generateShotChartMetadata($shots);
        }

        return $response;
    }

    /**
     * Komprimiere Shot-Daten basierend auf Compression-Level
     *
     * @param mixed $shot
     * @param string $compression
     * @return array
     */
    private function compressShotData($shot, string $compression): array
    {
        switch ($compression) {
            case 'high':
                // Maximale Kompression: nur wichtigste Daten
                return [
                    'x' => $shot->shot_x,
                    'y' => $shot->shot_y,
                    'm' => $shot->is_successful ? 1 : 0, // made
                    'd' => round($shot->shot_distance, 1), // distance
                    'p' => $shot->period
                ];

            case 'medium':
                // Ausgewogene Kompression
                return [
                    'x' => $shot->shot_x,
                    'y' => $shot->shot_y,
                    'made' => $shot->is_successful ? 1 : 0,
                    'dist' => round($shot->shot_distance, 1),
                    'zone' => $this->compressZoneName($shot->shot_zone),
                    'period' => $shot->period,
                    'time' => $shot->recorded_at ? Carbon::parse($shot->recorded_at)->timestamp : null
                ];

            case 'low':
            default:
                // Minimale Kompression: alle Daten behalten
                return [
                    'shot_x' => $shot->shot_x,
                    'shot_y' => $shot->shot_y,
                    'is_successful' => $shot->is_successful,
                    'shot_distance' => round($shot->shot_distance, 2),
                    'shot_zone' => $shot->shot_zone,
                    'action_type' => $shot->action_type,
                    'period' => $shot->period,
                    'recorded_at' => $shot->recorded_at
                ];
        }
    }

    /**
     * Komprimiere Zone-Namen zu Abkürzungen
     */
    private function compressZoneName(?string $zone): ?string
    {
        if (!$zone) return null;

        $zoneMapping = [
            'paint' => 'P',
            'mid_range' => 'MR',
            'three_point' => '3P',
            'free_throw' => 'FT',
            'backcourt' => 'BC'
        ];

        return $zoneMapping[$zone] ?? substr($zone, 0, 2);
    }

    /**
     * Generiere Shot Chart Metadata
     */
    private function generateShotChartMetadata(Collection $shots): array
    {
        return [
            'total_shots' => $shots->count(),
            'made_shots' => $shots->where('is_successful', true)->count(),
            'shooting_percentage' => $shots->count() > 0 ? round($shots->where('is_successful', true)->count() / $shots->count() * 100, 1) : 0,
            'average_distance' => round($shots->avg('shot_distance'), 1),
            'zones' => $shots->groupBy('shot_zone')->map(function ($zoneShots, $zone) {
                $made = $zoneShots->where('is_successful', true)->count();
                $total = $zoneShots->count();
                return [
                    'shots' => $total,
                    'made' => $made,
                    'percentage' => $total > 0 ? round($made / $total * 100, 1) : 0
                ];
            })->toArray()
        ];
    }

    /**
     * Optimierte Pagination Response
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $entityType
     * @param array $options
     * @return array
     */
    public function optimizePaginatedResponse($paginator, string $entityType, array $options = []): array
    {
        $requestedFields = $options['fields'] ?? [];
        $useAliases = $options['aliases'] ?? false;
        
        $optimizedData = $paginator->getCollection()->map(function ($item) use ($requestedFields, $entityType, $useAliases) {
            $fieldsToInclude = $this->determineFields($entityType, $requestedFields);
            return $this->optimizeItem($item, $fieldsToInclude, $useAliases);
        });

        return [
            'data' => $optimizedData->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more_pages' => $paginator->hasMorePages()
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl()
            ]
        ];
    }

    /**
     * Statistik-Response Aggregation und Optimierung
     *
     * @param Collection $statistics
     * @param array $groupBy
     * @param array $options
     * @return array
     */
    public function optimizeStatisticsResponse(Collection $statistics, array $groupBy = [], array $options = []): array
    {
        $precision = $options['precision'] ?? 2;
        $includeCalculated = $options['include_calculated'] ?? true;

        $optimized = $statistics->map(function ($stat) use ($precision, $includeCalculated) {
            $result = [
                'player_id' => $stat->player_id,
                'pts' => $stat->points,
                'reb' => $stat->rebounds,
                'ast' => $stat->assists,
                'stl' => $stat->steals,
                'blk' => $stat->blocks,
                'to' => $stat->turnovers
            ];

            // Schießstatistiken
            if (isset($stat->field_goal_made) && isset($stat->field_goal_attempts)) {
                $result['fg'] = "{$stat->field_goal_made}/{$stat->field_goal_attempts}";
                if ($includeCalculated && $stat->field_goal_attempts > 0) {
                    $result['fg_pct'] = round($stat->field_goal_made / $stat->field_goal_attempts * 100, $precision);
                }
            }

            // 3-Punkt-Statistiken
            if (isset($stat->three_point_made) && isset($stat->three_point_attempts)) {
                $result['3p'] = "{$stat->three_point_made}/{$stat->three_point_attempts}";
                if ($includeCalculated && $stat->three_point_attempts > 0) {
                    $result['3p_pct'] = round($stat->three_point_made / $stat->three_point_attempts * 100, $precision);
                }
            }

            return $result;
        });

        // Gruppierung anwenden wenn gewünscht
        if (!empty($groupBy)) {
            $grouped = $optimized->groupBy(function ($item) use ($groupBy) {
                return collect($groupBy)->map(function ($field) use ($item) {
                    return $item[$field] ?? 'unknown';
                })->implode('_');
            });

            return [
                'data' => $grouped->toArray(),
                'grouping' => $groupBy,
                'groups_count' => $grouped->count()
            ];
        }

        return [
            'data' => $optimized->toArray(),
            'optimization' => [
                'field_aliases_used' => true,
                'precision' => $precision,
                'calculated_fields' => $includeCalculated
            ]
        ];
    }

    /**
     * Performance-optimierte Live Game Response
     *
     * @param mixed $liveGame
     * @param array $options
     * @return array
     */
    public function optimizeLiveGameResponse($liveGame, array $options = []): array
    {
        $includeActions = $options['include_recent_actions'] ?? true;
        $actionsLimit = $options['actions_limit'] ?? 10;

        $response = [
            'id' => $liveGame->game_id,
            'home_score' => $liveGame->current_score_home,
            'away_score' => $liveGame->current_score_away,
            'period' => $liveGame->current_period,
            'time_remaining' => $liveGame->period_time_remaining,
            'status' => $liveGame->status,
            'updated' => Carbon::parse($liveGame->updated_at)->timestamp
        ];

        if ($includeActions && isset($liveGame->recent_actions)) {
            $response['recent_actions'] = collect($liveGame->recent_actions)
                ->take($actionsLimit)
                ->map(function ($action) {
                    return [
                        'type' => $action->action_type,
                        'player' => $action->player_id,
                        'pts' => $action->points ?? 0,
                        'time' => Carbon::parse($action->recorded_at)->timestamp
                    ];
                })
                ->toArray();
        }

        return $response;
    }

    /**
     * Bestimme zu verwendende Felder
     */
    private function determineFields(string $entityType, array $requestedFields): array
    {
        $defaultFields = self::DEFAULT_FIELDS[$entityType] ?? [];
        
        if (empty($requestedFields)) {
            return $defaultFields;
        }

        // Validiere angeforderte Felder
        $availableFields = array_merge($defaultFields, array_keys(self::FIELD_ALIASES));
        $validFields = array_intersect($requestedFields, $availableFields);

        return !empty($validFields) ? $validFields : $defaultFields;
    }

    /**
     * Schätze Größenreduktion durch Field Selection
     */
    private function estimateSizeReduction(array $selectedFields, $sampleItem): string
    {
        if (!$sampleItem) return '0%';

        $totalFields = count($this->getAvailableFields($sampleItem));
        $selectedFieldsCount = count($selectedFields);
        
        if ($totalFields === 0) return '0%';

        $reduction = ($totalFields - $selectedFieldsCount) / $totalFields * 100;
        return round($reduction) . '%';
    }

    /**
     * Erhalte verfügbare Felder eines Items
     */
    private function getAvailableFields($item): array
    {
        if (is_object($item)) {
            return array_keys(get_object_vars($item));
        }
        
        if (is_array($item)) {
            return array_keys($item);
        }
        
        return [];
    }

    /**
     * Cache optimierte Response
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     * @return mixed
     */
    public function cacheOptimizedResponse(string $key, $data, int $ttl = 300)
    {
        $cacheKey = 'api_optimized:' . md5($key);
        Cache::put($cacheKey, $data, $ttl);
        return $data;
    }

    /**
     * Erhalte gecachte optimierte Response
     *
     * @param string $key
     * @return mixed
     */
    public function getCachedOptimizedResponse(string $key)
    {
        $cacheKey = 'api_optimized:' . md5($key);
        return Cache::get($cacheKey);
    }

    /**
     * Generiere Performance-Report für Response Optimization
     *
     * @param array $beforeData
     * @param array $afterData
     * @return array
     */
    public function generateOptimizationReport(array $beforeData, array $afterData): array
    {
        $beforeSize = strlen(json_encode($beforeData));
        $afterSize = strlen(json_encode($afterData));
        
        $sizeReduction = $beforeSize > 0 ? ($beforeSize - $afterSize) / $beforeSize * 100 : 0;

        return [
            'original_size' => $beforeSize . ' bytes',
            'optimized_size' => $afterSize . ' bytes',
            'size_reduction' => round($sizeReduction, 2) . '%',
            'bytes_saved' => $beforeSize - $afterSize,
            'optimizations_applied' => [
                'field_selection' => isset($afterData['meta']['fields_included']),
                'value_compression' => true,
                'alias_usage' => isset($afterData['meta']['optimization']['alias_usage']),
                'null_filtering' => true
            ]
        ];
    }
}