<?php

return [
    'default' => env('AI_PROVIDER', 'groq'),

    'providers' => [
        'groq' => [
    'api_key' => env('GROQ_API_KEY'),
    'model'   => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
    'base_url'=> 'https://api.groq.com/openai/v1',
    'timeout' => 60,
],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o'),
            'base_url'=> 'https://api.openai.com/v1',
            'timeout' => 60,
        ],
    ],

    'limits' => [
        'max_tokens_per_blog' => 4000,
        'max_blogs_per_day'   => 100,
    ],

    'pricing' => [
        'groq' => [
            'input'  => 0.59,
            'output' => 0.79,
        ],
        'openai' => [
            'input'  => 2.50,
            'output' => 10.00,
        ],
    ],
];