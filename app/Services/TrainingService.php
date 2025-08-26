<?php

namespace App\Services;

use App\Models\TrainingSession;
use App\Models\Drill;
use App\Models\Team;
use App\Models\Player;
use App\Models\TrainingAttendance;
use App\Jobs\GenerateTrainingReport;
use App\Jobs\SendTrainingReminders;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrainingService
{
    public function createTrainingSession(array $data): TrainingSession
    {
        return DB::transaction(function () use ($data) {
            // Extract drills data before creating session
            $drillsData = $data['drills'] ?? [];
            unset($data['drills']);
            
            $session = TrainingSession::create($data);

            // Add selected drills to session
            if (!empty($drillsData)) {
                $this->syncSessionDrills($session, $drillsData);
            } elseif (isset($data['auto_add_drills']) && $data['auto_add_drills']) {
                // Auto-add default drills based on session type and focus
                $this->addDefaultDrills($session);
            }

            // Schedule reminder notifications
            if ($session->notification_settings['send_reminders'] ?? true) {
                $this->scheduleReminders($session);
            }

            return $session;
        });
    }

    public function updateTrainingSession(TrainingSession $session, array $data): TrainingSession
    {
        return DB::transaction(function () use ($session, $data) {
            // Extract drills data before updating session
            $drillsData = $data['drills'] ?? [];
            unset($data['drills']);
            
            $session->update($data);

            // Update session drills if provided
            if (isset($drillsData)) {
                $this->syncSessionDrills($session, $drillsData);
            }

            // Update reminders if date changed
            if ($session->wasChanged('scheduled_at')) {
                $this->rescheduleReminders($session);
            }

            return $session;
        });
    }

    public function addDrillToSession(TrainingSession $session, int $drillId, array $config = []): void
    {
        $drill = Drill::findOrFail($drillId);
        
        // Validate drill compatibility
        if (!$drill->isApplicableForTeam($session->team)) {
            throw new \Exception('Drill ist nicht für dieses Team geeignet.');
        }

        $session->addDrill($drill, $config);
        $drill->incrementUsage();
    }

    public function removeDrillFromSession(TrainingSession $session, int $drillId): void
    {
        $drill = Drill::findOrFail($drillId);
        $session->removeDrill($drill);
    }

    public function reorderSessionDrills(TrainingSession $session, array $drillOrder): void
    {
        DB::transaction(function () use ($session, $drillOrder) {
            foreach ($drillOrder as $index => $drillId) {
                $session->drills()->updateExistingPivot($drillId, [
                    'order_in_session' => $index + 1
                ]);
            }
        });
    }

    public function startTrainingSession(TrainingSession $session): TrainingSession
    {
        if (!$session->canStart()) {
            throw new \Exception('Training kann nicht gestartet werden.');
        }

        $session->start();

        // Mark attendance for present players
        $this->initializeAttendance($session);

        return $session;
    }

    public function completeTrainingSession(TrainingSession $session, array $completionData = []): TrainingSession
    {
        if (!$session->canComplete()) {
            throw new \Exception('Training kann nicht abgeschlossen werden.');
        }

        DB::transaction(function () use ($session, $completionData) {
            $session->complete();

            // Update session with completion data
            if (!empty($completionData)) {
                $session->update($completionData);
            }

            // Generate training report
            GenerateTrainingReport::dispatch($session);
        });

        return $session;
    }

    public function recordDrillPerformance(TrainingSession $session, int $drillId, array $performanceData): void
    {
        $session->drills()->updateExistingPivot($drillId, array_merge($performanceData, [
            'status' => 'completed'
        ]));
    }

    public function markAttendance(TrainingSession $session, int $playerId, string $status, ?string $notes = null): void
    {
        TrainingAttendance::updateOrCreate(
            [
                'training_session_id' => $session->id,
                'player_id' => $playerId,
            ],
            [
                'status' => $status,
                'arrival_time' => $status === 'present' ? now() : null,
                'notes' => $notes,
                'recorded_by_user_id' => auth()->id(),
            ]
        );
    }

    public function bulkMarkAttendance(TrainingSession $session, array $attendanceData): void
    {
        DB::transaction(function () use ($session, $attendanceData) {
            foreach ($attendanceData as $playerId => $data) {
                $this->markAttendance($session, $playerId, $data['status'], $data['notes'] ?? null);
            }
        });
    }

    public function getTrainingPlan(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = TrainingSession::where('team_id', $team->id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['drills', 'attendance.player'])
            ->orderBy('scheduled_at')
            ->get();

        return [
            'sessions' => $sessions,
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'total_training_hours' => $sessions->sum('actual_duration') / 60,
            'average_attendance' => $sessions->avg('attendance_rate'),
            'focus_areas_covered' => $this->analyzeFocusAreas($sessions),
        ];
    }

    public function getPlayerTrainingStats(Player $player, string $season): array
    {
        $sessions = TrainingSession::whereHas('team', function ($query) use ($player) {
                $query->where('id', $player->team_id);
            })
            ->whereHas('attendance', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->with(['attendance' => function ($query) use ($player) {
                $query->where('player_id', $player->id);
            }])
            ->get();

        $attendance = $sessions->pluck('attendance')->flatten();

        return [
            'total_sessions' => $sessions->count(),
            'attended_sessions' => $attendance->where('status', 'present')->count(),
            'absent_sessions' => $attendance->where('status', 'absent')->count(),
            'late_arrivals' => $attendance->where('status', 'late')->count(),
            'attendance_rate' => $sessions->count() > 0 
                ? round(($attendance->where('status', 'present')->count() / $sessions->count()) * 100, 1) 
                : 0,
            'training_hours' => $sessions->sum('actual_duration') / 60,
            'average_session_rating' => $sessions->where('overall_rating', '>', 0)->avg('overall_rating'),
        ];
    }

    public function recommendDrills(Team $team, array $criteria = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Drill::public()
            ->byAgeGroup($team->category)
            ->forPlayerCount($team->activePlayers()->count());

        // Apply criteria filters
        if (isset($criteria['category'])) {
            $query->byCategory($criteria['category']);
        }

        if (isset($criteria['difficulty'])) {
            $query->byDifficulty($criteria['difficulty']);
        }

        if (isset($criteria['duration'])) {
            $query->where('estimated_duration', '<=', $criteria['duration']);
        }

        if (isset($criteria['focus_areas'])) {
            $query->whereJsonContains('tags', $criteria['focus_areas']);
        }

        return $query->highlyRated()
                    ->limit($criteria['limit'] ?? 10)
                    ->get();
    }

    public function generateSessionTemplate(Team $team, string $sessionType, int $duration = 90): array
    {
        $template = [];
        
        switch ($sessionType) {
            case 'training':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 15),
                    'skill_development' => $this->getSkillDrills($team, 45),
                    'conditioning' => $this->getConditioningDrills($team, 20),
                    'cool_down' => $this->getCooldownDrills($team, 10),
                ];
                break;
                
            case 'tactical':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 10),
                    'tactical_drills' => $this->getTacticalDrills($team, 60),
                    'scrimmage' => $this->getScrimmageOptions($team, 15),
                    'review' => $this->getReviewActivities($team, 5),
                ];
                break;
                
            case 'conditioning':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 10),
                    'cardio' => $this->getCardioDrills($team, 30),
                    'strength' => $this->getStrengthDrills($team, 30),
                    'flexibility' => $this->getFlexibilityDrills($team, 20),
                ];
                break;
        }

        return $template;
    }

    public function getDrillRecommendationsForPlayer(Player $player, array $criteria = []): \Illuminate\Database\Eloquent\Collection
    {
        // Get player's recent performance data
        $recentPerformances = $player->trainingPerformances()
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $improvementAreas = [];
        foreach ($recentPerformances as $performance) {
            if ($performance->improvement_areas) {
                $improvementAreas = array_merge($improvementAreas, $performance->improvement_areas);
            }
        }

        // Find drills that target improvement areas
        $query = Drill::public()
            ->byAgeGroup($player->team->category)
            ->where(function ($q) use ($improvementAreas) {
                foreach ($improvementAreas as $area) {
                    $q->orWhereJsonContains('tags', $area['area'])
                      ->orWhere('category', 'like', '%' . $area['area'] . '%');
                }
            });

        return $query->highlyRated()
                    ->limit($criteria['limit'] ?? 5)
                    ->get();
    }

    public function analyzeTeamTrainingEffectiveness(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = TrainingSession::where('team_id', $team->id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->completed()
            ->with(['drills', 'attendance', 'playerPerformances'])
            ->get();

        $totalSessions = $sessions->count();
        $averageRating = $sessions->avg('overall_rating');
        $averageAttendance = $sessions->avg('attendance_rate');

        // Drill effectiveness analysis
        $drillPerformances = [];
        foreach ($sessions as $session) {
            foreach ($session->drills as $drill) {
                $drillId = $drill->id;
                if (!isset($drillPerformances[$drillId])) {
                    $drillPerformances[$drillId] = [
                        'drill' => $drill,
                        'usage_count' => 0,
                        'total_rating' => 0,
                        'goal_achievement' => 0,
                    ];
                }
                
                $drillPerformances[$drillId]['usage_count']++;
                if ($drill->pivot->drill_rating) {
                    $drillPerformances[$drillId]['total_rating'] += $drill->pivot->drill_rating;
                }
                if ($drill->pivot->goals_achieved) {
                    $drillPerformances[$drillId]['goal_achievement']++;
                }
            }
        }

        // Calculate averages
        foreach ($drillPerformances as &$performance) {
            $performance['average_rating'] = $performance['usage_count'] > 0 
                ? $performance['total_rating'] / $performance['usage_count'] 
                : 0;
            $performance['goal_achievement_rate'] = $performance['usage_count'] > 0 
                ? ($performance['goal_achievement'] / $performance['usage_count']) * 100 
                : 0;
        }

        // Sort by effectiveness
        uasort($drillPerformances, function ($a, $b) {
            return $b['average_rating'] <=> $a['average_rating'];
        });

        return [
            'period' => [
                'start' => $startDate->format('d.m.Y'),
                'end' => $endDate->format('d.m.Y'),
            ],
            'overview' => [
                'total_sessions' => $totalSessions,
                'average_rating' => round($averageRating, 1),
                'average_attendance' => round($averageAttendance, 1),
                'total_training_hours' => $sessions->sum('actual_duration') / 60,
            ],
            'most_effective_drills' => array_slice($drillPerformances, 0, 10, true),
            'least_effective_drills' => array_slice(array_reverse($drillPerformances, true), 0, 5, true),
            'focus_areas_covered' => $this->analyzeFocusAreas($sessions),
            'recommendations' => $this->generateTrainingRecommendations($team, $drillPerformances),
        ];
    }

    private function addDefaultDrills(TrainingSession $session): void
    {
        $drills = $this->recommendDrills($session->team, [
            'category' => $session->focus_areas[0] ?? null,
            'duration' => 15,
            'limit' => 5,
        ]);

        foreach ($drills as $index => $drill) {
            $session->addDrill($drill, [
                'order_in_session' => $index + 1,
                'planned_duration' => $drill->estimated_duration,
            ]);
        }
    }

    private function scheduleReminders(TrainingSession $session): void
    {
        // Send reminder 24 hours before
        SendTrainingReminders::dispatch($session)
            ->delay($session->scheduled_at->subDay());
            
        // Send reminder 2 hours before
        SendTrainingReminders::dispatch($session)
            ->delay($session->scheduled_at->subHours(2));
    }

    private function rescheduleReminders(TrainingSession $session): void
    {
        // Cancel existing reminders and schedule new ones
        // Implementation would depend on your queue system
        $this->scheduleReminders($session);
    }

    private function initializeAttendance(TrainingSession $session): void
    {
        $players = $session->team->activePlayers;
        
        foreach ($players as $player) {
            TrainingAttendance::firstOrCreate([
                'training_session_id' => $session->id,
                'player_id' => $player->id,
            ], [
                'status' => 'unknown',
                'recorded_by_user_id' => auth()->id(),
            ]);
        }
    }

    private function analyzeFocusAreas(\Illuminate\Database\Eloquent\Collection $sessions): array
    {
        $focusAreas = [];
        
        foreach ($sessions as $session) {
            if ($session->focus_areas) {
                foreach ($session->focus_areas as $area) {
                    $focusAreas[$area] = ($focusAreas[$area] ?? 0) + 1;
                }
            }
        }
        
        arsort($focusAreas);
        
        return $focusAreas;
    }

    private function getWarmupDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('warm_up')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->popular()
            ->limit(3)
            ->get();
    }

    private function getSkillDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereIn('category', ['ball_handling', 'shooting', 'passing'])
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 3)
            ->highlyRated()
            ->limit(3)
            ->get();
    }

    private function getConditioningDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('conditioning')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->popular()
            ->limit(2)
            ->get();
    }

    private function getCooldownDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('cool_down')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }

    private function getTacticalDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereIn('category', ['team_offense', 'team_defense', 'set_plays'])
            ->byAgeGroup($team->category)
            ->highlyRated()
            ->limit(4)
            ->get();
    }

    private function getScrimmageOptions(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('scrimmage')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }

    private function getReviewActivities(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return collect(); // Placeholder for review activities
    }

    private function getCardioDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'cardio')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 2)
            ->limit(2)
            ->get();
    }

    private function getStrengthDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'strength')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 2)
            ->limit(2)
            ->get();
    }

    private function getFlexibilityDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'flexibility')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }

    private function generateTrainingRecommendations(Team $team, array $drillPerformances): array
    {
        $recommendations = [];

        // Find underperforming areas
        $lowPerformingDrills = array_filter($drillPerformances, function ($perf) {
            return $perf['average_rating'] < 6;
        });

        if (!empty($lowPerformingDrills)) {
            $recommendations[] = [
                'type' => 'improvement',
                'title' => 'Drill-Effektivität verbessern',
                'description' => 'Einige Drills zeigen niedrige Bewertungen. Überprüfen Sie Anweisungen und Anpassungen.',
                'drills' => array_keys($lowPerformingDrills),
            ];
        }

        // Recommend high-performing drills for more use
        $highPerformingDrills = array_filter($drillPerformances, function ($perf) {
            return $perf['average_rating'] >= 8 && $perf['usage_count'] < 5;
        });

        if (!empty($highPerformingDrills)) {
            $recommendations[] = [
                'type' => 'expand',
                'title' => 'Erfolgreiche Drills häufiger nutzen',
                'description' => 'Diese Drills zeigen hohe Bewertungen und könnten häufiger eingesetzt werden.',
                'drills' => array_keys($highPerformingDrills),
            ];
        }

        return $recommendations;
    }

    /**
     * Sync drills with training session
     *
     * @param TrainingSession $session
     * @param array $drillsData
     * @return void
     */
    private function syncSessionDrills(TrainingSession $session, array $drillsData): void
    {
        // Clear existing drills
        $session->drills()->detach();
        
        // Add new drills
        foreach ($drillsData as $drillData) {
            $session->drills()->attach($drillData['drill_id'], [
                'order_in_session' => $drillData['order_in_session'] ?? 1,
                'planned_duration' => $drillData['planned_duration'] ?? 10,
                'specific_instructions' => $drillData['specific_instructions'] ?? null,
                'participants_count' => $drillData['participants_count'] ?? null,
                'status' => $drillData['status'] ?? 'planned',
            ]);
        }
    }
}