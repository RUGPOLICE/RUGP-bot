<?php

namespace App\Telegram\Middleware;

use App\Models\Account;
use App\Models\Chat;
use App\Models\Network;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class RetrieveAccount
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chat()->type === ChatType::PRIVATE) {

            $account = Account::query()->firstOrCreate(
                ['telegram_id' => $bot->user()->id],
                ['telegram_username' => $bot->user()->username, 'network_id' => Network::getDefault()->id],
            );

            $account->telegram_username = $bot->user()->username;
            $account->save();

            $bot->set('account', $account);
            $language = $account->language->value;

        } else {

            $chat = Chat::query()->firstOrCreate(
                ['telegram_id' => $bot->chat()->id],
                ['telegram_username' => $bot->chat()->username],
            );

            $chat->telegram_username = $bot->user()->username;
            $chat->save();

            $bot->set('chat', $chat);
            $language = $chat->language->value;

        }

        $bot->set('language', $language);
        App::setLocale($language);
        $next($bot);
    }
}
