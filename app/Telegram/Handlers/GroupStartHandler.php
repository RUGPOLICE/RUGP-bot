<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;

class GroupStartHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendImagedMessage(
            __('telegram.text.group'),
            options: ['image' => public_path('img/scan.png'),]
        );
    }
}
