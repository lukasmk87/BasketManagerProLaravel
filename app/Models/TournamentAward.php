<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TournamentAward extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'tournament_id',
        'award_name',
        'description',
        'award_type',
        'award_category',
        'recipient_team_id',
        'recipient_player_id',
        'recipient_coach_id',
        'recipient_name',
        'criteria',
        'statistics',
        'statistical_value',
        'statistical_unit',
        'selection_method',
        'voting_details',
        'selected_by_user_id',
        'selected_at',
        'presented',
        'presentation_date',
        'presentation_ceremony',
        'presentation_notes',
        'award_format',
        'award_sponsor',
        'award_value',
        'engraving_text',
        'photo_path',
        'press_release',
        'social_media_posts',
        'featured_on_website',
        'record_setting',
        'record_details',
        'comparison_data',
    ];

    protected $casts = [
        'criteria' => 'array',
        'statistics' => 'array',
        'statistical_value' => 'decimal:2',
        'voting_details' => 'array',
        'selected_at' => 'datetime',
        'presented' => 'boolean',
        'presentation_date' => 'datetime',
        'award_value' => 'decimal:2',
        'social_media_posts' => 'array',
        'featured_on_website' => 'boolean',
        'record_setting' => 'boolean',
        'comparison_data' => 'array',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function recipientTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'recipient_team_id');
    }

    public function recipientPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_player_id');
    }

    public function recipientCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_coach_id');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_user_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('award_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('award_category', $category);
    }

    public function scopeTeamAwards($query)
    {
        return $query->where('award_type', 'team_award');
    }

    public function scopeIndividualAwards($query)
    {
        return $query->where('award_type', 'individual_award');
    }

    public function scopePresented($query)
    {
        return $query->where('presented', true);
    }

    public function scopeNotPresented($query)
    {
        return $query->where('presented', false);
    }

    public function scopeRecordSetting($query)
    {
        return $query->where('record_setting', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured_on_website', true);
    }

    // Accessors
    public function isTeamAward(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->award_type === 'team_award',
        );
    }

    public function isIndividualAward(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->award_type === 'individual_award',
        );
    }

    public function isPresented(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->presented,
        );
    }

    public function isRecordSetting(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->record_setting,
        );
    }

    public function recipientName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->recipient_name) {
                    return $this->recipient_name;
                }

                if ($this->recipientTeam) {
                    return $this->recipientTeam->team->name;
                }

                if ($this->recipientPlayer) {
                    return $this->recipientPlayer->full_name;
                }

                if ($this->recipientCoach) {
                    return $this->recipientCoach->full_name;
                }

                return 'Unbekannt';
            },
        );
    }

    public function awardTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->award_type) {
                    'team_award' => 'Team-Auszeichnung',
                    'individual_award' => 'Individuelle Auszeichnung',
                    'special_recognition' => 'Besondere Anerkennung',
                    'statistical_award' => 'Statistische Auszeichnung',
                    'sportsmanship_award' => 'Fair-Play-Auszeichnung',
                    default => $this->award_type,
                };
            },
        );
    }

    public function awardCategoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->award_category) {
                    'champion' => 'Meister',
                    'runner_up' => 'Zweiter Platz',
                    'third_place' => 'Dritter Platz',
                    'mvp' => 'Wertvollster Spieler',
                    'best_player' => 'Bester Spieler',
                    'top_scorer' => 'Topscorer',
                    'best_defense' => 'Beste Verteidigung',
                    'most_rebounds' => 'Meiste Rebounds',
                    'most_assists' => 'Meiste Assists',
                    'most_steals' => 'Meiste Steals',
                    'most_blocks' => 'Meiste Blocks',
                    'best_coach' => 'Bester Trainer',
                    'sportsmanship' => 'Fair Play',
                    'most_improved' => 'Stärkste Verbesserung',
                    'rookie_of_tournament' => 'Newcomer des Turniers',
                    'all_tournament_team' => 'All-Tournament-Team',
                    default => $this->award_category,
                };
            },
        );
    }

    public function selectionMethodDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->selection_method) {
                    'automatic' => 'Automatisch',
                    'committee_vote' => 'Komitee-Abstimmung',
                    'fan_vote' => 'Fan-Abstimmung',
                    'peer_vote' => 'Spieler-Abstimmung',
                    'statistical' => 'Statistisch',
                    default => $this->selection_method,
                };
            },
        );
    }

    public function awardFormatDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->award_format) {
                    'trophy' => 'Pokal',
                    'medal' => 'Medaille',
                    'certificate' => 'Urkunde',
                    'plaque' => 'Plakette',
                    'other' => 'Sonstiges',
                    default => $this->award_format,
                };
            },
        );
    }

    public function statisticalValueDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->statistical_value) return null;

                $value = $this->statistical_value;
                $unit = $this->statistical_unit ?? '';

                return $unit ? "{$value} {$unit}" : (string) $value;
            },
        );
    }

    // Business Logic Methods
    public function selectRecipient(
        TournamentTeam $team = null,
        User $player = null,
        User $coach = null,
        string $customName = null,
        User $selectedBy = null
    ): void {
        $this->update([
            'recipient_team_id' => $team?->id,
            'recipient_player_id' => $player?->id,
            'recipient_coach_id' => $coach?->id,
            'recipient_name' => $customName,
            'selected_by_user_id' => $selectedBy?->id ?? auth()->id(),
            'selected_at' => now(),
        ]);
    }

    public function recordStatistics(array $stats, float $value = null, string $unit = null): void
    {
        $this->update([
            'statistics' => $stats,
            'statistical_value' => $value,
            'statistical_unit' => $unit,
        ]);
    }

    public function addVotingDetails(array $details): void
    {
        $this->update(['voting_details' => $details]);
    }

    public function present(
        \DateTime $date = null,
        string $ceremony = null,
        string $notes = null
    ): void {
        $this->update([
            'presented' => true,
            'presentation_date' => $date ?? now(),
            'presentation_ceremony' => $ceremony,
            'presentation_notes' => $notes,
        ]);
    }

    public function setPressRelease(string $release): void
    {
        $this->update(['press_release' => $release]);
    }

    public function addSocialMediaPost(string $platform, string $content, string $url = null): void
    {
        $posts = $this->social_media_posts ?? [];
        $posts[] = [
            'platform' => $platform,
            'content' => $content,
            'url' => $url,
            'posted_at' => now()->toISOString(),
        ];

        $this->update(['social_media_posts' => $posts]);
    }

    public function setEngraving(string $text): void
    {
        $this->update(['engraving_text' => $text]);
    }

    public function markAsRecord(string $details = null, array $comparisonData = null): void
    {
        $this->update([
            'record_setting' => true,
            'record_details' => $details,
            'comparison_data' => $comparisonData,
        ]);
    }

    public function featureOnWebsite(): void
    {
        $this->update(['featured_on_website' => true]);
    }

    public function unfeatured(): void
    {
        $this->update(['featured_on_website' => false]);
    }

    public function setSponsor(string $sponsor, float $value = null): void
    {
        $this->update([
            'award_sponsor' => $sponsor,
            'award_value' => $value,
        ]);
    }

    public function canBePresented(): bool
    {
        return !$this->presented && 
               $this->selected_at &&
               ($this->recipient_team_id || $this->recipient_player_id || 
                $this->recipient_coach_id || $this->recipient_name);
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logFillable()
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('certificates')
              ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }
}