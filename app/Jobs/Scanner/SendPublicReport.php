<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\ScanningError;
use App\Jobs\Middleware\Localized;
use App\Models\Chat;
use App\Models\Token;
use App\Telegram\Handlers\TokenReportHandler;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Nutgram\Laravel\Facades\Telegram;
use SergiX44\Nutgram\Nutgram;

class SendPublicReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Chat $chat,
        public Language $language,
        public int $report_message_id,
        public string $type,
    ) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(): void
    {
        $token = $this->token;
        $chat = $this->chat;
        $language = $this->language;
        $report_message_id = $this->report_message_id;
        $type = $this->type;

        try {

            App::call([new UpdateMetadata($token, $language), 'handle']);
            App::call([new UpdatePools($token, $language), 'handle']);

        } catch (\Throwable $e) {

            SendPublicReport::error($e, $token, $chat, $language, $report_message_id);
            return;

        }

        Bus::batch([

            new SimulateTransactions($token, $language),
            new UpdateHolders($token, $language),
            new UpdateLiquidity($token, $language),
            new CheckBurnLock($token, $language),

        ])->progress(function (Batch $batch) use ($token, $chat, $report_message_id, $type) {

            $group = new Nutgram(config('nutgram.group_token'));
            (new TokenReportHandler)->pending($group, $token, $chat, $report_message_id, type: $type, is_finished: false, show_buttons: false);

        })->finally(function (Batch $batch) use ($token, $chat, $language, $report_message_id, $type) {

            App::call([new UpdateStatistics($token, $language), 'handle']);
            $group = new Nutgram(config('nutgram.group_token'));
            (new TokenReportHandler)->pending($group, $token, $chat, $report_message_id, type: $type, is_finished: true, show_buttons: false);

        })->allowFailures()->dispatch();
    }

    public static function error(\Throwable $e, Token $token, Chat $chat, Language $language, int $report_message_id): void
    {
        $group = new Nutgram(config('nutgram.group_token'));
        $group->set('language', $language->value);
        $group->set('chat', $chat);

        $message = __('telegram.errors.scan.fail', ['address' => $token->address]);
        $log_message = "Scan Token Fail: $token->address ({$e->getMessage()})";

        if ($e instanceof ScanningError) {

            $message = $e->getMessage();
            $log_message = $e->getLogMessage();

        }

        (new TokenReportHandler)->error($group, $message, $chat->chat_id, $report_message_id);
        Log::error($log_message);
    }
}
