<?php

namespace App\Services\TacticBoard;

use App\Models\Play;
use App\Models\Playbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PlayExportService
{
    /**
     * Export a play as PNG (base64 string).
     * Note: The actual PNG generation happens client-side using Konva's toDataURL().
     * This method handles the storage and management of exported images.
     */
    public function exportAsPng(Play $play, string $base64Data, int $width = 800): string
    {
        // Remove the data URL prefix if present
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $imageData = base64_decode($imageData);

        $filename = "plays/{$play->uuid}_{$width}.png";
        Storage::disk('public')->put($filename, $imageData);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Export a play as PDF.
     */
    public function exportPlayAsPdf(Play $play, ?string $thumbnailBase64 = null): Response
    {
        $data = [
            'play' => $play,
            'playData' => $play->play_data,
            'animationData' => $play->animation_data,
            'thumbnail' => $thumbnailBase64,
            'createdBy' => $play->createdBy,
            'exportDate' => now()->format('d.m.Y H:i'),
        ];

        $pdf = Pdf::loadView('exports.play-pdf', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("play_{$play->uuid}.pdf");
    }

    /**
     * Export a playbook as PDF with all plays.
     */
    public function exportPlaybookAsPdf(Playbook $playbook, array $thumbnails = []): Response
    {
        $plays = $playbook->plays()->orderByPivot('order')->get();

        $data = [
            'playbook' => $playbook,
            'plays' => $plays,
            'thumbnails' => $thumbnails, // Array of base64 thumbnails keyed by play ID
            'team' => $playbook->team,
            'createdBy' => $playbook->createdBy,
            'exportDate' => now()->format('d.m.Y H:i'),
            'statistics' => [
                'total_plays' => $plays->count(),
                'plays_by_category' => $plays->groupBy('category')->map->count(),
            ],
        ];

        $pdf = Pdf::loadView('exports.playbook-pdf', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("playbook_{$playbook->uuid}.pdf");
    }

    /**
     * Generate a thumbnail for a play and save it.
     */
    public function saveThumbnail(Play $play, string $base64Data): string
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $imageData = base64_decode($imageData);

        $filename = "plays/thumbnails/{$play->uuid}.png";
        Storage::disk('public')->put($filename, $imageData);

        $thumbnailPath = Storage::disk('public')->url($filename);
        $play->update(['thumbnail_path' => $thumbnailPath]);

        return $thumbnailPath;
    }

    /**
     * Delete exported files for a play.
     */
    public function deleteExportedFiles(Play $play): void
    {
        $patterns = [
            "plays/{$play->uuid}_*.png",
            "plays/thumbnails/{$play->uuid}.png",
        ];

        foreach ($patterns as $pattern) {
            $files = Storage::disk('public')->files(dirname($pattern));
            foreach ($files as $file) {
                if (fnmatch(basename($pattern), basename($file))) {
                    Storage::disk('public')->delete($file);
                }
            }
        }
    }
}
