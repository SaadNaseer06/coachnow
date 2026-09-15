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
        $this->safe(fn () => Mail::to($user->email)->send(new WelcomeUserMail($user)));
    }

    public function notifyAdminsOfPendingCoach(Coach $coach): void
    {
        $coach->loadMissing('user');
        $admin = (string) config('coachnow.admin_email');

        if ($admin === '') {
            return;
        }

        $this->safe(fn () => Mail::to($admin)->send(new CoachPendingReviewMail($coach)));
    }

    public function notifyCoachStatusChanged(Coach $coach, string $status): void
    {
        $coach->loadMissing('user');
        $email = $coach->user?->email;

        if (! $email) {
            return;
        }

        $this->safe(fn () => Mail::to($email)->send(new CoachStatusChangedMail($coach, $status)));
    }

    public function sendSessionRequestConfirmation(SessionRequest $session): void
    {
        $session->loadMissing('requester');
        $email = $session->requester?->email;

        if (! $email) {
            return;
        }

        $this->safe(fn () => Mail::to($email)->send(new SessionRequestConfirmationMail($session)));
    }

    public function sendSessionRequestAccepted(SessionRequest $session): void
    {
        $session->loadMissing(['requester', 'hostCoach']);
        $email = $session->requester?->email;

        if (! $email) {
            return;
        }

        $this->safe(fn () => Mail::to($email)->send(new SessionRequestAcceptedMail($session)));
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

        $this->safe(fn () => Mail::to($admin)->send(new ContactMessageMail($payload)));
    }

    private function safe(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'did not match expected CN') || str_contains($message, 'smtp.gmail.com')) {
                $message .= ' | Host is blocking remote SMTP. Switch to MAIL_MAILER=resend with RESEND_API_KEY (HTTPS, no SMTP needed).';
            }

            Log::warning('Mail send failed: '.$message, [
                'exception' => $e::class,
            ]);
        }
    }
}
