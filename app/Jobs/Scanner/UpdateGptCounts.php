<?php

namespace App\Jobs\Scanner;

use App\Models\Account;
use App\Telegram\Conversations\GptMenu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateGptCounts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        foreach (Account::query()->get() as $account) {
            $account->gpt_count = GptMenu::MAX_ATTEMPTS;
            $account->save();
        }
    }
}
