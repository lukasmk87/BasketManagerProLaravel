<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EnterpriseLead extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Organization types available for enterprise leads.
     */
    public const ORGANIZATION_TYPES = [
        'verband' => 'Verband',
        'grossverein' => 'Großverein',
        'akademie' => 'Akademie',
        'sonstige' => 'Sonstige',
    ];

    /**
     * Lead status options.
     */
    public const STATUSES = [
        'new' => 'Neu',
        'contacted' => 'Kontaktiert',
        'qualified' => 'Qualifiziert',
        'proposal' => 'Angebot',
        'won' => 'Gewonnen',
        'lost' => 'Verloren',
    ];

    /**
     * Club count options for the form.
     */
    public const CLUB_COUNT_OPTIONS = [
        '1-10' => '1-10 Vereine',
        '11-50' => '11-50 Vereine',
        '51-100' => '51-100 Vereine',
        '100+' => 'Mehr als 100 Vereine',
    ];

    /**
     * Team count options for the form.
     */
    public const TEAM_COUNT_OPTIONS = [
        '1-20' => '1-20 Teams',
        '21-50' => '21-50 Teams',
        '51-100' => '51-100 Teams',
        '101-200' => '101-200 Teams',
        '200+' => 'Mehr als 200 Teams',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_name',
        'organization_type',
        'contact_name',
        'email',
        'phone',
        'club_count',
        'team_count',
        'message',
        'gdpr_accepted',
        'newsletter_optin',
        'status',
        'notes',
        'assigned_to',
        'contacted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gdpr_accepted' => 'boolean',
        'newsletter_optin' => 'boolean',
        'contacted_at' => 'datetime',
    ];

    /**
     * Get the user assigned to this lead.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope a query to only include leads with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include new leads.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope a query to only include leads of a specific organization type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('organization_type', $type);
    }

    /**
     * Scope a query to filter unassigned leads.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'assigned_to', 'notes', 'contacted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Mark the lead as contacted.
     */
    public function markAsContacted(): bool
    {
        $this->status = 'contacted';
        $this->contacted_at = now();
        return $this->save();
    }

    /**
     * Update the lead status.
     */
    public function updateStatus(string $status): bool
    {
        if (!array_key_exists($status, self::STATUSES)) {
            return false;
        }

        $this->status = $status;
        return $this->save();
    }

    /**
     * Assign the lead to a user.
     */
    public function assignTo(User $user): bool
    {
        $this->assigned_to = $user->id;
        return $this->save();
    }

    /**
     * Check if the lead is new.
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /**
     * Check if the lead is open (not won or lost).
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, ['won', 'lost']);
    }

    /**
     * Get the organization type label.
     */
    public function getOrganizationTypeLabel(): string
    {
        return self::ORGANIZATION_TYPES[$this->organization_type] ?? $this->organization_type;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
