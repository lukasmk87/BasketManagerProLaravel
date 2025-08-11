<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TournamentOfficial extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'role',
        'certification_level',
        'certifications',
        'experience_years',
        'available_dates',
        'available_times',
        'unavailable_periods',
        'max_games_per_day',
        'status',
        'response_deadline',
        'confirmed_at',
        'status_notes',
        'games_assigned',
        'games_completed',
        'assigned_games',
        'performance_rating',
        'total_ratings',
        'performance_notes',
        'technical_fouls_called',
        'ejections_made',
        'rate_per_game',
        'travel_allowance',
        'total_earnings',
        'payment_completed',
        'payment_method',
        'emergency_contact_name',
        'emergency_contact_phone',
        'dietary_restrictions',
        'accommodation_needs',
        'requires_transportation',
        'equipment_provided',
        'equipment_notes',
        'game_feedback',
        'punctuality_rating',
        'communication_rating',
        'professionalism_rating',
    ];

    protected $casts = [
        'certifications' => 'array',
        'experience_years' => 'integer',
        'available_dates' => 'array',
        'available_times' => 'array',
        'unavailable_periods' => 'array',
        'max_games_per_day' => 'integer',
        'response_deadline' => 'datetime',
        'confirmed_at' => 'datetime',
        'games_assigned' => 'integer',
        'games_completed' => 'integer',
        'assigned_games' => 'array',
        'performance_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'performance_notes' => 'array',
        'technical_fouls_called' => 'integer',
        'ejections_made' => 'integer',
        'rate_per_game' => 'decimal:2',
        'travel_allowance' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'payment_completed' => 'boolean',
        'requires_transportation' => 'boolean',
        'equipment_provided' => 'array',
        'game_feedback' => 'array',
        'punctuality_rating' => 'decimal:2',
        'communication_rating' => 'decimal:2',
        'professionalism_rating' => 'decimal:2',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInvited($query)
    {
        return $query->where('status', 'invited');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeReferees($query)
    {
        return $query->whereIn('role', ['head_referee', 'assistant_referee']);
    }

    public function scopeScorekeepers($query)
    {
        return $query->where('role', 'scorekeeper');
    }

    public function scopeTimekeepers($query)
    {
        return $query->where('role', 'timekeeper');
    }

    public function scopeHighRated($query)
    {
        return $query->where('performance_rating', '>=', 8.0)
                    ->where('total_ratings', '>=', 3);
    }

    public function scopeExperienced($query)
    {
        return $query->where('experience_years', '>=', 5);
    }

    // Accessors
    public function isConfirmed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'confirmed',
        );
    }

    public function isInvited(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'invited',
        );
    }

    public function isDeclined(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'declined',
        );
    }

    public function isReferee(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->role, ['head_referee', 'assistant_referee']),
        );
    }

    public function completionRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->games_assigned === 0) return 100;
                return ($this->games_completed / $this->games_assigned) * 100;
            },
        );
    }

    public function averageRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->performance_rating || !$this->punctuality_rating || !$this->communication_rating) {
                    return null;
                }
                
                return ($this->performance_rating + $this->punctuality_rating + 
                       $this->communication_rating + ($this->professionalism_rating ?? 0)) / 4;
            },
        );
    }

    public function roleDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->role) {
                    'head_referee' => 'Hauptschiedsrichter',
                    'assistant_referee' => 'Assistent-Schiedsrichter',
                    'scorekeeper' => 'Anschreiber',
                    'timekeeper' => 'Zeitnehmer',
                    'statistician' => 'Statistiker',
                    'announcer' => 'Ansager',
                    'media_coordinator' => 'Medienkoordinator',
                    'tournament_director' => 'Turnierleiter',
                    default => $this->role,
                };
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->status) {
                    'invited' => 'Eingeladen',
                    'confirmed' => 'Bestätigt',
                    'declined' => 'Abgelehnt',
                    'cancelled' => 'Abgesagt',
                    default => $this->status,
                };
            },
        );
    }

    public function experienceLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match(true) {
                    $this->experience_years <= 1 => 'Anfänger',
                    $this->experience_years <= 3 => 'Erfahren',
                    $this->experience_years <= 7 => 'Sehr erfahren',
                    $this->experience_years >= 8 => 'Experte',
                    default => 'Unbekannt',
                };
            },
        );
    }

    public function ratingDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->performance_rating) return null;
                
                return match(true) {
                    $this->performance_rating <= 3 => 'Ungenügend',
                    $this->performance_rating <= 5 => 'Ausreichend',
                    $this->performance_rating <= 7 => 'Gut',
                    $this->performance_rating <= 9 => 'Sehr gut',
                    $this->performance_rating <= 10 => 'Exzellent',
                    default => 'Unbewertet',
                };
            },
        );
    }

    // Business Logic Methods
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function decline(string $reason = null): void
    {
        $this->update([
            'status' => 'declined',
            'status_notes' => $reason,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'status_notes' => $reason,
        ]);
    }

    public function assignGame(int $gameId): void
    {
        $assignedGames = $this->assigned_games ?? [];
        if (!in_array($gameId, $assignedGames)) {
            $assignedGames[] = $gameId;
            $this->update([
                'assigned_games' => $assignedGames,
                'games_assigned' => count($assignedGames),
            ]);
        }
    }

    public function completeGame(int $gameId, array $feedback = null): void
    {
        $assignedGames = $this->assigned_games ?? [];
        if (in_array($gameId, $assignedGames)) {
            $this->increment('games_completed');
            
            if ($feedback) {
                $gameFeedback = $this->game_feedback ?? [];
                $gameFeedback[$gameId] = $feedback;
                $this->update(['game_feedback' => $gameFeedback]);
            }
        }
    }

    public function addPerformanceRating(
        float $rating,
        float $punctuality = null,
        float $communication = null,
        float $professionalism = null,
        string $notes = null
    ): void {
        // Calculate new averages
        $currentTotal = $this->performance_rating * $this->total_ratings;
        $newTotal = $currentTotal + $rating;
        $newCount = $this->total_ratings + 1;
        $newAverage = $newTotal / $newCount;

        $updateData = [
            'performance_rating' => $newAverage,
            'total_ratings' => $newCount,
        ];

        if ($punctuality) {
            $currentPunctuality = $this->punctuality_rating * $this->total_ratings;
            $newPunctuality = ($currentPunctuality + $punctuality) / $newCount;
            $updateData['punctuality_rating'] = $newPunctuality;
        }

        if ($communication) {
            $currentCommunication = $this->communication_rating * $this->total_ratings;
            $newCommunication = ($currentCommunication + $communication) / $newCount;
            $updateData['communication_rating'] = $newCommunication;
        }

        if ($professionalism) {
            $currentProfessionalism = $this->professionalism_rating * $this->total_ratings;
            $newProfessionalism = ($currentProfessionalism + $professionalism) / $newCount;
            $updateData['professionalism_rating'] = $newProfessionalism;
        }

        if ($notes) {
            $performanceNotes = $this->performance_notes ?? [];
            $performanceNotes[] = [
                'rating' => $rating,
                'notes' => $notes,
                'date' => now()->toDateString(),
            ];
            $updateData['performance_notes'] = $performanceNotes;
        }

        $this->update($updateData);
    }

    public function recordTechnicalFoul(): void
    {
        $this->increment('technical_fouls_called');
    }

    public function recordEjection(): void
    {
        $this->increment('ejections_made');
    }

    public function calculateEarnings(): void
    {
        $earnings = $this->games_completed * ($this->rate_per_game ?? 0);
        if ($this->travel_allowance) {
            $earnings += $this->travel_allowance;
        }
        
        $this->update(['total_earnings' => $earnings]);
    }

    public function markPaymentCompleted(string $method = null): void
    {
        $this->update([
            'payment_completed' => true,
            'payment_method' => $method,
        ]);
    }

    public function isAvailableOnDate($date): bool
    {
        if (!$this->available_dates) return true;
        
        $dateString = is_string($date) ? $date : $date->toDateString();
        return in_array($dateString, $this->available_dates);
    }

    public function hasReachedMaxGames($date): bool
    {
        // This would require checking the actual game assignments for the date
        // Implementation depends on how games are tracked
        return false; // Simplified for now
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logFillable()
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }
}