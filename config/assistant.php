<?php

return [
    'features' => [
        'llm_promotion' => env('ASSISTANT_LLM_PROMOTION', true),
    ],
    'classifier' => [
        'high_confidence_threshold' => env('ASSISTANT_CLASSIFIER_HIGH_CONFIDENCE', 0.75),
        'ml_fallback_threshold' => env('ASSISTANT_CLASSIFIER_ML_THRESHOLD', 0.6),
        'llm_fallback_threshold' => env('ASSISTANT_CLASSIFIER_LLM_THRESHOLD', 0.5),
        'ml' => [
            'endpoint' => env('ASSISTANT_CLASSIFIER_ENDPOINT'),
            'token' => env('ASSISTANT_CLASSIFIER_TOKEN'),
            'timeout' => env('ASSISTANT_CLASSIFIER_TIMEOUT', 3.0),
        ],
        'llm' => [
            'endpoint' => env('ASSISTANT_LLM_ENDPOINT'),
            'token' => env('ASSISTANT_LLM_TOKEN'),
            'model' => env('ASSISTANT_LLM_MODEL', 'mistral-small'),
            'timeout' => env('ASSISTANT_LLM_TIMEOUT', 6.0),
        ],
        'ner' => [
            'duckling_endpoint' => env('ASSISTANT_DUCKLING_ENDPOINT'),
            'duckling_timeout' => env('ASSISTANT_DUCKLING_TIMEOUT', 2.5),
        ],
    ],
    'health' => [
        'email' => env('ASSISTANT_HEALTH_EMAIL'),
        'telegram_bot_token' => env('ASSISTANT_HEALTH_TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('ASSISTANT_HEALTH_TELEGRAM_CHAT_ID'),
        'scheduler_grace_seconds' => env('ASSISTANT_HEALTH_SCHEDULER_GRACE', 150),
        'queue_grace_seconds' => env('ASSISTANT_HEALTH_QUEUE_GRACE', 150),
    ],
];
