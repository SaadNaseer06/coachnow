<?php

namespace App\Mail;

use App\Models\SessionRequest;
use App\Models\SessionRequestPlayer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionRequestJoinMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SessionRequest $session, public SessionRequestPlayer $player) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->player->name.' asked to join your session',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-request-join',
            with: [
                'session' => $this->session,
                'player' => $this->player,
                'dashboardUrl' => route('coach.dashboard', ['requests' => 'open'], absolute: true),
            ],
        );
    }
}
