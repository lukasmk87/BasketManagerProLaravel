<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LandingPageContent extends Model
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
     * Available landing page sections.
     *
     * @var array<string>
     */
    public const SECTIONS = [
        'hero',
        'features',
        'pricing',
        'testimonials',
        'faq',
        'cta',
        'footer',
        'seo',
    ];

    /**
     * Get the tenant that owns the landing page content.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
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
    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
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
            ->logOnly(['section', 'tenant_id', 'is_published', 'content'])
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
    public function isForTenant(int $tenantId): bool
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
}
