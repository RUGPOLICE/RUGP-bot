<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class PrivateHandler
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chat()->type === ChatType::PRIVATE || $bot->isInlineQuery())
            $next($bot);
    }
}
