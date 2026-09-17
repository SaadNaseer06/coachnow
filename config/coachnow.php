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
        // If Ollama is down, still return a professional draft so coaches can edit/save.
        'fallback_enabled' => filter_var(env('OLLAMA_FALLBACK', true), FILTER_VALIDATE_BOOL),
    ],
];
