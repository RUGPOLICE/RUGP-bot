<?php

namespace App\Jobs\Scanner;

use App\Models\Account;
use App\Telegram\Conversations\GptMenu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use SergiX44\Nutgram\Nutgram;

class UpdateGptCounts implements ShouldQueue
{
    use Queueable;

    public function handle(Nutgram $bot): void
    {
        Account::query()->update(['gpt_count' => GptMenu::MAX_ATTEMPTS]);
    }
}
