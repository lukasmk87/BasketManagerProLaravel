<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\StreamedResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class MemoryOptimizationService
{
    private const MEMORY_WARNING_THRESHOLD = 128 * 1024 * 1024; // 128MB
    private const MEMORY_CRITICAL_THRESHOLD = 256 * 1024 * 1024; // 256MB
    private const CHUNK_SIZE = 1000; // Default chunk size for large datasets
    
    private array $memoryCheckpoints = [];
    private int $initialMemory;
    private int $peakMemory = 0;

    public function __construct()
    {
        $this->initialMemory = memory_get_usage(true);
        $this->addCheckpoint('service_initialized');
    }

    /**
     * Add a memory usage checkpoint for monitoring
     */
    public function addCheckpoint(string $label): void
    {
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $this->memoryCheckpoints[$label] = [
            'current' => $currentMemory,
            'peak' => $peakMemory,
            'diff_from_initial' => $currentMemory - $this->initialMemory,
            'timestamp' => microtime(true),
        ];
        
        $this->peakMemory = max($this->peakMemory, $peakMemory);
        
        // Log if memory usage is concerning
        if ($currentMemory > self::MEMORY_WARNING_THRESHOLD) {
            $this->logMemoryWarning($label, $currentMemory);
        }
    }

    /**
     * Get current memory usage statistics
     */
    public function getMemoryStats(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimit();
        
        return [
            'current' => [
                'bytes' => $current,
                'formatted' => $this->formatBytes($current),
                'percentage' => $limit > 0 ? round(($current / $limit) * 100, 2) : 0,
            ],
            'peak' => [
                'bytes' => $peak,
                'formatted' => $this->formatBytes($peak),
                'percentage' => $limit > 0 ? round(($peak / $limit) * 100, 2) : 0,
            ],
            'limit' => [
                'bytes' => $limit,
                'formatted' => $limit > 0 ? $this->formatBytes($limit) : 'Unlimited',
            ],
            'available' => [
                'bytes' => max(0, $limit - $current),
                'formatted' => $limit > 0 ? $this->formatBytes(max(0, $limit - $current)) : 'Unlimited',
            ],
            'checkpoints' => $this->memoryCheckpoints,
        ];
    }

    /**
     * Optimize Eloquent model loading for large datasets
     */
    public function optimizeEloquentQueries(): void
    {
        // Prevent lazy loading in production to avoid N+1 queries
        if (app()->environment('production')) {
            Model::preventLazyLoading(true);
        }
        
        // Prevent silent attribute discarding
        Model::preventSilentlyDiscardingAttributes(!app()->environment('production'));
        
        // Prevent accessing missing attributes
        Model::preventAccessingMissingAttributes(!app()->environment('production'));
    }

    /**
     * Process large collections in memory-efficient chunks
     */
    public function processInChunks(
        callable $query,
        callable $processor,
        int $chunkSize = null
    ): void {
        $chunkSize = $chunkSize ?? self::CHUNK_SIZE;
        $this->addCheckpoint('chunked_processing_start');
        
        $query()->chunk($chunkSize, function ($chunk) use ($processor) {
            $this->addCheckpoint('chunk_processing_start');
            
            // Process the chunk
            $processor($chunk);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            $this->addCheckpoint('chunk_processing_end');
        });
        
        $this->addCheckpoint('chunked_processing_complete');
    }

    /**
     * Create a memory-efficient streamed response for large datasets
     */
    public function createStreamedResponse(
        callable $dataGenerator,
        array $headers = []
    ): StreamedResponse {
        return response()->stream(
            function () use ($dataGenerator) {
                $this->addCheckpoint('streaming_start');
                
                // Open output buffer
                $handle = fopen('php://output', 'w');
                
                try {
                    $dataGenerator($handle);
                } finally {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                    $this->addCheckpoint('streaming_complete');
                }
            },
            200,
            array_merge([
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
            ], $headers)
        );
    }

    /**
     * Optimize large CSV export with streaming
     */
    public function streamCsvExport(
        callable $dataQuery,
        array $headers,
        string $filename = 'export.csv'
    ): StreamedResponse {
        return $this->createStreamedResponse(
            function ($handle) use ($dataQuery, $headers) {
                // Write CSV headers
                fputcsv($handle, $headers);
                
                // Stream data in chunks
                $this->processInChunks(
                    $dataQuery,
                    function ($chunk) use ($handle) {
                        foreach ($chunk as $row) {
                            fputcsv($handle, $row instanceof Model ? $row->toArray() : (array)$row);
                        }
                        flush(); // Flush output buffer
                    }
                );
            },
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * Optimize basketball statistics export
     */
    public function streamBasketballStatsExport(string $tenantId, string $season): StreamedResponse
    {
        return $this->streamCsvExport(
            fn() => DB::table('players')
                ->join('player_team', 'player_team.player_id', '=', 'players.id')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->join('clubs', 'teams.club_id', '=', 'clubs.id')
                ->where('clubs.tenant_id', $tenantId)
                ->where('teams.season', $season)
                ->where('player_team.is_active', true)
                ->select([
                    'players.id',
                    'players.first_name',
                    'players.last_name',
                    'teams.name as team_name',
                    'player_team.primary_position as position',
                    'player_team.jersey_number',
                ])
                ->orderBy('teams.name')
                ->orderBy('players.jersey_number'),
            [
                'Player ID',
                'First Name', 
                'Last Name',
                'Team',
                'Position',
                'Jersey Number'
            ],
            "basketball_stats_{$season}_{$tenantId}.csv"
        );
    }

    /**
     * Monitor and log memory usage patterns
     */
    public function analyzeMemoryUsage(): array
    {
        $stats = $this->getMemoryStats();
        $analysis = [
            'status' => $this->getMemoryStatus($stats['current']['bytes']),
            'efficiency_rating' => $this->calculateEfficiencyRating($stats),
            'recommendations' => $this->generateMemoryRecommendations($stats),
            'checkpoint_analysis' => $this->analyzeCheckpoints(),
        ];
        
        // Log if memory usage is concerning
        if ($analysis['status'] !== 'optimal') {
            $this->logMemoryAnalysis($analysis);
        }
        
        return array_merge($stats, $analysis);
    }

    /**
     * Get memory status based on current usage
     */
    private function getMemoryStatus(int $currentMemory): string
    {
        if ($currentMemory > self::MEMORY_CRITICAL_THRESHOLD) {
            return 'critical';
        } elseif ($currentMemory > self::MEMORY_WARNING_THRESHOLD) {
            return 'warning';
        } else {
            return 'optimal';
        }
    }

    /**
     * Calculate memory efficiency rating (0-100)
     */
    private function calculateEfficiencyRating(array $stats): int
    {
        $current = $stats['current']['bytes'];
        $peak = $stats['peak']['bytes'];
        $limit = $stats['limit']['bytes'];
        
        if ($limit <= 0) {
            return 100; // No limit, assume efficient
        }
        
        $usageRatio = $current / $limit;
        $peakRatio = $peak / $limit;
        
        // Lower usage = higher rating
        $usageScore = max(0, 100 - ($usageRatio * 100));
        $peakScore = max(0, 100 - ($peakRatio * 100));
        
        // Consider memory spikes (difference between current and peak)
        $spikeRatio = $peak > 0 ? $current / $peak : 1;
        $stabilityScore = $spikeRatio * 100;
        
        // Weighted average
        return (int)round(($usageScore * 0.4) + ($peakScore * 0.4) + ($stabilityScore * 0.2));
    }

    /**
     * Generate memory optimization recommendations
     */
    private function generateMemoryRecommendations(array $stats): array
    {
        $recommendations = [];
        $current = $stats['current']['bytes'];
        $peak = $stats['peak']['bytes'];
        $limit = $stats['limit']['bytes'];
        
        if ($current > self::MEMORY_WARNING_THRESHOLD) {
            $recommendations[] = [
                'type' => 'high_usage',
                'severity' => $current > self::MEMORY_CRITICAL_THRESHOLD ? 'critical' : 'warning',
                'message' => 'High memory usage detected',
                'suggestion' => 'Consider using chunked processing for large datasets or implementing pagination',
            ];
        }
        
        if ($peak > $current * 2) {
            $recommendations[] = [
                'type' => 'memory_spikes',
                'severity' => 'warning',
                'message' => 'Large memory spikes detected',
                'suggestion' => 'Use streaming responses for large data exports or implement progressive loading',
            ];
        }
        
        if ($limit > 0 && $current > $limit * 0.8) {
            $recommendations[] = [
                'type' => 'approaching_limit',
                'severity' => 'warning',
                'message' => 'Memory usage approaching configured limit',
                'suggestion' => 'Consider increasing memory_limit or optimizing data processing',
            ];
        }
        
        if (count($this->memoryCheckpoints) > 10) {
            $checkpointGrowth = $this->analyzeCheckpointGrowth();
            if ($checkpointGrowth['significant_increases'] > 0) {
                $recommendations[] = [
                    'type' => 'checkpoint_growth',
                    'severity' => 'info',
                    'message' => 'Memory growth detected during request processing',
                    'suggestion' => 'Review memory-intensive operations and consider optimization',
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Analyze memory usage patterns across checkpoints
     */
    private function analyzeCheckpoints(): array
    {
        if (empty($this->memoryCheckpoints)) {
            return [];
        }
        
        $growth = $this->analyzeCheckpointGrowth();
        $phases = $this->identifyMemoryPhases();
        
        return [
            'total_checkpoints' => count($this->memoryCheckpoints),
            'memory_growth' => $growth,
            'memory_phases' => $phases,
            'peak_checkpoint' => $this->findPeakCheckpoint(),
        ];
    }

    /**
     * Analyze memory growth between checkpoints
     */
    private function analyzeCheckpointGrowth(): array
    {
        $growthPoints = [];
        $significantIncreases = 0;
        $previousMemory = $this->initialMemory;
        
        foreach ($this->memoryCheckpoints as $label => $checkpoint) {
            $growth = $checkpoint['current'] - $previousMemory;
            $growthPoints[] = [
                'checkpoint' => $label,
                'growth' => $growth,
                'growth_formatted' => $this->formatBytes($growth),
            ];
            
            // Consider growth > 10MB as significant
            if ($growth > 10 * 1024 * 1024) {
                $significantIncreases++;
            }
            
            $previousMemory = $checkpoint['current'];
        }
        
        return [
            'growth_points' => $growthPoints,
            'significant_increases' => $significantIncreases,
            'total_growth' => $previousMemory - $this->initialMemory,
            'total_growth_formatted' => $this->formatBytes($previousMemory - $this->initialMemory),
        ];
    }

    /**
     * Identify distinct memory usage phases
     */
    private function identifyMemoryPhases(): array
    {
        $phases = [];
        $currentPhase = null;
        $threshold = 5 * 1024 * 1024; // 5MB threshold for phase changes
        
        foreach ($this->memoryCheckpoints as $label => $checkpoint) {
            if ($currentPhase === null) {
                $currentPhase = [
                    'start_checkpoint' => $label,
                    'start_memory' => $checkpoint['current'],
                    'peak_memory' => $checkpoint['peak'],
                    'checkpoints' => 1,
                ];
            } elseif (abs($checkpoint['current'] - $currentPhase['start_memory']) > $threshold) {
                // Phase change detected
                $phases[] = $currentPhase;
                $currentPhase = [
                    'start_checkpoint' => $label,
                    'start_memory' => $checkpoint['current'],
                    'peak_memory' => $checkpoint['peak'],
                    'checkpoints' => 1,
                ];
            } else {
                // Continue current phase
                $currentPhase['checkpoints']++;
                $currentPhase['peak_memory'] = max($currentPhase['peak_memory'], $checkpoint['peak']);
            }
        }
        
        if ($currentPhase !== null) {
            $phases[] = $currentPhase;
        }
        
        return $phases;
    }

    /**
     * Find checkpoint with highest memory usage
     */
    private function findPeakCheckpoint(): ?array
    {
        $peakCheckpoint = null;
        $maxMemory = 0;
        
        foreach ($this->memoryCheckpoints as $label => $checkpoint) {
            if ($checkpoint['peak'] > $maxMemory) {
                $maxMemory = $checkpoint['peak'];
                $peakCheckpoint = array_merge($checkpoint, ['label' => $label]);
            }
        }
        
        return $peakCheckpoint;
    }

    /**
     * Log memory warning
     */
    private function logMemoryWarning(string $label, int $currentMemory): void
    {
        Log::warning('Memory Usage Warning', [
            'checkpoint' => $label,
            'current_memory' => $this->formatBytes($currentMemory),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'threshold' => $this->formatBytes(self::MEMORY_WARNING_THRESHOLD),
            'tenant_id' => app('tenant')?->id,
            'request_url' => request()?->fullUrl(),
        ]);
    }

    /**
     * Log comprehensive memory analysis
     */
    private function logMemoryAnalysis(array $analysis): void
    {
        Log::warning('Memory Usage Analysis', [
            'status' => $analysis['status'],
            'efficiency_rating' => $analysis['efficiency_rating'],
            'recommendations' => $analysis['recommendations'],
            'current_memory' => $analysis['current']['formatted'],
            'peak_memory' => $analysis['peak']['formatted'],
            'memory_limit' => $analysis['limit']['formatted'],
            'tenant_id' => app('tenant')?->id,
            'request_url' => request()?->fullUrl(),
        ]);
    }

    /**
     * Get PHP memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return 0; // Unlimited
        }
        
        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Clear memory checkpoints (useful for long-running processes)
     */
    public function clearCheckpoints(): void
    {
        $this->memoryCheckpoints = [];
        $this->initialMemory = memory_get_usage(true);
        $this->peakMemory = 0;
    }

    /**
     * Force garbage collection and return memory freed
     */
    public function forceGarbageCollection(): array
    {
        $beforeMemory = memory_get_usage(true);
        $beforePeak = memory_get_peak_usage(true);
        
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
        } else {
            $collected = 0;
        }
        
        $afterMemory = memory_get_usage(true);
        $freed = $beforeMemory - $afterMemory;
        
        return [
            'cycles_collected' => $collected,
            'memory_before' => $this->formatBytes($beforeMemory),
            'memory_after' => $this->formatBytes($afterMemory),
            'memory_freed' => $this->formatBytes($freed),
            'peak_memory' => $this->formatBytes($beforePeak),
        ];
    }
}