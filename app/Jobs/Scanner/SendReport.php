<?php

namespace App\Jobs\Scanner;

use App\Enums\Language;
use App\Exceptions\ScanningError;
use App\Jobs\Middleware\Localized;
use App\Models\Account;
use App\Models\Token;
use App\Telegram\Handlers\TokenReportHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use Throwable;

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

    public function handle(Nutgram $bot, TokenReportHandler $reportHandler): void
    {
        try {

            $this->token->network->job::dispatchSync($this->token, $this->language);
            $this->token->refresh();

            $reportHandler->pending(
                $bot,
                $this->token,
                $this->account,
                $this->report_message_id,
                type: 'main',
                is_finished: true,
                show_buttons: true,
            );

        } catch (Throwable $e) {

            $bot->set('language', $this->language->value);
            $bot->set('account', $this->account);

            $message = __('telegram.errors.scan.fail', ['address' => $this->token->address]);
            $log_message = "Scan Token Fail: {$this->token->address} ({$e->getMessage()})\n{$e->getTraceAsString()}";

            if ($e instanceof ScanningError) {

                $message = $e->getMessage();
                $log_message = $e->getLogMessage();

            }

            $reportHandler->error($bot, $message, $this->account->telegram_id, $this->report_message_id);
            Log::error($log_message);

        }
    }
}
