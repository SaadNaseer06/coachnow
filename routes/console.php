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

        if (str_contains($e->getMessage(), 'did not match expected CN')) {
            $this->warn('Your host is intercepting Gmail SMTP. Ask them to disable cPanel “SMTP Restrictions” / allow remote SMTP to smtp.gmail.com.');
        }

        return 1;
    }

    $this->info("Branded test email sent to {$to} via ".config('mail.default').' ('.config('mail.mailers.smtp.host').').');

    return 0;
})->purpose('Send a branded CoachNow test email through SMTP');

Artisan::command('mail:diagnose', function () {
    $host = (string) config('mail.mailers.smtp.host');
    $port = (int) config('mail.mailers.smtp.port', 587);
    $scheme = config('mail.mailers.smtp.scheme');

    $this->info("Configured SMTP: {$host}:{$port} scheme=".($scheme ?: 'null'));
    $this->info('Mailer: '.config('mail.default'));
    $this->info('Username: '.(config('mail.mailers.smtp.username') ?: '(empty)'));

    $targets = [
        ['smtp.gmail.com', 587],
        ['smtp.gmail.com', 465],
        [$host, $port],
    ];

    foreach ($targets as [$checkHost, $checkPort]) {
        if ($checkHost === '') {
            continue;
        }

        $this->line('');
        $this->comment("Probing {$checkHost}:{$checkPort} …");

        $errno = 0;
        $errstr = '';
        $remote = ($checkPort === 465 ? 'ssl://' : '').$checkHost;
        $socket = @stream_socket_client(
            $remote.':'.$checkPort,
            $errno,
            $errstr,
            12,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ])
        );

        if (! $socket) {
            $this->error("Connect failed: {$errstr} ({$errno})");
            continue;
        }

        $params = stream_context_get_params($socket);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        $cn = null;

        if ($cert) {
            $parsed = openssl_x509_parse($cert);
            $cn = $parsed['subject']['CN'] ?? null;
        }

        if ($checkPort === 587) {
            fread($socket, 1024);
            fwrite($socket, "EHLO coachnow\r\n");
            fread($socket, 1024);
            fwrite($socket, "STARTTLS\r\n");
            fread($socket, 1024);
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $params = stream_context_get_params($socket);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            if ($cert) {
                $parsed = openssl_x509_parse($cert);
                $cn = $parsed['subject']['CN'] ?? $cn;
            }
            $this->line('STARTTLS: '.($crypto ? 'ok' : 'failed'));
        }

        fclose($socket);

        if ($cn) {
            $this->line("Certificate CN: {$cn}");
            if ($checkHost === 'smtp.gmail.com' && $cn !== 'smtp.gmail.com' && ! str_contains((string) $cn, 'google')) {
                $this->error('Host is rewriting Gmail SMTP to its own server. Remote Gmail SMTP is blocked.');
                $this->warn('Ask the host to disable “SMTP Restrictions” for this account so outbound smtp.gmail.com is allowed.');
            } else {
                $this->info('Certificate looks OK for this target.');
            }
        } else {
            $this->line('No certificate captured.');
        }
    }

    return 0;
})->purpose('Diagnose SMTP connectivity and certificate interception');
