<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VideoAnnotationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total_annotations' => $this->collection->count(),
                'timeline_stats' => $this->getTimelineStats(),
                'type_distribution' => $this->getTypeDistribution(),
                'ai_vs_manual_stats' => $this->getAiVsManualStats(),
                'quality_metrics' => $this->getQualityMetrics(),
            ],
            'coverage_analysis' => $this->getCoverageAnalysis($request),
            'performance_insights' => $this->getPerformanceInsights(),
        ];
    }

    /**
     * Get timeline statistics.
     */
    private function getTimelineStats(): array
    {
        $annotations = $this->collection;
        
        if ($annotations->isEmpty()) {
            return [
                'total_duration_annotated' => 0,
                'earliest_annotation' => null,
                'latest_annotation' => null,
                'average_annotation_length' => 0,
                'annotation_density' => 0,
            ];
        }

        $totalAnnotatedTime = $annotations->sum(function ($annotation) {
            return $annotation->end_time - $annotation->start_time;
        });

        $earliestStart = $annotations->min('start_time');
        $latestEnd = $annotations->max('end_time');
        $timespan = $latestEnd - $earliestStart;

        return [
            'total_duration_annotated' => $totalAnnotatedTime,
            'total_duration_annotated_formatted' => $this->formatDuration($totalAnnotatedTime),
            'earliest_annotation' => $earliestStart,
            'latest_annotation' => $latestEnd,
            'timespan' => $timespan,
            'timespan_formatted' => $this->formatDuration($timespan),
            'average_annotation_length' => round($annotations->avg(function ($annotation) {
                return $annotation->end_time - $annotation->start_time;
            }), 1),
            'annotation_density' => $timespan > 0 ? round(($annotations->count() / $timespan) * 60, 2) : 0, // annotations per minute
            'gaps_between_annotations' => $this->findGaps(),
        ];
    }

    /**
     * Get annotation type distribution.
     */
    private function getTypeDistribution(): array
    {
        $annotations = $this->collection;
        
        $byType = $annotations->groupBy('annotation_type')->map->count();
        $byPlayType = $annotations->whereNotNull('play_type')->groupBy('play_type')->map->count();
        $byOutcome = $annotations->whereNotNull('outcome')->groupBy('outcome')->map->count();
        $byStatus = $annotations->groupBy('status')->map->count();

        return [
            'by_annotation_type' => $byType->toArray(),
            'by_play_type' => $byPlayType->toArray(),
            'by_outcome' => $byOutcome->toArray(),
            'by_status' => $byStatus->toArray(),
            'most_common_type' => $byType->keys()->first(),
            'most_common_play_type' => $byPlayType->keys()->first(),
        ];
    }

    /**
     * Get AI vs Manual annotation statistics.
     */
    private function getAiVsManualStats(): array
    {
        $annotations = $this->collection;
        
        $aiAnnotations = $annotations->where('is_ai_generated', true);
        $manualAnnotations = $annotations->where('is_ai_generated', false);

        $aiStats = [
            'count' => $aiAnnotations->count(),
            'percentage' => $annotations->count() > 0 ? 
                round(($aiAnnotations->count() / $annotations->count()) * 100, 1) : 0,
            'average_confidence' => $aiAnnotations->whereNotNull('ai_confidence')->avg('ai_confidence'),
            'high_confidence_count' => $aiAnnotations->where('ai_confidence', '>', 0.8)->count(),
            'low_confidence_count' => $aiAnnotations->where('ai_confidence', '<', 0.6)->count(),
        ];

        $manualStats = [
            'count' => $manualAnnotations->count(),
            'percentage' => $annotations->count() > 0 ? 
                round(($manualAnnotations->count() / $annotations->count()) * 100, 1) : 0,
            'average_quality_score' => $this->calculateAverageQualityScore($manualAnnotations),
        ];

        return [
            'ai_generated' => $aiStats,
            'manual' => $manualStats,
            'total_count' => $annotations->count(),
            'ai_accuracy_estimate' => $this->estimateAiAccuracy($aiAnnotations),
        ];
    }

    /**
     * Get quality metrics.
     */
    private function getQualityMetrics(): array
    {
        $annotations = $this->collection;
        
        $withDescription = $annotations->whereNotNull('description')->count();
        $withCourtPosition = $annotations->whereNotNull('court_position_x')->count();
        $withPlayType = $annotations->whereNotNull('play_type')->count();
        $withOutcome = $annotations->whereNotNull('outcome')->count();
        
        $published = $annotations->where('status', 'published')->count();
        $needingReview = $annotations->where('status', 'pending_review')->count();

        return [
            'completeness_metrics' => [
                'with_description' => $withDescription,
                'with_court_position' => $withCourtPosition,
                'with_play_type' => $withPlayType,
                'with_outcome' => $withOutcome,
                'description_rate' => $annotations->count() > 0 ? 
                    round(($withDescription / $annotations->count()) * 100, 1) : 0,
            ],
            'review_metrics' => [
                'published' => $published,
                'needing_review' => $needingReview,
                'approval_rate' => $annotations->count() > 0 ? 
                    round(($published / $annotations->count()) * 100, 1) : 0,
            ],
            'duration_quality' => [
                'appropriate_length' => $annotations->filter(function ($annotation) {
                    $duration = $annotation->end_time - $annotation->start_time;
                    return $duration >= 3 && $duration <= 30; // 3-30 seconds is appropriate
                })->count(),
                'too_short' => $annotations->filter(function ($annotation) {
                    return ($annotation->end_time - $annotation->start_time) < 3;
                })->count(),
                'too_long' => $annotations->filter(function ($annotation) {
                    return ($annotation->end_time - $annotation->start_time) > 30;
                })->count(),
            ],
        ];
    }

    /**
     * Get coverage analysis.
     */
    private function getCoverageAnalysis(Request $request): array
    {
        $annotations = $this->collection;
        
        if ($annotations->isEmpty()) {
            return ['covered_time' => 0, 'coverage_percentage' => 0];
        }

        // Calculate actual covered time (handling overlaps)
        $timeRanges = $annotations->map(function ($annotation) {
            return [
                'start' => $annotation->start_time,
                'end' => $annotation->end_time,
            ];
        })->sortBy('start')->values();

        $mergedRanges = $this->mergeOverlappingRanges($timeRanges->toArray());
        $coveredTime = collect($mergedRanges)->sum(function ($range) {
            return $range['end'] - $range['start'];
        });

        // Get video duration if available
        $videoDuration = $this->getVideoDuration($request);
        $coveragePercentage = $videoDuration > 0 ? 
            round(($coveredTime / $videoDuration) * 100, 1) : 0;

        return [
            'covered_time' => $coveredTime,
            'covered_time_formatted' => $this->formatDuration($coveredTime),
            'coverage_percentage' => $coveragePercentage,
            'uncovered_gaps' => $this->findUncoveredGaps($mergedRanges, $videoDuration),
            'overlapping_annotations' => $this->countOverlappingAnnotations(),
            'coverage_quality' => $this->assessCoverageQuality($coveragePercentage),
        ];
    }

    /**
     * Get performance insights from annotations.
     */
    private function getPerformanceInsights(): array
    {
        $annotations = $this->collection;
        
        $shotAnnotations = $annotations->where('play_type', 'shot');
        $passAnnotations = $annotations->where('play_type', 'pass');
        $reboundAnnotations = $annotations->where('play_type', 'rebound');

        $insights = [];

        // Shooting performance
        if ($shotAnnotations->count() > 0) {
            $madeShots = $shotAnnotations->where('outcome', 'successful');
            $insights['shooting'] = [
                'total_shots' => $shotAnnotations->count(),
                'made_shots' => $madeShots->count(),
                'shooting_percentage' => round(($madeShots->count() / $shotAnnotations->count()) * 100, 1),
                'total_points' => $shotAnnotations->sum('points_scored'),
                'shot_locations' => $this->getPopularShotLocations($shotAnnotations),
            ];
        }

        // Passing performance
        if ($passAnnotations->count() > 0) {
            $successfulPasses = $passAnnotations->where('outcome', 'successful');
            $insights['passing'] = [
                'total_passes' => $passAnnotations->count(),
                'successful_passes' => $successfulPasses->count(),
                'pass_success_rate' => round(($successfulPasses->count() / $passAnnotations->count()) * 100, 1),
            ];
        }

        // Rebounding
        if ($reboundAnnotations->count() > 0) {
            $insights['rebounding'] = [
                'total_rebounds' => $reboundAnnotations->count(),
                'offensive_rebounds' => $reboundAnnotations->where('outcome', 'offensive')->count(),
                'defensive_rebounds' => $reboundAnnotations->where('outcome', 'defensive')->count(),
            ];
        }

        return $insights;
    }

    /**
     * Find gaps between annotations.
     */
    private function findGaps(): array
    {
        $annotations = $this->collection->sortBy('start_time');
        $gaps = [];

        for ($i = 0; $i < $annotations->count() - 1; $i++) {
            $currentEnd = $annotations->values()[$i]->end_time;
            $nextStart = $annotations->values()[$i + 1]->start_time;
            
            if ($nextStart > $currentEnd) {
                $gaps[] = [
                    'start' => $currentEnd,
                    'end' => $nextStart,
                    'duration' => $nextStart - $currentEnd,
                ];
            }
        }

        return $gaps;
    }

    /**
     * Calculate average quality score for manual annotations.
     */
    private function calculateAverageQualityScore($annotations): float
    {
        if ($annotations->isEmpty()) return 0;

        $totalScore = $annotations->sum(function ($annotation) {
            $score = 50; // Base score
            
            if (strlen($annotation->title) >= 10) $score += 10;
            if ($annotation->description && strlen($annotation->description) >= 20) $score += 10;
            if ($annotation->play_type) $score += 5;
            if ($annotation->outcome) $score += 5;
            if ($annotation->court_position_x && $annotation->court_position_y) $score += 10;
            
            $duration = $annotation->end_time - $annotation->start_time;
            if ($duration >= 3 && $duration <= 30) $score += 10;
            
            return $score;
        });

        return round($totalScore / $annotations->count(), 1);
    }

    /**
     * Estimate AI accuracy based on confidence scores.
     */
    private function estimateAiAccuracy($aiAnnotations): array
    {
        if ($aiAnnotations->isEmpty()) {
            return ['estimated_accuracy' => 0, 'confidence_distribution' => []];
        }

        $confidenceDistribution = [
            'very_high' => $aiAnnotations->where('ai_confidence', '>=', 0.9)->count(),
            'high' => $aiAnnotations->whereBetween('ai_confidence', [0.8, 0.89])->count(),
            'medium' => $aiAnnotations->whereBetween('ai_confidence', [0.7, 0.79])->count(),
            'low' => $aiAnnotations->whereBetween('ai_confidence', [0.6, 0.69])->count(),
            'very_low' => $aiAnnotations->where('ai_confidence', '<', 0.6)->count(),
        ];

        $averageConfidence = $aiAnnotations->avg('ai_confidence');
        $estimatedAccuracy = $averageConfidence * 100; // Simple estimation

        return [
            'estimated_accuracy' => round($estimatedAccuracy, 1),
            'average_confidence' => round($averageConfidence, 3),
            'confidence_distribution' => $confidenceDistribution,
        ];
    }

    /**
     * Merge overlapping time ranges.
     */
    private function mergeOverlappingRanges(array $ranges): array
    {
        if (empty($ranges)) return [];

        $merged = [$ranges[0]];
        
        for ($i = 1; $i < count($ranges); $i++) {
            $lastMerged = &$merged[count($merged) - 1];
            $current = $ranges[$i];
            
            if ($current['start'] <= $lastMerged['end']) {
                $lastMerged['end'] = max($lastMerged['end'], $current['end']);
            } else {
                $merged[] = $current;
            }
        }
        
        return $merged;
    }

    /**
     * Get video duration from request context.
     */
    private function getVideoDuration(Request $request): int
    {
        // Try to get from route parameter or request
        $videoId = $request->route('videoFile')?->id ?? $request->route('video_file');
        
        if ($videoId && is_numeric($videoId)) {
            $video = \App\Models\VideoFile::find($videoId);
            return $video?->duration ?? 0;
        }

        // Fallback: estimate from annotation span
        if ($this->collection->isNotEmpty()) {
            return $this->collection->max('end_time');
        }

        return 0;
    }

    /**
     * Find uncovered gaps in video.
     */
    private function findUncoveredGaps(array $mergedRanges, int $videoDuration): array
    {
        if (empty($mergedRanges) || $videoDuration <= 0) return [];

        $gaps = [];
        
        // Gap at beginning
        if ($mergedRanges[0]['start'] > 0) {
            $gaps[] = [
                'start' => 0,
                'end' => $mergedRanges[0]['start'],
                'duration' => $mergedRanges[0]['start'],
            ];
        }
        
        // Gaps between ranges
        for ($i = 0; $i < count($mergedRanges) - 1; $i++) {
            $gapStart = $mergedRanges[$i]['end'];
            $gapEnd = $mergedRanges[$i + 1]['start'];
            
            if ($gapEnd > $gapStart) {
                $gaps[] = [
                    'start' => $gapStart,
                    'end' => $gapEnd,
                    'duration' => $gapEnd - $gapStart,
                ];
            }
        }
        
        // Gap at end
        $lastEnd = end($mergedRanges)['end'];
        if ($lastEnd < $videoDuration) {
            $gaps[] = [
                'start' => $lastEnd,
                'end' => $videoDuration,
                'duration' => $videoDuration - $lastEnd,
            ];
        }
        
        return $gaps;
    }

    /**
     * Count overlapping annotations.
     */
    private function countOverlappingAnnotations(): int
    {
        $annotations = $this->collection;
        $overlappingCount = 0;

        foreach ($annotations as $annotation) {
            $overlaps = $annotations->where('id', '!=', $annotation->id)
                ->filter(function ($other) use ($annotation) {
                    return ($other->start_time < $annotation->end_time && 
                            $other->end_time > $annotation->start_time);
                });
            
            if ($overlaps->count() > 0) {
                $overlappingCount++;
            }
        }

        return $overlappingCount;
    }

    /**
     * Assess coverage quality.
     */
    private function assessCoverageQuality(float $coveragePercentage): string
    {
        if ($coveragePercentage >= 80) return 'Excellent';
        if ($coveragePercentage >= 60) return 'Good';
        if ($coveragePercentage >= 40) return 'Fair';
        if ($coveragePercentage >= 20) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Get popular shot locations.
     */
    private function getPopularShotLocations($shotAnnotations): array
    {
        return $shotAnnotations
            ->filter(function ($shot) {
                return $shot->court_position_x && $shot->court_position_y;
            })
            ->groupBy(function ($shot) {
                // Group by court zones
                $x = $shot->court_position_x;
                $y = $shot->court_position_y;
                
                if ($y < 200) return 'Top Zone';
                if ($y > 400) return 'Bottom Zone';
                if ($x < 250) return 'Left Side';
                if ($x > 750) return 'Right Side';
                return 'Center';
            })
            ->map->count()
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    /**
     * Format duration in human readable format.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' Sek.';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds === 0) {
            return $minutes . ' Min.';
        }
        
        return $minutes . ' Min. ' . $remainingSeconds . ' Sek.';
    }

    /**
     * Additional response metadata.
     */
    public function with(Request $request): array
    {
        return [
            'export_options' => [
                'formats' => ['json', 'csv', 'srt', 'vtt'],
                'include_ai_generated' => true,
                'filter_by_confidence' => true,
            ],
            'visualization_data' => [
                'timeline_chart' => $this->getTimelineChartData(),
                'heatmap_data' => $this->getHeatmapData(),
                'type_distribution_chart' => $this->getTypeDistributionChart(),
            ],
        ];
    }

    /**
     * Get timeline chart data.
     */
    private function getTimelineChartData(): array
    {
        return $this->collection->map(function ($annotation) {
            return [
                'start' => $annotation->start_time,
                'end' => $annotation->end_time,
                'type' => $annotation->annotation_type,
                'color' => $annotation->color_code ?? '#007bff',
                'title' => $annotation->title,
            ];
        })->toArray();
    }

    /**
     * Get heatmap data for court positions.
     */
    private function getHeatmapData(): array
    {
        return $this->collection
            ->filter(function ($annotation) {
                return $annotation->court_position_x && $annotation->court_position_y;
            })
            ->map(function ($annotation) {
                return [
                    'x' => $annotation->court_position_x,
                    'y' => $annotation->court_position_y,
                    'intensity' => 1,
                    'type' => $annotation->play_type,
                ];
            })
            ->toArray();
    }

    /**
     * Get type distribution chart data.
     */
    private function getTypeDistributionChart(): array
    {
        return $this->collection
            ->groupBy('annotation_type')
            ->map(function ($group, $type) {
                return [
                    'label' => $type,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $this->collection->count()) * 100, 1),
                ];
            })
            ->values()
            ->toArray();
    }
}