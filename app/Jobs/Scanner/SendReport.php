<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\ScanningError;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
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

class SendReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Account $account,
        public Language $language,
        public int $report_message_id,
    ) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(Nutgram $bot): void
    {
        $token = $this->token;
        $account = $this->account;
        $language = $this->language;
        $report_message_id = $this->report_message_id;

        try {

            App::call([new UpdateMetadata($token, $language), 'handle']);
            App::call([new UpdatePools($token, $language), 'handle']);
            (new TokenReportHandler)->pending($bot, $token, $account, $report_message_id, type: 'main', is_finished: false, show_buttons: false);

        } catch (\Throwable $e) {

            SendReport::error($e, $token, $account, $language, $report_message_id);
            return;

        }

        Bus::batch([

            new SimulateTransactions($token, $language),
            new UpdateHolders($token, $language),
            new UpdateLiquidity($token, $language),
            new CheckBurnLock($token, $language),

        ])->finally(function (Batch $batch) use ($token, $account, $language, $report_message_id) {

            App::call([new UpdateStatistics($token, $language), 'handle']);
            $bot = app(Nutgram::class);
            (new TokenReportHandler)->pending($bot, $token, $account, $report_message_id, type: 'main', is_finished: true, show_buttons: true);

        })->allowFailures()->dispatch();
    }

    public static function error(\Throwable $e, Token $token, Account $account, Language $language, int $report_message_id): void
    {
        $bot = app(Nutgram::class);
        $bot->set('language', $language->value);
        $bot->set('account', $account);

        $message = __('telegram.errors.scan.fail', ['address' => $token->address]);
        $log_message = "Scan Token Fail: $token->address ({$e->getMessage()})";

        if ($e instanceof ScanningError) {

            $message = $e->getMessage();
            $log_message = $e->getLogMessage();

        }

        (new TokenReportHandler)->error($bot, $message, $account->telegram_id, $report_message_id);
        Log::error($log_message);
    }
}
