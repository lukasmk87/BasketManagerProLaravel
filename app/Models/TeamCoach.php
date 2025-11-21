<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TeamCoach Model
 *
 * Dedizierte Tabelle für Trainer-Zuordnungen zu Teams.
 * Ermöglicht Multi-Rollen: Ein Benutzer kann gleichzeitig Spieler UND Trainer sein.
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $role head_coach|assistant_coach
 * @property string|null $coaching_license
 * @property array|null $coaching_certifications
 * @property string|null $coaching_specialties
 * @property \Carbon\Carbon|null $joined_at
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read Team $team
 * @property-read User $user
 */
class TeamCoach extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_coaches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'coaching_license',
        'coaching_certifications',
        'coaching_specialties',
        'joined_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'coaching_certifications' => 'array',
        'joined_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the team that this coach assignment belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the user (coach) for this assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if this is a head coach assignment.
     *
     * @return bool
     */
    public function isHeadCoach(): bool
    {
        return $this->role === 'head_coach';
    }

    /**
     * Check if this is an assistant coach assignment.
     *
     * @return bool
     */
    public function isAssistantCoach(): bool
    {
        return $this->role === 'assistant_coach';
    }

    /**
     * Get the German label for the coach role.
     *
     * @return string
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'head_coach' => 'Haupttrainer',
            'assistant_coach' => 'Co-Trainer',
            default => $this->role,
        };
    }

    /**
     * Scope a query to only include head coaches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHeadCoaches($query)
    {
        return $query->where('role', 'head_coach');
    }

    /**
     * Scope a query to only include assistant coaches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssistantCoaches($query)
    {
        return $query->where('role', 'assistant_coach');
    }

    /**
     * Scope a query to only include active coaches.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
