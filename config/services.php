<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'default_language' => env('TELEGRAM_DEFAULT_LANGUAGE', 'id'),
        'contact_email' => env('TELEGRAM_CONTACT_EMAIL'),
        'contact_whatsapp' => env('TELEGRAM_CONTACT_WHATSAPP'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct'),
    ],

    'huggingface' => [
        'api_key' => env('HUGGINGFACE_API_KEY'),
        'model' => env('HUGGINGFACE_MODEL', 'microsoft/Phi-3-mini-4k-instruct'),
        'endpoints' => env('HUGGINGFACE_ENDPOINTS') ? explode(',', env('HUGGINGFACE_ENDPOINTS')) : [],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'keys' => (function () {
            $keys = [];
            $base = env('GEMINI_API_KEY');
            if ($base) {
                $keys[] = $base;
            }
            for ($i = 1; $i <= 6; $i++) {
                $value = env("GEMINI_API_KEY_{$i}");
                if ($value) {
                    $keys[] = $value;
                }
            }

            return array_values(array_unique($keys));
        })(),
    ],

    'langdb' => [
        'api_key' => env('LANGDB_API_KEY'),
        'endpoint' => env('LANGDB_ENDPOINT', 'https://api.us-east-1.langdb.ai/v1/chat/completions'),
        'model' => env('LANGDB_MODEL', 'deepinfra/llama-3.1-8b-instruct'),
        'allowed_models' => array_values(array_filter(array_map(
            static fn ($value) => trim($value),
            explode(',', (string) env('LANGDB_ALLOWED_MODELS', 'deepinfra/llama-3.1-8b-instruct'))
        ))) ?: ['deepinfra/llama-3.1-8b-instruct'],
    ],

    'mistral' => [
        'api_key' => env('MISTRAL_API_KEY'),
        'endpoint' => env('MISTRAL_ENDPOINT', 'https://api.mistral.ai/v1/chat/completions'),
        'model' => env('MISTRAL_MODEL', 'mistral-large-latest'),
    ],

    'cohere' => [
        'api_key' => env('COHERE_API_KEY'),
        'endpoint' => env('COHERE_ENDPOINT', 'https://api.cohere.com/v1/chat'),
        'model' => env('COHERE_MODEL', 'command-r7b-12-2024'),
    ],

];
