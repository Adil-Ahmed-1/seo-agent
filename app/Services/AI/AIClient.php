<?php

namespace App\Services\AI;

use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Support\Facades\Log;

class AIClient
{
    protected array $providers = [];

    public function chat(
        array $messages,
        ?string $provider = null,
        array $options = []
    ): AIResponse {
        $provider = $provider ?? config('ai.default');

        // Lazy load provider
        if (! isset($this->providers[$provider])) {
            $this->providers[$provider] = new OpenAIProvider($provider);
        }

        try {
            return $this->providers[$provider]->chat($messages, $options);
        } catch (\Exception $e) {
            Log::warning("AI provider [{$provider}] failed: " . $e->getMessage());

            // Fallback chain
            $fallbacks = collect(['groq', 'openai'])
                ->reject(fn ($p) => $p === $provider)
                ->filter(fn ($p) => ! empty(config("ai.providers.{$p}.api_key")));

            foreach ($fallbacks as $fallbackProvider) {
                try {
                    Log::info("Falling back to [{$fallbackProvider}]");
                    
                    if (! isset($this->providers[$fallbackProvider])) {
                        $this->providers[$fallbackProvider] = new OpenAIProvider($fallbackProvider);
                    }
                    
                    return $this->providers[$fallbackProvider]->chat($messages, $options);
                } catch (\Exception $fallbackError) {
                    continue;
                }
            }

            throw $e;
        }
    }
}