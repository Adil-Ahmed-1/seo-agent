<?php

namespace App\Services\Content;

use App\Services\AI\AIClient;
use App\Services\AI\PromptBuilder;

class KeywordGenerator
{
    public function __construct(protected AIClient $ai) {}

    /**
     * Generate keyword ideas for a website.
     */
    public function generate(array $websiteData, int $count = 20): array
    {
        $prompt = $this->buildPrompt($websiteData, $count);

        $response = $this->ai->chat([
            ['role' => 'system', 'content' => 'You are an SEO keyword research expert. Return only valid JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ], options: [
            'response_format' => ['type' => 'json_object'],
            'temperature'     => 0.8,
            'max_tokens'      => 3000,
        ]);

        $data = json_decode($response->content, true);

        return $data['keywords'] ?? [];
    }

    protected function buildPrompt(array $data, int $count): string
    {
        return <<<PROMPT
You are an SEO keyword research expert. Generate {$count} high-value keywords.

BUSINESS CONTEXT:
- Website: {$data['name']}
- Niche: {$data['niche']}
- Description: {$data['description']}
- Target Audience: {$data['audience']}
- Language: {$data['language']}

REQUIREMENTS:
- Mix of short-tail (1-2 words), mid-tail (2-4 words), long-tail (5+ words)
- Include buyer intent keywords (commercial, transactional)
- Include informational keywords (how-to, guide, tips)
- Focus on realistic keywords for small/medium businesses
- Native language if not English

Return ONLY valid JSON:
{
  "keywords": [
    {
      "keyword": "string",
      "intent": "informational|commercial|transactional|navigational",
      "difficulty_estimate": "integer 0-100",
      "search_volume_estimate": "integer",
      "priority": "high|medium|low"
    }
  ]
}
PROMPT;
    }
}