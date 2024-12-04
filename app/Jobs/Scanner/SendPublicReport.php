<?php

namespace App\Jobs\Scanner;

use App\Enums\Frame;
use App\Enums\Language;
use App\Exceptions\ScanningError;
use App\Jobs\Middleware\Localized;
use App\Models\Chat;
use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use Throwable;

class SendPublicReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Chat $chat,
        public Language $language,
        public string $type,
        public int|null $messageId,
    ) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(TokenReportService $tokenReportService): void
    {
        $bot = new Nutgram(config('nutgram.group_token'), new Configuration(botName: config('nutgram.group_bot_name')));
        $bot->set('language', $this->language->value);
        $bot->set('chat', $this->chat);

        try {

            $this->token->network->job::dispatchSync($this->token, $this->language, $this->chat);
            $this->token->refresh();

            $created_at = $this->token->pools()->first()->created_at;
            if ($created_at >= now()->subDay()) $frame = Frame::MINUTE;
            else if ($created_at >= now()->subMonth()) $frame = Frame::MINUTES;
            else if ($created_at >= now()->subMonths(3)) $frame = Frame::HOURS;
            else $frame = Frame::DAY;

            $tokenReportService->setWarningsEnabled($this->chat->is_show_warnings)->setFinished()->setForGroup();

            $options = [
                'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
                'chat_id' => $this->chat->telegram_id,
            ];

            $params = match($this->type) {
                'main' => $tokenReportService->main($this->token),
                'chart' => $tokenReportService->chart($this->token, $frame, is_show_text: true),
                'holders' => $tokenReportService->holders($this->token),
            };

            if (array_key_exists('image', $params))
                $options['image'] = $params['image'];

            $bot->sendImagedMessage(
                $params['text'],
                options: $options,
                reply_to_message_id: $this->messageId,
            );

        } catch (Throwable $e) {

            if (!str_contains($e->getMessage(), 'MEDIA_EMPTY') && !str_contains($e->getMessage(), 'wrong type of the web page content')) {

                $message = __('telegram.errors.scan.fail', ['address' => $this->token->address]);
                $log_message = "Scan Token Fail: {$this->token->address} ({$e->getMessage()})\n{$e->getTraceAsString()}";

                if ($e instanceof ScanningError) {

                    $message = $e->getMessage();
                    $log_message = $e->getLogMessage();

                }

                Log::error($log_message);
                $bot->sendImagedMessage(
                    $message,
                    chat_id: $this->chat->telegram_id,
                    reply_to_message_id: $this->messageId,
                );

            } else {

                $options['image'] = public_path('img/blank.png');
                $bot->sendImagedMessage(
                    $params['text'],
                    chat_id: $this->chat->telegram_id,
                    options: $options,
                    reply_to_message_id: $bot->messageId(),
                );

            }

        }
    }
}
