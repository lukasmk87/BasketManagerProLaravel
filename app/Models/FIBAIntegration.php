<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIBA Integration Model
 *
 * Tracks FIBA Europe integration data and sync status for international
 * basketball players, competitions, and clubs
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $entity_type (player, team, club, competition, etc.)
 * @property string $entity_id (local entity ID)
 * @property string $fiba_id (FIBA system ID)
 * @property string $fiba_type (player_profile, team_registration, club_license, etc.)
 * @property array $fiba_data
 * @property string $sync_status
 * @property string|null $last_sync_at
 * @property string|null $last_error
 * @property int $sync_attempts
 * @property array|null $validation_errors
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class FIBAIntegration extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'fiba_integrations';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'fiba_id',
        'fiba_type',
        'fiba_data',
        'sync_status',
        'last_sync_at',
        'last_error',
        'sync_attempts',
        'validation_errors',
    ];

    protected $casts = [
        'fiba_data' => 'array',
        'validation_errors' => 'array',
        'last_sync_at' => 'datetime',
        'sync_attempts' => 'integer',
    ];

    /**
     * Possible sync statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCING = 'syncing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Entity types
     */
    public const ENTITY_PLAYER = 'player';
    public const ENTITY_TEAM = 'team';
    public const ENTITY_CLUB = 'club';
    public const ENTITY_COMPETITION = 'competition';
    public const ENTITY_REFEREE = 'referee';
    public const ENTITY_GAME = 'game';

    /**
     * FIBA types
     */
    public const FIBA_TYPE_PLAYER_PROFILE = 'player_profile';
    public const FIBA_TYPE_PLAYER_ELIGIBILITY = 'player_eligibility';
    public const FIBA_TYPE_TEAM_REGISTRATION = 'team_registration';
    public const FIBA_TYPE_CLUB_LICENSE = 'club_license';
    public const FIBA_TYPE_COMPETITION_REGISTRATION = 'competition_registration';
    public const FIBA_TYPE_REFEREE_CERTIFICATION = 'referee_certification';
    public const FIBA_TYPE_GAME_OFFICIAL = 'game_official';

    /**
     * Get the tenant that owns this integration
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by entity type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $entityType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by sync status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope to filter synced integrations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCED);
    }

    /**
     * Scope to filter failed integrations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter pending integrations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter by FIBA type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $fibaType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $fibaType)
    {
        return $query->where('fiba_type', $fibaType);
    }

    /**
     * Check if sync is required
     *
     * @return bool
     */
    public function needsSync(): bool
    {
        if ($this->sync_status === self::STATUS_PENDING) {
            return true;
        }

        if ($this->sync_status === self::STATUS_FAILED && $this->sync_attempts < 3) {
            return true;
        }

        // Check if data is stale (older than 48 hours for FIBA data)
        if ($this->sync_status === self::STATUS_SYNCED && $this->last_sync_at) {
            return $this->last_sync_at->diffInHours(now()) > 48;
        }

        return false;
    }

    /**
     * Check if integration is valid and synced
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->sync_status === self::STATUS_SYNCED && !empty($this->fiba_id);
    }

    /**
     * Check if player is eligible for international play
     *
     * @return bool
     */
    public function isPlayerEligible(): bool
    {
        if ($this->entity_type !== self::ENTITY_PLAYER || $this->fiba_type !== self::FIBA_TYPE_PLAYER_ELIGIBILITY) {
            return false;
        }

        $eligibilityData = $this->fiba_data['eligibility'] ?? [];
        return $eligibilityData['eligible'] ?? false;
    }

    /**
     * Get player nationalities for eligibility
     *
     * @return array
     */
    public function getPlayerNationalities(): array
    {
        if ($this->entity_type !== self::ENTITY_PLAYER) {
            return [];
        }

        $playerData = $this->fiba_data['player_data'] ?? [];
        $eligibilityData = $this->fiba_data['eligibility'] ?? [];

        $nationalities = [];
        
        if (isset($playerData['nationality'])) {
            $nationalities[] = $playerData['nationality'];
        }

        if (isset($eligibilityData['passport_countries'])) {
            $nationalities = array_merge($nationalities, $eligibilityData['passport_countries']);
        }

        return array_unique($nationalities);
    }

    /**
     * Get competition registration status
     *
     * @return string|null
     */
    public function getCompetitionStatus(): ?string
    {
        if ($this->fiba_type !== self::FIBA_TYPE_TEAM_REGISTRATION && 
            $this->fiba_type !== self::FIBA_TYPE_COMPETITION_REGISTRATION) {
            return null;
        }

        return $this->fiba_data['registration']['status'] ?? null;
    }

    /**
     * Mark as syncing
     *
     * @return void
     */
    public function markSyncing(): void
    {
        $this->update([
            'sync_status' => self::STATUS_SYNCING,
            'last_error' => null,
        ]);
    }

    /**
     * Mark as synced
     *
     * @param string $fibaId
     * @param array $fibaData
     * @return void
     */
    public function markSynced(string $fibaId, array $fibaData = []): void
    {
        $this->update([
            'fiba_id' => $fibaId,
            'fiba_data' => array_merge($this->fiba_data ?? [], $fibaData),
            'sync_status' => self::STATUS_SYNCED,
            'last_sync_at' => now(),
            'last_error' => null,
            'validation_errors' => null,
        ]);
    }

    /**
     * Mark as failed
     *
     * @param string $error
     * @param array $validationErrors
     * @return void
     */
    public function markFailed(string $error, array $validationErrors = []): void
    {
        $this->update([
            'sync_status' => self::STATUS_FAILED,
            'last_error' => $error,
            'sync_attempts' => $this->sync_attempts + 1,
            'validation_errors' => $validationErrors,
        ]);
    }

    /**
     * Reset sync status to pending
     *
     * @return void
     */
    public function resetSync(): void
    {
        $this->update([
            'sync_status' => self::STATUS_PENDING,
            'last_error' => null,
            'sync_attempts' => 0,
            'validation_errors' => null,
        ]);
    }

    /**
     * Get formatted status for display
     *
     * @return string
     */
    public function getFormattedStatusAttribute(): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_SYNCING => 'Synchronisiert',
            self::STATUS_SYNCED => 'Synchronisiert',
            self::STATUS_FAILED => 'Fehlgeschlagen',
            self::STATUS_EXPIRED => 'Abgelaufen',
        ];

        return $statusMap[$this->sync_status] ?? $this->sync_status;
    }

    /**
     * Get status color for UI
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        $colorMap = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_SYNCING => 'info',
            self::STATUS_SYNCED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
        ];

        return $colorMap[$this->sync_status] ?? 'secondary';
    }

    /**
     * Get entity type display name
     *
     * @return string
     */
    public function getEntityTypeDisplayAttribute(): string
    {
        $typeMap = [
            self::ENTITY_PLAYER => 'Spieler',
            self::ENTITY_TEAM => 'Team',
            self::ENTITY_CLUB => 'Verein',
            self::ENTITY_COMPETITION => 'Wettbewerb',
            self::ENTITY_REFEREE => 'Schiedsrichter',
            self::ENTITY_GAME => 'Spiel',
        ];

        return $typeMap[$this->entity_type] ?? $this->entity_type;
    }

    /**
     * Get FIBA type display name
     *
     * @return string
     */
    public function getFibaTypeDisplayAttribute(): string
    {
        $typeMap = [
            self::FIBA_TYPE_PLAYER_PROFILE => 'Spielerprofil',
            self::FIBA_TYPE_PLAYER_ELIGIBILITY => 'Spielberechtigung',
            self::FIBA_TYPE_TEAM_REGISTRATION => 'Team-Registrierung',
            self::FIBA_TYPE_CLUB_LICENSE => 'Vereinslizenz',
            self::FIBA_TYPE_COMPETITION_REGISTRATION => 'Wettbewerb-Anmeldung',
            self::FIBA_TYPE_REFEREE_CERTIFICATION => 'Schiedsrichter-Zertifizierung',
            self::FIBA_TYPE_GAME_OFFICIAL => 'Offizielles Spiel',
        ];

        return $typeMap[$this->fiba_type] ?? $this->fiba_type;
    }

    /**
     * Check if retry is allowed
     *
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->sync_status === self::STATUS_FAILED && $this->sync_attempts < 3;
    }

    /**
     * Get time since last sync
     *
     * @return string|null
     */
    public function getTimeSinceLastSyncAttribute(): ?string
    {
        if (!$this->last_sync_at) {
            return null;
        }

        return $this->last_sync_at->diffForHumans();
    }

    /**
     * Get international experience summary
     *
     * @return array
     */
    public function getInternationalExperienceAttribute(): array
    {
        if ($this->entity_type !== self::ENTITY_PLAYER) {
            return [];
        }

        $playerData = $this->fiba_data['player_data'] ?? [];
        
        return [
            'national_team_caps' => $playerData['national_team_caps'] ?? 0,
            'international_competitions' => $playerData['international_experience'] ?? [],
            'career_highlights' => $playerData['career_highlights'] ?? [],
        ];
    }

    /**
     * Create or update FIBA integration record
     *
     * @param string $tenantId
     * @param string $entityType
     * @param string $entityId
     * @param string $fibaType
     * @param array $fibaData
     * @return static
     */
    public static function createOrUpdate(
        string $tenantId,
        string $entityType,
        string $entityId,
        string $fibaType,
        array $fibaData = []
    ): self {
        return static::updateOrCreate([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'fiba_type' => $fibaType,
        ], [
            'fiba_data' => $fibaData,
            'sync_status' => self::STATUS_PENDING,
        ]);
    }
}