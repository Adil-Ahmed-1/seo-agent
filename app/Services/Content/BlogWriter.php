<?php

namespace App\Services\Content;

use App\Models\BlogPost;
use App\Models\Website;
use App\Services\AI\AIClient;
use App\Services\AI\PromptBuilder;
use Illuminate\Support\Facades\Log;

class BlogWriter
{
    public function __construct(
        protected AIClient $ai,
        protected SEOOptimizer $seo,
    ) {}

    /**
     * Full pipeline: Outline → Blog → SEO → Save
     */
    public function generate(Website $website, string $keyword, array $options = []): BlogPost
    {
        // Step 1: Create draft record
        $post = BlogPost::create([
            'website_id'       => $website->id,
            'title'            => 'Generating...',
            'slug'             => 'temp-' . uniqid(),
            'content_html'     => '',
            'meta_description' => '',
            'focus_keyword'    => $keyword,
            'status'           => 'generating',
        ]);

        try {
            Log::info("Blog generation started", ['website' => $website->id, 'keyword' => $keyword]);

            // Step 2: Generate outline
            $outline = $this->generateOutline($website, $keyword);
            Log::info("Outline generated", ['title' => $outline['title'] ?? 'N/A']);

            // Step 3: Write full blog
            $blog = $this->writeBlog($website, $outline, $options);
            Log::info("Blog written", ['words' => str_word_count(strip_tags($blog['content']))]);

            // Step 4: SEO analysis
            $analysis = $this->seo->analyze(
                $blog['content'],
                $keyword,
                $outline['meta_description'],
                $outline['title']
            );

            // Step 5: Save everything
            $post->update([
                'title'              => $outline['title'],
                'slug'               => BlogPost::generateSlug($outline['title'], $website->id),
                'meta_description'   => $outline['meta_description'],
                'secondary_keywords' => $outline['secondary_keywords'] ?? [],
                'content_html'       => $blog['content'],
                'word_count'         => $analysis['word_count'],
                'seo_analysis'       => $analysis,
                'seo_score'          => $analysis['seo_score'],
                'status'             => $options['auto_publish'] ?? false ? 'approved' : 'pending_review',
                'ai_model'           => $blog['model'],
                'tokens_used'        => $blog['tokens'],
                'cost'               => $blog['cost'],
            ]);

            // Update team usage
            if ($website->team) {
                $website->team->increment('blogs_used_this_month');
            }

            Log::info("Blog generation complete", ['post_id' => $post->id]);

            return $post->fresh();

        } catch (\Exception $e) {
            Log::error("Blog generation failed", [
                'website' => $website->id,
                'keyword' => $keyword,
                'error'   => $e->getMessage(),
            ]);

            $post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function generateOutline(Website $website, string $keyword): array
{
    $prompt = PromptBuilder::blogOutline([
        'business_name'      => $website->name,
        'niche'              => $website->niche,
        'description'        => $website->business_description ?? 'N/A',
        'audience'           => $website->target_audience ?? 'General audience',
        'focus_keyword'      => $keyword,
        'secondary_keywords' => $website->keywords()->take(5)->pluck('keyword')->implode(', ') ?: 'N/A',
    ]);

    $response = $this->ai->chat([
        ['role' => 'system', 'content' => 'You are an SEO content strategist. Return only valid JSON.'],
        ['role' => 'user', 'content' => $prompt],
    ], options: [
        'response_format' => ['type' => 'json_object'],
        'temperature'     => 0.7,
        'max_tokens'      => 2500,
    ]);

    // Clean JSON if AI adds markdown
    $content = trim($response->content);
    $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
    $data = json_decode($content, true);

    if (! $data || ! isset($data['title'])) {
        throw new \Exception('AI failed to generate valid outline. Raw: ' . substr($response->content, 0, 500));
    }

    return $data;
}
    protected function writeBlog(Website $website, array $outline, array $options): array
    {
        $wordCount = $options['word_count'] ?? $website->getSetting('word_count', 1800);
        $tone      = $options['tone'] ?? $website->getSetting('tone', 'professional');

        $prompt = PromptBuilder::fullBlog([
            'outline'    => $outline,
            'word_count' => $wordCount,
            'tone'       => $tone,
        ]);

        $response = $this->ai->chat([
            ['role' => 'system', 'content' => 'You are a professional SEO blog writer. Write engaging, helpful content.'],
            ['role' => 'user', 'content' => $prompt],
        ], options: [
            'temperature' => 0.7,
            'max_tokens'  => 5000,
        ]);

        return [
            'content' => $response->content,
            'model'   => $response->model,
            'tokens'  => $response->tokens,
            'cost'    => $response->cost,
        ];
    }
}