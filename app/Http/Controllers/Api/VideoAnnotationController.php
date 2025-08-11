<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoAnnotation;
use App\Models\VideoFile;
use App\Http\Requests\StoreVideoAnnotationRequest;
use App\Http\Requests\UpdateVideoAnnotationRequest;
use App\Http\Resources\VideoAnnotationResource;
use App\Http\Resources\VideoAnnotationCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Exception;

class VideoAnnotationController extends Controller
{
    /**
     * Display a listing of video annotations.
     */
    public function index(Request $request, VideoFile $videoFile): VideoAnnotationCollection
    {
        Gate::authorize('view', $videoFile);

        $query = $videoFile->annotations()
                          ->with(['creator', 'videoFile'])
                          ->where(function($q) {
                              $q->where('status', 'published')
                                ->orWhere('created_by_user_id', Auth::id());
                          });

        // Filter by annotation type
        if ($request->has('annotation_type')) {
            $query->where('annotation_type', $request->annotation_type);
        }

        // Filter by play type
        if ($request->has('play_type')) {
            $query->where('play_type', $request->play_type);
        }

        // Filter by outcome
        if ($request->has('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        // Filter by time range
        if ($request->has('start_time') && $request->has('end_time')) {
            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $query->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($subQ) use ($startTime, $endTime) {
                      $subQ->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                  });
            });
        }

