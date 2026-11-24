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
        // Must be a model your Groq account can access (llama-3.3 may be unavailable).
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'timeout' => (int) env('GROQ_TIMEOUT', 90),
    ],

    'marketplace' => [
        // Launch day (YYYY-MM-DD). Unset = pre-launch: every coach has marketplace access.
        'launch_date' => env('COACHNOW_LAUNCH_DATE'),
        // All coaches get open-request access for this many months after launch.
        'free_months' => (int) env('COACHNOW_FREE_MARKETPLACE_MONTHS', 3),
    ],

    'booking' => [
        // How far ahead parents can book on Book Now (month calendar).
        'horizon_days' => (int) env('COACHNOW_BOOKING_HORIZON_DAYS', 180),
        // Most sessions in one checkout (multi-date / recurring).
        'max_sessions_per_checkout' => (int) env('COACHNOW_MAX_SESSIONS_PER_CHECKOUT', 24),
    ],

    'subscription' => [
        // Parent "Family membership": waives the $2 fee, unlocks session requests + coach reports.
        'price' => (float) env('COACHNOW_PARENT_SUBSCRIPTION_PRICE', 10),
        'name' => env('COACHNOW_PARENT_SUBSCRIPTION_NAME', 'CoachNow Family Membership'),
        // false = everyone can use member features (handy while testing); the fee waiver still needs a membership.
        'gate_features' => filter_var(env('COACHNOW_GATE_MEMBER_FEATURES', true), FILTER_VALIDATE_BOOL),
    ],

    'payments' => [
        // When true: no Stripe. Bookings and memberships are samples (testing only).
        'sample_mode' => filter_var(env('COACHNOW_SAMPLE_PAYMENTS', false), FILTER_VALIDATE_BOOL),
        // Families pay the coach directly. Non-members pay CoachNow this fee per session booked
        // on Book Now (per session, not per player). Members don't pay it.
        'convenience_fee' => (float) env('COACHNOW_CONVENIENCE_FEE', 2),
        // Parents may cancel direct/group bookings up to this many hours before start. The coach
        // handles any session money; the CoachNow fee is non-refundable when the parent cancels.
        'cancel_cutoff_hours' => (int) env('COACHNOW_CANCEL_CUTOFF_HOURS', 24),
        // Give the CoachNow fee back when the coach is the one who cancels.
        'refund_fee_on_coach_cancel' => filter_var(env('COACHNOW_REFUND_FEE_ON_COACH_CANCEL', true), FILTER_VALIDATE_BOOL),
    ],
];
