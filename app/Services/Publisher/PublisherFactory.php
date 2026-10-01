<?php

namespace App\Services\Publisher;

use App\Models\Website;
use InvalidArgumentException;

class PublisherFactory
{
    public static function make(Website $website): PublisherInterface
    {
        return match ($website->platform) {
            'wordpress' => new WordPressPublisher($website),
            'laravel'   => new LaravelPublisher($website),
            'static'    => new StaticPublisher($website),
            default     => throw new InvalidArgumentException(
                "Unknown platform: {$website->platform}"
            ),
        };
    }
}