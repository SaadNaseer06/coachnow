<?php

namespace App\Mail;

use App\Models\SessionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionRequestConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SessionRequest $session) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Session request '.$this->session->reference.' submitted',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-request-confirmation',
            with: [
                'session' => $this->session,
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }
}
