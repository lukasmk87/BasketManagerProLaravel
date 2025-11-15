<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTransferLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'club_transfer_id',
        'step',
        'status',
        'message',
        'data',
        'duration_ms',
    ];

    protected $casts = [
        'data' => 'array',
        'duration_ms' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_STARTED = 'started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Step constants (for common steps)
     */
    const STEP_VALIDATION = 'validation';
    const STEP_ROLLBACK_SNAPSHOT = 'rollback_snapshot';
    const STEP_STRIPE_CANCELLATION = 'stripe_cancellation';
    const STEP_MEMBERSHIP_REMOVAL = 'membership_removal';
    const STEP_MEDIA_MIGRATION = 'media_migration';
    const STEP_CLUB_UPDATE = 'club_update';
    const STEP_RELATED_RECORDS_UPDATE = 'related_records_update';
    const STEP_CACHE_CLEAR = 'cache_clear';
    const STEP_COMPLETION = 'completion';
    const STEP_ROLLBACK = 'rollback';

    /**
     * Get the club transfer this log belongs to.
     */
    public function clubTransfer(): BelongsTo
    {
        return $this->belongsTo(ClubTransfer::class);
    }

    /**
     * Scopes
     */

    public function scopeForStep($query, string $step)
    {
        return $query->where('step', $step);
    }

    public function scopeStarted($query)
    {
        return $query->where('status', self::STATUS_STARTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    public function scopeOrderedByTime($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Helper methods
     */

    public function isStarted(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): ?string
    {
        if (!$this->duration_ms) {
            return null;
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        if ($this->duration_ms < 60000) {
            return round($this->duration_ms / 1000, 2) . 's';
        }

        $seconds = floor($this->duration_ms / 1000);
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes . 'm ' . $remainingSeconds . 's';
    }

    /**
     * Get specific data field.
     */
    public function getDataField(string $field, $default = null)
    {
        return data_get($this->data, $field, $default);
    }

    /**
     * Static helper to create log entry.
     */
    public static function createLog(
        string $clubTransferId,
        string $step,
        string $status,
        string $message,
        ?array $data = null,
        ?int $durationMs = null
    ): self {
        return self::create([
            'club_transfer_id' => $clubTransferId,
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'duration_ms' => $durationMs,
        ]);
    }
}
