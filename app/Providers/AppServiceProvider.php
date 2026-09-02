<?php

namespace App\Providers;

use App\Support\DemoSessionRequests;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.coach', function ($view) {
            $view->with('sessionRequests', DemoSessionRequests::all());
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
