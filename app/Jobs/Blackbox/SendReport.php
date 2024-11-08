<?php

namespace App\Jobs\Blackbox;

use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Input\InputMediaDocument;

class SendReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Report $report, public Language $language) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(Nutgram $bot): void
    {
        $user = $this->report->account->telegram_username ?? $this->report->account->telegram_id;
        $message = "<b>Blackbox Report</b>\nFrom: <i><a href='tg://user?id={$this->report->account->telegram_id}'>$user</a></i>\n\n<blockquote>{$this->report->message}</blockquote>";
        $chat_id = '-1002297242771';
        $thread_id = '606';

        if ($this->report->files) {

            $bot->sendMediaGroup(
                media: $this->report->files->map(fn (array $file) => InputMediaDocument::make($file['file'], caption: $message, parse_mode: ParseMode::HTML))->toArray(),
                chat_id: $chat_id,
                message_thread_id: $thread_id,
            );

        } else $bot->sendMessage($message, chat_id: $chat_id, message_thread_id: $thread_id, parse_mode: ParseMode::HTML);
    }
}
