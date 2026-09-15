<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to CoachNow',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome-user',
            with: [
                'name' => $this->user->name,
                'role' => $this->user->role,
                'dashboardUrl' => url($this->user->dashboardPath()),
            ],
        );
    }
}
