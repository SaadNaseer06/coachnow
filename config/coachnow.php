<?php

return [
    'ffmpeg_path' => env('FFMPEG_PATH'),
    'max_video_upload_kb' => (int) env('MAX_VIDEO_UPLOAD_KB', 102400), // 100 MB
    'admin_email' => env('MAIL_ADMIN_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@coachnow.com')),
    'mail_logo_url' => env('MAIL_LOGO_URL'),
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
        // If no AI provider is available, still return a professional draft.
        'fallback_enabled' => filter_var(env('OLLAMA_FALLBACK', true), FILTER_VALIDATE_BOOL),
    ],
    // Free cloud AI for live/shared hosting (Ollama cannot run on most shared hosts).
    // Get a key at https://console.groq.com/keys
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => rtrim(env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => (int) env('GROQ_TIMEOUT', 90),
    ],
];
