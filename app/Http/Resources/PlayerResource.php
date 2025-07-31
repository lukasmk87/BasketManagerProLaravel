<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            
            // Basic Information
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'display_name' => $this->display_name,
            
            // Personal Details
            'birth_date' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->birth_date
            ),
            'age' => $this->age,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'birth_place' => $this->birth_place,
            
            // Contact Information (restricted access)
            'email' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->email
            ),
            'phone' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->phone
            ),
            'parent_phone' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->parent_phone
            ),
            
            // Address (restricted access)
            'street' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->street
            ),
            'street_number' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->street_number
            ),
            'postal_code' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->postal_code
            ),
            'city' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->city
            ),
            'full_address' => $this->when(
                $request->user()?->can('viewContactInfo', $this->resource),
                $this->full_address
            ),
            
            // Basketball Information
            'jersey_number' => $this->jersey_number,
            'primary_position' => $this->primary_position,
            'secondary_positions' => $this->secondary_positions,
            'all_positions' => $this->all_positions,
            'position_display_name' => $this->getPositionDisplayName(),
            'preferred_position' => $this->preferred_position,
            
            // Physical Attributes
            'height_cm' => $this->height_cm,
            'height_feet' => $this->height_feet,
            'weight_kg' => $this->weight_kg,
            'bmi' => $this->bmi,
            'dominant_hand' => $this->dominant_hand,
            'shoe_size' => $this->shoe_size,
            
            // Experience & Background
            'started_playing' => $this->started_playing,
            'years_experience' => $this->years_experience,
            'basketball_experience' => $this->basketball_experience,
            'previous_teams' => $this->previous_teams,
            'achievements' => $this->achievements,
            
            // Team Information
            'team_id' => $this->team_id,
            'joined_team_at' => $this->joined_team_at,
            'contract_start' => $this->contract_start,
            'contract_end' => $this->contract_end,
            'is_captain' => $this->is_captain,
            'is_vice_captain' => $this->is_vice_captain,
            'is_starter' => $this->is_starter,
            'is_rookie' => $this->is_rookie,
            'status' => $this->status,
            
            // Player Ratings
            'shooting_rating' => $this->shooting_rating,
            'defense_rating' => $this->defense_rating,
            'passing_rating' => $this->passing_rating,
            'rebounding_rating' => $this->rebounding_rating,
            'speed_rating' => $this->speed_rating,
            'overall_rating' => $this->overall_rating,
            
            // Season Statistics
            'games_played' => $this->games_played,
            'games_started' => $this->games_started,
            'minutes_played' => $this->minutes_played,
            'points_scored' => $this->points_scored,
            'points_per_game' => $this->points_per_game,
            
            // Shooting Statistics
            'field_goals_made' => $this->field_goals_made,
            'field_goals_attempted' => $this->field_goals_attempted,
            'field_goal_percentage' => $this->field_goal_percentage,
            'three_pointers_made' => $this->three_pointers_made,
            'three_pointers_attempted' => $this->three_pointers_attempted,
            'three_point_percentage' => $this->three_point_percentage,
            'free_throws_made' => $this->free_throws_made,
            'free_throws_attempted' => $this->free_throws_attempted,
            'free_throw_percentage' => $this->free_throw_percentage,
            
            // Other Statistics
            'rebounds_offensive' => $this->rebounds_offensive,
            'rebounds_defensive' => $this->rebounds_defensive,
            'rebounds_total' => $this->rebounds_total,
            'rebounds_per_game' => $this->rebounds_per_game,
            'assists' => $this->assists,
            'assists_per_game' => $this->assists_per_game,
            'steals' => $this->steals,
            'blocks' => $this->blocks,
            'turnovers' => $this->turnovers,
            'fouls_personal' => $this->fouls_personal,
            'fouls_technical' => $this->fouls_technical,
            
            // Registration & Compliance
            'registration_number' => $this->registration_number,
            'is_registered' => $this->is_registered,
            'registered_at' => $this->registered_at,
            
            // Medical Information (highly restricted)
            'medical_conditions' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->medical_conditions
            ),
            'allergies' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->allergies
            ),
            'medications' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->medications
            ),
            'blood_type' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->blood_type
            ),
            'last_medical_check' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->last_medical_check
            ),
            'medical_clearance' => $this->medical_clearance,
            'medical_clearance_expires' => $this->medical_clearance_expires,
            'medical_clearance_expired' => $this->medical_clearance_expired,
            'emergency_medical_contact' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->emergency_medical_contact
            ),
            'emergency_medical_phone' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->emergency_medical_phone
            ),
            'preferred_hospital' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->preferred_hospital
            ),
            'medical_notes' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->medical_notes
            ),
            
            // Insurance Information (restricted access)
            'insurance_provider' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->insurance_provider
            ),
            'insurance_policy_number' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->insurance_policy_number
            ),
            'insurance_expires' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource),
                $this->insurance_expires
            ),
            'insurance_expired' => $this->insurance_expired,
            
            // Parent/Guardian Information (for minors)
            'parent_user_id' => $this->parent_user_id,
            'parent_name' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->parent_name
            ),
            'parent_email' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->parent_email
            ),
            'parent_phone_primary' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->parent_phone_primary
            ),
            'parent_phone_secondary' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->parent_phone_secondary
            ),
            'guardian_name' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->guardian_name
            ),
            'guardian_contact' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->guardian_contact
            ),
            'guardian_contacts' => $this->when(
                $this->isMinor() && $request->user()?->can('viewContactInfo', $this->resource),
                $this->guardian_contacts
            ),
            
            // Development & Training
            'training_focus_areas' => $this->training_focus_areas,
            'development_goals' => $this->development_goals,
            'coach_notes' => $this->when(
                $request->user()?->can('viewCoachNotes', $this->resource),
                $this->coach_notes
            ),
            
            // Academic Information (for student athletes)
            'school_name' => $this->school_name,
            'grade_level' => $this->grade_level,
            'gpa' => $this->when(
                $request->user()?->can('viewAcademicInfo', $this->resource),
                $this->gpa
            ),
            'academic_eligibility' => $this->academic_eligibility,
            
            // Social & Media
            'social_media' => $this->social_media,
            'allow_photos' => $this->allow_photos,
            'allow_media_interviews' => $this->allow_media_interviews,
            
            // Preferences & Settings
            'preferences' => $this->preferences,
            'dietary_restrictions' => $this->dietary_restrictions,
            'settings' => $this->settings,
            'notes' => $this->when(
                $request->user()?->can('viewNotes', $this->resource),
                $this->notes
            ),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'user_id' => $this->user_id,
            
            'team' => new TeamResource($this->whenLoaded('team')),
            
            'parent' => new UserResource($this->whenLoaded('parent')),
            
            'emergency_contacts' => $this->when(
                $request->user()?->can('viewEmergencyContacts', $this->resource),
                EmergencyContactResource::collection($this->whenLoaded('emergencyContacts'))
            ),
            
            // Helper Method Results
            'is_minor' => $this->isMinor(),
            'can_play' => $this->canPlay(),
            'has_valid_consents' => $this->hasValidConsents(),
            'is_eligible_to_play' => $this->isEligibleToPlay(),
            
            // Comprehensive Statistics Summary
            'statistics_summary' => $this->when(
                $request->has('include_stats_summary'),
                fn() => $this->getStatistics()
            ),
            
            // Emergency Contacts Summary (for quick access)
            'emergency_contacts_summary' => $this->when(
                $request->user()?->can('viewEmergencyContacts', $this->resource),
                fn() => $this->getEmergencyContacts()
            ),
            
            // Media Collections
            'profile_photo_url' => $this->when(
                $this->hasMedia('profile_photos'),
                fn() => $this->getFirstMediaUrl('profile_photos')
            ),
            'profile_photo_thumb' => $this->when(
                $this->hasMedia('profile_photos'),
                fn() => $this->getFirstMediaUrl('profile_photos', 'thumb')
            ),
            
            'medical_documents' => $this->when(
                $request->user()?->can('viewMedicalInfo', $this->resource) && $this->hasMedia('medical_documents'),
                fn() => $this->getMedia('medical_documents')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                    ];
                })
            ),
            
            'game_photos' => $this->when(
                $this->hasMedia('game_photos'),
                fn() => $this->getMedia('game_photos')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'url' => $media->getUrl(),
                        'thumb_url' => $media->getUrl('thumb'),
                    ];
                })
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}