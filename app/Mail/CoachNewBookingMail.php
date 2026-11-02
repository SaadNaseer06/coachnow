<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the coach a parent booked — including players joining a public group.
 */
class CoachNewBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<Booking>  $bookings
     */
    public function __construct(public array $bookings, public string $kind, public string $parentName) {}

    public function envelope(): Envelope
    {
        $count = count($this->bookings);
        $subject = match ($this->kind) {
            'group_join' => $count.' player'.($count === 1 ? '' : 's').' joined your group session',
            'private_group' => 'New private group booking ('.$count.' players)',
            default => $count > 1 ? $count.' new 1-on-1 sessions booked' : 'New 1-on-1 booking',
        };

        return new Envelope(subject: $subject.' — '.$this->bookings[0]->session_date?->format('D, M j'));
    }

    public function content(): Content
    {
        $first = $this->bookings[0];
        $group = $first->groupSession;

        return new Content(
            markdown: 'emails.coach-new-booking',
            with: [
                'first' => $first,
                'kindLabel' => BookingConfirmedMail::kindLabel($this->kind),
                'parentName' => $this->parentName,
                'players' => array_values(array_unique(array_map(fn (Booking $b) => $b->athlete_name, $this->bookings))),
                // Several dates in one checkout (multi-date or weekly series).
                'sessions' => array_values(array_unique(array_map(fn (Booking $b) => $b->whenLabel(), $this->bookings))),
                'isSeries' => (bool) ($this->bookings[0]->series_id ?? null),
                'payout' => array_sum(array_map(fn (Booking $b) => (float) $b->coach_payout_amount, $this->bookings)),
                'group' => $group,
                'isPrivateGroup' => $this->kind === 'private_group',
                'scheduleUrl' => route('coach.schedule', ['date' => $first->session_date?->toDateString()], absolute: true),
            ],
        );
    }
}
