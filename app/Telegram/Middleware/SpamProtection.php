<?php

namespace App\Telegram\Middleware;

use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

class SpamProtection
{
    public function __invoke(Nutgram $bot, ?callable $next = null): bool
    {
        $key = "spam:{$bot->userId()}";
        $key_message = $key . '.message';

        $value = Cache::remember($key, config('app.spam.timeout'), fn () => 0);
        if (!Cache::has($key_message)) Cache::increment($key);

        if (++$value > config('app.spam.attempts') || Cache::has($key_message)) {

            if (!Cache::has($key_message)) {

                $bot->sendMessage(__('telegram.spam'));
                Cache::put($key_message, true, config('app.spam.timeout'));

            }

            return false;

        }

        if ($next) $next($bot);
        return true;
    }
}
