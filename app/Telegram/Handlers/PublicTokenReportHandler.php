<?php

namespace App\Telegram\Handlers;

use App\Enums\Frame;
use App\Enums\RequestModule;
use App\Enums\RequestSource;
use App\Models\Chat;
use App\Models\Network;
use App\Models\Request;
use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use Throwable;

class PublicTokenReportHandler
{
    public function publicMain(Nutgram $bot, string $search, ?string $explicit_network = null): void
    {
        $this->public($bot, $search, 'main', $explicit_network);
    }

    public function publicPrice(Nutgram $bot, string $search, ?string $explicit_network = null): void
    {
        $this->public($bot, $search, 'chart', $explicit_network);
    }

    public function publicHolders(Nutgram $bot, string $search, ?string $explicit_network = null): void
    {
        $this->public($bot, $search, 'holders', $explicit_network);
    }

    public function public(Nutgram $bot, string $search, string $type, ?string $explicit_network = null): void
    {
        $networks = Network::all();
        if ($explicit_network && !$networks->pluck('slug')->contains(strtolower($explicit_network)) && !$networks->pluck('alias')->contains(strtolower($explicit_network)))
            return;

        $address = Token::getAddress($search . ' ' . strtolower($explicit_network), $bot->get('chat')?->network);
        if (!$address['success']) {

            $this->send($bot, $address['error']);
            return;

        }

        try {

            $network = Network::query()->where('slug', $address['network'])->first();
            $token = Token::query()->firstOrCreate(['address' => $address['address']]);

            $token->network()->associate($network);
            $token->save();

            $network->job::dispatchSync($token, $bot->get('chat')->language, $bot->get('chat'));
            $token->refresh();

            $created_at = $token->pools()->first()->created_at;
            if ($created_at >= now()->subDay()) $frame = Frame::MINUTE;
            else if ($created_at >= now()->subMonth()) $frame = Frame::MINUTES;
            else if ($created_at >= now()->subMonths(3)) $frame = Frame::HOURS;
            else $frame = Frame::DAY;

        } catch (Throwable $e) {

            $this->send($bot, __('telegram.errors.scan.fail', ['address' => $address['address']]));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return;

        }

        Request::log($bot->get('chat'), $token, RequestSource::TELEGRAM, RequestModule::SCANNER);

        [$message, $options] = $this->getReport($bot, $token, $type, $frame);
        $this->send($bot, $message, $options);
    }

    private function getReport(Nutgram $bot, Token $token, string $type, Frame $frame): array
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');

        /** @var TokenReportService $tokenReportService */
        $tokenReportService = App::make(TokenReportService::class);
        $tokenReportService->setWarningsEnabled($chat->is_show_warnings)->setFinished()->setForGroup();

        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true)];
        $params = match($type) {
            'main' => $tokenReportService->main($token),
            'chart' => $tokenReportService->chart($token, $frame, is_show_text: true),
            'holders' => $tokenReportService->holders($token),
        };

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        return [$params['text'], $options];
    }

    private function send(Nutgram $bot, string $message, array $options = []): void
    {
        try {

            $bot->sendImagedMessage(
                $message,
                options: $options,
                reply_to_message_id: $bot->messageId(),
            );

        } catch (\Throwable $e) {

            if (!str_contains($e->getMessage(), 'MEDIA_EMPTY') && !str_contains($e->getMessage(), 'wrong type of the web page content'))
                throw $e;

            $options['image'] = public_path('img/blank.png');
            $bot->sendImagedMessage(
                $message,
                options: $options,
                reply_to_message_id: $bot->messageId(),
            );

        }
    }
}
