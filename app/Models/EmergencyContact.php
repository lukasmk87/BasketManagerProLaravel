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
        'name',
        'relationship',
        'primary_phone',
        'secondary_phone',
        'email',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'address_country',
        'preferred_contact_method',
        'language',
        'is_primary',
        'is_active',
        'priority_order',
        'availability_schedule',
        'work_phone',
        'work_hours',
        'can_authorize_medical_treatment',
        'has_medical_power_of_attorney',
        'medical_authorization_notes',
        'is_legal_guardian',
        'can_make_decisions',
        'legal_relationship',
        'can_pickup_player',
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
        'gdpr_consent',
        'gdpr_consent_at',
        'data_processing_consent',
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
        'availability_schedule' => 'array',
        'can_authorize_medical_treatment' => 'boolean',
        'has_medical_power_of_attorney' => 'boolean',
        'is_legal_guardian' => 'boolean',
        'can_make_decisions' => 'boolean',
        'can_pickup_player' => 'boolean',
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
        'gdpr_consent' => 'boolean',
        'gdpr_consent_at' => 'datetime',
        'data_processing_consent' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'qr_code_token',
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
                'name', 'relationship', 'primary_phone', 'email',
                'is_primary', 'is_active', 'can_authorize_medical_treatment',
                'is_legal_guardian'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}