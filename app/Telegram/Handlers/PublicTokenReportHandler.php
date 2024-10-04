<?php

namespace App\Telegram\Handlers;

use App\Enums\RequestModule;
use App\Enums\RequestSource;
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
    public function publicMain(Nutgram $bot, string $search, string $explicit_network = ''): void
    {
        $this->public($bot, $search . $explicit_network, 'main');
    }

    public function publicPrice(Nutgram $bot, string $search, string $explicit_network = ''): void
    {
        $this->public($bot, $search . $explicit_network, 'chart');
    }

    public function publicVolume(Nutgram $bot, string $search, string $explicit_network = ''): void
    {
        $this->public($bot, $search . $explicit_network, 'volume');
    }

    public function publicHolders(Nutgram $bot, string $search, string $explicit_network = ''): void
    {
        $this->public($bot, $search . $explicit_network, 'holders');
    }

    public function public(Nutgram $bot, string $search, string $type): void
    {
        $address = Token::getAddress($search, $bot->get('chat')?->network);
        if (!$address['success']) {

            $this->send($bot, $address['error']);
            return;

        }

        try {

            $network = Network::query()->where('slug', $address['network'])->first();
            $token = Token::query()->firstOrCreate(['address' => $address['address']]);

            $token->network()->associate($network);
            $token->save();

            $network->job::dispatchSync($token, $bot->get('chat')->language);

        } catch (Throwable $e) {

            $this->send($bot, __('telegram.errors.scan.fail', ['address' => $address['address']]));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return;

        }

        Request::log($bot->get('chat'), $token, RequestSource::TELEGRAM, RequestModule::SCANNER);

        [$message, $options] = $this->getReport($bot, $token, $type);
        $this->send($bot, $message, $options);
    }

    private function getReport(Nutgram $bot, Token $token, string $type): array
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
