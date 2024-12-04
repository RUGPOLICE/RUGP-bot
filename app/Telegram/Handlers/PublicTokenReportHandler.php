<?php

namespace App\Telegram\Handlers;

use App\Enums\RequestModule;
use App\Enums\RequestSource;
use App\Jobs\Scanner\SendPublicReport;
use App\Models\Network;
use App\Models\Request;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
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

            $chat = $bot->get('chat');
            SendPublicReport::dispatch($token, $chat, $chat->language, $type, $bot->messageId())->delay(now()->addSeconds(2));
            Request::log($bot->get('chat'), $token, RequestSource::TELEGRAM, RequestModule::SCANNER);

        } catch (Throwable $e) {

            $this->send($bot, __('telegram.errors.scan.fail', ['address' => $address['address']]));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return;

        }
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
