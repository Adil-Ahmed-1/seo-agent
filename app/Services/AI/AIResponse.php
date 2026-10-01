<?php

namespace App\Services\AI;

class AIResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public int $tokens = 0,
        public float $cost = 0.0,
        public array $raw = [],
    ) {}

    public function isJson(): bool
    {
        json_decode($this->content);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model'   => $this->model,
            'tokens'  => $this->tokens,
            'cost'    => $this->cost,
        ];
    }
}