<?php

namespace App\Jobs;

use App\Models\TrainingSession;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job for generating training session reports
 * Creates comprehensive reports after training sessions are completed
 */
class GenerateTrainingReport implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    public $timeout = 300; // 5 minutes for report generation

    private TrainingSession $trainingSession;

    /**
     * Create a new job instance
     *
     * @param TrainingSession $trainingSession
     */
    public function __construct(TrainingSession $trainingSession)
    {
        $this->trainingSession = $trainingSession;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Load the session with all relationships
            $session = $this->trainingSession->fresh([
                'team.club',
                'drills',
                'attendance.player.user',
                'trainer',
                'assistantTrainer'
            ]);

            if (!$session) {
                Log::warning("Training session {$this->trainingSession->id} not found, skipping report generation");
                return;
            }

            if ($session->status !== 'completed') {
                Log::info("Training session {$session->id} is not completed, skipping report generation");
                return;
            }

            Log::info("Generating training report for session {$session->id}");

            // Generate the report data
            $reportData = $this->generateReportData($session);

            // Save report data to database
            $this->saveReportData($session, $reportData);

            // Generate PDF report if enabled
            if (config('reports.generate_pdf', true)) {
                $this->generatePdfReport($session, $reportData);
            }

            // Send report to coaches
            if (config('reports.email_to_coaches', true)) {
                $this->emailReportToCoaches($session, $reportData);
            }

            // Update session with report completion
            $session->update([
                'report_generated_at' => now(),
                'report_status' => 'completed'
            ]);

            Log::info("Successfully generated training report for session {$session->id}");

        } catch (\Exception $e) {
            Log::error("Failed to generate training report for session {$this->trainingSession->id}: " . $e->getMessage());
            
            // Mark session report as failed
            $this->trainingSession->update([
                'report_status' => 'failed',
                'report_error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateTrainingReport job failed for session {$this->trainingSession->id}: " . $exception->getMessage());
        
        $this->trainingSession->update([
            'report_status' => 'failed',
            'report_error' => $exception->getMessage()
        ]);
    }

    /**
     * Generate comprehensive report data
     *
     * @param TrainingSession $session
     * @return array
     */
    private function generateReportData(TrainingSession $session): array
    {
        $attendance = $session->attendance;
        $drills = $session->drills;

        // Calculate attendance statistics
        $totalPlayers = $attendance->count();
        $presentPlayers = $attendance->where('status', 'present')->count();
        $absentPlayers = $attendance->where('status', 'absent')->count();
        $lateArrivals = $attendance->where('status', 'late')->count();
        $attendanceRate = $totalPlayers > 0 ? round(($presentPlayers / $totalPlayers) * 100, 1) : 0;

        // Analyze drill performance
        $drillAnalysis = [];
        foreach ($drills as $drill) {
            $drillAnalysis[] = [
                'name' => $drill->name,
                'category' => $drill->category,
                'planned_duration' => $drill->pivot->planned_duration ?? $drill->estimated_duration,
                'actual_duration' => $drill->pivot->actual_duration,
                'effectiveness_rating' => $drill->pivot->drill_rating,
                'goals_achieved' => $drill->pivot->goals_achieved ?? false,
                'notes' => $drill->pivot->notes,
                'player_feedback' => $drill->pivot->player_feedback ?? [],
            ];
        }

        // Calculate session metrics
        $actualDuration = $session->actual_duration ?? $session->planned_duration;
        $intensityAchieved = $session->intensity_achieved ?? $session->intensity_level;
        $overallRating = $session->overall_rating;

        // Identify key highlights and areas for improvement
        $highlights = $this->identifyHighlights($session, $drillAnalysis, $attendance);
        $improvements = $this->identifyImprovements($session, $drillAnalysis, $attendance);

        return [
            'session_info' => [
                'id' => $session->id,
                'title' => $session->title,
                'date' => $session->scheduled_at->format('d.m.Y'),
                'time' => $session->scheduled_at->format('H:i'),
                'venue' => $session->venue,
                'team' => $session->team->name,
                'club' => $session->team->club->name,
                'trainer' => $session->trainer->name ?? 'N/A',
                'assistant_trainer' => $session->assistantTrainer->name ?? null,
                'session_type' => $session->session_type,
                'intensity_level' => $session->intensity_level,
            ],
            'attendance' => [
                'total_players' => $totalPlayers,
                'present_players' => $presentPlayers,
                'absent_players' => $absentPlayers,
                'late_arrivals' => $lateArrivals,
                'attendance_rate' => $attendanceRate,
                'details' => $attendance->map(function ($att) {
                    return [
                        'player_name' => $att->player->user->name ?? 'Unknown',
                        'status' => $att->status,
                        'arrival_time' => $att->arrival_time?->format('H:i'),
                        'notes' => $att->notes,
                    ];
                })->toArray(),
            ],
            'session_metrics' => [
                'planned_duration' => $session->planned_duration,
                'actual_duration' => $actualDuration,
                'duration_difference' => $actualDuration - $session->planned_duration,
                'planned_intensity' => $session->intensity_level,
                'achieved_intensity' => $intensityAchieved,
                'overall_rating' => $overallRating,
                'weather_conditions' => $session->weather_conditions,
                'temperature' => $session->temperature,
            ],
            'drills' => [
                'total_drills' => $drills->count(),
                'completed_drills' => collect($drillAnalysis)->where('goals_achieved', true)->count(),
                'average_effectiveness' => collect($drillAnalysis)->avg('effectiveness_rating'),
                'details' => $drillAnalysis,
            ],
            'focus_areas' => $session->focus_areas ?? [],
            'highlights' => $highlights,
            'improvements' => $improvements,
            'trainer_notes' => $session->trainer_notes,
            'next_session_recommendations' => $this->generateNextSessionRecommendations($session, $drillAnalysis),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Save report data to database
     *
     * @param TrainingSession $session
     * @param array $reportData
     * @return void
     */
    private function saveReportData(TrainingSession $session, array $reportData): void
    {
        // Update session with report data
        $session->update([
            'report_data' => $reportData,
            'attendance_rate' => $reportData['attendance']['attendance_rate'],
            'drill_effectiveness_avg' => $reportData['drills']['average_effectiveness'],
        ]);

        // Create training report record
        \App\Models\TrainingReport::create([
            'training_session_id' => $session->id,
            'team_id' => $session->team_id,
            'report_data' => $reportData,
            'generated_by_user_id' => null, // System generated
            'report_type' => 'session_completion',
            'status' => 'completed',
        ]);
    }

    /**
     * Generate PDF report
     *
     * @param TrainingSession $session
     * @param array $reportData
     * @return void
     */
    private function generatePdfReport(TrainingSession $session, array $reportData): void
    {
        try {
            if (!class_exists('\App\Services\PdfGeneratorService')) {
                Log::info("PDF generator service not available, skipping PDF generation");
                return;
            }

            $pdfService = app(\App\Services\PdfGeneratorService::class);
            $pdfContent = $pdfService->generateTrainingReport($reportData);
            
            // Store PDF file
            $fileName = "training-report-{$session->id}-" . now()->format('Y-m-d') . ".pdf";
            $filePath = "reports/training/{$session->team_id}/{$fileName}";
            
            Storage::disk('local')->put($filePath, $pdfContent);
            
            // Update session with PDF path
            $session->update(['report_pdf_path' => $filePath]);
            
            Log::info("PDF report generated and stored at: {$filePath}");
            
        } catch (\Exception $e) {
            Log::warning("Failed to generate PDF report: " . $e->getMessage());
            // Don't throw exception, PDF generation is optional
        }
    }

    /**
     * Email report to coaches
     *
     * @param TrainingSession $session
     * @param array $reportData
     * @return void
     */
    private function emailReportToCoaches(TrainingSession $session, array $reportData): void
    {
        try {
            $recipients = [];
            
            if ($session->trainer && $session->trainer->email) {
                $recipients[] = $session->trainer;
            }
            
            if ($session->assistantTrainer && $session->assistantTrainer->email) {
                $recipients[] = $session->assistantTrainer;
            }

            foreach ($recipients as $coach) {
                if ($coach->notification_preferences['training_reports'] ?? true) {
                    \Illuminate\Support\Facades\Mail::to($coach->email)->queue(
                        new \App\Mail\TrainingReportMail($session, $reportData, $coach)
                    );
                }
            }
            
            Log::info("Training report emailed to " . count($recipients) . " coaches");
            
        } catch (\Exception $e) {
            Log::warning("Failed to email training report: " . $e->getMessage());
            // Don't throw exception, email is optional
        }
    }

    /**
     * Identify session highlights
     *
     * @param TrainingSession $session
     * @param array $drillAnalysis
     * @param \Illuminate\Database\Eloquent\Collection $attendance
     * @return array
     */
    private function identifyHighlights(TrainingSession $session, array $drillAnalysis, $attendance): array
    {
        $highlights = [];
        
        // High attendance
        $attendanceRate = $attendance->count() > 0 ? 
            ($attendance->where('status', 'present')->count() / $attendance->count()) * 100 : 0;
        
        if ($attendanceRate >= 90) {
            $highlights[] = "Ausgezeichnete Anwesenheit ({$attendanceRate}%)";
        }
        
        // High drill effectiveness
        $avgEffectiveness = collect($drillAnalysis)->avg('effectiveness_rating');
        if ($avgEffectiveness >= 8) {
            $highlights[] = "Sehr effektive Drills (Durchschnitt: " . round($avgEffectiveness, 1) . "/10)";
        }
        
        // Session completed on time
        if ($session->actual_duration && abs($session->actual_duration - $session->planned_duration) <= 5) {
            $highlights[] = "Training pünktlich beendet";
        }
        
        // High overall rating
        if ($session->overall_rating >= 8) {
            $highlights[] = "Hohe Gesamtbewertung ({$session->overall_rating}/10)";
        }
        
        return $highlights;
    }

    /**
     * Identify areas for improvement
     *
     * @param TrainingSession $session
     * @param array $drillAnalysis
     * @param \Illuminate\Database\Eloquent\Collection $attendance
     * @return array
     */
    private function identifyImprovements(TrainingSession $session, array $drillAnalysis, $attendance): array
    {
        $improvements = [];
        
        // Low attendance
        $attendanceRate = $attendance->count() > 0 ? 
            ($attendance->where('status', 'present')->count() / $attendance->count()) * 100 : 0;
            
        if ($attendanceRate < 70) {
            $improvements[] = "Anwesenheit verbessern ({$attendanceRate}%)";
        }
        
        // Low drill effectiveness
        $avgEffectiveness = collect($drillAnalysis)->avg('effectiveness_rating');
        if ($avgEffectiveness < 6) {
            $improvements[] = "Drill-Effektivität steigern (Durchschnitt: " . round($avgEffectiveness, 1) . "/10)";
        }
        
        // Session ran overtime
        if ($session->actual_duration && ($session->actual_duration - $session->planned_duration) > 15) {
            $improvements[] = "Zeitmanagement optimieren (Überziehung: " . ($session->actual_duration - $session->planned_duration) . " Min.)";
        }
        
        // Many late arrivals
        $lateArrivals = $attendance->where('status', 'late')->count();
        if ($lateArrivals > 2) {
            $improvements[] = "Pünktlichkeit fördern ({$lateArrivals} verspätete Ankünfte)";
        }
        
        return $improvements;
    }

    /**
     * Generate recommendations for next session
     *
     * @param TrainingSession $session
     * @param array $drillAnalysis
     * @return array
     */
    private function generateNextSessionRecommendations(TrainingSession $session, array $drillAnalysis): array
    {
        $recommendations = [];
        
        // Recommend repeating effective drills
        $effectiveDrills = collect($drillAnalysis)->where('effectiveness_rating', '>=', 8);
        if ($effectiveDrills->isNotEmpty()) {
            $recommendations[] = "Wiederhole erfolgreiche Drills: " . 
                $effectiveDrills->pluck('name')->take(3)->implode(', ');
        }
        
        // Recommend improving low-rated drills
        $lowRatedDrills = collect($drillAnalysis)->where('effectiveness_rating', '<', 6);
        if ($lowRatedDrills->isNotEmpty()) {
            $recommendations[] = "Überarbeite diese Drills: " . 
                $lowRatedDrills->pluck('name')->take(2)->implode(', ');
        }
        
        // Focus area recommendations based on goals not achieved
        $unachievedGoals = collect($drillAnalysis)->where('goals_achieved', false);
        if ($unachievedGoals->isNotEmpty()) {
            $recommendations[] = "Fokus auf: " . 
                $unachievedGoals->pluck('category')->unique()->take(3)->implode(', ');
        }
        
        return $recommendations;
    }
}