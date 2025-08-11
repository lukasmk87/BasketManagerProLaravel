<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Video file upload
            'video_file' => [
                'required_without:file_path',
                'file',
                'mimes:mp4,avi,mov,wmv,flv,webm,mkv,m4v',
                'max:2048000', // 2GB max file size
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $duration = $this->getVideoDuration($value);
                        if ($duration && $duration > 7200) { // 2 hours max
                            $fail('Das Video darf nicht länger als 2 Stunden sein.');
                        }
                    }
                },
            ],
            
            // Alternative: direct file path for server-side uploads
            'file_path' => 'required_without:video_file|string|max:500',
            
            // Basic metadata
            'title' => 'required|string|max:255|min:3',
            'description' => 'nullable|string|max:2000',
            
            // Video classification
            'video_type' => [
                'required',
                Rule::in([
                    'full_game',
                    'game_highlights', 
                    'training_session',
                    'drill_demo',
                    'player_analysis',
                    'tactical_analysis',
                    'referee_footage',
                    'fan_footage'
                ])
            ],
            
            'visibility' => [
                'sometimes',
                Rule::in(['public', 'team_only', 'private'])
            ],
            
            // Basketball-specific metadata
            'game_id' => 'nullable|integer|exists:games,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'tournament_id' => 'nullable|integer|exists:tournaments,id',
            'season_id' => 'nullable|integer|exists:seasons,id',
            
            // Game context
            'game_period' => 'nullable|integer|min:1|max:10',
            'game_clock_start' => 'nullable|string|regex:/^\d{1,2}:\d{2}$/',
            'game_clock_end' => 'nullable|string|regex:/^\d{1,2}:\d{2}$/',
            'court_side' => [
                'nullable',
                Rule::in(['home', 'away', 'neutral', 'baseline', 'sideline'])
            ],
            'camera_angle' => [
                'nullable',
                Rule::in(['overhead', 'sideline', 'baseline', 'corner', 'behind_basket', 'courtside'])
            ],
            'recording_quality' => [
                'nullable',
                Rule::in(['480p', '720p', '1080p', '4k', 'unknown'])
            ],
            
            // Tags and keywords
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
            'keywords' => 'nullable|string|max:500',
            
            // Processing options
            'ai_analysis_enabled' => 'boolean',
            'auto_generate_highlights' => 'boolean',
            'extract_statistics' => 'boolean',
            'generate_thumbnails' => 'boolean',
            
            // Advanced options
            'poster_frame_time' => 'nullable|integer|min:0',
            'custom_metadata' => 'nullable|array',
            'custom_metadata.*' => 'string|max:255',
            
            // Privacy and access
            'allow_downloads' => 'boolean',
            'allow_sharing' => 'boolean',
            'require_login' => 'boolean',
            'password_protected' => 'boolean',
            'access_password' => 'required_if:password_protected,true|nullable|string|min:6|max:20',
            
            // Scheduling
            'scheduled_publish_at' => 'nullable|date|after:now',
            'scheduled_archive_at' => 'nullable|date|after:scheduled_publish_at',
            
            // Content warnings
            'content_warnings' => 'nullable|array',
            'content_warnings.*' => Rule::in([
                'language', 
                'violence', 
                'disputed_calls', 
                'player_injury',
                'crowd_behavior'
            ]),
            
            // External references
            'external_video_id' => 'nullable|string|max:100',
            'source_platform' => [
                'nullable',
                Rule::in(['youtube', 'vimeo', 'twitch', 'facebook', 'instagram', 'tiktok', 'custom'])
            ],
            'original_url' => 'nullable|url|max:500',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'video_file.required_without' => 'Eine Videodatei oder ein Dateipfad ist erforderlich.',
            'video_file.file' => 'Die hochgeladene Datei muss eine gültige Videodatei sein.',
            'video_file.mimes' => 'Das Video muss eine der folgenden Dateierweiterungen haben: mp4, avi, mov, wmv, flv, webm, mkv, m4v.',
            'video_file.max' => 'Die Videodatei darf nicht größer als 2GB sein.',
            
            'title.required' => 'Ein Titel ist erforderlich.',
            'title.min' => 'Der Titel muss mindestens 3 Zeichen lang sein.',
            'title.max' => 'Der Titel darf nicht länger als 255 Zeichen sein.',
            
            'description.max' => 'Die Beschreibung darf nicht länger als 2000 Zeichen sein.',
            
            'video_type.required' => 'Der Video-Typ muss ausgewählt werden.',
            'video_type.in' => 'Ungültiger Video-Typ ausgewählt.',
            
            'game_id.exists' => 'Das ausgewählte Spiel existiert nicht.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'tournament_id.exists' => 'Das ausgewählte Turnier existiert nicht.',
            'season_id.exists' => 'Die ausgewählte Saison existiert nicht.',
            
            'game_period.integer' => 'Die Spielperiode muss eine Zahl sein.',
            'game_period.min' => 'Die Spielperiode muss mindestens 1 sein.',
            'game_period.max' => 'Die Spielperiode darf nicht größer als 10 sein.',
            
            'game_clock_start.regex' => 'Die Startzeit muss im Format MM:SS angegeben werden.',
            'game_clock_end.regex' => 'Die Endzeit muss im Format MM:SS angegeben werden.',
            
            'tags.array' => 'Tags müssen als Array übergeben werden.',
            'tags.max' => 'Es können maximal 10 Tags vergeben werden.',
            'tags.*.max' => 'Jeder Tag darf maximal 50 Zeichen lang sein.',
            
            'keywords.max' => 'Keywords dürfen nicht länger als 500 Zeichen sein.',
            
            'access_password.required_if' => 'Ein Passwort ist erforderlich, wenn das Video passwortgeschützt ist.',
            'access_password.min' => 'Das Passwort muss mindestens 6 Zeichen lang sein.',
            
            'scheduled_publish_at.after' => 'Das Veröffentlichungsdatum muss in der Zukunft liegen.',
            'scheduled_archive_at.after' => 'Das Archivierungsdatum muss nach dem Veröffentlichungsdatum liegen.',
            
            'original_url.url' => 'Die ursprüngliche URL muss eine gültige URL sein.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'video_file' => 'Videodatei',
            'file_path' => 'Dateipfad',
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'video_type' => 'Video-Typ',
            'visibility' => 'Sichtbarkeit',
            'game_id' => 'Spiel',
            'team_id' => 'Team',
            'tournament_id' => 'Turnier',
            'season_id' => 'Saison',
            'game_period' => 'Spielperiode',
            'game_clock_start' => 'Startzeit',
            'game_clock_end' => 'Endzeit',
            'court_side' => 'Spielfeldseite',
            'camera_angle' => 'Kamerawinkel',
            'recording_quality' => 'Aufnahmequalität',
            'tags' => 'Tags',
            'keywords' => 'Keywords',
            'ai_analysis_enabled' => 'AI-Analyse aktiviert',
            'auto_generate_highlights' => 'Automatische Highlights',
            'extract_statistics' => 'Statistiken extrahieren',
            'generate_thumbnails' => 'Thumbnails generieren',
            'poster_frame_time' => 'Poster-Frame Zeit',
            'custom_metadata' => 'Benutzerdefinierte Metadaten',
            'allow_downloads' => 'Downloads erlauben',
            'allow_sharing' => 'Teilen erlauben',
            'require_login' => 'Anmeldung erforderlich',
            'password_protected' => 'Passwortgeschützt',
            'access_password' => 'Zugangspasswort',
            'scheduled_publish_at' => 'Geplante Veröffentlichung',
            'scheduled_archive_at' => 'Geplante Archivierung',
            'content_warnings' => 'Inhaltswarnungen',
            'external_video_id' => 'Externe Video-ID',
            'source_platform' => 'Quellplattform',
            'original_url' => 'Original-URL',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('visibility')) {
            $this->merge(['visibility' => 'private']);
        }

        if (!$this->has('ai_analysis_enabled')) {
            $this->merge(['ai_analysis_enabled' => true]);
        }

        if (!$this->has('auto_generate_highlights')) {
            $this->merge(['auto_generate_highlights' => true]);
        }

        if (!$this->has('extract_statistics')) {
            $this->merge(['extract_statistics' => true]);
        }

        if (!$this->has('generate_thumbnails')) {
            $this->merge(['generate_thumbnails' => true]);
        }

        // Clean and process tags
        if ($this->has('tags') && is_array($this->tags)) {
            $cleanTags = array_map('trim', $this->tags);
            $cleanTags = array_filter($cleanTags); // Remove empty tags
            $cleanTags = array_unique($cleanTags); // Remove duplicates
            $this->merge(['tags' => array_values($cleanTags)]);
        }

        // Process keywords
        if ($this->has('keywords') && is_string($this->keywords)) {
            $keywords = trim($this->keywords);
            $this->merge(['keywords' => $keywords]);
        }

        // Convert boolean strings to actual booleans
        $booleanFields = [
            'ai_analysis_enabled',
            'auto_generate_highlights', 
            'extract_statistics',
            'generate_thumbnails',
            'allow_downloads',
            'allow_sharing',
            'require_login',
            'password_protected'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_string($value)) {
                    $this->merge([$field => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
                }
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate game clock times if both are provided
            if ($this->game_clock_start && $this->game_clock_end) {
                $startSeconds = $this->timeToSeconds($this->game_clock_start);
                $endSeconds = $this->timeToSeconds($this->game_clock_end);
                
                if ($startSeconds <= $endSeconds) {
                    $validator->errors()->add('game_clock_end', 'Die Endzeit muss vor der Startzeit liegen (Uhr läuft rückwärts im Basketball).');
                }
            }

            // Validate that team belongs to the game if both are specified
            if ($this->game_id && $this->team_id) {
                $game = \App\Models\Game::find($this->game_id);
                if ($game && !in_array($this->team_id, [$game->home_team_id, $game->away_team_id])) {
                    $validator->errors()->add('team_id', 'Das ausgewählte Team spielt nicht in diesem Spiel.');
                }
            }

            // Validate tournament and game relationship
            if ($this->tournament_id && $this->game_id) {
                $game = \App\Models\Game::find($this->game_id);
                if ($game && $game->tournament_id != $this->tournament_id) {
                    $validator->errors()->add('game_id', 'Das ausgewählte Spiel gehört nicht zu diesem Turnier.');
                }
            }

            // Validate poster frame time
            if ($this->poster_frame_time && $this->hasFile('video_file')) {
                $duration = $this->getVideoDuration($this->file('video_file'));
                if ($duration && $this->poster_frame_time > $duration) {
                    $validator->errors()->add('poster_frame_time', 'Die Poster-Frame Zeit darf nicht länger als die Videodauer sein.');
                }
            }

            // Validate custom metadata structure
            if ($this->custom_metadata && is_array($this->custom_metadata)) {
                foreach ($this->custom_metadata as $key => $value) {
                    if (!is_string($key) || strlen($key) > 50) {
                        $validator->errors()->add('custom_metadata', 'Metadaten-Schlüssel müssen Strings mit maximal 50 Zeichen sein.');
                        break;
                    }
                }
            }

            // Check file size for different video types
            if ($this->hasFile('video_file')) {
                $file = $this->file('video_file');
                $sizeLimit = $this->getFileSizeLimit($this->video_type);
                
                if ($file->getSize() > $sizeLimit) {
                    $sizeLimitMB = $sizeLimit / (1024 * 1024);
                    $validator->errors()->add('video_file', "Für Video-Typ '{$this->video_type}' ist die maximale Dateigröße {$sizeLimitMB}MB.");
                }
            }
        });
    }

    /**
     * Get video duration from uploaded file.
     */
    private function getVideoDuration($file): ?int
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        try {
            // This would require FFmpeg or similar tool
            // For now, return null to skip validation
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert time string (MM:SS) to seconds.
     */
    private function timeToSeconds(string $time): int
    {
        $parts = explode(':', $time);
        if (count($parts) !== 2) {
            return 0;
        }
        
        $minutes = (int) $parts[0];
        $seconds = (int) $parts[1];
        
        return ($minutes * 60) + $seconds;
    }

    /**
     * Get file size limit based on video type.
     */
    private function getFileSizeLimit(string $videoType): int
    {
        $limits = [
            'full_game' => 2048 * 1024 * 1024, // 2GB
            'game_highlights' => 1024 * 1024 * 1024, // 1GB
            'training_session' => 1024 * 1024 * 1024, // 1GB
            'drill_demo' => 512 * 1024 * 1024, // 512MB
            'player_analysis' => 512 * 1024 * 1024, // 512MB
            'tactical_analysis' => 512 * 1024 * 1024, // 512MB
            'referee_footage' => 1024 * 1024 * 1024, // 1GB
            'fan_footage' => 256 * 1024 * 1024, // 256MB
        ];

        return $limits[$videoType] ?? (512 * 1024 * 1024); // Default 512MB
    }
}