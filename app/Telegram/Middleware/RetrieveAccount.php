<?php

namespace App\Telegram\Middleware;

use App\Models\Account;
use SergiX44\Nutgram\Nutgram;

class RetrieveAccount
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $bot->set('account', Account::query()->firstOrCreate(
            ['telegram_id' => $bot->user()->id],
            ['telegram_username' => $bot->user()->username],
        ));

        $next($bot);
    }
}
