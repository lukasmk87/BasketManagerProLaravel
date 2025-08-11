<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVideoAnnotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $videoFile = $this->route('videoFile');
        $annotation = $this->route('annotation');
        
        return auth()->check() && 
               auth()->user()->can('view', $videoFile) &&
               auth()->user()->can('update', $annotation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $videoFile = $this->route('videoFile');
        $annotation = $this->route('annotation');

        return [
            // Basic annotation data
            'title' => 'sometimes|string|max:255|min:3',
            'description' => 'sometimes|nullable|string|max:1000',
            
            // Time boundaries
            'start_time' => [
                'sometimes',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($videoFile->duration && $value >= $videoFile->duration) {
                        $fail('Die Startzeit darf nicht größer als die Videodauer sein.');
                    }
                }
            ],
            'end_time' => [
                'sometimes',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($videoFile, $annotation) {
                    $startTime = $this->start_time ?? $annotation->start_time;
                    if ($value <= $startTime) {
                        $fail('Die Endzeit muss nach der Startzeit liegen.');
                    }
                    if ($videoFile->duration && $value > $videoFile->duration) {
                        $fail('Die Endzeit darf nicht größer als die Videodauer sein.');
                    }
                }
            ],
            
            // Annotation classification
            'annotation_type' => [
                'sometimes',
                Rule::in([
                    'play_action',
                    'statistical_event',
                    'coaching_note',
                    'tactical_analysis',
                    'player_performance',
                    'referee_decision',
                    'technical_issue',
                    'highlight_moment'
                ])
            ],
            
            // Basketball-specific data
            'play_type' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'shot', 'pass', 'dribble', 'rebound', 'steal', 'block',
                    'foul', 'turnover', 'timeout', 'substitution', 'free_throw',
                    'jump_ball', 'violation', 'technical_foul'
                ])
            ],
            
            'outcome' => [
                'sometimes',
                'nullable',
                Rule::in(['successful', 'unsuccessful', 'neutral', 'positive', 'negative'])
            ],
            
            'points_scored' => 'sometimes|nullable|integer|min:0|max:10',
            'player_involved' => 'sometimes|nullable|string|max:255',
            'team_involved' => [
                'sometimes',
                'nullable',
                Rule::in(['home', 'away', 'both', 'neutral'])
            ],
            
            // Court positioning
            'court_position_x' => 'sometimes|nullable|integer|min:0|max:1000',
            'court_position_y' => 'sometimes|nullable|integer|min:0|max:600',
            
            // Visual and organizational
            'color_code' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            'tags' => 'sometimes|nullable|array|max:8',
            'tags.*' => 'string|max:30',
            'keywords' => 'sometimes|nullable|string|max:300',
            
            // Status and workflow
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'pending_review', 'approved', 'rejected', 'archived']),
                function ($attribute, $value, $fail) use ($annotation) {
                    // Only allow certain status transitions
                    $currentStatus = $annotation->status;
                    $allowedTransitions = [
                        'draft' => ['published', 'pending_review'],
                        'published' => ['archived', 'pending_review'],
                        'pending_review' => ['approved', 'rejected', 'draft'],
                        'approved' => ['published', 'archived'],
                        'rejected' => ['draft', 'pending_review'],
                        'archived' => ['draft'],
                    ];
                    
                    if (!in_array($value, $allowedTransitions[$currentStatus] ?? [])) {
                        $fail("Status kann nicht von '{$currentStatus}' zu '{$value}' geändert werden.");
                    }
                }
            ],
            
            'priority' => [
                'sometimes',
                Rule::in(['low', 'normal', 'high', 'urgent'])
            ],
            'visibility' => [
                'sometimes',
                Rule::in(['public', 'team_only', 'private'])
            ],
            
            // Frame references
            'frame_start' => 'sometimes|nullable|integer|min:0',
            'frame_end' => 'sometimes|nullable|integer|min:0|gte:frame_start',
            'thumbnail_frame' => 'sometimes|nullable|integer|min:0',
            
            // Statistical data
            'statistical_data' => 'sometimes|nullable|array',
            'statistical_data.shot_distance' => 'sometimes|nullable|numeric|min:0|max:50',
            'statistical_data.shot_angle' => 'sometimes|nullable|numeric|min:0|max:360',
            'statistical_data.pass_distance' => 'sometimes|nullable|numeric|min:0|max:30',
            'statistical_data.speed' => 'sometimes|nullable|numeric|min:0|max:50',
            'statistical_data.possession_time' => 'sometimes|nullable|numeric|min:0|max:24',
            
            // Parent-child relationships
            'parent_annotation_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:video_annotations,id',
                function ($attribute, $value, $fail) use ($videoFile, $annotation) {
                    if ($value) {
                        if ($value == $annotation->id) {
                            $fail('Eine Annotation kann nicht sich selbst als übergeordnete Annotation haben.');
                        }
                        
                        $parent = \App\Models\VideoAnnotation::find($value);
                        if ($parent && $parent->video_file_id != $videoFile->id) {
                            $fail('Die übergeordnete Annotation muss zum selben Video gehören.');
                        }
                        
                        // Check for circular references
                        if ($this->wouldCreateCircularReference($annotation->id, $value)) {
                            $fail('Diese Zuweisung würde eine zirkuläre Referenz erstellen.');
                        }
                    }
                }
            ],
            
            // Review fields (only for reviewers)
            'review_required' => 'sometimes|boolean',
            'reviewed_by_user_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!auth()->user()->can('review', $this->route('annotation'))) {
                        $fail('Sie haben keine Berechtigung, Überprüfungen zuzuweisen.');
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
            
            'description.max' => 'Die Beschreibung darf nicht länger als 1000 Zeichen sein.',
            
            'start_time.integer' => 'Die Startzeit muss eine ganze Zahl (Sekunden) sein.',
            'start_time.min' => 'Die Startzeit darf nicht negativ sein.',
            
            'end_time.integer' => 'Die Endzeit muss eine ganze Zahl (Sekunden) sein.',
            'end_time.min' => 'Die Endzeit muss mindestens 1 Sekunde betragen.',
            
            'annotation_type.in' => 'Ungültiger Annotationstyp ausgewählt.',
            'play_type.in' => 'Ungültiger Spielzugtyp ausgewählt.',
            'outcome.in' => 'Ungültiges Ergebnis ausgewählt.',
            'team_involved.in' => 'Ungültige Teamauswahl.',
            
            'points_scored.integer' => 'Die Punktzahl muss eine ganze Zahl sein.',
            'points_scored.min' => 'Die Punktzahl darf nicht negativ sein.',
            'points_scored.max' => 'Die Punktzahl darf nicht größer als 10 sein.',
            
            'court_position_x.integer' => 'Die X-Position muss eine ganze Zahl sein.',
            'court_position_x.min' => 'Die X-Position darf nicht negativ sein.',
            'court_position_x.max' => 'Die X-Position darf nicht größer als 1000 sein.',
            
            'court_position_y.integer' => 'Die Y-Position muss eine ganze Zahl sein.',
            'court_position_y.min' => 'Die Y-Position darf nicht negativ sein.',
            'court_position_y.max' => 'Die Y-Position darf nicht größer als 600 sein.',
            
            'color_code.regex' => 'Der Farbcode muss im Format #RRGGBB angegeben werden.',
            
            'tags.array' => 'Tags müssen als Array übergeben werden.',
            'tags.max' => 'Es können maximal 8 Tags vergeben werden.',
            'tags.*.max' => 'Jeder Tag darf maximal 30 Zeichen lang sein.',
            
            'keywords.max' => 'Keywords dürfen nicht länger als 300 Zeichen sein.',
            
            'status.in' => 'Ungültiger Status ausgewählt.',
            'priority.in' => 'Ungültige Priorität ausgewählt.',
            'visibility.in' => 'Ungültige Sichtbarkeit ausgewählt.',
            
            'frame_start.integer' => 'Der Start-Frame muss eine ganze Zahl sein.',
            'frame_start.min' => 'Der Start-Frame darf nicht negativ sein.',
            
            'frame_end.integer' => 'Der End-Frame muss eine ganze Zahl sein.',
            'frame_end.min' => 'Der End-Frame darf nicht negativ sein.',
            'frame_end.gte' => 'Der End-Frame muss größer oder gleich dem Start-Frame sein.',
            
            'thumbnail_frame.integer' => 'Der Thumbnail-Frame muss eine ganze Zahl sein.',
            'thumbnail_frame.min' => 'Der Thumbnail-Frame darf nicht negativ sein.',
            
            'parent_annotation_id.exists' => 'Die ausgewählte übergeordnete Annotation existiert nicht.',
            
            'reviewed_by_user_id.exists' => 'Der ausgewählte Prüfer existiert nicht.',
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
            'start_time' => 'Startzeit',
            'end_time' => 'Endzeit',
            'annotation_type' => 'Annotationstyp',
            'play_type' => 'Spielzugtyp',
            'outcome' => 'Ergebnis',
            'points_scored' => 'Erzielte Punkte',
            'player_involved' => 'Beteiligter Spieler',
            'team_involved' => 'Beteiligtes Team',
            'court_position_x' => 'X-Position',
            'court_position_y' => 'Y-Position',
            'color_code' => 'Farbcode',
            'tags' => 'Tags',
            'keywords' => 'Keywords',
            'status' => 'Status',
            'priority' => 'Priorität',
            'visibility' => 'Sichtbarkeit',
            'frame_start' => 'Start-Frame',
            'frame_end' => 'End-Frame',
            'thumbnail_frame' => 'Thumbnail-Frame',
            'statistical_data' => 'Statistische Daten',
            'parent_annotation_id' => 'Übergeordnete Annotation',
            'review_required' => 'Überprüfung erforderlich',
            'reviewed_by_user_id' => 'Prüfer',
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
            $cleanTags = array_slice($cleanTags, 0, 8); // Limit to 8 tags
            $this->merge(['tags' => array_values($cleanTags)]);
        }

        // Process keywords
        if ($this->has('keywords') && is_string($this->keywords)) {
            $keywords = trim($this->keywords);
            $this->merge(['keywords' => $keywords]);
        }

        // Ensure statistical_data is properly formatted
        if ($this->has('statistical_data') && !is_array($this->statistical_data)) {
            $this->merge(['statistical_data' => []]);
        }

        // Calculate frame numbers from time if not provided but time is updated
        $videoFile = $this->route('videoFile');
        $annotation = $this->route('annotation');
        
        if ($videoFile && $videoFile->frame_rate) {
            if (!$this->has('frame_start') && $this->has('start_time')) {
                $this->merge(['frame_start' => (int)($this->start_time * $videoFile->frame_rate)]);
            }

            if (!$this->has('frame_end') && $this->has('end_time')) {
                $this->merge(['frame_end' => (int)($this->end_time * $videoFile->frame_rate)]);
            }

            // Update thumbnail frame if time range changed
            if (($this->has('start_time') || $this->has('end_time')) && !$this->has('thumbnail_frame')) {
                $startTime = $this->start_time ?? $annotation->start_time;
                $endTime = $this->end_time ?? $annotation->end_time;
                $middleTime = $startTime + ($endTime - $startTime) / 2;
                $this->merge(['thumbnail_frame' => (int)($middleTime * $videoFile->frame_rate)]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $videoFile = $this->route('videoFile');
            $annotation = $this->route('annotation');

            // Check annotation duration limits
            $startTime = $this->start_time ?? $annotation->start_time;
            $endTime = $this->end_time ?? $annotation->end_time;
            
            if ($startTime !== null && $endTime !== null) {
                $duration = $endTime - $startTime;
                
                if ($duration > 300) { // 5 minutes max
                    $validator->errors()->add('end_time', 'Annotationen dürfen nicht länger als 5 Minuten sein.');
                }
                
                if ($duration < 1) { // At least 1 second
                    $validator->errors()->add('end_time', 'Annotationen müssen mindestens 1 Sekunde lang sein.');
                }
            }

            // Validate court position consistency
            $xPos = $this->court_position_x ?? $annotation->court_position_x;
            $yPos = $this->court_position_y ?? $annotation->court_position_y;
            
            if (($xPos !== null) !== ($yPos !== null)) {
                $validator->errors()->add('court_position_y', 'Sowohl X- als auch Y-Position müssen angegeben werden.');
            }

            // Validate play type and outcome consistency
            $playType = $this->play_type ?? $annotation->play_type;
            $outcome = $this->outcome ?? $annotation->outcome;
            
            if ($playType && $outcome) {
                $validCombinations = [
                    'shot' => ['successful', 'unsuccessful'],
                    'pass' => ['successful', 'unsuccessful'],
                    'free_throw' => ['successful', 'unsuccessful'],
                    'steal' => ['successful'],
                    'block' => ['successful'],
                    'rebound' => ['successful'],
                    'foul' => ['negative'],
                    'turnover' => ['negative'],
                    'violation' => ['negative'],
                    'technical_foul' => ['negative'],
                ];

                if (isset($validCombinations[$playType])) {
                    if (!in_array($outcome, $validCombinations[$playType])) {
                        $validator->errors()->add('outcome', "Das Ergebnis '{$outcome}' ist nicht gültig für den Spielzugtyp '{$playType}'.");
                    }
                }
            }

            // Validate points scored logic
            $pointsScored = $this->points_scored ?? $annotation->points_scored;
            
            if ($pointsScored !== null && $pointsScored > 0) {
                if ($playType && !in_array($playType, ['shot', 'free_throw'])) {
                    $validator->errors()->add('points_scored', 'Punkte können nur bei Würfen oder Freiwürfen erzielt werden.');
                }
                
                if ($outcome === 'unsuccessful') {
                    $validator->errors()->add('points_scored', 'Bei erfolglosen Aktionen können keine Punkte erzielt werden.');
                }

                // Validate point values
                if ($playType === 'free_throw' && $pointsScored > 1) {
                    $validator->errors()->add('points_scored', 'Freiwürfe können maximal 1 Punkt ergeben.');
                } elseif ($playType === 'shot' && $pointsScored > 3) {
                    $validator->errors()->add('points_scored', 'Feldwürfe können maximal 3 Punkte ergeben.');
                }
            }

            // Validate statistical data consistency
            $statisticalData = $this->statistical_data ?? $annotation->statistical_data;
            
            if ($statisticalData && is_array($statisticalData)) {
                if ($playType === 'shot') {
                    if (isset($statisticalData['pass_distance'])) {
                        $validator->errors()->add('statistical_data.pass_distance', 'Pass-Distanz ist bei Würfen nicht relevant.');
                    }
                } elseif ($playType === 'pass') {
                    if (isset($statisticalData['shot_distance']) || isset($statisticalData['shot_angle'])) {
                        $validator->errors()->add('statistical_data', 'Schuss-Daten sind bei Pässen nicht relevant.');
                    }
                }
            }

            // Check for overlapping annotations (warning only)
            if (($this->start_time !== null || $this->end_time !== null)) {
                $newStartTime = $this->start_time ?? $annotation->start_time;
                $newEndTime = $this->end_time ?? $annotation->end_time;
                
                $overlapping = \App\Models\VideoAnnotation::where('video_file_id', $videoFile->id)
                    ->where('id', '!=', $annotation->id)
                    ->where('status', 'published')
                    ->where(function($query) use ($newStartTime, $newEndTime) {
                        $query->whereBetween('start_time', [$newStartTime, $newEndTime])
                              ->orWhereBetween('end_time', [$newStartTime, $newEndTime])
                              ->orWhere(function($subQuery) use ($newStartTime, $newEndTime) {
                                  $subQuery->where('start_time', '<=', $newStartTime)
                                           ->where('end_time', '>=', $newEndTime);
                              });
                    })
                    ->exists();
                
                // Store overlap info for controller to handle as warning
                if ($overlapping && !$this->has('ignore_overlap_warning')) {
                    $this->attributes()->add('has_overlapping_annotations', true);
                }
            }

            // Validate AI-generated annotation modifications
            if ($annotation->is_ai_generated) {
                $restrictedFields = ['start_time', 'end_time', 'play_type', 'outcome'];
                foreach ($restrictedFields as $field) {
                    if ($this->has($field) && !auth()->user()->hasRole(['admin', 'coach'])) {
                        $validator->errors()->add($field, 'AI-generierte Annotationen können nur von Trainern oder Administratoren in diesem Feld bearbeitet werden.');
                    }
                }
            }

            // Validate status change permissions
            if ($this->has('status')) {
                $newStatus = $this->status;
                $currentStatus = $annotation->status;
                
                // Check if user can change to this status
                if ($newStatus === 'published' && !auth()->user()->can('publish', $annotation)) {
                    $validator->errors()->add('status', 'Sie haben keine Berechtigung, diese Annotation zu veröffentlichen.');
                }
                
                if (in_array($newStatus, ['approved', 'rejected']) && !auth()->user()->can('review', $annotation)) {
                    $validator->errors()->add('status', 'Sie haben keine Berechtigung, diese Annotation zu genehmigen oder abzulehnen.');
                }
            }

            // Validate visibility restrictions
            if ($this->has('visibility')) {
                $newVisibility = $this->visibility;
                
                if ($newVisibility === 'public' && $annotation->is_ai_generated && $annotation->ai_confidence < 0.8) {
                    $validator->errors()->add('visibility', 'AI-generierte Annotationen mit niedriger Vertrauenswürdigkeit können nicht öffentlich gemacht werden.');
                }
            }
        });
    }

    /**
     * Check if assigning a parent would create a circular reference.
     */
    private function wouldCreateCircularReference(int $annotationId, int $parentId): bool
    {
        $visited = [];
        $currentId = $parentId;
        
        while ($currentId && !in_array($currentId, $visited)) {
            if ($currentId == $annotationId) {
                return true; // Circular reference found
            }
            
            $visited[] = $currentId;
            $parent = \App\Models\VideoAnnotation::find($currentId);
            $currentId = $parent ? $parent->parent_annotation_id : null;
            
            // Safety limit to prevent infinite loops
            if (count($visited) > 10) {
                break;
            }
        }
        
        return false;
    }

    /**
     * Get only the fields that should be updated.
     */
    public function getUpdatableFields(): array
    {
        $annotation = $this->route('annotation');
        $user = auth()->user();
        
        $basicFields = [
            'title',
            'description',
            'annotation_type',
            'play_type',
            'outcome',
            'points_scored',
            'player_involved',
            'team_involved',
            'court_position_x',
            'court_position_y',
            'color_code',
            'tags',
            'keywords',
            'priority',
            'visibility',
            'frame_start',
            'frame_end',
            'thumbnail_frame',
            'statistical_data',
            'parent_annotation_id',
        ];

        // Add time fields if user can modify them
        if (!$annotation->is_ai_generated || $user->hasRole(['admin', 'coach'])) {
            $basicFields[] = 'start_time';
            $basicFields[] = 'end_time';
        }

        // Add status if user can change it
        if ($user->can('update', $annotation)) {
            $basicFields[] = 'status';
        }

        // Add review fields if user can review
        if ($user->can('review', $annotation)) {
            $basicFields[] = 'review_required';
            $basicFields[] = 'reviewed_by_user_id';
        }

        return array_intersect_key($this->validated(), array_flip($basicFields));
    }
}