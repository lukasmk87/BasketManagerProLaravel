<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegalPage extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_description',
        'is_published',
    ];

    /**
     * Get the attributes that should be cast.
     * Uses PurifyHtmlOnGet for XSS protection if the package is available.
     */
    protected function casts(): array
    {
        $casts = [
            'is_published' => 'boolean',
        ];

        // Conditional cast: Only use PurifyHtmlOnGet if the package is installed
        if (class_exists(\Stevebauman\Purify\Casts\PurifyHtmlOnGet::class)) {
            $casts['content'] = \Stevebauman\Purify\Casts\PurifyHtmlOnGet::class;
        }

        return $casts;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'is_published'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the page's URL.
     */
    public function getUrlAttribute(): string
    {
        $slugMap = [
            'privacy' => 'datenschutz',
            'terms' => 'agb',
            'imprint' => 'impressum',
            'gdpr' => 'gdpr',
        ];

        $localizedSlug = $slugMap[$this->slug] ?? $this->slug;

        return route('legal.show', $localizedSlug);
    }

    /**
     * Get a short excerpt from the content.
     */
    public function getExcerptAttribute(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content), 150);
    }
}
