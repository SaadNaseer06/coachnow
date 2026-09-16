<?php

namespace App\Services;

use App\Mail\CoachPendingReviewMail;
use App\Mail\CoachStatusChangedMail;
use App\Mail\ContactMessageMail;
use App\Mail\SessionRequestAcceptedMail;
use App\Mail\SessionRequestConfirmationMail;
use App\Mail\WelcomeUserMail;
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
            $this->logFailure($e);
        }
    }

    private function logFailure(Throwable $e): void
    {
        Log::warning('Mail send failed: '.$e->getMessage(), [
            'exception' => $e::class,
        ]);
    }
}
