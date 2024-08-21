<?php

namespace App\Telegram\Middleware;

use App\Models\Account;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;

class RetrieveAccount
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $account = Account::query()->firstOrCreate(
            ['telegram_id' => $bot->user()->id],
            ['telegram_username' => $bot->user()->username],
        );

        $account->refresh();
        App::setLocale($account->language->value);

        $bot->set('account', $account);
        $next($bot);
    }
}
