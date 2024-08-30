<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;

class ForDevelopers
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chatId() === intval(env('TELEGRAM_LOG_CHAT_ID')))
            $next($bot);
    }
}
