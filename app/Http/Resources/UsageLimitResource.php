<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageLimitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Handle both array and object resource formats
        $data = is_array($this->resource) ? $this->resource : (array) $this->resource;

        $metric = $data['metric'] ?? array_key_first($data);
        $current = $data['current'] ?? 0;
        $limit = $data['limit'] ?? 0;
        $percentage = $data['percentage'] ?? 0;
        $unlimited = $data['unlimited'] ?? false;

        return [
            'metric' => $metric,
            'current' => $current,
            'limit' => $limit,
            'percentage' => round($percentage, 1),
            'unlimited' => $unlimited,

            // Status flags
            'is_approaching_limit' => !$unlimited && $percentage > 80,
            'is_at_limit' => !$unlimited && $percentage >= 100,
            'is_over_limit' => !$unlimited && $percentage > 100,

            // Formatted values for display
            'formatted_current' => number_format($current, 0, ',', '.'),
            'formatted_limit' => $unlimited ? 'Unbegrenzt' : number_format($limit, 0, ',', '.'),
            'formatted_percentage' => $unlimited ? 'N/A' : round($percentage, 1) . '%',

            // Remaining capacity
            'remaining' => $unlimited ? -1 : max(0, $limit - $current),
            'formatted_remaining' => $unlimited ? 'Unbegrenzt' : number_format(max(0, $limit - $current), 0, ',', '.'),

            // Status severity for UI
            'severity' => $this->getSeverity($percentage, $unlimited),
            'severity_label' => $this->getSeverityLabel($percentage, $unlimited),

            // Metric metadata
            'metric_label' => $this->getMetricLabel($metric),
            'metric_icon' => $this->getMetricIcon($metric),
            'metric_unit' => $this->getMetricUnit($metric),
        ];
    }

    /**
     * Get severity level based on usage percentage.
     */
    private function getSeverity(float $percentage, bool $unlimited): string
    {
        if ($unlimited) {
            return 'none';
        }

        if ($percentage >= 100) {
            return 'critical';
        }

        if ($percentage >= 90) {
            return 'error';
        }

        if ($percentage >= 80) {
            return 'warning';
        }

        if ($percentage >= 70) {
            return 'info';
        }

        return 'success';
    }

    /**
     * Get severity label for display.
     */
    private function getSeverityLabel(float $percentage, bool $unlimited): string
    {
        if ($unlimited) {
            return 'Unbegrenzt';
        }

        if ($percentage >= 100) {
            return 'Limit erreicht';
        }

        if ($percentage >= 90) {
            return 'Kritisch';
        }

        if ($percentage >= 80) {
            return 'Warnung';
        }

        if ($percentage >= 70) {
            return 'Bald erreicht';
        }

        return 'OK';
    }

    /**
     * Get human-readable metric label.
     */
    private function getMetricLabel(string $metric): string
    {
        return match($metric) {
            'users' => 'Benutzer',
            'teams' => 'Teams',
            'players' => 'Spieler',
            'storage_gb' => 'Speicher',
            'api_calls_per_hour' => 'API-Aufrufe',
            'games_per_month' => 'Spiele pro Monat',
            'training_sessions_per_month' => 'Trainingseinheiten pro Monat',
            default => ucfirst(str_replace('_', ' ', $metric)),
        };
    }

    /**
     * Get icon name for metric.
     */
    private function getMetricIcon(string $metric): string
    {
        return match($metric) {
            'users' => 'users',
            'teams' => 'users-group',
            'players' => 'user',
            'storage_gb' => 'database',
            'api_calls_per_hour' => 'api',
            'games_per_month' => 'calendar',
            'training_sessions_per_month' => 'clipboard',
            default => 'chart-bar',
        };
    }

    /**
     * Get unit for metric.
     */
    private function getMetricUnit(string $metric): string
    {
        return match($metric) {
            'storage_gb' => 'GB',
            'api_calls_per_hour' => 'Aufrufe/Std',
            'games_per_month' => 'Spiele/Monat',
            'training_sessions_per_month' => 'Einheiten/Monat',
            default => '',
        };
    }
}
