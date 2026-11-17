<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Tells a parent their coach changed a session's date, time or place. $oldLocation is only set
 * when the place changed.
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
        public ?Location $oldLocation = null,
    ) {}

    private function timeChanged(): bool
    {
        return $this->oldDate !== $this->booking->session_date?->toDateString()
            || substr($this->oldTime, 0, 5) !== substr((string) $this->booking->session_time, 0, 5);
    }

    public function envelope(): Envelope
    {
        $when = $this->booking->session_date?->format('D, M j').' at '.Carbon::parse($this->booking->session_time)->format('g:i A');
        $where = $this->booking->location?->name ?? 'a new location';

        return new Envelope(
            subject: match (true) {
                $this->oldLocation !== null && $this->timeChanged() => "Session changed — now {$when} at {$where}",
                $this->oldLocation !== null => "Session location changed — now at {$where}",
                default => 'Session time changed — now '.$when,
            },
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
                'timeChanged' => $this->timeChanged(),
                'placeChanged' => $this->oldLocation !== null,
                'oldWhere' => $this->oldLocation?->label(),
                'newWhere' => $this->booking->location?->label() ?? 'Field TBD',
                'directionsUrl' => $this->booking->location?->directionsUrl(),
            ],
        );
    }
}
