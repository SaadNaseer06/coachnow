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

    /**
     * @param  'parent'|'coach'|'coach_cancelled'  $audience  coach_cancelled goes to the family when the coach cancels.
     */
    public function __construct(public Booking $booking, public string $audience, public float $feeRefund = 0.0, public ?string $players = null) {}

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
                'feeRefund' => $this->feeRefund,
                'players' => $this->players ?: $this->booking->athlete_name,
                'forCoach' => $this->audience === 'coach',
                'byCoach' => $this->audience === 'coach_cancelled',
                'group' => $group,
                'url' => $this->audience === 'coach'
                    ? route('coach.schedule', ['date' => $this->booking->session_date?->toDateString()], absolute: true)
                    : route('player-dashboard', absolute: true),
            ],
        );
    }
}
