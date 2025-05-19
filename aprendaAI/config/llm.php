<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => 'gemini-2.0-flash',
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'max_tokens' => 2048,
        'temperature' => 0.7,
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 60 * 60 * 24, // 24 hours
    ],
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100, // Per user per day
        'decay_minutes' => 1440, // 24 hours
    ],
];