<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Parent receipt for one checkout (1-on-1, private group, or joining a public group).
 */
class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<Booking>  $bookings
     */
    public function __construct(public array $bookings, public string $kind) {}

    public function envelope(): Envelope
    {
        $first = $this->bookings[0];

        return new Envelope(
            subject: 'You’re booked with '.($first->coach?->display_name ?? 'your coach').' — '.$first->session_date?->format('D, M j'),
        );
    }

    public function content(): Content
    {
        $first = $this->bookings[0];

        return new Content(
            markdown: 'emails.booking-confirmed',
            with: [
                'first' => $first,
                'kindLabel' => self::kindLabel($this->kind),
                'players' => array_map(fn (Booking $b) => $b->athlete_name, $this->bookings),
                'total' => array_sum(array_map(fn (Booking $b) => (float) $b->amount, $this->bookings)),
                'fee' => array_sum(array_map(fn (Booking $b) => (float) $b->platform_fee_amount, $this->bookings)),
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }

    public static function kindLabel(string $kind): string
    {
        return match ($kind) {
            'group_join' => 'Open group session',
            'private_group' => 'Private group',
            default => '1-on-1 session',
        };
    }
}
