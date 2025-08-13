<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DBB Integration Model
 * 
 * Tracks DBB (Deutscher Basketball Bund) integration data and sync status
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $entity_type (player, team, game, etc.)
 * @property string $entity_id (local entity ID)
 * @property string $dbb_id (DBB system ID)
 * @property string $dbb_type (player_license, team_registration, game_result, etc.)
 * @property array $dbb_data
 * @property string $sync_status
 * @property string|null $last_sync_at
 * @property string|null $last_error
 * @property int $sync_attempts
 * @property array|null $validation_errors
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class DBBIntegration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dbb_integrations';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'dbb_id',
        'dbb_type',
        'dbb_data',
        'sync_status',
        'last_sync_at',
        'last_error',
        'sync_attempts',
        'validation_errors',
    ];

    protected $casts = [
        'dbb_data' => 'array',
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
    public const ENTITY_GAME = 'game';
    public const ENTITY_CLUB = 'club';
    public const ENTITY_LEAGUE = 'league';

    /**
     * DBB types
     */
    public const DBB_TYPE_PLAYER_LICENSE = 'player_license';
    public const DBB_TYPE_TEAM_REGISTRATION = 'team_registration';
    public const DBB_TYPE_GAME_RESULT = 'game_result';
    public const DBB_TYPE_CLUB_MEMBERSHIP = 'club_membership';
    public const DBB_TYPE_LEAGUE_PARTICIPATION = 'league_participation';

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

        // Check if data is stale (older than 24 hours)
        if ($this->sync_status === self::STATUS_SYNCED && $this->last_sync_at) {
            return $this->last_sync_at->diffInHours(now()) > 24;
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
        return $this->sync_status === self::STATUS_SYNCED && !empty($this->dbb_id);
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
     * @param string $dbbId
     * @param array $dbbData
     * @return void
     */
    public function markSynced(string $dbbId, array $dbbData = []): void
    {
        $this->update([
            'dbb_id' => $dbbId,
            'dbb_data' => array_merge($this->dbb_data ?? [], $dbbData),
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
            self::ENTITY_GAME => 'Spiel',
            self::ENTITY_CLUB => 'Verein',
            self::ENTITY_LEAGUE => 'Liga',
        ];

        return $typeMap[$this->entity_type] ?? $this->entity_type;
    }

    /**
     * Get DBB type display name
     *
     * @return string
     */
    public function getDbbTypeDisplayAttribute(): string
    {
        $typeMap = [
            self::DBB_TYPE_PLAYER_LICENSE => 'Spielerlizenz',
            self::DBB_TYPE_TEAM_REGISTRATION => 'Team-Registrierung',
            self::DBB_TYPE_GAME_RESULT => 'Spielergebnis',
            self::DBB_TYPE_CLUB_MEMBERSHIP => 'Vereinsmitgliedschaft',
            self::DBB_TYPE_LEAGUE_PARTICIPATION => 'Liga-Teilnahme',
        ];

        return $typeMap[$this->dbb_type] ?? $this->dbb_type;
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
     * Create or update DBB integration record
     *
     * @param string $tenantId
     * @param string $entityType
     * @param string $entityId
     * @param string $dbbType
     * @param array $dbbData
     * @return static
     */
    public static function createOrUpdate(
        string $tenantId,
        string $entityType,
        string $entityId,
        string $dbbType,
        array $dbbData = []
    ): self {
        return static::updateOrCreate([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'dbb_type' => $dbbType,
        ], [
            'dbb_data' => $dbbData,
            'sync_status' => self::STATUS_PENDING,
        ]);
    }
}