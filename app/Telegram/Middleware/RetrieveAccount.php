<?php

namespace App\Telegram\Middleware;

use App\Models\Account;
use App\Models\Chat;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class RetrieveAccount
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chat()->type === ChatType::PRIVATE || $bot->isCallbackQuery()) {

            $account = Account::query()->firstOrCreate(
                ['telegram_id' => $bot->user()->id],
                ['telegram_username' => $bot->user()->username],
            );

            $account->refresh();
            $bot->set('account', $account);
            $language = $account->language->value;

        } else {

            $chat = Chat::query()->firstOrCreate(['chat_id' => $bot->chatId()]);
            $chat->refresh();

            $bot->set('chat', $chat);
            $language = $chat->language->value;

        }

        $bot->set('language', $language);
        App::setLocale($language);
        $next($bot);
    }
}
