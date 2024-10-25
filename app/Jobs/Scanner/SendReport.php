<?php

namespace App\Jobs\Scanner;

use App\Enums\Frame;
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
        $bot->set('language', $this->language->value);
        $bot->set('account', $this->account);

        try {

            $this->token->network->job::dispatchSync($this->token, $this->language, $this->account);
            $this->token->refresh();

            $created_at = $this->token->pools()->first()->created_at;
            if ($created_at >= now()->subDay()) $this->account->frame = Frame::MINUTE;
            else if ($created_at >= now()->subMonth()) $this->account->frame = Frame::MINUTES;
            else if ($created_at >= now()->subMonths(3)) $this->account->frame = Frame::HOURS;
            else $this->account->frame = Frame::DAY;

            $this->account->is_show_chart_text = true;
            $this->account->save();

            $reportHandler->report(
                $bot,
                $this->token,
                type: 'main',
                chat_id: $this->account->telegram_id,
                message_id: $this->report_message_id,
            );

        } catch (Throwable $e) {

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
