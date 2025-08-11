<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoAnnotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $videoFile = $this->route('videoFile');
        
        return auth()->check() && auth()->user()->can('view', $videoFile);
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
            // Basic annotation data
            'title' => 'required|string|max:255|min:3',
            'description' => 'nullable|string|max:1000',
            
            // Time boundaries
            'start_time' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($videoFile->duration && $value >= $videoFile->duration) {
                        $fail('Die Startzeit darf nicht größer als die Videodauer sein.');
                    }
                }
            ],
            'end_time' => [
                'required',
                'integer',
                'min:1',
                'gt:start_time',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($videoFile->duration && $value > $videoFile->duration) {
                        $fail('Die Endzeit darf nicht größer als die Videodauer sein.');
                    }
                }
            ],
            
            // Annotation classification
            'annotation_type' => [
                'required',
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
                'nullable',
                Rule::in([
                    'shot', 'pass', 'dribble', 'rebound', 'steal', 'block',
                    'foul', 'turnover', 'timeout', 'substitution', 'free_throw',
                    'jump_ball', 'violation', 'technical_foul'
                ])
            ],
            
            'outcome' => [
                'nullable',
                Rule::in(['successful', 'unsuccessful', 'neutral', 'positive', 'negative'])
            ],
            
            'points_scored' => 'nullable|integer|min:0|max:10',
            'player_involved' => 'nullable|string|max:255',
            'team_involved' => [
                'nullable',
                Rule::in(['home', 'away', 'both', 'neutral'])
            ],
            
            // Court positioning
            'court_position_x' => 'nullable|integer|min:0|max:1000',
            'court_position_y' => 'nullable|integer|min:0|max:600',
            
            // Visual and organizational
            'color_code' => [
                'nullable',
                'string',
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            'tags' => 'nullable|array|max:8',
            'tags.*' => 'string|max:30',
            'keywords' => 'nullable|string|max:300',
            
            // Status and workflow
            'priority' => [
                'sometimes',
                Rule::in(['low', 'normal', 'high', 'urgent'])
            ],
            'visibility' => [
                'sometimes',
                Rule::in(['public', 'team_only', 'private'])
            ],
            
            // Frame references
            'frame_start' => 'nullable|integer|min:0',
            'frame_end' => 'nullable|integer|min:0|gte:frame_start',
            'thumbnail_frame' => 'nullable|integer|min:0',
            
            // Statistical data
            'statistical_data' => 'nullable|array',
            'statistical_data.shot_distance' => 'nullable|numeric|min:0|max:50',
            'statistical_data.shot_angle' => 'nullable|numeric|min:0|max:360',
            'statistical_data.pass_distance' => 'nullable|numeric|min:0|max:30',
            'statistical_data.speed' => 'nullable|numeric|min:0|max:50',
            'statistical_data.possession_time' => 'nullable|numeric|min:0|max:24',
            
            // Parent-child relationships
            'parent_annotation_id' => [
                'nullable',
                'integer',
                'exists:video_annotations,id',
                function ($attribute, $value, $fail) use ($videoFile) {
                    if ($value) {
                        $parent = \App\Models\VideoAnnotation::find($value);
                        if ($parent && $parent->video_file_id != $videoFile->id) {
                            $fail('Die übergeordnete Annotation muss zum selben Video gehören.');
                        }
                    }
                }
            ],
            
            // Content validation
            'requires_review' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Ein Titel ist erforderlich.',
            'title.min' => 'Der Titel muss mindestens 3 Zeichen lang sein.',
            'title.max' => 'Der Titel darf nicht länger als 255 Zeichen sein.',
            
            'description.max' => 'Die Beschreibung darf nicht länger als 1000 Zeichen sein.',
            
            'start_time.required' => 'Eine Startzeit ist erforderlich.',
            'start_time.integer' => 'Die Startzeit muss eine ganze Zahl (Sekunden) sein.',
            'start_time.min' => 'Die Startzeit darf nicht negativ sein.',
            
            'end_time.required' => 'Eine Endzeit ist erforderlich.',
            'end_time.integer' => 'Die Endzeit muss eine ganze Zahl (Sekunden) sein.',
            'end_time.min' => 'Die Endzeit muss mindestens 1 Sekunde betragen.',
            'end_time.gt' => 'Die Endzeit muss nach der Startzeit liegen.',
            
            'annotation_type.required' => 'Ein Annotationstyp muss ausgewählt werden.',
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
            
            'priority.in' => 'Ungültige Priorität ausgewählt.',
            'visibility.in' => 'Ungültige Sichtbarkeit ausgewählt.',
            
            'frame_start.integer' => 'Der Start-Frame muss eine ganze Zahl sein.',
            'frame_start.min' => 'Der Start-Frame darf nicht negativ sein.',
            
            'frame_end.integer' => 'Der End-Frame muss eine ganze Zahl sein.',
            'frame_end.min' => 'Der End-Frame darf nicht negativ sein.',
            'frame_end.gte' => 'Der End-Frame muss größer oder gleich dem Start-Frame sein.',
            
            'thumbnail_frame.integer' => 'Der Thumbnail-Frame muss eine ganze Zahl sein.',
            'thumbnail_frame.min' => 'Der Thumbnail-Frame darf nicht negativ sein.',
            
            'statistical_data.shot_distance.numeric' => 'Die Schussdistanz muss eine Zahl sein.',
            'statistical_data.shot_distance.min' => 'Die Schussdistanz darf nicht negativ sein.',
            'statistical_data.shot_distance.max' => 'Die Schussdistanz darf nicht größer als 50m sein.',
            
            'statistical_data.shot_angle.numeric' => 'Der Schusswinkel muss eine Zahl sein.',
            'statistical_data.shot_angle.min' => 'Der Schusswinkel darf nicht negativ sein.',
            'statistical_data.shot_angle.max' => 'Der Schusswinkel darf nicht größer als 360° sein.',
            
            'parent_annotation_id.exists' => 'Die ausgewählte übergeordnete Annotation existiert nicht.',
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
            'priority' => 'Priorität',
            'visibility' => 'Sichtbarkeit',
            'frame_start' => 'Start-Frame',
            'frame_end' => 'End-Frame',
            'thumbnail_frame' => 'Thumbnail-Frame',
            'statistical_data' => 'Statistische Daten',
            'parent_annotation_id' => 'Übergeordnete Annotation',
            'requires_review' => 'Überprüfung erforderlich',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        if (!$this->has('priority')) {
            $this->merge(['priority' => 'normal']);
        }

        if (!$this->has('visibility')) {
            $this->merge(['visibility' => 'private']);
        }

        if (!$this->has('requires_review')) {
            $this->merge(['requires_review' => false]);
        }

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

        // Calculate frame numbers from time if not provided
        $videoFile = $this->route('videoFile');
        if ($videoFile && $videoFile->frame_rate) {
            if (!$this->has('frame_start') && $this->has('start_time')) {
                $this->merge(['frame_start' => (int)($this->start_time * $videoFile->frame_rate)]);
            }

            if (!$this->has('frame_end') && $this->has('end_time')) {
                $this->merge(['frame_end' => (int)($this->end_time * $videoFile->frame_rate)]);
            }

            if (!$this->has('thumbnail_frame') && $this->has('start_time')) {
                // Set thumbnail to middle of annotation by default
                $middleTime = $this->start_time + (($this->end_time ?? $this->start_time) - $this->start_time) / 2;
                $this->merge(['thumbnail_frame' => (int)($middleTime * $videoFile->frame_rate)]);
            }
        }

        // Auto-assign color based on annotation type if not provided
        if (!$this->has('color_code') && $this->has('annotation_type')) {
            $colorMap = [
                'play_action' => '#007bff',      // Blue
                'statistical_event' => '#28a745', // Green
                'coaching_note' => '#ffc107',     // Yellow
                'tactical_analysis' => '#17a2b8', // Teal
                'player_performance' => '#dc3545', // Red
                'referee_decision' => '#6c757d',  // Gray
                'technical_issue' => '#fd7e14',   // Orange
                'highlight_moment' => '#e83e8c',  // Pink
            ];

            $color = $colorMap[$this->annotation_type] ?? '#007bff';
            $this->merge(['color_code' => $color]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $videoFile = $this->route('videoFile');

            // Check annotation duration limits
            if ($this->start_time !== null && $this->end_time !== null) {
                $duration = $this->end_time - $this->start_time;
                
                if ($duration > 300) { // 5 minutes max
                    $validator->errors()->add('end_time', 'Annotationen dürfen nicht länger als 5 Minuten sein.');
                }
                
                if ($duration < 1) { // At least 1 second
                    $validator->errors()->add('end_time', 'Annotationen müssen mindestens 1 Sekunde lang sein.');
                }
            }

            // Validate court position consistency
            if (($this->court_position_x !== null) !== ($this->court_position_y !== null)) {
                $validator->errors()->add('court_position_y', 'Sowohl X- als auch Y-Position müssen angegeben werden.');
            }

            // Validate play type and outcome consistency
            if ($this->play_type && $this->outcome) {
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

                if (isset($validCombinations[$this->play_type])) {
                    if (!in_array($this->outcome, $validCombinations[$this->play_type])) {
                        $validator->errors()->add('outcome', "Das Ergebnis '{$this->outcome}' ist nicht gültig für den Spielzugtyp '{$this->play_type}'.");
                    }
                }
            }

            // Validate points scored logic
            if ($this->points_scored !== null && $this->points_scored > 0) {
                if ($this->play_type && !in_array($this->play_type, ['shot', 'free_throw'])) {
                    $validator->errors()->add('points_scored', 'Punkte können nur bei Würfen oder Freiwürfen erzielt werden.');
                }
                
                if ($this->outcome === 'unsuccessful') {
                    $validator->errors()->add('points_scored', 'Bei erfolglosen Aktionen können keine Punkte erzielt werden.');
                }

                // Validate point values
                if ($this->play_type === 'free_throw' && $this->points_scored > 1) {
                    $validator->errors()->add('points_scored', 'Freiwürfe können maximal 1 Punkt ergeben.');
                } elseif ($this->play_type === 'shot' && $this->points_scored > 3) {
                    $validator->errors()->add('points_scored', 'Feldwürfe können maximal 3 Punkte ergeben.');
                }
            }

            // Validate statistical data consistency
            if ($this->statistical_data && is_array($this->statistical_data)) {
                if ($this->play_type === 'shot') {
                    // Shot distance and angle should be provided for shots
                    if (isset($this->statistical_data['pass_distance'])) {
                        $validator->errors()->add('statistical_data.pass_distance', 'Pass-Distanz ist bei Würfen nicht relevant.');
                    }
                } elseif ($this->play_type === 'pass') {
                    if (isset($this->statistical_data['shot_distance']) || isset($this->statistical_data['shot_angle'])) {
                        $validator->errors()->add('statistical_data', 'Schuss-Daten sind bei Pässen nicht relevant.');
                    }
                }
            }

            // Check for overlapping annotations warning
            if ($videoFile && $this->start_time !== null && $this->end_time !== null) {
                $overlapping = \App\Models\VideoAnnotation::where('video_file_id', $videoFile->id)
                    ->where('status', 'published')
                    ->where(function($query) {
                        $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                              ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                              ->orWhere(function($subQuery) {
                                  $subQuery->where('start_time', '<=', $this->start_time)
                                           ->where('end_time', '>=', $this->end_time);
                              });
                    })
                    ->exists();

                // This creates a warning rather than an error
                if ($overlapping && !$this->has('ignore_overlap_warning')) {
                    // You might want to handle this in the controller instead
                    // as warnings are harder to manage in validation
                }
            }

            // Validate parent-child relationship depth
            if ($this->parent_annotation_id) {
                $depth = 0;
                $currentParentId = $this->parent_annotation_id;
                
                while ($currentParentId && $depth < 5) { // Prevent infinite loops
                    $parent = \App\Models\VideoAnnotation::find($currentParentId);
                    if (!$parent) break;
                    
                    $currentParentId = $parent->parent_annotation_id;
                    $depth++;
                }
                
                if ($depth >= 3) {
                    $validator->errors()->add('parent_annotation_id', 'Annotationen können maximal 3 Ebenen tief verschachtelt werden.');
                }
            }

            // Validate video is ready for annotation
            if ($videoFile->processing_status !== 'completed') {
                $validator->errors()->add('start_time', 'Das Video muss vollständig verarbeitet sein, bevor Annotationen erstellt werden können.');
            }
        });
    }

    /**
     * Get the error messages for validation rules that detect overlaps.
     */
    public function getOverlapWarning(): ?string
    {
        $videoFile = $this->route('videoFile');
        
        if (!$videoFile || !$this->has('start_time') || !$this->has('end_time')) {
            return null;
        }

        $overlapping = \App\Models\VideoAnnotation::where('video_file_id', $videoFile->id)
            ->where('status', 'published')
            ->where(function($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                      ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('start_time', '<=', $this->start_time)
                                   ->where('end_time', '>=', $this->end_time);
                      });
            })
            ->count();

        if ($overlapping > 0) {
            return "Es gibt {$overlapping} überlappende Annotation(en) in diesem Zeitbereich.";
        }

        return null;
    }
}