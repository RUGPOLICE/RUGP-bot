<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Chat;
use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class SendScamPost implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Chat $chat,
        public Language $language,
    ) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(TokenReportService $tokenReportService): void
    {
        $bot = new Nutgram(config('nutgram.group_token'));
        [$message, $options] = $this->getReport($tokenReportService);

        $bot->sendImagedMessage(
            $message,
            options: $options,
            chat_id: $this->chat->chat_id,
        );
    }

    private function getReport(TokenReportService $tokenReportService): array
    {
        $params = $tokenReportService->main($this->token, $this->chat->is_show_warnings, is_finished: true, for_group: true);
        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true),];

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        return [$params['text'], $options];
    }
}
