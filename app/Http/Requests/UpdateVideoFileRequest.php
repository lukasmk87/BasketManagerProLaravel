<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVideoFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $videoFile = $this->route('videoFile');
        
        return auth()->check() && 
               (auth()->user()->can('update', $videoFile) || 
                auth()->id() === $videoFile->uploaded_by_user_id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $videoFile = $this->route('videoFile');

        return [
            // Basic metadata (always updatable)
            'title' => 'sometimes|string|max:255|min:3',
            'description' => 'sometimes|nullable|string|max:2000',
            
            // Video classification
            'video_type' => [
                'sometimes',
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
            'game_id' => 'sometimes|nullable|integer|exists:games,id',
            'team_id' => 'sometimes|nullable|integer|exists:teams,id',
            'tournament_id' => 'sometimes|nullable|integer|exists:tournaments,id',
            'season_id' => 'sometimes|nullable|integer|exists:seasons,id',
            
            // Game context
            'game_period' => 'sometimes|nullable|integer|min:1|max:10',
            'game_clock_start' => 'sometimes|nullable|string|regex:/^\d{1,2}:\d{2}$/',
            'game_clock_end' => 'sometimes|nullable|string|regex:/^\d{1,2}:\d{2}$/',
            'court_side' => [
                'sometimes',
                'nullable',
                Rule::in(['home', 'away', 'neutral', 'baseline', 'sideline'])
            ],
            'camera_angle' => [
                'sometimes',
                'nullable',
                Rule::in(['overhead', 'sideline', 'baseline', 'corner', 'behind_basket', 'courtside'])
            ],
            'recording_quality' => [
                'sometimes',
                'nullable',
                Rule::in(['480p', '720p', '1080p', '4k', 'unknown'])
            ],
            
            // Tags and keywords
            'tags' => 'sometimes|nullable|array|max:10',
            'tags.*' => 'string|max:50',
            'keywords' => 'sometimes|nullable|string|max:500',
            
            // Processing options (only if video is not processed yet)
            'ai_analysis_enabled' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($videoFile->ai_analysis_status === 'in_progress') {
                        $fail('AI-Analyse läuft bereits und kann nicht geändert werden.');
                    }
                }
            ],
            
            // Poster frame (only if video is processed)
            'poster_frame_time' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($value && $videoFile->duration && $value > $videoFile->duration) {
                        $fail('Die Poster-Frame Zeit darf nicht länger als die Videodauer sein.');
                    }
                }
            ],
            
            // Custom metadata
            'custom_metadata' => 'sometimes|nullable|array',
            'custom_metadata.*' => 'string|max:255',
            
            // Privacy and access
            'allow_downloads' => 'sometimes|boolean',
            'allow_sharing' => 'sometimes|boolean',
            'require_login' => 'sometimes|boolean',
            'password_protected' => 'sometimes|boolean',
            'access_password' => 'sometimes|nullable|string|min:6|max:20',
            
            // Scheduling
            'scheduled_publish_at' => 'sometimes|nullable|date|after:now',
            'scheduled_archive_at' => 'sometimes|nullable|date|after:scheduled_publish_at',
            
            // Content warnings
            'content_warnings' => 'sometimes|nullable|array',
            'content_warnings.*' => Rule::in([
                'language', 
                'violence', 
                'disputed_calls', 
                'player_injury',
                'crowd_behavior'
            ]),
            
            // External references
            'external_video_id' => 'sometimes|nullable|string|max:100',
            'source_platform' => [
                'sometimes',
                'nullable',
                Rule::in(['youtube', 'vimeo', 'twitch', 'facebook', 'instagram', 'tiktok', 'custom'])
            ],
            'original_url' => 'sometimes|nullable|url|max:500',
            
            // Admin-only fields
            'processing_status' => [
                'sometimes',
                Rule::in(['pending', 'processing', 'completed', 'failed']),
                function ($attribute, $value, $fail) {
                    if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
                        $fail('Nur Administratoren können den Verarbeitungsstatus ändern.');
                    }
                }
            ],
            
            'ai_analysis_status' => [
                'sometimes',
                Rule::in(['pending', 'in_progress', 'completed', 'failed', 'disabled']),
                function ($attribute, $value, $fail) {
                    if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
                        $fail('Nur Administratoren können den AI-Analyse Status ändern.');
                    }
                }
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Der Titel muss mindestens 3 Zeichen lang sein.',
            'title.max' => 'Der Titel darf nicht länger als 255 Zeichen sein.',
            
            'description.max' => 'Die Beschreibung darf nicht länger als 2000 Zeichen sein.',
            
            'video_type.in' => 'Ungültiger Video-Typ ausgewählt.',
            'visibility.in' => 'Ungültige Sichtbarkeitsoption ausgewählt.',
            
            'game_id.exists' => 'Das ausgewählte Spiel existiert nicht.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'tournament_id.exists' => 'Das ausgewählte Turnier existiert nicht.',
            'season_id.exists' => 'Die ausgewählte Saison existiert nicht.',
            
            'game_period.integer' => 'Die Spielperiode muss eine Zahl sein.',
            'game_period.min' => 'Die Spielperiode muss mindestens 1 sein.',
            'game_period.max' => 'Die Spielperiode darf nicht größer als 10 sein.',
            
            'game_clock_start.regex' => 'Die Startzeit muss im Format MM:SS angegeben werden.',
            'game_clock_end.regex' => 'Die Endzeit muss im Format MM:SS angegeben werden.',
            
            'court_side.in' => 'Ungültige Spielfeldseite ausgewählt.',
            'camera_angle.in' => 'Ungültiger Kamerawinkel ausgewählt.',
            'recording_quality.in' => 'Ungültige Aufnahmequalität ausgewählt.',
            
            'tags.array' => 'Tags müssen als Array übergeben werden.',
            'tags.max' => 'Es können maximal 10 Tags vergeben werden.',
            'tags.*.max' => 'Jeder Tag darf maximal 50 Zeichen lang sein.',
            
            'keywords.max' => 'Keywords dürfen nicht länger als 500 Zeichen sein.',
            
            'poster_frame_time.integer' => 'Die Poster-Frame Zeit muss eine Zahl sein.',
            'poster_frame_time.min' => 'Die Poster-Frame Zeit muss mindestens 0 sein.',
            
            'access_password.min' => 'Das Passwort muss mindestens 6 Zeichen lang sein.',
            'access_password.max' => 'Das Passwort darf nicht länger als 20 Zeichen sein.',
            
            'scheduled_publish_at.after' => 'Das Veröffentlichungsdatum muss in der Zukunft liegen.',
            'scheduled_archive_at.after' => 'Das Archivierungsdatum muss nach dem Veröffentlichungsdatum liegen.',
            
            'external_video_id.max' => 'Die externe Video-ID darf nicht länger als 100 Zeichen sein.',
            'source_platform.in' => 'Ungültige Quellplattform ausgewählt.',
            'original_url.url' => 'Die ursprüngliche URL muss eine gültige URL sein.',
            'original_url.max' => 'Die ursprüngliche URL darf nicht länger als 500 Zeichen sein.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
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
            'processing_status' => 'Verarbeitungsstatus',
            'ai_analysis_status' => 'AI-Analyse Status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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

        // Handle password protection logic
        if ($this->has('password_protected') && !$this->password_protected) {
            $this->merge(['access_password' => null]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $videoFile = $this->route('videoFile');

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

            // Validate custom metadata structure
            if ($this->custom_metadata && is_array($this->custom_metadata)) {
                foreach ($this->custom_metadata as $key => $value) {
                    if (!is_string($key) || strlen($key) > 50) {
                        $validator->errors()->add('custom_metadata', 'Metadaten-Schlüssel müssen Strings mit maximal 50 Zeichen sein.');
                        break;
                    }
                }
            }

            // Check if certain fields can be updated based on video state
            if ($videoFile->processing_status === 'processing') {
                $restrictedFields = ['video_type', 'ai_analysis_enabled'];
                foreach ($restrictedFields as $field) {
                    if ($this->has($field)) {
                        $validator->errors()->add($field, 'Dieses Feld kann während der Videobearbeitung nicht geändert werden.');
                    }
                }
            }

            // Validate visibility changes
            if ($this->has('visibility')) {
                $newVisibility = $this->visibility;
                $currentVisibility = $videoFile->visibility;
                
                // Check if user can change from private to public
                if ($currentVisibility === 'private' && $newVisibility === 'public') {
                    if (!auth()->user()->can('publish', $videoFile)) {
                        $validator->errors()->add('visibility', 'Sie haben keine Berechtigung, dieses Video zu veröffentlichen.');
                    }
                }
                
                // Check if video meets requirements for public visibility
                if ($newVisibility === 'public') {
                    if ($videoFile->processing_status !== 'completed') {
                        $validator->errors()->add('visibility', 'Video muss vollständig verarbeitet sein, bevor es öffentlich gemacht werden kann.');
                    }
                    
                    if (empty($videoFile->description) && empty($this->description)) {
                        $validator->errors()->add('description', 'Eine Beschreibung ist für öffentliche Videos erforderlich.');
                    }
                }
            }

            // Validate password requirement
            if ($this->has('password_protected') && $this->password_protected) {
                if (empty($this->access_password) && empty($videoFile->access_password)) {
                    $validator->errors()->add('access_password', 'Ein Passwort ist erforderlich für passwortgeschützte Videos.');
                }
            }

            // Validate scheduling
            if ($this->scheduled_publish_at && $this->scheduled_archive_at) {
                $publishTime = strtotime($this->scheduled_publish_at);
                $archiveTime = strtotime($this->scheduled_archive_at);
                
                if ($archiveTime <= $publishTime) {
                    $validator->errors()->add('scheduled_archive_at', 'Archivierungsdatum muss nach dem Veröffentlichungsdatum liegen.');
                }
                
                // Check minimum publication duration (e.g., 1 hour)
                if (($archiveTime - $publishTime) < 3600) {
                    $validator->errors()->add('scheduled_archive_at', 'Video muss mindestens 1 Stunde veröffentlicht bleiben.');
                }
            }
        });
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
     * Get only the fields that should be updated.
     */
    public function getUpdatableFields(): array
    {
        $updatableFields = [
            'title',
            'description',
            'video_type',
            'visibility',
            'game_id',
            'team_id',
            'tournament_id',
            'season_id',
            'game_period',
            'game_clock_start',
            'game_clock_end',
            'court_side',
            'camera_angle',
            'recording_quality',
            'tags',
            'keywords',
            'ai_analysis_enabled',
            'poster_frame_time',
            'custom_metadata',
            'allow_downloads',
            'allow_sharing',
            'require_login',
            'password_protected',
            'access_password',
            'scheduled_publish_at',
            'scheduled_archive_at',
            'content_warnings',
            'external_video_id',
            'source_platform',
            'original_url',
        ];

        // Add admin-only fields if user is admin
        if (auth()->user()->hasRole(['admin', 'super_admin'])) {
            $updatableFields = array_merge($updatableFields, [
                'processing_status',
                'ai_analysis_status',
            ]);
        }

        return array_intersect_key($this->validated(), array_flip($updatableFields));
    }
}