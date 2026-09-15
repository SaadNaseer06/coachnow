<?php

namespace App\Providers;

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

        View::composer('layouts.coach', function ($view) {
            $sessionRequests = SessionRequest::query()
                ->with(['players', 'requester', 'hostCoach.user', 'location'])
                ->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])
                ->orderByDesc('created_at')
                ->limit(40)
                ->get()
                ->map->toPortalArray()
                ->values()
                ->all();

            $view->with('sessionRequests', $sessionRequests);
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
