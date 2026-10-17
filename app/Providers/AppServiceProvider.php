<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        $host = request()->getHost();
        $isNgrok = str_contains($host, 'ngrok');
        $isExternal = $host !== 'localhost' && $host !== '127.0.0.1' && $host !== '::1';
        $isHttpsForwarded = request()->header('x-forwarded-proto') === 'https' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https';

        if ($isNgrok || $isHttpsForwarded) {
            URL::forceScheme('https');
        }

        if ($isExternal || $isNgrok || $isHttpsForwarded) {
            Vite::useHotFile(storage_path('vite.hot.dummy'));
        }
    }
}
