<?php

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatMemberStatus;

class ForAdmins
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if (in_array($bot->getChatMember($bot->chatId(), $bot->userId())->status, [ChatMemberStatus::ADMINISTRATOR, ChatMemberStatus::CREATOR]))
            $next($bot);
    }
}
