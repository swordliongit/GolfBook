<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        // Fail loudly in development on N+1 queries and on writes to non-fillable attributes.
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::defaultView('pagination');

        // Each partner gets its own API quota; unauthenticated calls are limited per IP.
        RateLimiter::for('partner-api', function (Request $request) {
            $partner = $request->attributes->get('partner');

            return Limit::perMinute(120)->by($partner ? 'partner:'.$partner->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
