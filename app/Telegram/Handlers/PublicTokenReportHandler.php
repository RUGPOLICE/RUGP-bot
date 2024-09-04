<?php

namespace App\Telegram\Handlers;

use App\Exceptions\ScanningError;
use App\Jobs\Scanner\CheckBurnLock;
use App\Jobs\Scanner\SimulateTransactions;
use App\Jobs\Scanner\UpdateHolders;
use App\Jobs\Scanner\UpdateLiquidity;
use App\Jobs\Scanner\UpdateMetadata;
use App\Jobs\Scanner\UpdatePools;
use App\Jobs\Scanner\UpdateStatistics;
use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class PublicTokenReportHandler
{
    public function publicMain(Nutgram $bot, string $search): void
    {
        $this->public($bot, $search, 'main');
    }

    public function publicPrice(Nutgram $bot, string $search): void
    {
        $this->public($bot, $search, 'chart');
    }

    public function publicVolume(Nutgram $bot, string $search): void
    {
        $this->public($bot, $search, 'volume');
    }

    public function publicHolders(Nutgram $bot, string $search): void
    {
        $this->public($bot, $search, 'holders');
    }

    public function public(Nutgram $bot, string $search, string $type): void
    {
        $address = Token::getAddress($search);
        if (!$address['success']) {

            $this->send($bot, $address['error']);
            return;

        }

        try {

            $token = Token::query()->firstOrCreate(['address' => $address['address']]);
            $this->scan($token);

        } catch (\Throwable $e) {

            $this->send($bot, __('telegram.errors.scan.fail', ['address' => $address['address']]));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return;

        }

        [$message, $options] = $this->getReport($bot, $token, $bot->messageId(), $type);
        $this->send($bot, $message, $options);
    }


    private function scan(Token $token): void
    {
        UpdateMetadata::dispatchSync($token);
        UpdatePools::dispatchSync($token);

        $jobs = [
            SimulateTransactions::class,
            UpdateHolders::class,
            UpdateLiquidity::class,
            UpdateStatistics::class,
        ];

        foreach ($jobs as $job) {
            try {

                $job::dispatchSync($token);

            } catch (ScanningError $e) {

                Log::error($e->getLogMessage());

            }
        }

        $token->refresh();
    }

    private function getReport(Nutgram $bot, Token $token, int $message_id, string $type): array
    {
        $chat = $bot->get('chat');
        $tokenReportService = App::make(TokenReportService::class);

        $params = $tokenReportService->{$type}($token, $chat->is_show_warnings, is_finished: true, for_group: true);
        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true)];

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        return [$params['text'], $options];
    }

    private function send(Nutgram $bot, string $message, array $options = []): void
    {
        $bot->asResponse()->sendImagedMessage(
            $message,
            options: $options,
            reply_to_message_id: $bot->messageId(),
        );
    }
}
