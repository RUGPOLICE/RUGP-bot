<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class PublicHandler
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chat()->type !== ChatType::PRIVATE)
            $next($bot);
    }
}
