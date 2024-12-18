<?php

namespace App\Telegram\Middleware;

use App\Models\Account;
use App\Models\Chat;
use App\Models\Network;
use App\Telegram\Conversations\GptMenu;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class RetrieveAccount
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->chat()->type === ChatType::PRIVATE) {

            $name = $bot->user()->first_name . ($bot->user()->last_name ? ' ' . $bot->user()->last_name : '');
            $account = Account::query()->firstOrCreate(
                ['telegram_id' => $bot->user()->id],
                ['telegram_username' => $bot->user()->username, 'network_id' => Network::getDefault()->id, 'gpt_count' => GptMenu::MAX_ATTEMPTS],
            );

            $account->telegram_username = $bot->user()->username;
            $account->telegram_name = $name;
            $account->last_active_at = now();
            $account->save();
            $account->refresh();

            $bot->set('account', $account);
            $language = $account->language->value;

        } else {

            $chat = Chat::query()->firstOrCreate(
                ['telegram_id' => $bot->chat()->id],
                ['telegram_username' => $bot->chat()->username, 'network_id' => Network::getDefault()->id],
            );

            $chat->telegram_username = $bot->user()->username;
            $chat->last_active_at = now();
            $chat->save();
            $chat->refresh();

            $bot->set('chat', $chat);
            $language = $chat->language->value;

        }

        $bot->set('language', $language);
        App::setLocale($language);
        $next($bot);
    }
}
