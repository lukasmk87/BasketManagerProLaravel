<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GdprConsentRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'consentable_type',
        'consentable_id',
        'given_by_user_id',
        'consent_type',
        'consent_text',
        'consent_version',
        'consent_given',
        'consent_given_at',
        'consent_withdrawn_at',
        'collection_method',
        'ip_address',
        'user_agent',
        'form_data',
        'purposes',
        'data_categories',
        'expires_at',
        'is_minor',
        'subject_birth_date',
        'guardian_relationship',
        'parental_consent_verified',
        'evidence_files',
        'additional_notes',
        'is_active',
        'status',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'consent_withdrawn_at' => 'datetime',
        'form_data' => 'array',
        'purposes' => 'array',
        'data_categories' => 'array',
        'expires_at' => 'datetime',
        'is_minor' => 'boolean',
        'subject_birth_date' => 'date',
        'parental_consent_verified' => 'boolean',
        'evidence_files' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function givenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopeGiven($query)
    {
        return $query->where('consent_given', true);
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->orWhere('status', 'expired');
    }

    public function scopeForMinors($query)
    {
        return $query->where('is_minor', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    public function scopeExpiringWithin($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now()->addDays($days))
                     ->where('expires_at', '>', now());
    }

    public function scopeRequiringParentalConsent($query)
    {
        return $query->where('is_minor', true)
                     ->where('parental_consent_verified', false);
    }

    // Accessors
    public function isValid(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->consent_given || $this->status !== 'active') {
                    return false;
                }

                // Check expiration
                if ($this->expires_at && $this->expires_at->isPast()) {
                    return false;
                }

                // Check parental consent for minors
                if ($this->is_minor && !$this->parental_consent_verified) {
                    return false;
                }

                return true;
            }
        );
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->expires_at && $this->expires_at->isPast()
        );
    }

    public function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->expires_at) return null;
                return now()->diffInDays($this->expires_at, false);
            }
        );
    }

    public function consentDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->consent_given_at) return null;
                
                $endDate = $this->consent_withdrawn_at ?? now();
                return $this->consent_given_at->diffInDays($endDate);
            }
        );
    }

    public function requiresParentalConsent(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_minor) return false;
                
                // Check if subject is under 16 (GDPR threshold)
                if ($this->subject_birth_date) {
                    $age = $this->subject_birth_date->diffInYears(now());
                    return $age < 16;
                }
                
                return true; // Conservative approach if no birth date
            }
        );
    }

    public function subjectAge(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->subject_birth_date) return null;
                return $this->subject_birth_date->diffInYears(now());
            }
        );
    }

    // Helper Methods
    public function withdrawConsent(?User $withdrawnBy = null, string $reason = null): void
    {
        $this->update([
            'consent_given' => false,
            'consent_withdrawn_at' => now(),
            'status' => 'withdrawn',
            'additional_notes' => $this->additional_notes . "\n\nWithdrawn at " . now()->toDateTimeString() . 
                               ($reason ? " - Reason: {$reason}" : '') .
                               ($withdrawnBy ? " by {$withdrawnBy->name}" : ''),
        ]);

        // Log withdrawal
        activity()
            ->performedOn($this)
            ->causedBy($withdrawnBy)
            ->withProperties([
                'consent_type' => $this->consent_type,
                'subject_type' => $this->consentable_type,
                'subject_id' => $this->consentable_id,
                'reason' => $reason,
            ])
            ->log('consent_withdrawn');
    }

    public function renewConsent(string $newConsentText, string $newVersion, ?User $renewedBy = null): void
    {
        // Create new consent record to maintain audit trail
        $newConsent = $this->replicate(['id', 'created_at', 'updated_at']);
        $newConsent->fill([
            'consent_text' => $newConsentText,
            'consent_version' => $newVersion,
            'consent_given' => true,
            'consent_given_at' => now(),
            'consent_withdrawn_at' => null,
            'status' => 'active',
            'given_by_user_id' => $renewedBy?->id ?? $this->given_by_user_id,
        ]);
        $newConsent->save();

        // Mark current consent as superseded
        $this->update(['status' => 'superseded', 'is_active' => false]);
    }

    public function verifyParentalConsent(?User $verifiedBy = null, array $evidence = []): void
    {
        if (!$this->is_minor) {
            throw new \Exception('Parental consent verification only applies to minors');
        }

        $this->update([
            'parental_consent_verified' => true,
            'evidence_files' => array_merge($this->evidence_files ?? [], $evidence),
            'additional_notes' => $this->additional_notes . "\n\nParental consent verified at " . 
                               now()->toDateTimeString() . 
                               ($verifiedBy ? " by {$verifiedBy->name}" : ''),
        ]);
    }

    public function extendExpiry(Carbon $newExpiryDate, ?User $extendedBy = null): void
    {
        $this->update([
            'expires_at' => $newExpiryDate,
            'additional_notes' => $this->additional_notes . "\n\nExpiry extended to " . 
                               $newExpiryDate->toDateString() . 
                               ($extendedBy ? " by {$extendedBy->name}" : ''),
        ]);
    }

    public function addEvidence(array $files, string $description = null): void
    {
        $evidence = $this->evidence_files ?? [];
        $evidence[] = [
            'files' => $files,
            'description' => $description,
            'added_at' => now()->toISOString(),
            'added_by' => auth()->user()?->name,
        ];

        $this->update(['evidence_files' => $evidence]);
    }

    public function getConsentHistory(): array
    {
        return static::where('consentable_type', $this->consentable_type)
            ->where('consentable_id', $this->consentable_id)
            ->where('consent_type', $this->consent_type)
            ->orderBy('created_at')
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'version' => $record->consent_version,
                    'given' => $record->consent_given,
                    'given_at' => $record->consent_given_at,
                    'withdrawn_at' => $record->consent_withdrawn_at,
                    'status' => $record->status,
                    'collection_method' => $record->collection_method,
                    'given_by' => $record->givenBy?->name,
                ];
            })
            ->toArray();
    }

    public function generateConsentProof(): array
    {
        return [
            'consent_id' => $this->id,
            'subject' => [
                'type' => $this->consentable_type,
                'id' => $this->consentable_id,
            ],
            'consent_details' => [
                'type' => $this->consent_type,
                'version' => $this->consent_version,
                'given' => $this->consent_given,
                'given_at' => $this->consent_given_at?->toISOString(),
                'expires_at' => $this->expires_at?->toISOString(),
            ],
            'legal_basis' => [
                'collection_method' => $this->collection_method,
                'purposes' => $this->purposes,
                'data_categories' => $this->data_categories,
            ],
            'minor_protection' => [
                'is_minor' => $this->is_minor,
                'requires_parental_consent' => $this->requires_parental_consent,
                'parental_consent_verified' => $this->parental_consent_verified,
                'guardian_relationship' => $this->guardian_relationship,
            ],
            'evidence' => [
                'has_evidence_files' => !empty($this->evidence_files),
                'evidence_count' => count($this->evidence_files ?? []),
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent ? substr($this->user_agent, 0, 100) . '...' : null,
            ],
            'status' => [
                'current_status' => $this->status,
                'is_valid' => $this->is_valid,
                'is_expired' => $this->is_expired,
                'days_until_expiry' => $this->days_until_expiry,
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public static function getConsentTypes(): array
    {
        return [
            'emergency_contacts' => 'Emergency contact information storage and processing',
            'statistics_sharing' => 'Performance statistics sharing and analysis',
            'medical_information' => 'Medical and health information processing',
            'communication' => 'Communication and marketing preferences',
            'photo_video' => 'Photo and video usage rights',
            'third_party_sharing' => 'Third-party data sharing',
            'analytics' => 'Analytics and performance tracking',
            'cookies' => 'Cookie usage and tracking',
        ];
    }

    public static function cleanupExpiredRecords(int $daysAfterExpiry = 90): int
    {
        $cutoffDate = now()->subDays($daysAfterExpiry);
        
        return static::where('expires_at', '<', $cutoffDate)
            ->where('status', 'expired')
            ->delete();
    }

    // Automatically handle expired consents
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($record) {
            if ($record->expires_at && $record->expires_at->isPast() && $record->status === 'active') {
                $record->status = 'expired';
                $record->is_active = false;
            }
        });
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consent_type', 'consent_given', 'consent_version', 'status',
                'is_minor', 'parental_consent_verified'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Consent record {$eventName}: {$this->consent_type}")
            ->dontLogIfAttributesChangedOnly(['additional_notes']);
    }
}