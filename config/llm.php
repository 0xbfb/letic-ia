<?php

return [
    'provider' => env('LLM_PROVIDER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'embedding_dimensions' => (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536),
    ],
];
