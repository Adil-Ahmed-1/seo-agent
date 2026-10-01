<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Services\Publisher\PublisherFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishBlogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 3;

    public function __construct(public BlogPost $post) {}

    public function handle(): void
    {
        $post = $this->post;
        $post->update(['status' => 'publishing']);

        $startTime = microtime(true);

        try {
            $publisher = PublisherFactory::make($post->website);
            $result = $publisher->publish($post);

            $duration = round((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $post->update([
                    'status'       => 'published',
                    'remote_id'    => $result['remote_id'] ?? null,
                    'remote_url'   => $result['remote_url'] ?? null,
                    'published_at' => now(),
                    'error_message' => null,
                ]);

                $post->website->publishLogs()->create([
                    'blog_post_id' => $post->id,
                    'action'       => 'publish',
                    'status'       => 'success',
                    'platform'     => $post->website->platform,
                    'response'     => $result,
                    'duration_ms'  => $duration,
                ]);

                Log::info('Blog published', [
                    'post_id' => $post->id,
                    'url'     => $result['remote_url'] ?? null,
                ]);

            } else {
                throw new \Exception($result['error'] ?? 'Publishing failed');
            }

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            $post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $post->website->publishLogs()->create([
                'blog_post_id' => $post->id,
                'action'       => 'publish',
                'status'       => 'failed',
                'platform'     => $post->website->platform,
                'error_message' => $e->getMessage(),
                'duration_ms'  => $duration,
            ]);

            Log::error('Blog publish failed', [
                'post_id' => $post->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}