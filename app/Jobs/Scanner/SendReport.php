<?php

namespace App\Jobs\Scanner;

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

class SendReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public Token $token,
        public Account $account,
        public int $chat_id,
        public int $report_message_id,
    ) {}

    public function middleware(): array
    {
        return [new Localized];
    }

    public function handle(): void
    {
        $token = $this->token;
        $account = $this->account;
        $chat_id = $this->chat_id;
        $report_message_id = $this->report_message_id;

        try {

            App::call([new UpdateMetadata($token, $account), 'handle']);
            App::call([new UpdatePools($token, $account), 'handle']);

        } catch (\Throwable $e) {

            SendReport::error($e, $token, $account, $chat_id, $report_message_id);
            return;

        }

        Bus::batch([

            new SimulateTransactions($token, $account),
            new UpdateHolders($token, $account),
            new UpdateLiquidity($token, $account),
            new CheckBurnLock($token, $account),

        ])->progress(function (Batch $batch) use ($token, $account, $chat_id, $report_message_id) {

            (new TokenReportHandler)->pending($token, $account, $chat_id, $report_message_id, is_finished: false);

        })->finally(function (Batch $batch) use ($token, $account, $chat_id, $report_message_id) {

            App::call([new UpdateStatistics($token, $account), 'handle']);
            (new TokenReportHandler)->pending($token, $account, $chat_id, $report_message_id, is_finished: true);

        })->allowFailures()->dispatch();
    }

    public static function error(\Throwable $e, Token $token, Account $account, int $chat_id, int $report_message_id): void
    {
        Telegram::set('account', $account);
        $message = __('telegram.errors.scan.fail', ['address' => $token->address]);
        $log_message = "Scan Token Fail: $token->address ({$e->getMessage()})";

        if ($e instanceof ScanningError) {

            $message = $e->getMessage();
            $log_message = $e->getLogMessage();

        }

        (new TokenReportHandler)->error($account, $message, $chat_id, $report_message_id);
        Log::error($log_message);
    }
}
