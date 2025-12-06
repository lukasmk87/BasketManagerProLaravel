<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EnterprisePageContent extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'section',
        'locale',
        'content',
        'is_published',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Available enterprise page sections.
     *
     * @var array<string>
     */
    public const SECTIONS = [
        'seo',
        'hero',
        'audience',
        'whitelabel',
        'multiclub',
        'federation',
        'usecases',
        'pricing',
        'faq',
        'contact',
    ];

    /**
     * Section labels for display.
     *
     * @var array<string, string>
     */
    public const SECTION_LABELS = [
        'seo' => 'SEO Metadaten',
        'hero' => 'Hero Bereich',
        'audience' => 'Zielgruppen',
        'whitelabel' => 'White-Label Features',
        'multiclub' => 'Multi-Club Management',
        'federation' => 'Verbandsintegration',
        'usecases' => 'Erfolgsgeschichten',
        'pricing' => 'Enterprise Preise',
        'faq' => 'FAQ',
        'contact' => 'Kontakt',
    ];

    /**
     * Section icons for display.
     *
     * @var array<string, string>
     */
    public const SECTION_ICONS = [
        'seo' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'hero' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        'audience' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'whitelabel' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
        'multiclub' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'federation' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'usecases' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'pricing' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'faq' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'contact' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    ];

    /**
     * Get the tenant that owns the enterprise page content.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'section';
    }

    /**
     * Scope a query to only include published content.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include content for a specific section.
     */
    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope a query to only include content for a specific tenant.
     */
    public function scopeForTenant($query, ?string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include content for a specific locale.
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to only include global content (no tenant).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['section', 'tenant_id', 'locale', 'is_published', 'content'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Publish the content.
     */
    public function publish(): bool
    {
        $this->is_published = true;
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Unpublish the content.
     */
    public function unpublish(): bool
    {
        $this->is_published = false;
        return $this->save();
    }

    /**
     * Check if content is for a specific tenant.
     */
    public function isForTenant(string $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Check if content is global (not tenant-specific).
     */
    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Get the label for a section.
     */
    public static function getSectionLabel(string $section): string
    {
        return self::SECTION_LABELS[$section] ?? ucfirst($section);
    }

    /**
     * Get the icon for a section.
     */
    public static function getSectionIcon(string $section): string
    {
        return self::SECTION_ICONS[$section] ?? 'M4 6h16M4 12h16M4 18h16';
    }
}
