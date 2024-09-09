<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;

class ForSuperusers
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if (in_array($bot->user()->username, explode(',', config('nutgram.superusers'))))
            $next($bot);
    }
}
