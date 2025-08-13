<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmergencyContact extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'player_id', // New PRD field
        'name', // Legacy field
        'contact_name', // New PRD field
        'relationship',
        'primary_phone', // Legacy field
        'phone_number', // New PRD field
        'secondary_phone',
        'email',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'address_country',
        'latitude', // New PRD field
        'longitude', // New PRD field
        'distance_to_venue_km', // New PRD field
        'preferred_contact_method',
        'language',
        'is_primary',
        'is_active',
        'priority_order',
        'availability_schedule',
        'available_24_7', // New PRD field
        'work_phone',
        'work_hours',
        'alternate_contact_info', // New PRD field
        'can_authorize_medical_treatment',
        'medical_decisions_authorized', // New PRD field
        'has_medical_power_of_attorney',
        'has_medical_training', // New PRD field
        'medical_authorization_notes',
        'authorization_notes', // New PRD field
        'authorization_expires_at', // New PRD field
        'is_legal_guardian',
        'can_make_decisions',
        'legal_relationship',
        'can_pickup_player',
        'emergency_pickup_authorized', // New PRD field
        'pickup_notes',
        'qr_code_token',
        'qr_code_generated_at',
        'qr_code_expires_at',
        'qr_code_active',
        'qr_code_access_count',
        'qr_code_last_accessed',
        'emergency_instructions',
        'medical_notes',
        'special_considerations',
        'phone_verified',
        'email_verified',
        'last_verified_at',
        'verification_sent_at',
        'contact_attempts',
        'last_contacted_at',
        'contact_log',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_group_number',
        'family_doctor_name',
        'family_doctor_phone',
        'pediatrician_name',
        'pediatrician_phone',
        'additional_contacts',
        'consent_to_contact',
        'consent_to_share_medical_info',
        'consent_given_at',
        'consent_expires_at',
        'consent_given_by_user_id', // New PRD field
        'consent_details', // New PRD field
        'gdpr_consent',
        'gdpr_consent_at',
        'data_processing_consent',
        'encrypted_fields', // New PRD field
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'priority_order' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_to_venue_km' => 'integer',
        'availability_schedule' => 'array',
        'available_24_7' => 'boolean',
        'can_authorize_medical_treatment' => 'boolean',
        'medical_decisions_authorized' => 'boolean',
        'has_medical_power_of_attorney' => 'boolean',
        'has_medical_training' => 'boolean',
        'authorization_expires_at' => 'date',
        'is_legal_guardian' => 'boolean',
        'can_make_decisions' => 'boolean',
        'can_pickup_player' => 'boolean',
        'emergency_pickup_authorized' => 'boolean',
        'qr_code_generated_at' => 'datetime',
        'qr_code_expires_at' => 'datetime',
        'qr_code_active' => 'boolean',
        'qr_code_access_count' => 'integer',
        'qr_code_last_accessed' => 'datetime',
        'special_considerations' => 'array',
        'phone_verified' => 'boolean',
        'email_verified' => 'boolean',
        'last_verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'contact_attempts' => 'integer',
        'last_contacted_at' => 'datetime',
        'contact_log' => 'array',
        'additional_contacts' => 'array',
        'consent_to_contact' => 'boolean',
        'consent_to_share_medical_info' => 'boolean',
        'consent_given_at' => 'datetime',
        'consent_expires_at' => 'datetime',
        'consent_given_by_user_id' => 'integer',
        'gdpr_consent' => 'boolean',
        'gdpr_consent_at' => 'datetime',
        'data_processing_consent' => 'array',
        'encrypted_fields' => 'boolean',
        'metadata' => 'array',
        // Encrypted fields for sensitive data (PRD requirement)
        'phone_number' => 'encrypted',
        'secondary_phone' => 'encrypted',
        'email' => 'encrypted',
        'medical_notes' => 'encrypted',
        'emergency_instructions' => 'encrypted',
        'alternate_contact_info' => 'encrypted',
        'address_street' => 'encrypted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'qr_code_token',
        'phone_number',
        'secondary_phone', 
        'email',
        'medical_notes',
        'emergency_instructions',
        'alternate_contact_info',
        'address_street',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contact) {
            if (empty($contact->uuid)) {
                $contact->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the user this emergency contact belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the player this emergency contact belongs to (PRD requirement).
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the user who gave consent for this contact.
     */
    public function consentGivenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consent_given_by_user_id');
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active contacts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority_order');
    }

    /**
     * Scope a query to only include verified contacts.
     */
    public function scopeVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    /**
     * Scope a query to filter by relationship.
     */
    public function scopeByRelationship($query, string $relationship)
    {
        return $query->where('relationship', $relationship);
    }

    /**
     * Scope a query to only include contacts with consent.
     */
    public function scopeWithConsent($query)
    {
        return $query->where('consent_to_contact', true);
    }

    /**
     * Scope a query to only include available contacts.
     */
    public function scopeAvailableNow($query)
    {
        return $query->where(function ($q) {
            $q->where('available_24_7', true)
              ->orWhere(function ($subQuery) {
                  // Check if current time falls within availability hours
                  $currentHour = now()->format('H:i');
                  $subQuery->whereJsonContains('availability_schedule', $currentHour);
              });
        });
    }

    /**
     * Scope a query to only include medically authorized contacts.
     */
    public function scopeMedicallyAuthorized($query)
    {
        return $query->where('can_authorize_medical_treatment', true)
                     ->orWhere('medical_decisions_authorized', true);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_zip . ' ' . $this->address_city,
            $this->address_state,
            $this->address_country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the best contact phone number.
     */
    public function getBestPhoneAttribute(): string
    {
        return $this->primary_phone ?: $this->secondary_phone ?: $this->work_phone;
    }

    /**
     * Check if QR code is expired.
     */
    public function getQrCodeExpiredAttribute(): bool
    {
        return $this->qr_code_expires_at && $this->qr_code_expires_at->isPast();
    }

    /**
     * Check if contact can be reached now based on availability.
     */
    public function getIsAvailableNowAttribute(): bool
    {
        if (!$this->availability_schedule) {
            return true; // Always available if no schedule set
        }

        $now = now();
        $currentDay = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $schedule = $this->availability_schedule[$currentDay] ?? null;
        
        if (!$schedule || !isset($schedule['start']) || !isset($schedule['end'])) {
            return false;
        }

        return $currentTime >= $schedule['start'] && $currentTime <= $schedule['end'];
    }

    /**
     * Check if consent is expired.
     */
    public function getConsentExpiredAttribute(): bool
    {
        return $this->consent_expires_at && $this->consent_expires_at->isPast();
    }

    /**
     * Get contact verification status.
     */
    public function getVerificationStatusAttribute(): string
    {
        if ($this->phone_verified && $this->email_verified) {
            return 'fully_verified';
        } elseif ($this->phone_verified || $this->email_verified) {
            return 'partially_verified';
        }
        
        return 'unverified';
    }

    /**
     * Get days since last contact.
     */
    public function getDaysSinceLastContactAttribute(): ?int
    {
        return $this->last_contacted_at ? 
            $this->last_contacted_at->diffInDays(now()) : null;
    }

    /**
     * Get formatted display phone number (PRD requirement).
     */
    public function getDisplayPhoneNumberAttribute(): ?string
    {
        $phone = $this->phone_number ?? $this->primary_phone;
        if (!$phone) return null;
        
        // Format phone number for display (German format)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
        }
        return $phone;
    }

    /**
     * Get emergency access information for QR codes (PRD requirement).
     */
    public function getEmergencyAccessInfoAttribute(): array
    {
        return [
            'name' => $this->contact_name ?? $this->name,
            'relationship' => $this->relationship,
            'phone' => $this->display_phone_number,
            'secondary_phone' => $this->secondary_phone ? $this->formatPhoneNumber($this->secondary_phone) : null,
            'is_primary' => $this->is_primary,
            'priority' => $this->priority_order,
            'medical_training' => $this->has_medical_training,
            'pickup_authorized' => $this->emergency_pickup_authorized ?? $this->can_pickup_player,
            'medical_decisions' => $this->medical_decisions_authorized ?? $this->can_authorize_medical_treatment,
            'available_24_7' => $this->available_24_7,
            'special_instructions' => $this->emergency_instructions,
        ];
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Generate a new QR code token.
     */
    public function generateQRCode(int $expirationHours = 24): string
    {
        $token = Str::random(32);
        
        $this->update([
            'qr_code_token' => $token,
            'qr_code_generated_at' => now(),
            'qr_code_expires_at' => now()->addHours($expirationHours),
            'qr_code_active' => true,
        ]);

        return $token;
    }

    /**
     * Deactivate QR code.
     */
    public function deactivateQRCode(): void
    {
        $this->update([
            'qr_code_active' => false,
            'qr_code_expires_at' => now(),
        ]);
    }

    /**
     * Access QR code and log the access.
     */
    public function accessViaQRCode(User $accessor = null): bool
    {
        if (!$this->qr_code_active || $this->qr_code_expired) {
            return false;
        }

        $this->increment('qr_code_access_count');
        $this->update(['qr_code_last_accessed' => now()]);

        // Log the access
        $this->logContactAttempt('qr_code_access', [
            'accessor_id' => $accessor?->id,
            'accessor_name' => $accessor?->name,
            'access_time' => now(),
        ]);

        return true;
    }

    /**
     * Mark phone as verified.
     */
    public function verifyPhone(): void
    {
        $this->update([
            'phone_verified' => true,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Mark email as verified.
     */
    public function verifyEmail(): void
    {
        $this->update([
            'email_verified' => true,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Log a contact attempt.
     */
    public function logContactAttempt(string $method, array $details = []): void
    {
        $contactLog = $this->contact_log ?? [];
        
        $contactLog[] = array_merge([
            'method' => $method,
            'timestamp' => now(),
            'success' => true,
        ], $details);

        $this->update([
            'contact_log' => $contactLog,
            'contact_attempts' => $this->contact_attempts + 1,
            'last_contacted_at' => now(),
        ]);
    }

    /**
     * Get emergency contact information for QR code.
     */
    public function getEmergencyInfo(): array
    {
        return [
            'contact_name' => $this->name,
            'relationship' => $this->relationship,
            'primary_phone' => $this->primary_phone,
            'secondary_phone' => $this->secondary_phone,
            'email' => $this->email,
            'preferred_contact_method' => $this->preferred_contact_method,
            'medical_authorization' => $this->can_authorize_medical_treatment,
            'legal_guardian' => $this->is_legal_guardian,
            'emergency_instructions' => $this->emergency_instructions,
            'medical_notes' => $this->medical_notes,
            'insurance_info' => [
                'provider' => $this->insurance_provider,
                'policy_number' => $this->insurance_policy_number,
            ],
            'doctors' => [
                'family_doctor' => [
                    'name' => $this->family_doctor_name,
                    'phone' => $this->family_doctor_phone,
                ],
                'pediatrician' => [
                    'name' => $this->pediatrician_name,
                    'phone' => $this->pediatrician_phone,
                ],
            ],
            'player_info' => [
                'name' => $this->user->name,
                'date_of_birth' => $this->user->date_of_birth,
                'medical_conditions' => $this->user->medical_conditions,
                'allergies' => $this->user->allergies,
                'medications' => $this->user->medications,
                'blood_type' => $this->user->blood_type,
            ],
        ];
    }

    /**
     * Send emergency notification.
     */
    public function sendEmergencyNotification(string $message, array $details = []): bool
    {
        // This would integrate with your notification system
        // For now, we'll just log the attempt
        
        $this->logContactAttempt('emergency_notification', array_merge([
            'message' => $message,
            'urgent' => true,
        ], $details));

        return true;
    }

    /**
     * Update consent status.
     */
    public function updateConsent(array $consentData): void
    {
        $this->update(array_merge([
            'consent_given_at' => now(),
        ], $consentData));
    }

    /**
     * Check if contact is reachable.
     */
    public function isReachable(): bool
    {
        return $this->is_active && 
               $this->consent_to_contact && 
               !$this->consent_expired &&
               ($this->phone_verified || $this->email_verified);
    }

    /**
     * Check if authorization is valid (PRD requirement).
     */
    public function isAuthorizationValid(): bool
    {
        if (!$this->authorization_expires_at) {
            return true; // No expiration set
        }
        
        return $this->authorization_expires_at->isFuture();
    }

    /**
     * Check if contact needs verification (PRD requirement).
     */
    public function needsVerification(): bool
    {
        if (!$this->last_verified_at) {
            return true;
        }
        
        // Needs verification if last verified more than 6 months ago
        return $this->last_verified_at->diffInMonths(now()) > 6;
    }

    /**
     * Calculate distance to venue using Haversine formula (PRD requirement).
     */
    public function calculateDistanceToVenue(float $venueLat, float $venueLng): ?float
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }
        
        // Haversine formula for calculating distance
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($venueLat - $this->latitude);
        $dLng = deg2rad($venueLng - $this->longitude);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($venueLat)) *
             sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Check if contact is available at specific time (PRD requirement).
     */
    public function isAvailableAt(\DateTime $datetime): bool
    {
        if ($this->available_24_7) {
            return true;
        }
        
        if (!$this->availability_schedule) {
            return false;
        }
        
        $hour = $datetime->format('H:i');
        return in_array($hour, $this->availability_schedule);
    }

    /**
     * Update last contact result (PRD requirement).
     */
    public function updateLastContactResult(string $result): void
    {
        $this->update([
            'last_contacted_at' => now(),
            'last_contact_result' => $result,
        ]);
    }

    /**
     * Get contact statistics.
     */
    public function getContactStats(): array
    {
        return [
            'total_contacts' => $this->contact_attempts,
            'last_contacted' => $this->last_contacted_at,
            'days_since_last_contact' => $this->days_since_last_contact,
            'verification_status' => $this->verification_status,
            'qr_code_accesses' => $this->qr_code_access_count,
            'is_available_now' => $this->is_available_now,
            'consent_valid' => !$this->consent_expired,
        ];
    }

    /**
     * Set as primary contact (and unset others).
     */
    public function setAsPrimary(): void
    {
        // First, unset all other primary contacts for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Then set this one as primary
        $this->update([
            'is_primary' => true,
            'priority_order' => 1,
        ]);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'contact_name', 'name', 'relationship', 'phone_number', 'primary_phone', 
                'is_primary', 'is_active', 'can_authorize_medical_treatment',
                'medical_decisions_authorized', 'is_legal_guardian'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Emergency contact {$eventName}")
            ->dontLogIfAttributesChangedOnly(['last_contacted_at', 'last_contact_result']);
    }

    // ============================
    // GDPR COMPLIANCE METHODS (PRD REQUIREMENT)
    // ============================

    /**
     * Anonymize contact data per GDPR request (PRD requirement).
     */
    public function anonymize(): void
    {
        $this->update([
            'contact_name' => 'Anonymized Contact',
            'name' => 'Anonymized Contact',
            'phone_number' => null,
            'primary_phone' => null,
            'secondary_phone' => null,
            'email' => null,
            'medical_notes' => null,
            'emergency_instructions' => null,
            'alternate_contact_info' => null,
            'address_street' => null,
            'notes' => 'Contact anonymized per GDPR request at ' . now()->toDateTimeString(),
            'is_active' => false,
        ]);
    }

    /**
     * Export data for GDPR request (PRD requirement).
     */
    public function exportForGDPR(): array
    {
        return [
            'contact_information' => [
                'name' => $this->contact_name ?? $this->name,
                'phone' => $this->phone_number ?? $this->primary_phone,
                'secondary_phone' => $this->secondary_phone,
                'email' => $this->email,
                'relationship' => $this->relationship,
            ],
            'emergency_details' => [
                'is_primary' => $this->is_primary,
                'priority' => $this->priority_order,
                'medical_notes' => $this->medical_notes,
                'emergency_instructions' => $this->emergency_instructions,
                'has_medical_training' => $this->has_medical_training,
            ],
            'authorization' => [
                'emergency_pickup_authorized' => $this->emergency_pickup_authorized ?? $this->can_pickup_player,
                'medical_decisions_authorized' => $this->medical_decisions_authorized ?? $this->can_authorize_medical_treatment,
                'authorization_notes' => $this->authorization_notes ?? $this->medical_authorization_notes,
            ],
            'consent' => [
                'consent_given' => $this->consent_to_contact,
                'consent_given_at' => $this->consent_given_at,
                'consent_details' => $this->consent_details,
                'gdpr_consent' => $this->gdpr_consent,
                'gdpr_consent_at' => $this->gdpr_consent_at,
            ],
            'metadata' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'last_verified_at' => $this->last_verified_at,
                'last_contacted_at' => $this->last_contacted_at,
            ],
        ];
    }
}