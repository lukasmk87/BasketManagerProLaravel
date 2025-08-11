<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoFile;
use App\Http\Requests\StoreVideoFileRequest;
use App\Http\Requests\UpdateVideoFileRequest;
use App\Http\Resources\VideoFileResource;
use App\Http\Resources\VideoFileCollection;
use App\Services\VideoProcessingService;
use App\Services\AIVideoAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Spatie\ActivityLog\Facades\CauserResolver;
use Exception;

class VideoFileController extends Controller
{
    public function __construct(
        private VideoProcessingService $videoProcessingService,
        private AIVideoAnalysisService $aiVideoAnalysisService
    ) {}

    /**
     * Display a listing of video files.
     */
    public function index(Request $request): VideoFileCollection
    {
        $query = VideoFile::with(['uploader', 'game', 'team', 'annotations']);

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Filter by team
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by video type
        if ($request->has('video_type')) {
            $query->where('video_type', $request->video_type);
        }

        // Filter by processing status
        if ($request->has('processing_status')) {
            $query->where('processing_status', $request->processing_status);
        }

        // Filter by AI analysis status
        if ($request->has('ai_analysis_status')) {
            $query->where('ai_analysis_status', $request->ai_analysis_status);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = [
            'created_at', 'title', 'duration', 'file_size', 
            'processing_status', 'ai_analysis_status', 'views_count'
        ];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $videos = $query->paginate($perPage);

        return new VideoFileCollection($videos);
    }

    /**
     * Store a newly created video file.
     */
    public function store(StoreVideoFileRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Handle file upload
            if ($request->hasFile('video_file')) {
                $file = $request->file('video_file');
                $filePath = $file->store('videos/' . now()->format('Y/m'), 'private');
                
                $validated['file_path'] = $filePath;
                $validated['file_name'] = $file->getClientOriginalName();
                $validated['file_size'] = $file->getSize();
                $validated['mime_type'] = $file->getMimeType();
            }

            // Set default values
            $validated['uploaded_by_user_id'] = Auth::id();
            $validated['processing_status'] = 'pending';
            $validated['ai_analysis_status'] = 'pending';

            $videoFile = VideoFile::create($validated);

            // Queue video processing
            if ($videoFile->file_path) {
                $this->videoProcessingService->queueProcessing($videoFile);
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($videoFile)
                ->withProperties(['video_type' => $videoFile->video_type])
                ->log('Video hochgeladen');

            return response()->json([
                'message' => 'Video erfolgreich hochgeladen und zur Verarbeitung eingereiht.',
                'data' => new VideoFileResource($videoFile)
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hochladen des Videos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified video file.
     */
    public function show(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        // Increment view count
        $videoFile->increment('views_count');

        $videoFile->load([
            'uploader', 'game', 'team', 'tournament', 
            'annotations' => function($query) {
                $query->where('status', 'published')
                      ->orderBy('start_time');
            }
        ]);

        return response()->json([
            'data' => new VideoFileResource($videoFile)
        ]);
    }

    /**
     * Update the specified video file.
     */
    public function update(UpdateVideoFileRequest $request, VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('update', $videoFile);

        try {
            $validated = $request->validated();
            
            $originalData = $videoFile->toArray();
            $videoFile->update($validated);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($videoFile)
                ->withProperties([
                    'old_values' => $originalData,
                    'new_values' => $validated
                ])
                ->log('Video aktualisiert');

            return response()->json([
                'message' => 'Video erfolgreich aktualisiert.',
                'data' => new VideoFileResource($videoFile->fresh())
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Videos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified video file.
     */
    public function destroy(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('delete', $videoFile);

        try {
            // Store info for activity log
            $videoInfo = [
                'title' => $videoFile->title,
                'video_type' => $videoFile->video_type,
                'file_size' => $videoFile->file_size
            ];

            // Delete file from storage
            if ($videoFile->file_path && Storage::exists($videoFile->file_path)) {
                Storage::delete($videoFile->file_path);
            }

            // Delete thumbnail if exists
            if ($videoFile->thumbnail_path && Storage::exists($videoFile->thumbnail_path)) {
                Storage::delete($videoFile->thumbnail_path);
            }

            // Clean up processed video files
            $this->videoProcessingService->cleanupProcessedFiles($videoFile);

            $videoFile->delete();

            activity()
                ->causedBy(Auth::user())
                ->withProperties($videoInfo)
                ->log('Video gelöscht');

            return response()->json([
                'message' => 'Video erfolgreich gelöscht.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Videos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video streaming URL with proper authentication.
     */
    public function stream(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        if ($videoFile->processing_status !== 'completed') {
            return response()->json([
                'message' => 'Video ist noch nicht bereit für Streaming.'
            ], 422);
        }

        try {
            $streamingUrls = $this->videoProcessingService->getStreamingUrls($videoFile);

            return response()->json([
                'data' => [
                    'video_id' => $videoFile->id,
                    'streaming_urls' => $streamingUrls,
                    'duration' => $videoFile->duration,
                    'dimensions' => [
                        'width' => $videoFile->width,
                        'height' => $videoFile->height
                    ],
                    'frame_rate' => $videoFile->frame_rate
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Streaming-URLs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and download video thumbnail.
     */
    public function thumbnail(VideoFile $videoFile, Request $request): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        try {
            $timestamp = $request->get('timestamp', $videoFile->duration / 2);
            $quality = $request->get('quality', 'medium');
            
            $thumbnailUrl = $this->videoProcessingService->generateThumbnail(
                $videoFile, 
                $timestamp, 
                $quality
            );

            return response()->json([
                'data' => [
                    'thumbnail_url' => $thumbnailUrl,
                    'timestamp' => $timestamp,
                    'quality' => $quality
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Generieren des Thumbnails.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start or queue AI analysis for video.
     */
    public function analyzeWithAI(VideoFile $videoFile, Request $request): JsonResponse
    {
        Gate::authorize('update', $videoFile);

        if ($videoFile->processing_status !== 'completed') {
            return response()->json([
                'message' => 'Video muss vollständig verarbeitet sein, bevor AI-Analyse gestartet werden kann.'
            ], 422);
        }

        if ($videoFile->ai_analysis_status === 'in_progress') {
            return response()->json([
                'message' => 'AI-Analyse läuft bereits für dieses Video.'
            ], 422);
        }

        try {
            $analysisOptions = $request->validate([
                'analysis_type' => 'string|in:comprehensive_game_analysis,highlight_analysis,training_analysis,player_performance_analysis',
                'capabilities' => 'array',
                'capabilities.player_detection' => 'array',
                'capabilities.court_detection' => 'array',
                'capabilities.action_recognition' => 'array',
                'capabilities.shot_analysis' => 'array',
            ]);

            $this->aiVideoAnalysisService->queueAnalysis($videoFile, $analysisOptions);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($videoFile)
                ->withProperties(['analysis_options' => $analysisOptions])
                ->log('AI-Analyse gestartet');

            return response()->json([
                'message' => 'AI-Analyse wurde erfolgreich gestartet.',
                'data' => [
                    'video_id' => $videoFile->id,
                    'ai_analysis_status' => 'pending',
                    'estimated_completion' => now()->addMinutes(15)->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Starten der AI-Analyse.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI analysis results and report.
     */
    public function aiAnalysisResults(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        if ($videoFile->ai_analysis_status !== 'completed') {
            return response()->json([
                'message' => 'AI-Analyse ist noch nicht abgeschlossen.',
                'data' => [
                    'status' => $videoFile->ai_analysis_status,
                    'progress' => $this->getAnalysisProgress($videoFile)
                ]
            ], 202);
        }

        try {
            $report = $this->aiVideoAnalysisService->generateAnalysisReport($videoFile);

            return response()->json([
                'data' => $report
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der AI-Analyse-Ergebnisse.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video processing status and progress.
     */
    public function processingStatus(VideoFile $videoFile): JsonResponse
    {
        Gate::authorize('view', $videoFile);

        $status = [
            'video_id' => $videoFile->id,
            'processing_status' => $videoFile->processing_status,
            'ai_analysis_status' => $videoFile->ai_analysis_status,
            'progress' => [
                'upload' => 100,
                'processing' => $this->getProcessingProgress($videoFile),
                'ai_analysis' => $this->getAnalysisProgress($videoFile)
            ],
            'metadata' => [
                'duration' => $videoFile->duration,
                'file_size' => $videoFile->file_size,
                'dimensions' => [
                    'width' => $videoFile->width,
                    'height' => $videoFile->height
                ]
            ],
            'timestamps' => [
                'created_at' => $videoFile->created_at,
                'processing_completed_at' => $videoFile->processing_completed_at,
                'ai_analysis_completed_at' => $videoFile->ai_analysis_completed_at
            ]
        ];

        return response()->json(['data' => $status]);
    }

    /**
     * Bulk operations on multiple videos.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'video_ids' => 'required|array|min:1|max:100',
            'video_ids.*' => 'integer|exists:video_files,id',
            'action' => 'required|string|in:delete,start_ai_analysis,update_visibility',
            'options' => 'array'
        ]);

        $videoIds = $validated['video_ids'];
        $action = $validated['action'];
        $options = $validated['options'] ?? [];

        $videos = VideoFile::whereIn('id', $videoIds)->get();
        
        // Check permissions for all videos
        foreach ($videos as $video) {
            Gate::authorize('update', $video);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($videos as $video) {
            try {
                switch ($action) {
                    case 'delete':
                        $this->deleteVideoWithFiles($video);
                        $results['success']++;
                        break;
                        
                    case 'start_ai_analysis':
                        if ($video->processing_status === 'completed' && 
                            $video->ai_analysis_status !== 'in_progress') {
                            $this->aiVideoAnalysisService->queueAnalysis($video, $options);
                            $results['success']++;
                        } else {
                            $results['errors'][] = "Video {$video->id}: Nicht bereit für AI-Analyse";
                        }
                        break;
                        
                    case 'update_visibility':
                        $video->update([
                            'visibility' => $options['visibility'] ?? 'private'
                        ]);
                        $results['success']++;
                        break;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Video {$video->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "Bulk-Operation abgeschlossen: {$results['success']} erfolgreich, {$results['failed']} fehlgeschlagen.",
            'data' => $results
        ]);
    }

    // Private helper methods

    private function getProcessingProgress(VideoFile $videoFile): int
    {
        switch ($videoFile->processing_status) {
            case 'completed':
                return 100;
            case 'processing':
                return 75;
            case 'pending':
                return 0;
            case 'failed':
                return 0;
            default:
                return 50;
        }
    }

    private function getAnalysisProgress(VideoFile $videoFile): int
    {
        switch ($videoFile->ai_analysis_status) {
            case 'completed':
                return 100;
            case 'in_progress':
                return 60;
            case 'pending':
                return 0;
            case 'failed':
                return 0;
            default:
                return 0;
        }
    }

    private function deleteVideoWithFiles(VideoFile $video): void
    {
        // Delete files from storage
        if ($video->file_path && Storage::exists($video->file_path)) {
            Storage::delete($video->file_path);
        }

        if ($video->thumbnail_path && Storage::exists($video->thumbnail_path)) {
            Storage::delete($video->thumbnail_path);
        }

        // Clean up processed files
        $this->videoProcessingService->cleanupProcessedFiles($video);

        $video->delete();
    }
}