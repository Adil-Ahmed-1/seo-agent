<?php

namespace App\Services\Publisher;

use App\Models\BlogPost;

interface PublisherInterface
{
    /**
     * Publish blog post to target website.
     * Returns: ['success' => bool, 'remote_id' => ?, 'remote_url' => ?, 'error' => ?]
     */
    public function publish(BlogPost $post): array;

    /**
     * Test connection with target website.
     */
    public function testConnection(): bool;
}