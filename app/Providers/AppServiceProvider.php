<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\SessionRequest;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        Event::listen(MessageSending::class, function (MessageSending $event) {
            $logo = public_path('assets/logo.png');

            if (! is_file($logo)) {
                return;
            }

            $event->message->embedFromPath($logo, 'coachnow-logo', 'image/png');
        });

        View::composer(['layouts.coach', 'layouts.admin'], function ($view) {
            $user = auth()->user();
            $coach = $user?->coach;
            $isAdmin = (bool) $user?->isAdmin();

            $query = SessionRequest::query()
                ->with(['players', 'requester', 'hostCoach.user', 'requestedCoach.user', 'location'])
                ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
                ->orderByDesc('created_at');

            if ($isAdmin) {
                // Admins can view every request, including private/targeted ones.
            } elseif ($coach) {
                $query->visibleToCoach($coach);
            } else {
                $query->whereRaw('1 = 0');
            }

            $sessionRequests = $query
                ->limit(40)
                ->get()
                ->map->toPortalArray()
                ->values()
                ->all();

            $view->with([
                'sessionRequests' => $sessionRequests,
                'currentCoachId' => $coach?->id,
                'currentCoachStatus' => $coach?->status,
                'sessionRequestsIsAdmin' => $isAdmin,
                'unreadContactCount' => $isAdmin
                    ? ContactMessage::query()->whereNull('read_at')->count()
                    : 0,
            ]);
        });

        if (! $this->app->environment('local')) {
            $rootUrl = rtrim((string) config('app.url'), '/');

            if ($rootUrl !== '') {
                \Illuminate\Support\Facades\URL::forceRootUrl($rootUrl);

                if (str_starts_with($rootUrl, 'https://')) {
                    \Illuminate\Support\Facades\URL::forceScheme('https');
                }
            }
        }
    }
}
