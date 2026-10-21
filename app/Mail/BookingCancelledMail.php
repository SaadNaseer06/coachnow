<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the parent (receipt) and the coach (spot freed) when a parent cancels.
 */
class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public float $refund, public string $audience) {}

    public function envelope(): Envelope
    {
        $who = $this->audience === 'coach' ? $this->booking->athlete_name.' cancelled' : 'Booking cancelled';

        return new Envelope(subject: $who.' — '.$this->booking->session_date?->format('D, M j'));
    }

    public function content(): Content
    {
        $group = $this->booking->groupSession;

        return new Content(
            markdown: 'emails.booking-cancelled',
            with: [
                'booking' => $this->booking,
                'refund' => $this->refund,
                'forCoach' => $this->audience === 'coach',
                'group' => $group,
                'url' => $this->audience === 'coach'
                    ? route('coach.schedule', ['date' => $this->booking->session_date?->toDateString()], absolute: true)
                    : route('player-dashboard', absolute: true),
            ],
        );
    }
}
