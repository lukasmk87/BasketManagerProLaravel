<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GdprDataProcessingRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'activity_name',
        'activity_description',
        'processing_purpose',
        'legal_basis',
        'special_category_basis',
        'data_categories',
        'data_subjects',
        'recipients',
        'international_transfers',
        'transfer_destinations',
        'transfer_safeguards',
        'retention_period',
        'retention_criteria',
        'next_review_date',
        'technical_measures',
        'organizational_measures',
        'controller_user_id',
        'processor_details',
        'dpo_contact',
        'is_active',
        'last_reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'legal_basis' => 'array',
        'special_category_basis' => 'array',
        'data_categories' => 'array',
        'data_subjects' => 'array',
        'recipients' => 'array',
        'international_transfers' => 'boolean',
        'transfer_destinations' => 'array',
        'transfer_safeguards' => 'array',
        'next_review_date' => 'date',
        'technical_measures' => 'array',
        'organizational_measures' => 'array',
        'is_active' => 'boolean',
        'last_reviewed_at' => 'datetime',
    ];

    // Relationships
    public function controller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controller_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPurpose($query, string $purpose)
    {
        return $query->where('processing_purpose', $purpose);
    }

    public function scopeRequiringReview($query, int $days = 30)
    {
        return $query->where('next_review_date', '<=', now()->addDays($days));
    }

    public function scopeWithInternationalTransfers($query)
    {
        return $query->where('international_transfers', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_review_date', '<', now());
    }

    // Accessors
    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->next_review_date && $this->next_review_date->isPast()
        );
    }

    public function daysUntilReview(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->next_review_date) return null;
                return now()->diffInDays($this->next_review_date, false);
            }
        );
    }

    public function hasSpecialCategories(): Attribute
    {
        return Attribute::make(
            get: fn() => !empty($this->special_category_basis)
        );
    }

    public function complianceRiskLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $riskFactors = 0;
                
                // High risk factors
                if ($this->has_special_categories) $riskFactors += 2;
                if ($this->international_transfers) $riskFactors += 2;
                if ($this->is_overdue) $riskFactors += 3;
                if (in_array('children', $this->data_subjects ?? [])) $riskFactors += 2;
                
                // Medium risk factors
                if (!$this->last_reviewed_at) $riskFactors += 1;
                if ($this->days_until_review !== null && $this->days_until_review < 30) $riskFactors += 1;
                
                return match(true) {
                    $riskFactors >= 5 => 'high',
                    $riskFactors >= 3 => 'medium',
                    default => 'low'
                };
            }
        );
    }

    // Helper Methods
    public function getLegalBasisDescription(): array
    {
        $descriptions = [
            '6.1.a' => 'Consent of the data subject',
            '6.1.b' => 'Performance of a contract',
            '6.1.c' => 'Legal obligation',
            '6.1.d' => 'Vital interests',
            '6.1.e' => 'Public task',
            '6.1.f' => 'Legitimate interests',
        ];

        return collect($this->legal_basis ?? [])
            ->mapWithKeys(fn($basis) => [$basis => $descriptions[$basis] ?? $basis])
            ->toArray();
    }

    public function getSpecialCategoryBasisDescription(): array
    {
        $descriptions = [
            '9.2.a' => 'Explicit consent',
            '9.2.b' => 'Employment, social security and social protection law',
            '9.2.c' => 'Vital interests',
            '9.2.d' => 'Legitimate activities of foundation/association',
            '9.2.e' => 'Data manifestly made public',
            '9.2.f' => 'Legal claims',
            '9.2.g' => 'Substantial public interest',
            '9.2.h' => 'Medical purposes',
            '9.2.i' => 'Public health',
            '9.2.j' => 'Archiving, research and statistics',
        ];

        return collect($this->special_category_basis ?? [])
            ->mapWithKeys(fn($basis) => [$basis => $descriptions[$basis] ?? $basis])
            ->toArray();
    }

    public function markAsReviewed(?User $reviewer = null): void
    {
        $this->update([
            'last_reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer?->id ?? auth()->id(),
            'next_review_date' => $this->calculateNextReviewDate(),
        ]);
    }

    public function updateSecurityMeasures(array $technicalMeasures, array $organizationalMeasures): void
    {
        $this->update([
            'technical_measures' => $technicalMeasures,
            'organizational_measures' => $organizationalMeasures,
        ]);
    }

    public function addRecipient(string $category, string $description = null): void
    {
        $recipients = $this->recipients ?? [];
        $recipients[] = [
            'category' => $category,
            'description' => $description,
            'added_at' => now()->toISOString(),
        ];
        
        $this->update(['recipients' => $recipients]);
    }

    public function removeRecipient(string $category): void
    {
        $recipients = collect($this->recipients ?? [])
            ->filter(fn($recipient) => $recipient['category'] !== $category)
            ->values()
            ->toArray();
            
        $this->update(['recipients' => $recipients]);
    }

    public function addInternationalTransfer(string $destination, array $safeguards = []): void
    {
        $destinations = $this->transfer_destinations ?? [];
        $destinations[] = [
            'country' => $destination,
            'safeguards' => $safeguards,
            'added_at' => now()->toISOString(),
        ];
        
        $this->update([
            'international_transfers' => true,
            'transfer_destinations' => $destinations,
        ]);
    }

    public function generateComplianceReport(): array
    {
        return [
            'record_id' => $this->id,
            'activity_name' => $this->activity_name,
            'processing_purpose' => $this->processing_purpose,
            'compliance_status' => [
                'risk_level' => $this->compliance_risk_level,
                'is_overdue' => $this->is_overdue,
                'days_until_review' => $this->days_until_review,
                'has_special_categories' => $this->has_special_categories,
                'international_transfers' => $this->international_transfers,
            ],
            'legal_basis' => $this->getLegalBasisDescription(),
            'special_category_basis' => $this->getSpecialCategoryBasisDescription(),
            'data_categories' => $this->data_categories,
            'data_subjects' => $this->data_subjects,
            'security_measures' => [
                'technical' => count($this->technical_measures ?? []),
                'organizational' => count($this->organizational_measures ?? []),
            ],
            'retention' => [
                'period' => $this->retention_period,
                'criteria' => $this->retention_criteria,
            ],
            'last_review' => [
                'reviewed_at' => $this->last_reviewed_at?->toISOString(),
                'reviewed_by' => $this->reviewer?->name,
                'next_review_date' => $this->next_review_date?->toISOString(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function calculateNextReviewDate(): Carbon
    {
        // High-risk processing activities need more frequent reviews
        $months = match($this->compliance_risk_level) {
            'high' => 6,
            'medium' => 12,
            'low' => 18,
            default => 12
        };

        return now()->addMonths($months);
    }

    public static function getProcessingPurposes(): array
    {
        return [
            'club_management' => 'Club and team management',
            'emergency_contacts' => 'Emergency contact information',
            'game_statistics' => 'Game statistics and performance analysis',
            'training_records' => 'Training session records',
            'communication' => 'Communication with members',
            'legal_compliance' => 'Legal and regulatory compliance',
            'performance_analysis' => 'Performance analysis and reporting',
            'medical_information' => 'Medical and health information',
            'other' => 'Other purposes',
        ];
    }

    public static function getDataCategories(): array
    {
        return [
            'identity' => 'Identity data (name, date of birth, etc.)',
            'contact' => 'Contact information (email, phone, address)',
            'performance' => 'Performance and statistics data',
            'medical' => 'Medical and health information',
            'emergency' => 'Emergency contact details',
            'communication' => 'Communication preferences',
            'technical' => 'Technical data (IP address, browser, etc.)',
            'usage' => 'Usage data and analytics',
            'financial' => 'Financial and payment information',
            'location' => 'Location data',
        ];
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'activity_name', 'processing_purpose', 'legal_basis',
                'international_transfers', 'retention_period', 'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Data processing record {$eventName}: {$this->activity_name}")
            ->dontLogIfAttributesChangedOnly(['last_reviewed_at', 'reviewed_by_user_id']);
    }
}