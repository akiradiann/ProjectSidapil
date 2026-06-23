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

        // Buat symlink storage secara otomatis jika belum ada (terutama di Railway)
        if (! file_exists(public_path('storage'))) {
            try {
                app('files')->link(storage_path('app/public'), public_path('storage'));
            } catch (\Exception $e) {
                // Abaikan jika gagal agar tidak merusak aplikasi
            }
        }
    }
}
