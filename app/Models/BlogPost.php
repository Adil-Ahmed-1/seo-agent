<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'website_id',
        'title',
        'slug',
        'content_html',
        'meta_description',
        'focus_keyword',
        'secondary_keywords',
        'featured_image',
        'images',
        'seo_analysis',
        'seo_score',
        'word_count',
        'status',
        'scheduled_at',
        'published_at',
        'remote_id',
        'remote_url',
        'error_message',
        'ai_model',
        'tokens_used',
        'cost',
    ];

    protected $casts = [
        'secondary_keywords' => 'array',
        'images'             => 'array',
        'seo_analysis'       => 'array',
        'scheduled_at'       => 'datetime',
        'published_at'       => 'datetime',
        'cost'               => 'decimal:4',
    ];

    // Relationships
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Helpers
    public static function generateSlug(string $title, int $websiteId): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (static::where('website_id', $websiteId)->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }
}