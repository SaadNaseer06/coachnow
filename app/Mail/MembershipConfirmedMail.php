<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your CoachNow Family Membership is active',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.membership-confirmed',
            with: [
                'price' => number_format((float) config('coachnow.subscription.price', 10), 0),
                'firstCharge' => now()->format('M j, Y'),
                'nextBilling' => $this->user->subscription_ends_at?->format('M j, Y'),
                'dashboardUrl' => route('player-dashboard', absolute: true).'#membership',
            ],
        );
    }
}
