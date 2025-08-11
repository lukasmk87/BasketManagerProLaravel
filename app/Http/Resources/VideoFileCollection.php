<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VideoFileCollection extends ResourceCollection
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
                'total_count' => $this->collection->count(),
                'statistics' => $this->generateStatistics(),
                'filters_applied' => $this->getAppliedFilters($request),
                'available_filters' => $this->getAvailableFilters(),
            ],
            'processing_status_summary' => $this->getProcessingStatusSummary(),
            'ai_analysis_summary' => $this->getAiAnalysisSummary(),
        ];
    }

    /**
     * Generate statistics for the video collection.
     */
    private function generateStatistics(): array
    {
        $videos = $this->collection;
        
        return [
            'total_videos' => $videos->count(),
            'total_duration' => $videos->sum('duration'),
            'total_duration_formatted' => $this->formatTotalDuration($videos->sum('duration')),
            'total_file_size' => $videos->sum('file_size'),
            'total_file_size_human' => $this->formatFileSize($videos->sum('file_size')),
            'average_duration' => round($videos->avg('duration')),
            'average_file_size' => round($videos->avg('file_size')),
            
            'by_video_type' => $videos->groupBy('video_type')->map->count(),
            'by_processing_status' => $videos->groupBy('processing_status')->map->count(),
            'by_ai_analysis_status' => $videos->groupBy('ai_analysis_status')->map->count(),
            'by_visibility' => $videos->groupBy('visibility')->map->count(),
            
            'total_views' => $videos->sum('views_count'),
            'total_annotations' => $videos->sum('annotation_count'),
            'videos_with_ai_analysis' => $videos->where('ai_analysis_status', 'completed')->count(),
            'videos_ready_for_playback' => $videos->where('processing_status', 'completed')->count(),
        ];
    }

    /**
     * Get applied filters from request.
     */
    private function getAppliedFilters(Request $request): array
    {
        $appliedFilters = [];
        
        $possibleFilters = [
            'game_id' => 'Spiel',
            'team_id' => 'Team',
            'video_type' => 'Video-Typ',
            'processing_status' => 'Verarbeitungsstatus',
            'ai_analysis_status' => 'AI-Analyse Status',
            'search' => 'Suchbegriff',
            'from_date' => 'Von Datum',
            'to_date' => 'Bis Datum',
            'sort_by' => 'Sortierung',
            'sort_direction' => 'Sortierrichtung'
        ];

        foreach ($possibleFilters as $param => $label) {
            if ($request->has($param)) {
                $appliedFilters[$param] = [
                    'label' => $label,
                    'value' => $request->get($param)
                ];
            }
        }

        return $appliedFilters;
    }

    /**
     * Get available filter options.
     */
    private function getAvailableFilters(): array
    {
        return [
            'video_types' => [
                'full_game' => 'Komplettes Spiel',
                'game_highlights' => 'Spiel-Highlights',
                'training_session' => 'Trainingseinheit',
                'drill_demo' => 'Übungs-Demo',
                'player_analysis' => 'Spieler-Analyse',
                'tactical_analysis' => 'Taktik-Analyse',
                'referee_footage' => 'Schiedsrichter-Material',
                'fan_footage' => 'Fan-Material'
            ],
            'processing_statuses' => [
                'pending' => 'Wartend',
                'processing' => 'Verarbeitung läuft',
                'completed' => 'Abgeschlossen',
                'failed' => 'Fehlgeschlagen'
            ],
            'ai_analysis_statuses' => [
                'pending' => 'Wartend',
                'in_progress' => 'Läuft',
                'completed' => 'Abgeschlossen',
                'failed' => 'Fehlgeschlagen',
                'disabled' => 'Deaktiviert'
            ],
            'visibility_options' => [
                'public' => 'Öffentlich',
                'team_only' => 'Nur Team',
                'private' => 'Privat'
            ],
            'sort_options' => [
                'created_at' => 'Erstellungsdatum',
                'title' => 'Titel',
                'duration' => 'Dauer',
                'file_size' => 'Dateigröße',
                'processing_status' => 'Verarbeitungsstatus',
                'ai_analysis_status' => 'AI-Status',
                'views_count' => 'Aufrufe'
            ]
        ];
    }

    /**
     * Get processing status summary.
     */
    private function getProcessingStatusSummary(): array
    {
        $videos = $this->collection;
        
        $statusCounts = [
            'pending' => $videos->where('processing_status', 'pending')->count(),
            'processing' => $videos->where('processing_status', 'processing')->count(),
            'completed' => $videos->where('processing_status', 'completed')->count(),
            'failed' => $videos->where('processing_status', 'failed')->count(),
        ];

        $totalProcessingTime = $videos
            ->whereNotNull('processing_completed_at')
            ->map(function ($video) {
                return $video->processing_completed_at?->diffInMinutes($video->created_at) ?? 0;
            })
            ->sum();

        return [
            'status_counts' => $statusCounts,
            'completion_rate' => $videos->count() > 0 ? 
                round(($statusCounts['completed'] / $videos->count()) * 100, 1) : 0,
            'failure_rate' => $videos->count() > 0 ? 
                round(($statusCounts['failed'] / $videos->count()) * 100, 1) : 0,
            'average_processing_time_minutes' => $statusCounts['completed'] > 0 ? 
                round($totalProcessingTime / $statusCounts['completed'], 1) : 0,
            'currently_processing' => $statusCounts['pending'] + $statusCounts['processing'],
        ];
    }

    /**
     * Get AI analysis summary.
     */
    private function getAiAnalysisSummary(): array
    {
        $videos = $this->collection;
        
        $aiStatusCounts = [
            'pending' => $videos->where('ai_analysis_status', 'pending')->count(),
            'in_progress' => $videos->where('ai_analysis_status', 'in_progress')->count(),
            'completed' => $videos->where('ai_analysis_status', 'completed')->count(),
            'failed' => $videos->where('ai_analysis_status', 'failed')->count(),
            'disabled' => $videos->where('ai_analysis_status', 'disabled')->count(),
        ];

        $completedAnalyses = $videos->where('ai_analysis_status', 'completed');
        $averageConfidence = $completedAnalyses->count() > 0 ? 
            round($completedAnalyses->avg('ai_confidence_score'), 3) : 0;

        return [
            'status_counts' => $aiStatusCounts,
            'completion_rate' => $videos->count() > 0 ? 
                round(($aiStatusCounts['completed'] / $videos->count()) * 100, 1) : 0,
            'average_confidence_score' => $averageConfidence,
            'high_confidence_analyses' => $completedAnalyses->where('ai_confidence_score', '>', 0.8)->count(),
            'total_insights_generated' => $this->countTotalInsights($completedAnalyses),
            'ai_enabled_videos' => $videos->where('ai_analysis_enabled', true)->count(),
        ];
    }

    /**
     * Format total duration in human readable format.
     */
    private function formatTotalDuration(int $totalSeconds): string
    {
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d Std. %d Min. %d Sek.', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%d Min. %d Sek.', $minutes, $seconds);
        } else {
            return sprintf('%d Sek.', $seconds);
        }
    }

    /**
     * Format file size in human readable format.
     */
    private function formatFileSize(int $totalBytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($totalBytes) / log(1024));
        
        return round($totalBytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }

    /**
     * Count total insights from AI analyses.
     */
    private function countTotalInsights($completedAnalyses): array
    {
        $totalPlayers = 0;
        $totalActions = 0;
        $totalShots = 0;
        $courtsDetected = 0;

        foreach ($completedAnalyses as $video) {
            $results = $video->ai_analysis_results;
            if (is_array($results)) {
                $totalPlayers += $results['players']['total_players_detected'] ?? 0;
                $totalActions += $results['actions']['total_actions'] ?? 0;
                $totalShots += $results['shots']['total_shots'] ?? 0;
                
                if ($results['court']['court_detected'] ?? false) {
                    $courtsDetected++;
                }
            }
        }

        return [
            'total_players_detected' => $totalPlayers,
            'total_actions_recognized' => $totalActions,
            'total_shots_analyzed' => $totalShots,
            'courts_detected' => $courtsDetected,
        ];
    }

    /**
     * Additional response metadata.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => $request->url(),
                'export_csv' => url('/api/videos/export?format=csv'),
                'export_json' => url('/api/videos/export?format=json'),
            ],
            'export_options' => [
                'formats' => ['json', 'csv', 'excel'],
                'max_records' => 1000,
            ],
            'bulk_actions' => [
                'available_actions' => [
                    'delete' => 'Löschen',
                    'start_ai_analysis' => 'AI-Analyse starten',
                    'update_visibility' => 'Sichtbarkeit ändern'
                ],
                'max_selection' => 100,
            ]
        ];
    }
}