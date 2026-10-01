<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'subscription_plan',
        'blogs_used_this_month',
        'subscription_ends_at',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name) . '-' . Str::random(5);
            }
        });
    }

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'current_team_id');
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function blogPosts(): HasManyThrough
    {
        return $this->hasManyThrough(BlogPost::class, Website::class);
    }

    // Helpers
    public function hasReachedLimit(): bool
    {
        $plan = config("plans.{$this->subscription_plan}");

        if (! $plan) {
            return true;
        }

        if (($plan['blogs_per_month'] ?? 0) === -1) {
            return false;
        }

        return $this->blogs_used_this_month >= $plan['blogs_per_month'];
    }

    public function getPlanAttribute(): array
    {
        return config("plans.{$this->subscription_plan}", config('plans.free'));
    }
}