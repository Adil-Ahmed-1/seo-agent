<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keyword extends Model
{
    protected $fillable = [
        'website_id',
        'keyword',
        'search_volume',
        'difficulty',
        'cpc',
        'intent',
        'current_rank',
        'target_rank',
        'last_checked_at',
        'status',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'cpc'             => 'decimal:2',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isRankingWell(): bool
    {
        return $this->current_rank !== null && $this->current_rank <= $this->target_rank;
    }
}