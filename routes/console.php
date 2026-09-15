<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?}', function (?string $email = null) {
    $to = $email ?: (string) config('coachnow.admin_email');

    if ($to === '') {
        $this->error('No recipient. Pass an email or set MAIL_ADMIN_ADDRESS.');

        return 1;
    }

    try {
        $user = App\Models\User::query()->first() ?: new App\Models\User([
            'name' => 'CoachNow Tester',
            'email' => $to,
            'role' => 'athlete',
        ]);

        Illuminate\Support\Facades\Mail::to($to)->send(new App\Mail\WelcomeUserMail($user));
    } catch (Throwable $e) {
        $this->error('SMTP send failed: '.$e->getMessage());

        return 1;
    }

    $this->info("Branded test email sent to {$to} via ".config('mail.default').' ('.config('mail.mailers.smtp.host').').');

    return 0;
})->purpose('Send a branded CoachNow test email through SMTP');
