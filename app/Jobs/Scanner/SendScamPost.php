<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class SendScamPost implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Chat|Account $sendable,
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

        try {

            $bot->sendImagedMessage(
                $message,
                options: $options,
                chat_id: $this->sendable->telegram_id,
            );

        } catch (\Throwable $e) {

            if (str_contains($e->getMessage(), 'bot was blocked') || str_contains($e->getMessage(), 'bot was kicked') || str_contains($e->getMessage(), 'chat not found')) {

                $this->sendable->is_blocked = true;
                $this->sendable->save();

            } else Log::error($e->getMessage());

        }
    }

    private function getReport(TokenReportService $tokenReportService): array
    {
        $params = $tokenReportService->main($this->token, $this->sendable->is_show_warnings, for_group: true);
        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true),];

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        return [$params['text'], $options];
    }
}
