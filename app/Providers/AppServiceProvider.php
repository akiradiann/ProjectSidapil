<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\URL;

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
        if (app()->environment('production') || env('APP_ENV') === 'production' || (request()->header('X-Forwarded-Proto') === 'https') || (request()->getHost() && str_contains(request()->getHost(), 'railway.app'))) {
            URL::forceScheme('https');
        }
    }
}
