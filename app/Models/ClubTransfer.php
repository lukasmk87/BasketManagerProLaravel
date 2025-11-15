<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ClubTransfer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'club_id',
        'source_tenant_id',
        'target_tenant_id',
        'initiated_by',
        'status',
        'started_at',
        'completed_at',
        'failed_at',
        'rolled_back_at',
        'metadata',
        'can_rollback',
        'rollback_expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'rollback_expires_at' => 'datetime',
        'can_rollback' => 'boolean',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_ROLLED_BACK = 'rolled_back';

    /**
     * Get the club that was transferred.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the source tenant.
     */
    public function sourceTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'source_tenant_id');
    }

    /**
     * Get the target tenant.
     */
    public function targetTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'target_tenant_id');
    }

    /**
     * Get the user who initiated the transfer.
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the rollback data for this transfer.
     */
    public function rollbackData(): HasMany
    {
        return $this->hasMany(ClubTransferRollbackData::class);
    }

    /**
     * Get the logs for this transfer.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ClubTransferLog::class);
    }

    /**
     * Scopes
     */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRolledBack($query)
    {
        return $query->where('status', self::STATUS_ROLLED_BACK);
    }

    public function scopeCanRollback($query)
    {
        return $query->where('can_rollback', true)
            ->where('rollback_expires_at', '>', now())
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    /**
     * Helper methods
     */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRolledBack(): bool
    {
        return $this->status === self::STATUS_ROLLED_BACK;
    }

    public function canBeRolledBack(): bool
    {
        return $this->can_rollback
            && $this->rollback_expires_at
            && $this->rollback_expires_at->isFuture()
            && in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    public function markAsPending(): self
    {
        $this->update(['status' => self::STATUS_PENDING]);
        return $this;
    }

    public function markAsProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        return $this;
    }

    public function markAsFailed(?string $reason = null): self
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'metadata' => $metadata,
        ]);
        return $this;
    }

    public function markAsRolledBack(): self
    {
        $this->update([
            'status' => self::STATUS_ROLLED_BACK,
            'rolled_back_at' => now(),
            'can_rollback' => false,
        ]);
        return $this;
    }

    /**
     * Get formatted duration.
     */
    public function getDuration(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? $this->rolled_back_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get human readable duration.
     */
    public function getFormattedDuration(): ?string
    {
        $duration = $this->getDuration();

        if (!$duration) {
            return null;
        }

        if ($duration < 60) {
            return $duration . 's';
        }

        if ($duration < 3600) {
            return floor($duration / 60) . 'm ' . ($duration % 60) . 's';
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Get metadata value.
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value.
     */
    public function setMetadata(string $key, $value): self
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
        return $this;
    }

    /**
     * Add log entry (convenience method).
     */
    public function addLog(string $step, string $status, string $message, ?array $data = null, ?int $durationMs = null): ClubTransferLog
    {
        return $this->logs()->create([
            'step' => $step,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'duration_ms' => $durationMs,
        ]);
    }
}
