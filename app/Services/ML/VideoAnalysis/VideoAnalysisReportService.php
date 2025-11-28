<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use Illuminate\Support\Facades\Log;

/**
 * Generates analysis reports and insights from AI results.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Handles report generation, summaries, insights, and recommendations.
 */
class VideoAnalysisReportService
{
    /**
     * Generate comprehensive analysis report for a video.
     */
    public function generateAnalysisReport(VideoFile $videoFile): array
    {
        $results = $videoFile->ai_analysis_results ?? [];

        $report = [
            'video_id' => $videoFile->id,
            'analysis_date' => $videoFile->ai_analysis_completed_at,
            'overall_confidence' => $results['overall_confidence'] ?? 0,
            'analysis_summary' => $this->generateAnalysisSummary($results),
            'detected_elements' => $this->summarizeDetectedElements($results),
            'basketball_insights' => $this->generateBasketballInsights($results),
            'recommendations' => $this->generateRecommendations($results, $videoFile),
        ];

        Log::info("Generated AI analysis report for video {$videoFile->id}");

        return $report;
    }

    /**
     * Generate analysis summary.
     */
    public function generateAnalysisSummary(array $results): array
    {
        return [
            'players_detected' => $results['players']['total_players_detected'] ?? 0,
            'actions_recognized' => $results['actions']['total_actions'] ?? 0,
            'shots_analyzed' => $results['shots']['total_shots'] ?? 0,
            'court_detected' => $results['court']['court_detected'] ?? false,
            'overall_confidence' => $results['overall_confidence'] ?? 0,
        ];
    }

    /**
     * Summarize detected elements.
     */
    public function summarizeDetectedElements(array $results): array
    {
        $elements = [];

        if (isset($results['players'])) {
            $elements['players'] = $results['players']['total_players_detected'] ?? 0;
        }

        if (isset($results['court']) && $results['court']['court_detected']) {
            $elements['court'] = 'detected';
        }

        if (isset($results['actions'])) {
            $elements['actions'] = $results['actions']['action_counts'] ?? [];
        }

        return $elements;
    }

    /**
     * Generate basketball-specific insights.
     */
    public function generateBasketballInsights(array $results): array
    {
        $insights = [];

        // Player insights
        if (isset($results['players'])) {
            $playerCount = $results['players']['total_players_detected'] ?? 0;
            if ($playerCount >= 8) {
                $insights[] = 'Full team scrimmage detected';
            } elseif ($playerCount >= 4) {
                $insights[] = 'Small group training detected';
            }
        }

        // Shot insights
        if (isset($results['shots'])) {
            $shootingPct = $results['shots']['shooting_percentage'] ?? 0;
            if ($shootingPct > 50) {
                $insights[] = 'Good shooting performance';
            } elseif ($shootingPct < 30) {
                $insights[] = 'Low shooting percentage - may need practice';
            }

            // Shot distribution insights
            $shotsByType = $results['shots']['shots_by_type'] ?? [];
            if (isset($shotsByType['three_point']) && $shotsByType['three_point'] > 5) {
                $insights[] = 'High three-point attempt volume';
            }
        }

        // Action insights
        if (isset($results['actions'])) {
            $actionCounts = $results['actions']['action_counts'] ?? [];

            if (isset($actionCounts['pass']) && $actionCounts['pass'] > 20) {
                $insights[] = 'Good ball movement detected';
            }

            if (isset($actionCounts['steal']) && $actionCounts['steal'] > 5) {
                $insights[] = 'Active defensive play detected';
            }
        }

        return $insights;
    }

    /**
     * Generate recommendations based on analysis.
     */
    public function generateRecommendations(array $results, VideoFile $videoFile): array
    {
        $recommendations = [];

        $confidence = $results['overall_confidence'] ?? 0;

        if ($confidence < 0.7) {
            $recommendations[] = 'Consider manual review due to low AI confidence';
        }

        if (isset($results['shots']) && $results['shots']['total_shots'] > 5) {
            $recommendations[] = 'Create shot chart analysis session';
        }

        if (isset($results['actions']) && $results['actions']['total_actions'] > 10) {
            $recommendations[] = 'Generate training recommendations based on detected actions';
        }

        // Video type specific recommendations
        switch ($videoFile->video_type) {
            case 'full_game':
                $recommendations[] = 'Consider creating highlight reel from detected plays';
                break;
            case 'training_session':
                $recommendations[] = 'Review drill effectiveness based on action patterns';
                break;
            case 'player_analysis':
                $recommendations[] = 'Generate player-specific performance report';
                break;
        }

        return $recommendations;
    }

    /**
     * Generate performance metrics from results.
     */
    public function generatePerformanceMetrics(array $results): array
    {
        $metrics = [];

        if (isset($results['shots'])) {
            $metrics['shooting'] = [
                'total_attempts' => $results['shots']['total_shots'] ?? 0,
                'made' => $results['shots']['made_shots'] ?? 0,
                'percentage' => $results['shots']['shooting_percentage'] ?? 0,
                'by_type' => $results['shots']['shots_by_type'] ?? [],
            ];
        }

        if (isset($results['actions'])) {
            $metrics['activity'] = [
                'total_actions' => $results['actions']['total_actions'] ?? 0,
                'breakdown' => $results['actions']['action_counts'] ?? [],
            ];
        }

        if (isset($results['players'])) {
            $metrics['players'] = [
                'detected' => $results['players']['total_players_detected'] ?? 0,
                'avg_confidence' => $results['players']['average_confidence'] ?? 0,
            ];
        }

        return $metrics;
    }
}
