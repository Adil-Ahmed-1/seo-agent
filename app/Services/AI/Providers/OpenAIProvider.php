<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider
{
    public function __construct(
        protected string $providerName = 'openai'
    ) {}

    public function chat(array $messages, array $options = []): AIResponse
    {
        $config = config("ai.providers.{$this->providerName}");

        if (empty($config['api_key'])) {
            throw new \Exception("{$this->providerName} API key is missing. Check .env file.");
        }

        $model = $options['model'] ?? $config['model'];
        $baseUrl = $config['base_url'];

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 4000,
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $startTime = microtime(true);

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'] ?? 60)
            ->retry(3, 2000, throw: false)
            ->post("{$baseUrl}/chat/completions", $payload);

        $duration = round((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            Log::error("{$this->providerName} API failed", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("{$this->providerName} API error: " . $response->body());
        }

        $data = $response->json();

        $content   = $data['choices'][0]['message']['content'] ?? '';
        $tokens    = $data['usage']['total_tokens'] ?? 0;
        $modelUsed = $data['model'] ?? $model;

        // Cost calculation
        $pricing   = config("ai.pricing.{$this->providerName}", ['input' => 0, 'output' => 0]);
        $inTokens  = $data['usage']['prompt_tokens'] ?? 0;
        $outTokens = $data['usage']['completion_tokens'] ?? 0;
        $cost = ($inTokens / 1_000_000 * $pricing['input'])
              + ($outTokens / 1_000_000 * $pricing['output']);

        return new AIResponse(
            content: $content,
            model:   $modelUsed,
            tokens:  $tokens,
            cost:    round($cost, 6),
            raw:     $data,
        );
    }
}