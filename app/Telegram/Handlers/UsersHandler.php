<?php

namespace App\Telegram\Handlers;

use App\Models\Account;
use App\Models\Chat;
use SergiX44\Nutgram\Nutgram;

class UsersHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $accountsCount = Account::query()->count();
        $chatsCount = Chat::query()->count();

        $bot->asResponse()->sendImagedMessage("<b>Информация по БД</b>\n\nПользователей: <b>$accountsCount</b>\nГрупп: <b>$chatsCount</b>");
    }
}
