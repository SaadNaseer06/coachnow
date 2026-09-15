<?php

namespace App\Mail;

use App\Models\SessionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionRequestAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SessionRequest $session) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A coach accepted your session request',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-request-accepted',
            with: [
                'session' => $this->session,
                'coachName' => $this->session->hostCoach?->display_name ?? 'Your coach',
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }
}
