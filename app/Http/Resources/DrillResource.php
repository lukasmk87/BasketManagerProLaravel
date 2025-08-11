<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'instructions' => $this->instructions,
            
            // Classification
            'category' => $this->category,
            'category_display' => $this->category_display,
            'sub_category' => $this->sub_category,
            'sub_category_display' => $this->getSubCategoryDisplay(),
            'difficulty_level' => $this->difficulty_level,
            'difficulty_display' => $this->getDifficultyDisplay(),
            'age_group' => $this->age_group,
            'age_group_display' => $this->getAgeGroupDisplay(),
            
            // Logistics
            'players' => [
                'min_players' => $this->min_players,
                'max_players' => $this->max_players,
                'optimal_players' => $this->optimal_players,
                'player_count_range' => $this->player_count_range,
            ],
            'duration' => [
                'estimated_duration' => $this->estimated_duration,
                'display_duration' => $this->display_duration,
            ],
            'space_required' => $this->space_required,
            
            // Equipment and Court Requirements
            'equipment' => [
                'required_equipment' => $this->required_equipment,
                'optional_equipment' => $this->optional_equipment,
                'requires_full_court' => $this->requires_full_court,
                'requires_half_court' => $this->requires_half_court,
                'court_requirements' => $this->getCourtRequirements(),
            ],
            
            // Instructions and Guidance
            'guidance' => [
                'variations' => $this->variations,
                'progressions' => $this->progressions,
                'regressions' => $this->regressions,
                'coaching_points' => $this->coaching_points,
            ],
            
            // Evaluation
            'evaluation' => [
                'measurable_outcomes' => $this->measurable_outcomes,
                'success_criteria' => $this->success_criteria,
                'is_competitive' => $this->is_competitive,
                'scoring_system' => $this->when($this->is_competitive, $this->scoring_system),
            ],
            
            // Media
            'media' => [
                'diagram_path' => $this->diagram_path,
                'diagram_annotations' => $this->diagram_annotations,
                'has_video' => $this->has_video,
                'video_duration' => $this->when($this->has_video, $this->video_duration),
                'thumbnail_url' => $this->getFirstMediaUrl('thumbnails', 'thumbnail'),
                'diagram_url' => $this->getFirstMediaUrl('diagrams', 'thumb'),
            ],
            
            // Usage and Popularity
            'stats' => [
                'usage_count' => $this->usage_count,
                'average_rating' => $this->average_rating,
                'rating_count' => $this->rating_count,
                'is_featured' => $this->is_featured,
                'popularity_rank' => $this->getPopularityRank(),
            ],
            
            // Tags and Search
            'meta' => [
                'tags' => $this->tags,
                'search_keywords' => $this->search_keywords,
                'source' => $this->source,
                'author' => $this->author,
            ],
            
            // Status and Approval
            'status' => $this->status,
            'status_display' => $this->getStatusDisplay(),
            'is_approved' => $this->is_approved,
            'is_public' => $this->is_public,
            'can_be_used' => $this->can_be_used,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_notes' => $this->when($this->review_notes, $this->review_notes),
            
            // Creator Information
            'creator' => [
                'id' => $this->created_by_user_id,
                'name' => $this->whenLoaded('createdBy', fn() => $this->createdBy->full_name),
                'email' => $this->whenLoaded('createdBy', fn() => $this->createdBy->email),
            ],
            'reviewer' => $this->when($this->reviewed_by_user_id, [
                'id' => $this->reviewed_by_user_id,
                'name' => $this->whenLoaded('reviewedBy', fn() => $this->reviewedBy->full_name),
            ]),
            
            // Ratings (when loaded)
            'ratings' => $this->when($this->relationLoaded('ratings'), function () {
                return $this->ratings->map(function ($rating) {
                    return [
                        'id' => $rating->id,
                        'rating' => $rating->rating,
                        'comment' => $rating->comment,
                        'pros' => $rating->pros,
                        'cons' => $rating->cons,
                        'would_recommend' => $rating->would_recommend,
                        'effectiveness_rating' => $rating->effectiveness_rating,
                        'engagement_rating' => $rating->engagement_rating,
                        'difficulty_rating' => $rating->difficulty_rating,
                        'user' => [
                            'id' => $rating->user_id,
                            'name' => $rating->user->full_name ?? null,
                        ],
                        'created_at' => $rating->created_at->toISOString(),
                    ];
                });
            }),
            
            // User's rating and favorite status
            'user_interaction' => $this->when(auth()->check(), [
                'is_favorited' => $this->favorites()->where('user_id', auth()->id())->exists(),
                'user_rating' => $this->ratings()->where('user_id', auth()->id())->first()?->rating,
                'can_edit' => $this->canUserEdit(),
                'can_delete' => $this->canUserDelete(),
            ]),
            
            // Similar drills (when specifically requested)
            'similar_drills' => $this->when(
                $request->has('include_similar'),
                fn() => DrillResource::collection($this->getSimilarDrills())
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get sub-category display text.
     */
    private function getSubCategoryDisplay(): ?string
    {
        if (!$this->sub_category) return null;
        
        return match($this->sub_category) {
            'fundamental' => 'Grundlagen',
            'advanced' => 'Fortgeschritten',
            'position_specific' => 'Positionsspezifisch',
            'game_situation' => 'Spielsituation',
            'individual' => 'Individuell',
            'small_group' => 'Kleine Gruppe',
            'team' => 'Team',
            'competitive' => 'Wettkampf',
            default => $this->sub_category,
        };
    }

    /**
     * Get difficulty display text.
     */
    private function getDifficultyDisplay(): string
    {
        return match($this->difficulty_level) {
            'beginner' => 'Anfänger',
            'intermediate' => 'Fortgeschritten',
            'advanced' => 'Experte',
            'expert' => 'Profi',
            default => $this->difficulty_level,
        };
    }

    /**
     * Get age group display text.
     */
    private function getAgeGroupDisplay(): string
    {
        return match($this->age_group) {
            'all' => 'Alle Altersgruppen',
            'adult' => 'Erwachsene',
            default => $this->age_group,
        };
    }

    /**
     * Get court requirements summary.
     */
    private function getCourtRequirements(): string
    {
        if ($this->requires_full_court) {
            return 'Vollfeld erforderlich';
        }
        
        if ($this->requires_half_court) {
            return 'Halbfeld erforderlich';
        }
        
        return 'Kein spezieller Platz erforderlich';
    }

    /**
     * Get status display text.
     */
    private function getStatusDisplay(): string
    {
        return match($this->status) {
            'draft' => 'Entwurf',
            'pending_review' => 'Wartet auf Freigabe',
            'approved' => 'Freigegeben',
            'rejected' => 'Abgelehnt',
            'archived' => 'Archiviert',
            default => $this->status,
        };
    }

    /**
     * Get popularity rank based on usage count.
     */
    private function getPopularityRank(): string
    {
        $usage = $this->usage_count;
        
        if ($usage >= 100) return 'Sehr beliebt';
        if ($usage >= 50) return 'Beliebt';
        if ($usage >= 20) return 'Bekannt';
        if ($usage >= 5) return 'Erprobt';
        
        return 'Neu';
    }

    /**
     * Check if user can edit this drill.
     */
    private function canUserEdit(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->created_by_user_id === auth()->id() || 
               auth()->user()->can('edit-drills');
    }

    /**
     * Check if user can delete this drill.
     */
    private function canUserDelete(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->created_by_user_id === auth()->id() || 
               auth()->user()->can('delete-drills');
    }
}