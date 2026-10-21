<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Tells a parent their coach moved a session (only when the coach chooses "Move & Notify Parent").
 */
class SessionRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $players
     */
    public function __construct(
        public Booking $booking,
        public array $players,
        public string $oldDate,
        public string $oldTime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Session time changed — now '.$this->booking->session_date?->format('D, M j').' at '.Carbon::parse($this->booking->session_time)->format('g:i A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.session-rescheduled',
            with: [
                'booking' => $this->booking,
                'players' => $this->players,
                'coachName' => $this->booking->coach?->display_name ?? 'Your coach',
                'oldWhen' => Carbon::parse($this->oldDate.' '.$this->oldTime)->format('l, M j · g:i A'),
                'newWhen' => Carbon::parse($this->booking->session_date?->toDateString().' '.$this->booking->session_time)->format('l, M j · g:i A'),
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }
}
