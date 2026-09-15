<?php

return [
    'ffmpeg_path' => env('FFMPEG_PATH'),
    'max_video_upload_kb' => (int) env('MAX_VIDEO_UPLOAD_KB', 102400), // 100 MB
    'admin_email' => env('MAIL_ADMIN_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@coachnow.com')),
    'mail_logo_url' => env('MAIL_LOGO_URL'),
];
