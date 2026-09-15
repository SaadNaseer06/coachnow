<?php

namespace App\Mail;

use App\Models\Coach;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoachPendingReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Coach $coach) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New coach application: '.$this->coach->display_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.coach-pending-review',
            with: [
                'coach' => $this->coach,
                'adminUrl' => route('admin.coaches', absolute: true),
            ],
        );
    }
}
