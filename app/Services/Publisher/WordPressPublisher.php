<?php

namespace App\Services\Publisher;

use App\Models\BlogPost;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressPublisher implements PublisherInterface
{
    public function __construct(protected Website $website) {}

    public function publish(BlogPost $post): array
    {
        try {
            $endpoint = rtrim($this->website->url, '/') . '/wp-json/wp/v2/posts';

            $payload = [
                'title'   => $post->title,
                'content' => $post->content_html,
                'slug'    => $post->slug,
                'status'  => 'publish',
                'excerpt' => $post->meta_description,
                'meta'    => [
                    '_yoast_wpseo_metadesc' => $post->meta_description,
                    '_yoast_wpseo_focuskw'  => $post->focus_keyword,
                ],
            ];

            $response = Http::withBasicAuth(
                $this->website->wp_username,
                $this->website->wp_app_password
            )->timeout(30)->post($endpoint, $payload);

            if ($response->failed()) {
                Log::error('WordPress publish failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error'   => 'WP API error: ' . $response->body(),
                ];
            }

            $data = $response->json();

            return [
                'success'    => true,
                'remote_id'  => (string) $data['id'],
                'remote_url' => $data['link'],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withBasicAuth(
                $this->website->wp_username,
                $this->website->wp_app_password
            )->timeout(10)->get(
                rtrim($this->website->url, '/') . '/wp-json/wp/v2/users/me'
            );

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}