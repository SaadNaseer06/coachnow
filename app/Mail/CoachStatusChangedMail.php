<?php

namespace App\Mail;

use App\Models\Coach;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoachStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Coach $coach,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->status) {
            'active' => 'Your CoachNow profile is live',
            'paused' => 'Your CoachNow profile was paused',
            default => 'Your CoachNow application status changed',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.coach-status-changed',
            with: [
                'coach' => $this->coach,
                'status' => $this->status,
                'profileUrl' => route('coach.profile', absolute: true),
                'findUrl' => route('find-a-coach', absolute: true),
            ],
        );
    }
}
