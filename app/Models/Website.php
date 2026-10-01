<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Website extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'url',
        'niche',
        'language',
        'business_description',
        'target_audience',
        'platform',
        'status',
        'credentials',
        'crawled_data',
        'settings',
        'last_crawled_at',
    ];

    protected $casts = [
        'credentials'     => 'encrypted:array',
        'crawled_data'    => 'array',
        'settings'        => 'array',
        'last_crawled_at' => 'datetime',
    ];

    protected $hidden = ['credentials'];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }

    // Accessors for credentials
    public function getWpUsernameAttribute(): ?string
    {
        return $this->credentials['wp_username'] ?? null;
    }

    public function getWpAppPasswordAttribute(): ?string
    {
        return $this->credentials['wp_app_password'] ?? null;
    }

    public function getApiEndpointAttribute(): ?string
    {
        return $this->credentials['api_endpoint'] ?? null;
    }

    public function getApiTokenAttribute(): ?string
    {
        return $this->credentials['api_token'] ?? null;
    }

    public function getFtpHostAttribute(): ?string
    {
        return $this->credentials['ftp_host'] ?? null;
    }

    public function getFtpUsernameAttribute(): ?string
    {
        return $this->credentials['ftp_username'] ?? null;
    }

    public function getFtpPasswordAttribute(): ?string
    {
        return $this->credentials['ftp_password'] ?? null;
    }

    public function getFtpPathAttribute(): string
    {
        return $this->credentials['ftp_path'] ?? '/public_html/';
    }

    // Helpers
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}