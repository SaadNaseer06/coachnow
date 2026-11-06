<?php

namespace App\Mail;

use App\Models\SessionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionRequestPlaceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SessionRequest $session, public bool $confirmed) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->confirmed ? 'Your place is confirmed' : 'Update on your session request',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-request-place',
            with: [
                'session' => $this->session,
                'confirmed' => $this->confirmed,
                'coachName' => $this->session->hostCoach?->display_name ?? 'Your coach',
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }
}
