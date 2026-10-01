<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Models\Website;
use App\Services\Content\BlogWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateBlogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public Website $website,
        public string $keyword,
        public array $options = [],
    ) {}

    public function handle(BlogWriter $writer): void
    {
        Log::info("GenerateBlogJob started", [
            'website_id' => $this->website->id,
            'keyword'    => $this->keyword,
        ]);

        try {
            $post = $writer->generate($this->website, $this->keyword, $this->options);

            Log::info("GenerateBlogJob completed", [
                'post_id'    => $post->id,
                'word_count' => $post->word_count,
                'seo_score'  => $post->seo_score,
            ]);

            // Auto-publish if enabled
            if ($this->options['auto_publish'] ?? false) {
                PublishBlogJob::dispatch($post);
            }
        } catch (\Exception $e) {
            Log::error("GenerateBlogJob failed", [
                'website_id' => $this->website->id,
                'keyword'    => $this->keyword,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [60, 120];
    }
}