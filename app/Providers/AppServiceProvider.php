<?php

namespace App\Providers;

use App\Services\Ai\ChatClient;
use App\Services\Ai\ZaiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatClient::class, ZaiClient::class);
    }

    public function boot(): void
    {
        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