        // Filter AI vs Manual annotations
        if ($request->has('is_ai_generated')) {
            $query->where('is_ai_generated', $request->boolean('is_ai_generated'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Sort by time or creation date
        $sortBy = $request->get('sort_by', 'start_time');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        $allowedSortFields = ['start_time', 'end_time', 'created_at', 'ai_confidence'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min($request->get('per_page', 50), 200);
        $annotations = $query->paginate($perPage);

        return new VideoAnnotationCollection($annotations);
    }

    /**
     * Store a newly created video annotation.
     */
    public function store(StoreVideoAnnotationRequest $request, VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        try {
            $validated = $request->validated();
            $validated['video_file_id'] = $videoFile->id;
            $validated['created_by_user_id'] = Auth::id();
            $validated['is_ai_generated'] = false;
            $validated['status'] = 'published';

            // Validate time bounds
            if ($validated['end_time'] <= $validated['start_time']) {
                return response()->json([
                    'message' => 'Endzeit muss nach der Startzeit liegen.'
                ], 422);
            }

            if ($validated['end_time'] > $videoFile->duration) {
                return response()->json([
                    'message' => 'Annotation kann nicht länger als das Video sein.'
                ], 422);
            }

            // Check for overlapping annotations (optional warning)
            $overlapping = VideoAnnotation::where('video_file_id', $videoFile->id)
                ->where('status', 'published')
                ->where(function($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function($subQuery) use ($validated) {
                              $subQuery->where('start_time', '<=', $validated['start_time'])
                                       ->where('end_time', '>=', $validated['end_time']);
                          });
                })
                ->exists();

            $annotation = VideoAnnotation::create($validated);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($annotation)
                ->withProperties([
                    'video_id' => $videoFile->id,
                    'annotation_type' => $annotation->annotation_type,
                    'time_range' => [$annotation->start_time, $annotation->end_time]
                ])
                ->log('Video-Annotation erstellt');

            $response = [
                'message' => 'Annotation erfolgreich erstellt.',
                'data' => new VideoAnnotationResource($annotation)
            ];

            if ($overlapping) {
                $response['warning'] = 'Es gibt überlappende Annotationen in diesem Zeitbereich.';
            }

            return response()->json($response, 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen der Annotation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified video annotation.
     */
    public function show(VideoFile $videoFile, VideoAnnotation $annotation): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        if ($annotation->video_file_id !== $videoFile->id) {
            return response()->json([
                'message' => 'Annotation gehört nicht zu diesem Video.'
            ], 404);
        }

        // Check if user can see this annotation
        if ($annotation->status !== 'published' && $annotation->created_by_user_id !== Auth::id()) {
            Gate::authorize('update', $annotation);
        }

        $annotation->load(['creator', 'videoFile']);

        return response()->json([
            'data' => new VideoAnnotationResource($annotation)
        ]);
    }

    /**
     * Update the specified video annotation.
     */
    public function update(UpdateVideoAnnotationRequest $request, VideoFile $videoFile, VideoAnnotation $annotation): JsonResponse
    {
        Gate::authorize('view', $videoFile);
        Gate::authorize('update', $annotation);

        if ($annotation->video_file_id !== $videoFile->id) {
            return response()->json([
                'message' => 'Annotation gehört nicht zu diesem Video.'
            ], 404);
        }

        try {
            $validated = $request->validated();
            
            // Validate time bounds if they're being updated
            if (isset($validated['start_time']) || isset($validated['end_time'])) {
                $startTime = $validated['start_time'] ?? $annotation->start_time;
                $endTime = $validated['end_time'] ?? $annotation->end_time;
                
                if ($endTime <= $startTime) {
                    return response()->json([
                        'message' => 'Endzeit muss nach der Startzeit liegen.'
                    ], 422);
                }

                if ($endTime > $videoFile->duration) {
                    return response()->json([
                        'message' => 'Annotation kann nicht länger als das Video sein.'
                    ], 422);
                }
            }

            $originalData = $annotation->toArray();
            $annotation->update($validated);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($annotation)
                ->withProperties([
                    'old_values' => $originalData,
                    'new_values' => $validated
                ])
                ->log('Video-Annotation aktualisiert');

            return response()->json([
                'message' => 'Annotation erfolgreich aktualisiert.',
                'data' => new VideoAnnotationResource($annotation->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Annotation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified video annotation.
     */
    public function destroy(VideoFile $videoFile, VideoAnnotation $annotation): JsonResponse
    {
        Gate::authorize('view', $videoFile);
        Gate::authorize('delete', $annotation);

        if ($annotation->video_file_id !== $videoFile->id) {
            return response()->json([
                'message' => 'Annotation gehört nicht zu diesem Video.'
            ], 404);
        }

        try {
            $annotationInfo = [
                'title' => $annotation->title,
                'annotation_type' => $annotation->annotation_type,
                'time_range' => [$annotation->start_time, $annotation->end_time]
            ];

            $annotation->delete();

            activity()
                ->causedBy(Auth::user())
                ->withProperties($annotationInfo)
                ->log('Video-Annotation gelöscht');

            return response()->json([
                'message' => 'Annotation erfolgreich gelöscht.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen der Annotation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get annotation statistics for a video.
     */
    public function statistics(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        $stats = DB::select("
            SELECT 
                annotation_type,
                COUNT(*) as count,
                AVG(CASE WHEN ai_confidence IS NOT NULL THEN ai_confidence END) as avg_ai_confidence,
                SUM(CASE WHEN is_ai_generated = 1 THEN 1 ELSE 0 END) as ai_generated_count,
                SUM(CASE WHEN is_ai_generated = 0 THEN 1 ELSE 0 END) as manual_count
            FROM video_annotations 
            WHERE video_file_id = ? AND status = 'published'
            GROUP BY annotation_type
        ", [$videoFile->id]);

        $playTypeStats = DB::select("
            SELECT 
                play_type,
                COUNT(*) as count,
                SUM(CASE WHEN outcome = 'successful' THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN outcome = 'unsuccessful' THEN 1 ELSE 0 END) as unsuccessful_count,
                SUM(points_scored) as total_points
            FROM video_annotations 
            WHERE video_file_id = ? AND status = 'published' AND play_type IS NOT NULL
            GROUP BY play_type
        ", [$videoFile->id]);

        $timelineData = DB::select("
            SELECT 
                FLOOR(start_time / 60) as minute_interval,
                COUNT(*) as annotation_count
            FROM video_annotations 
            WHERE video_file_id = ? AND status = 'published'
            GROUP BY FLOOR(start_time / 60)
            ORDER BY minute_interval
        ", [$videoFile->id]);

        return response()->json([
            'data' => [
                'video_id' => $videoFile->id,
                'total_annotations' => $videoFile->annotations()->where('status', 'published')->count(),
                'by_type' => $stats,
                'by_play_type' => $playTypeStats,
                'timeline_distribution' => $timelineData,
                'coverage_percentage' => $this->calculateCoveragePercentage($videoFile),
                'average_annotation_length' => $this->calculateAverageLength($videoFile)
            ]
        ]);
    }

    /**
     * Export annotations in various formats.
     */
    public function export(VideoFile $videoFile, Request $request): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        $format = $request->get('format', 'json');
        $includeAI = $request->boolean('include_ai', true);
        
        $query = $videoFile->annotations()
                          ->where('status', 'published')
                          ->orderBy('start_time');

        if (!$includeAI) {
            $query->where('is_ai_generated', false);
        }

        $annotations = $query->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($annotations, $videoFile);
            case 'srt':
                return $this->exportToSrt($annotations, $videoFile);
            case 'json':
            default:
                return response()->json([
                    'data' => [
                        'video_id' => $videoFile->id,
                        'video_title' => $videoFile->title,
                        'export_date' => now()->toISOString(),
                        'annotations' => VideoAnnotationResource::collection($annotations)
                    ]
                ]);
        }
    }

    /**
     * Bulk operations on annotations.
     */
    public function bulkAction(Request $request, VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        $validated = $request->validate([
            'annotation_ids' => 'required|array|min:1|max:100',
            'annotation_ids.*' => 'integer|exists:video_annotations,id',
            'action' => 'required|string|in:delete,publish,unpublish,approve,reject',
            'options' => 'array'
        ]);

        $annotationIds = $validated['annotation_ids'];
        $action = $validated['action'];

        $annotations = VideoAnnotation::whereIn('id', $annotationIds)
                                     ->where('video_file_id', $videoFile->id)
                                     ->get();

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($annotations as $annotation) {
            try {
                // Check permissions for each annotation
                if (in_array($action, ['delete', 'approve', 'reject'])) {
                    Gate::authorize('delete', $annotation);
                } else {
                    Gate::authorize('update', $annotation);
                }

                switch ($action) {
                    case 'delete':
                        $annotation->delete();
                        $results['success']++;
                        break;
                        
                    case 'publish':
                        $annotation->update(['status' => 'published']);
                        $results['success']++;
                        break;
                        
                    case 'unpublish':
                        $annotation->update(['status' => 'draft']);
                        $results['success']++;
                        break;
                        
                    case 'approve':
                        $annotation->update(['status' => 'published']);
                        $results['success']++;
                        break;
                        
                    case 'reject':
                        $annotation->update(['status' => 'rejected']);
                        $results['success']++;
                        break;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Annotation {$annotation->id}: " . $e->getMessage();
            }
        }

        activity()
            ->causedBy(Auth::user())
            ->performedOn($videoFile)
            ->withProperties([
                'action' => $action,
                'annotation_count' => count($annotationIds),
                'results' => $results
            ])
            ->log('Bulk-Operation auf Video-Annotationen');

        return response()->json([
            'message' => "Bulk-Operation abgeschlossen: {$results['success']} erfolgreich, {$results['failed']} fehlgeschlagen.",
            'data' => $results
        ]);
    }

    /**
     * Get court position heatmap data from annotations.
     */
    public function heatmap(VideoFile $videoFile, Request $request): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        $playType = $request->get('play_type');
        $outcome = $request->get('outcome');

        $query = $videoFile->annotations()
                          ->where('status', 'published')
                          ->whereNotNull('court_position_x')
                          ->whereNotNull('court_position_y');

        if ($playType) {
            $query->where('play_type', $playType);
        }

        if ($outcome) {
            $query->where('outcome', $outcome);
        }

        $annotations = $query->get();

        $heatmapData = $annotations->groupBy(function($annotation) {
            // Group by 20x20 pixel grid sections
            $gridX = floor($annotation->court_position_x / 20) * 20;
            $gridY = floor($annotation->court_position_y / 20) * 20;
            return "{$gridX},{$gridY}";
        })->map(function($group, $position) {
            list($x, $y) = explode(',', $position);
            return [
                'x' => (int)$x,
                'y' => (int)$y,
                'intensity' => count($group),
                'annotations' => $group->count()
            ];
        })->values();

        return response()->json([
            'data' => [
                'video_id' => $videoFile->id,
                'play_type' => $playType,
                'outcome' => $outcome,
                'heatmap_points' => $heatmapData,
                'total_points' => $annotations->count()
            ]
        ]);
    }

    // Private helper methods

    private function calculateCoveragePercentage(VideoFile $videoFile): float
    {
        if (!$videoFile->duration) {
            return 0;
        }

        $totalAnnotatedTime = DB::selectOne("
            SELECT SUM(end_time - start_time) as total_time
            FROM video_annotations 
            WHERE video_file_id = ? AND status = 'published'
        ", [$videoFile->id])->total_time ?? 0;

        return round(($totalAnnotatedTime / $videoFile->duration) * 100, 2);
    }

    private function calculateAverageLength(VideoFile $videoFile): float
    {
        $avgLength = DB::selectOne("
            SELECT AVG(end_time - start_time) as avg_length
            FROM video_annotations 
            WHERE video_file_id = ? AND status = 'published'
        ", [$videoFile->id])->avg_length ?? 0;

        return round($avgLength, 2);
    }

    private function exportToCsv($annotations, VideoFile $videoFile): JsonResponse
    {
        $csvData = "Start Time,End Time,Title,Description,Type,Play Type,Outcome,Points,AI Generated,Confidence\n";
        
        foreach ($annotations as $annotation) {
            $csvData .= implode(',', [
                $annotation->start_time,
                $annotation->end_time,
                '"' . str_replace('"', '""', $annotation->title) . '"',
                '"' . str_replace('"', '""', $annotation->description ?? '') . '"',
                $annotation->annotation_type,
                $annotation->play_type ?? '',
                $annotation->outcome ?? '',
                $annotation->points_scored ?? 0,
                $annotation->is_ai_generated ? 'Yes' : 'No',
                $annotation->ai_confidence ?? ''
            ]) . "\n";
        }

        return response()->json([
            'data' => [
                'format' => 'csv',
                'filename' => "annotations_{$videoFile->id}_" . now()->format('Y-m-d_H-i-s') . ".csv",
                'content' => $csvData
            ]
        ]);
    }

    private function exportToSrt($annotations, VideoFile $videoFile): JsonResponse
    {
        $srtContent = '';
        $counter = 1;

        foreach ($annotations as $annotation) {
            $startTime = $this->formatSrtTime($annotation->start_time);
            $endTime = $this->formatSrtTime($annotation->end_time);
            
            $srtContent .= "{$counter}\n";
            $srtContent .= "{$startTime} --> {$endTime}\n";
            $srtContent .= $annotation->title . "\n";
            if ($annotation->description) {
                $srtContent .= $annotation->description . "\n";
            }
            $srtContent .= "\n";
            
            $counter++;
        }

        return response()->json([
            'data' => [
                'format' => 'srt',
                'filename' => "annotations_{$videoFile->id}_" . now()->format('Y-m-d_H-i-s') . ".srt",
                'content' => $srtContent
            ]
        ]);
    }

    private function formatSrtTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d,000', $hours, $minutes, $secs);
    }
}