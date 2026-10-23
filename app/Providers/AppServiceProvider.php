<?php

namespace App\Providers;

use App\Services\Ai\ChatClient;
use App\Services\Ai\ZaiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatClient::class, ZaiClient::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceHttps();
        }

        RateLimiter::for('chat', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute(20)->by('chat-minute:'.$identity),
                Limit::perDay(config('chatbot.daily_limit', 100))
                    ->by('chat-daily:'.$identity)
                    ->response(fn (Request $request, array $headers) => response()->json([
                        'error' => 'Você atingiu o limite diário de mensagens. Tente novamente amanhã.',
                    ], 429, $headers)),
            ];
        });
    }
}
