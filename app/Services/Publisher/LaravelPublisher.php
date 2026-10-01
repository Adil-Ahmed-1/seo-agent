<?php

namespace App\Services\Publisher;

use App\Models\BlogPost;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LaravelPublisher implements PublisherInterface
{
    public function __construct(protected Website $website) {}

    public function publish(BlogPost $post): array
    {
        try {
            $endpoint = rtrim($this->website->api_endpoint, '/') . '/api/blog/receive';

            $response = Http::withToken($this->website->api_token)
                ->timeout(30)
                ->post($endpoint, [
                    'title'            => $post->title,
                    'slug'             => $post->slug,
                    'content'          => $post->content_html,
                    'meta_description' => $post->meta_description,
                    'focus_keyword'    => $post->focus_keyword,
                    'featured_image'   => $post->featured_image,
                ]);

            if ($response->failed()) {
                Log::error('Laravel publish failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error'   => 'API error: ' . $response->body(),
                ];
            }

            $data = $response->json();

            return [
                'success'    => true,
                'remote_id'  => (string) ($data['id'] ?? ''),
                'remote_url' => $data['url'] ?? '',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withToken($this->website->api_token)
                ->timeout(10)
                ->get(rtrim($this->website->api_endpoint, '/') . '/api/user');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}