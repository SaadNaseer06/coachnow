<?php

namespace App\Services;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\CoachNewBookingMail;
use App\Mail\CoachPendingReviewMail;
use App\Mail\CoachStatusChangedMail;
use App\Mail\ContactMessageMail;
use App\Mail\SessionRequestAcceptedMail;
use App\Mail\SessionRequestConfirmationMail;
use App\Mail\SessionRescheduledMail;
use App\Mail\WelcomeUserMail;
use App\Models\Booking;
use App\Models\Coach;
use App\Models\SessionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppMailer
{
    public function sendWelcome(User $user): void
    {
        $this->deliver($user->email, new WelcomeUserMail($user));
    }

    public function notifyAdminsOfPendingCoach(Coach $coach): void
    {
        $coach->loadMissing('user');
        $admin = (string) config('coachnow.admin_email');

        if ($admin === '') {
            return;
        }

        $this->deliver($admin, new CoachPendingReviewMail($coach));
    }

    public function notifyCoachStatusChanged(Coach $coach, string $status): void
    {
        $coach->loadMissing('user');
        $email = $coach->user?->email;

        if (! $email) {
            return;
        }

        $this->deliver($email, new CoachStatusChangedMail($coach, $status));
    }

    public function sendSessionRequestConfirmation(SessionRequest $session): void
    {
        $session->loadMissing('requester');
        $email = $session->requester?->email;

        if (! $email) {
            return;
        }

        $this->deliver($email, new SessionRequestConfirmationMail($session));
    }

    public function sendSessionRequestAccepted(SessionRequest $session): void
    {
        $session->loadMissing(['requester', 'hostCoach']);
        $email = $session->requester?->email;

        if (! $email) {
            return;
        }

        $this->deliver($email, new SessionRequestAcceptedMail($session));
    }

    /**
     * Parent receipt + coach alert for one checkout.
     *
     * @param  list<Booking>  $bookings
     * @param  'single'|'private_group'|'group_join'  $kind
     */
    public function notifyBookingCreated(array $bookings, string $kind, User $parent): void
    {
        if ($bookings === []) {
            return;
        }

        foreach ($bookings as $booking) {
            $booking->loadMissing(['coach.user', 'location', 'groupSession']);
        }

        $this->deliver($parent->email, new BookingConfirmedMail($bookings, $kind));

        $coachEmail = $bookings[0]->coach?->user?->email;
        if ($coachEmail) {
            $this->deliver($coachEmail, new CoachNewBookingMail($bookings, $kind, $parent->name));
        }
    }

    /**
     * One email per parent account on the moved session (a group can span several families).
     *
     * @return int Number of parents emailed.
     */
    public function notifySessionRescheduled(Booking $booking, string $oldDate, string $oldTime): int
    {
        $booking->loadMissing(['coach', 'location', 'groupSession']);

        $roster = $booking->group_session_id
            ? Booking::query()->with('athlete')->where('group_session_id', $booking->group_session_id)->where('status', '!=', 'cancelled')->get()
            : collect([$booking->loadMissing('athlete')]);

        $sent = 0;
        foreach ($roster->filter(fn (Booking $b) => $b->athlete?->email)->groupBy('athlete_id') as $family) {
            $first = $family->first();
            $playerNames = $family
                ->map(fn (Booking $b) => $b->displayName())
                ->filter()
                ->unique()
                ->values()
                ->all();
            $this->deliver($first->athlete->email, new SessionRescheduledMail(
                $booking,
                $playerNames !== [] ? $playerNames : ['your player'],
                $oldDate,
                $oldTime,
            ));
            $sent++;
        }

        return $sent;
    }

    public function notifyBookingCancelled(Booking $booking, float $refund): void
    {
        $booking->loadMissing(['coach.user', 'athlete', 'location', 'groupSession']);

        if ($booking->athlete?->email) {
            $this->deliver($booking->athlete->email, new BookingCancelledMail($booking, $refund, 'parent'));
        }

        if ($booking->coach?->user?->email) {
            $this->deliver($booking->coach->user->email, new BookingCancelledMail($booking, $refund, 'coach'));
        }
    }

    /**
     * @param  array{name:string,email:string,topic:string,message:string}  $payload
     */
    public function sendContactMessage(array $payload): void
    {
        $admin = (string) config('coachnow.admin_email');

        if ($admin === '') {
            return;
        }

        $this->deliver($admin, new ContactMessageMail($payload));
    }

    private function deliver(string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (Throwable $e) {
            $this->logFailure($e, $email, $mailable::class);
        }
    }

    private function logFailure(Throwable $e, string $email = '', string $mailable = ''): void
    {
        $message = $e->getMessage();

        if (str_contains($message, 'only send testing emails') || str_contains($message, 'verify a domain')) {
            $message .= ' | Fix: verify a domain at https://resend.com/domains, then set MAIL_FROM_ADDRESS to an address on that domain (not onboarding@resend.dev).';
        }

        Log::warning('Mail send failed: '.$message, [
            'exception' => $e::class,
            'to' => $email,
            'mailable' => $mailable,
            'mailer' => config('mail.default'),
            'from' => config('mail.from.address'),
        ]);
    }
}