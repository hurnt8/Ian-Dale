<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Débit global d'envoi des emails de campagne (configurable par l'admin)
        RateLimiter::for('campaign-mail', fn () => Limit::perMinute(max(1, Setting::int('per_minute'))));
    }
}
