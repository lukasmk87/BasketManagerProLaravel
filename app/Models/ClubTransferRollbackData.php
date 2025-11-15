<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTransferRollbackData extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'club_transfer_rollback_data';

    protected $fillable = [
        'club_transfer_id',
        'table_name',
        'record_id',
        'record_data',
        'operation_type',
    ];

    protected $casts = [
        'record_data' => 'array',
    ];

    /**
     * Operation type constants
     */
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_CREATE = 'create';

    /**
     * Get the club transfer this rollback data belongs to.
     */
    public function clubTransfer(): BelongsTo
    {
        return $this->belongsTo(ClubTransfer::class);
    }

    /**
     * Scopes
     */

    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeForRecord($query, string $recordId)
    {
        return $query->where('record_id', $recordId);
    }

    public function scopeUpdates($query)
    {
        return $query->where('operation_type', self::OPERATION_UPDATE);
    }

    public function scopeDeletes($query)
    {
        return $query->where('operation_type', self::OPERATION_DELETE);
    }

    public function scopeCreates($query)
    {
        return $query->where('operation_type', self::OPERATION_CREATE);
    }

    /**
     * Helper methods
     */

    public function isUpdate(): bool
    {
        return $this->operation_type === self::OPERATION_UPDATE;
    }

    public function isDelete(): bool
    {
        return $this->operation_type === self::OPERATION_DELETE;
    }

    public function isCreate(): bool
    {
        return $this->operation_type === self::OPERATION_CREATE;
    }

    /**
     * Get specific field from record data.
     */
    public function getRecordField(string $field, $default = null)
    {
        return data_get($this->record_data, $field, $default);
    }

    /**
     * Static helper to create rollback snapshot.
     */
    public static function createSnapshot(
        string $clubTransferId,
        string $tableName,
        string $recordId,
        array $recordData,
        string $operationType = self::OPERATION_UPDATE
    ): self {
        return self::create([
            'club_transfer_id' => $clubTransferId,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'record_data' => $recordData,
            'operation_type' => $operationType,
        ]);
    }
}
