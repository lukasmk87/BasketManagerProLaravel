<?php

namespace App\Observers;

use App\Models\VideoFile;
use App\Services\ClubUsageTrackingService;

/**
 * SEC-008: Observer for automatic storage tracking on VideoFile create/delete.
 */
class VideoFileObserver
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle the VideoFile "created" event.
     * Tracks storage usage when a video is uploaded.
     */
    public function created(VideoFile $video): void
    {
        $club = $video->team?->club;

        if ($club && $video->file_size > 0) {
            // Convert bytes to GB
            $sizeGB = $video->file_size / (1024 * 1024 * 1024);
            $this->usageTracker->trackResource($club, 'max_storage_gb', $sizeGB);

            activity()
                ->performedOn($video)
                ->causedBy(auth()->user())
                ->withProperties(['file_size_gb' => round($sizeGB, 3)])
                ->log('Video file uploaded');
        }
    }

    /**
     * Handle the VideoFile "deleted" event.
     * Untracks storage usage when a video is deleted.
     */
    public function deleted(VideoFile $video): void
    {
        $club = $video->team?->club;

        if ($club && $video->file_size > 0) {
            // Convert bytes to GB
            $sizeGB = $video->file_size / (1024 * 1024 * 1024);
            $this->usageTracker->untrackResource($club, 'max_storage_gb', $sizeGB);

            activity()
                ->performedOn($video)
                ->causedBy(auth()->user())
                ->withProperties(['file_size_gb' => round($sizeGB, 3)])
                ->log('Video file deleted');
        }
    }

    /**
     * Handle the VideoFile "restored" event.
     * Re-tracks storage if a soft-deleted video is restored.
     */
    public function restored(VideoFile $video): void
    {
        $club = $video->team?->club;

        if ($club && $video->file_size > 0) {
            $sizeGB = $video->file_size / (1024 * 1024 * 1024);
            $this->usageTracker->trackResource($club, 'max_storage_gb', $sizeGB);

            activity()
                ->performedOn($video)
                ->causedBy(auth()->user())
                ->log('Video file restored');
        }
    }

    /**
     * Handle the VideoFile "force deleted" event.
     */
    public function forceDeleted(VideoFile $video): void
    {
        activity()
            ->log('Video file permanently deleted: ' . $video->original_filename);
    }
}
