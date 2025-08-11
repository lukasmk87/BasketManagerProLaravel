<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class VideoAnalysisSession extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'video_file_id',
        'analyst_user_id',
        'team_id',
        'session_name',
        'session_description',
        'analysis_objectives',
        'analysis_type',
        'focus_areas',
        'analysis_criteria',
        'status',
        'started_at',
        'completed_at',
        'total_duration',
        'invited_users',
        'participant_roles',
        'allow_collaborative_editing',
        'active_participants',
        'key_findings',
        'recommendations',
        'action_items',
        'summary_notes',
        'statistical_insights',
        'tactical_observations',
        'player_evaluations',
        'play_breakdowns',
        'improvement_areas',
        'strengths_identified',
        'analysis_settings',
        'auto_save_enabled',
        'auto_save_interval',
        'video_playback_settings',
        'presentation_ready',
        'presentation_template',
        'presentation_slides',
        'is_shareable',
        'sharing_settings',
        'export_format',
        'linked_training_sessions',
        'suggested_drills',
        'training_recommendations',
        'create_training_plan',
        'annotation_count',
        'analysis_completeness',
        'confidence_rating',
        'priority_level',
        'follow_up_actions',
        'next_review_date',
        'reviewer_user_id',
        'review_status',
        'review_comments',
        'version_number',
        'previous_version_id',
        'version_changes',
        'is_current_version',
    ];

    protected $casts = [
        'focus_areas' => 'array',
        'analysis_criteria' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'invited_users' => 'array',
        'participant_roles' => 'array',
        'allow_collaborative_editing' => 'boolean',
        'active_participants' => 'array',
        'key_findings' => 'array',
        'recommendations' => 'array',
        'action_items' => 'array',
        'statistical_insights' => 'array',
        'tactical_observations' => 'array',
        'player_evaluations' => 'array',
        'play_breakdowns' => 'array',
        'improvement_areas' => 'array',
        'strengths_identified' => 'array',
        'analysis_settings' => 'array',
        'auto_save_enabled' => 'boolean',
        'video_playback_settings' => 'array',
        'presentation_ready' => 'boolean',
        'presentation_slides' => 'array',
        'is_shareable' => 'boolean',
        'sharing_settings' => 'array',
        'linked_training_sessions' => 'array',
        'suggested_drills' => 'array',
        'training_recommendations' => 'array',
        'create_training_plan' => 'boolean',
        'analysis_completeness' => 'decimal:2',
        'confidence_rating' => 'decimal:2',
        'follow_up_actions' => 'array',
        'next_review_date' => 'datetime',
        'version_changes' => 'array',
        'is_current_version' => 'boolean',
    ];

    // Relationships
    public function videoFile(): BelongsTo
    {
        return $this->belongsTo(VideoFile::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(VideoAnalysisSession::class, 'previous_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VideoAnalysisSession::class, 'previous_version_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByAnalysisType($query, $type)
    {
        return $query->where('analysis_type', $type);
    }

    public function scopeByAnalyst($query, $userId)
    {
        return $query->where('analyst_user_id', $userId);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeShareable($query)
    {
        return $query->where('is_shareable', true);
    }

    public function scopePresentationReady($query)
    {
        return $query->where('presentation_ready', true);
    }

    public function scopeCurrentVersions($query)
    {
        return $query->where('is_current_version', true);
    }

    public function scopeCollaborative($query)
    {
        return $query->where('allow_collaborative_editing', true);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority_level', ['high', 'urgent']);
    }

    public function scopeRecentlyActive($query, $hours = 24)
    {
        return $query->where('updated_at', '>=', now()->subHours($hours));
    }

    // Accessors and Mutators
    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'in_progress',
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'completed',
        );
    }

    public function actualDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->started_at && $this->completed_at) {
                    return $this->completed_at->diffInMinutes($this->started_at);
                }
                return $this->total_duration;
            },
        );
    }

    public function analysisTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'player_performance' => 'Spielerleistung',
                    'team_tactics' => 'Teamtaktik',
                    'opponent_scouting' => 'Gegnerscouting',
                    'drill_effectiveness' => 'Drill-Effektivität',
                    'game_breakdown' => 'Spielanalyse',
                    'skill_development' => 'Skillentwicklung',
                    'injury_analysis' => 'Verletzungsanalyse',
                    'referee_decisions' => 'Schiedsrichterentscheidungen',
                    'custom_analysis' => 'Benutzerdefinierte Analyse',
                ];
                
                return $types[$this->analysis_type] ?? $this->analysis_type;
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'planned' => 'Geplant',
                    'in_progress' => 'In Bearbeitung',
                    'paused' => 'Pausiert',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgebrochen',
                ];
                
                return $statuses[$this->status] ?? $this->status;
            },
        );
    }

    public function priorityDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $priorities = [
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Dringend',
                ];
                
                return $priorities[$this->priority_level] ?? $this->priority_level;
            },
        );
    }

    // Collaboration Methods
    public function addParticipant(User $user, string $role = 'viewer'): bool
    {
        $invitedUsers = $this->invited_users ?? [];
        $participantRoles = $this->participant_roles ?? [];
        
        if (!in_array($user->id, $invitedUsers)) {
            $invitedUsers[] = $user->id;
            $participantRoles[$user->id] = $role;
            
            return $this->update([
                'invited_users' => $invitedUsers,
                'participant_roles' => $participantRoles,
            ]);
        }
        
        return false;
    }

    public function removeParticipant(User $user): bool
    {
        $invitedUsers = array_filter($this->invited_users ?? [], fn($id) => $id !== $user->id);
        $participantRoles = $this->participant_roles ?? [];
        unset($participantRoles[$user->id]);
        
        return $this->update([
            'invited_users' => array_values($invitedUsers),
            'participant_roles' => $participantRoles,
        ]);
    }

    public function assignRole(User $user, string $role): bool
    {
        if (!$this->hasParticipant($user)) {
            return false;
        }
        
        $participantRoles = $this->participant_roles ?? [];
        $participantRoles[$user->id] = $role;
        
        return $this->update(['participant_roles' => $participantRoles]);
    }

    public function hasParticipant(User $user): bool
    {
        return in_array($user->id, $this->invited_users ?? []);
    }

    public function getParticipantRole(User $user): ?string
    {
        return $this->participant_roles[$user->id] ?? null;
    }

    public function canUserEdit(User $user): bool
    {
        if ($this->analyst_user_id === $user->id) {
            return true;
        }
        
        if (!$this->allow_collaborative_editing) {
            return false;
        }
        
        $role = $this->getParticipantRole($user);
        return in_array($role, ['editor', 'co_analyst']);
    }

    public function canUserView(User $user): bool
    {
        if ($this->analyst_user_id === $user->id) {
            return true;
        }
        
        return $this->hasParticipant($user);
    }

    public function markUserAsActive(User $user): void
    {
        $activeParticipants = $this->active_participants ?? [];
        $activeParticipants[$user->id] = now()->toISOString();
        
        $this->update(['active_participants' => $activeParticipants]);
    }

    public function markUserAsInactive(User $user): void
    {
        $activeParticipants = $this->active_participants ?? [];
        unset($activeParticipants[$user->id]);
        
        $this->update(['active_participants' => $activeParticipants]);
    }

    public function getActiveParticipants(): array
    {
        $activeParticipants = $this->active_participants ?? [];
        $cutoff = now()->subMinutes(5); // Consider active if seen in last 5 minutes
        
        return array_filter($activeParticipants, function ($timestamp) use ($cutoff) {
            return \Carbon\Carbon::parse($timestamp)->gt($cutoff);
        });
    }

    // Analysis Methods
    public function compileFindings(): array
    {
        $findings = [
            'key_findings' => $this->key_findings ?? [],
            'recommendations' => $this->recommendations ?? [],
            'action_items' => $this->action_items ?? [],
            'statistical_insights' => $this->statistical_insights ?? [],
            'tactical_observations' => $this->tactical_observations ?? [],
            'player_evaluations' => $this->player_evaluations ?? [],
        ];
        
        return array_filter($findings, fn($value) => !empty($value));
    }

    public function generateReport(): array
    {
        return [
            'session_info' => [
                'name' => $this->session_name,
                'type' => $this->analysis_type_display,
                'duration' => $this->actual_duration . ' Minuten',
                'analyst' => $this->analyst->full_name,
                'completed' => $this->completed_at?->format('d.m.Y H:i'),
            ],
            'analysis_summary' => $this->compileFindings(),
            'completeness' => $this->analysis_completeness . '%',
            'confidence' => $this->confidence_rating,
            'next_steps' => $this->follow_up_actions ?? [],
        ];
    }

    public function suggestDrills(): array
    {
        $suggestions = [];
        
        // Analyze improvement areas to suggest relevant drills
        foreach ($this->improvement_areas ?? [] as $area) {
            $drills = $this->findRelevantDrills($area);
            $suggestions = array_merge($suggestions, $drills);
        }
        
        return array_unique($suggestions, SORT_REGULAR);
    }

    private function findRelevantDrills(string $improvementArea): array
    {
        // This would integrate with the drill database
        $drillMappings = [
            'shooting' => ['shooting_form', 'catch_and_shoot', 'off_dribble_shooting'],
            'passing' => ['chest_pass', 'bounce_pass', 'outlet_passing'],
            'defense' => ['defensive_stance', 'close_out', 'help_defense'],
            'rebounding' => ['box_out', 'offensive_rebounding', 'outlet_passing'],
            'ball_handling' => ['stationary_dribbling', 'cone_dribbling', 'two_ball_dribbling'],
        ];
        
        return $drillMappings[$improvementArea] ?? [];
    }

    public function createActionItems(): array
    {
        $actionItems = [];
        
        // Generate action items based on findings
        foreach ($this->recommendations ?? [] as $recommendation) {
            $actionItems[] = [
                'title' => $recommendation['title'] ?? 'Umsetzung erforderlich',
                'description' => $recommendation['description'] ?? '',
                'priority' => $recommendation['priority'] ?? 'medium',
                'assigned_to' => $this->analyst_user_id,
                'due_date' => now()->addDays(7),
                'status' => 'pending',
            ];
        }
        
        return $actionItems;
    }

    // Presentation Methods
    public function buildSlideshow(): array
    {
        $slides = [];
        
        // Title slide
        $slides[] = [
            'type' => 'title',
            'title' => $this->session_name,
            'subtitle' => $this->analysis_type_display,
            'date' => $this->completed_at?->format('d.m.Y'),
        ];
        
        // Objectives slide
        if ($this->analysis_objectives) {
            $slides[] = [
                'type' => 'objectives',
                'title' => 'Analyseziele',
                'content' => $this->analysis_objectives,
            ];
        }
        
        // Key findings
        if ($this->key_findings) {
            $slides[] = [
                'type' => 'findings',
                'title' => 'Wichtigste Erkenntnisse',
                'items' => $this->key_findings,
            ];
        }
        
        // Recommendations
        if ($this->recommendations) {
            $slides[] = [
                'type' => 'recommendations',
                'title' => 'Empfehlungen',
                'items' => $this->recommendations,
            ];
        }
        
        // Action items
        if ($this->action_items) {
            $slides[] = [
                'type' => 'action_items',
                'title' => 'Nächste Schritte',
                'items' => $this->action_items,
            ];
        }
        
        return $slides;
    }

    public function exportToPDF(): string
    {
        // This would integrate with a PDF generation library
        // For now, return placeholder path
        return "exports/analysis_session_{$this->id}.pdf";
    }

    // Version Control Methods
    public function createNewVersion(): self
    {
        // Mark current version as not current
        $this->update(['is_current_version' => false]);
        
        // Create new version
        $newVersion = $this->replicate();
        $newVersion->previous_version_id = $this->id;
        $newVersion->version_number = $this->version_number + 1;
        $newVersion->is_current_version = true;
        $newVersion->created_at = now();
        $newVersion->save();
        
        return $newVersion;
    }

    public function getVersionHistory()
    {
        return self::where(function ($query) {
                $query->where('id', $this->id)
                      ->orWhere('previous_version_id', $this->id);
            })
            ->orderBy('version_number', 'desc')
            ->get();
    }

    // Helper Methods
    public function calculateCompleteness(): float
    {
        $totalSections = 8; // Based on analysis structure
        $completedSections = 0;
        
        $sections = [
            'key_findings', 'recommendations', 'action_items', 'summary_notes',
            'tactical_observations', 'player_evaluations', 'play_breakdowns', 'improvement_areas'
        ];
        
        foreach ($sections as $section) {
            if (!empty($this->$section)) {
                $completedSections++;
            }
        }
        
        return round(($completedSections / $totalSections) * 100, 2);
    }

    public function updateCompleteness(): void
    {
        $this->update(['analysis_completeness' => $this->calculateCompleteness()]);
    }

    public function start(): bool
    {
        if ($this->status !== 'planned') {
            return false;
        }
        
        return $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'total_duration' => $this->actual_duration,
        ]);
    }

    public function pause(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        return $this->update(['status' => 'paused']);
    }

    public function resume(): bool
    {
        if ($this->status !== 'paused') {
            return false;
        }
        
        return $this->update(['status' => 'in_progress']);
    }

    public function cancel(): bool
    {
        if ($this->is_completed) {
            return false;
        }
        
        return $this->update(['status' => 'cancelled']);
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logOnly([
                             'session_name', 'status', 'analysis_completeness', 
                             'presentation_ready', 'is_shareable'
                         ])
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }
}