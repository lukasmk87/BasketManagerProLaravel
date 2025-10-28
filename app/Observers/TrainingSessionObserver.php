<?php

namespace App\Observers;

use App\Models\TrainingSession;
use App\Services\ClubUsageTrackingService;

class TrainingSessionObserver
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle the TrainingSession "created" event.
     */
    public function created(TrainingSession $trainingSession): void
    {
        // Log training session creation
        activity()
            ->performedOn($trainingSession)
            ->causedBy(auth()->user())
            ->log('Training session created');

        // Track usage for club (only current month sessions count toward limit)
        if ($trainingSession->scheduled_at && $trainingSession->scheduled_at->isSameMonth(now())) {
            $this->trackTrainingSessionUsage($trainingSession, 'track');
        }
    }

    /**
     * Handle the TrainingSession "updated" event.
     */
    public function updated(TrainingSession $trainingSession): void
    {
        activity()
            ->performedOn($trainingSession)
            ->causedBy(auth()->user())
            ->log('Training session updated');

        // If scheduled_at changed and crosses month boundary, adjust tracking
        if ($trainingSession->isDirty('scheduled_at')) {
            $originalDate = $trainingSession->getOriginal('scheduled_at');
            $newDate = $trainingSession->scheduled_at;

            $originalInCurrentMonth = $originalDate && \Carbon\Carbon::parse($originalDate)->isSameMonth(now());
            $newInCurrentMonth = $newDate && $newDate->isSameMonth(now());

            // If moved OUT of current month, untrack
            if ($originalInCurrentMonth && !$newInCurrentMonth) {
                $this->trackTrainingSessionUsage($trainingSession, 'untrack');
            }

            // If moved INTO current month, track
            if (!$originalInCurrentMonth && $newInCurrentMonth) {
                $this->trackTrainingSessionUsage($trainingSession, 'track');
            }
        }
    }

    /**
     * Handle the TrainingSession "deleted" event.
     */
    public function deleted(TrainingSession $trainingSession): void
    {
        activity()
            ->performedOn($trainingSession)
            ->causedBy(auth()->user())
            ->log('Training session deleted');

        // Untrack usage if session was in current month
        if ($trainingSession->scheduled_at && $trainingSession->scheduled_at->isSameMonth(now())) {
            $this->trackTrainingSessionUsage($trainingSession, 'untrack');
        }
    }

    /**
     * Handle the TrainingSession "restored" event.
     */
    public function restored(TrainingSession $trainingSession): void
    {
        activity()
            ->performedOn($trainingSession)
            ->causedBy(auth()->user())
            ->log('Training session restored');

        // Re-track usage if session is in current month
        if ($trainingSession->scheduled_at && $trainingSession->scheduled_at->isSameMonth(now())) {
            $this->trackTrainingSessionUsage($trainingSession, 'track');
        }
    }

    /**
     * Handle the TrainingSession "force deleted" event.
     */
    public function forceDeleted(TrainingSession $trainingSession): void
    {
        activity()
            ->log('Training session permanently deleted: ' . $trainingSession->title);
    }

    /**
     * Track or untrack training session usage for the club.
     *
     * @param TrainingSession $trainingSession
     * @param string $action 'track' or 'untrack'
     * @return void
     */
    private function trackTrainingSessionUsage(TrainingSession $trainingSession, string $action): void
    {
        // TrainingSession belongs to Team, Team belongs to Club
        $team = $trainingSession->team;

        if (!$team || !$team->club) {
            return;
        }

        $club = $team->club;

        if ($action === 'track') {
            $this->usageTracker->trackResource($club, 'max_training_sessions_per_month', 1);
        } else {
            $this->usageTracker->untrackResource($club, 'max_training_sessions_per_month', 1);
        }
    }
}
