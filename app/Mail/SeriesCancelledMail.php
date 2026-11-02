<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a parent their coach cancelled the rest of a weekly series (one email, all dates).
 */
class SeriesCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<Booking>  $bookings
     */
    public function __construct(public array $bookings, public float $refund) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your weekly sessions with '.($this->bookings[0]->coach?->display_name ?? 'your coach').' were cancelled');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.series-cancelled',
            with: [
                'coachName' => $this->bookings[0]->coach?->display_name ?? 'Your coach',
                'sessions' => array_map(fn (Booking $b) => $b->whenLabel(), $this->bookings),
                'refund' => $this->refund,
                'dashboardUrl' => route('player-dashboard', absolute: true),
            ],
        );
    }
}
