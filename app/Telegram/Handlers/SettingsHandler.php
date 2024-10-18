<?php

namespace App\Telegram\Handlers;

use App\Enums\Language;
use App\Models\Chat;
use App\Models\Network;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SettingsHandler
{
    public function __invoke(Nutgram $bot): void
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');

        $buttons = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.scam_' . ($chat->is_show_scam ? 'shown' : 'hidden')), callback_data: 'scanner:settings:scam'),
                InlineKeyboardButton::make(__('telegram.buttons.warnings_' . ($chat->is_show_warnings ? 'shown' : 'hidden')), callback_data: 'scanner:settings:warnings'),
            )
            ->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.network'), callback_data: 'scanner:settings:network:0'),
                InlineKeyboardButton::make(__('telegram.buttons.language'), callback_data: 'scanner:settings:language:null'),
            )
            ->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.ok'), callback_data: 'scanner:settings:exit'),
            );

        $this->send($bot, $chat, $buttons);
    }

    public function setScam(Nutgram $bot): void
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');
        $chat->is_show_scam = !$chat->is_show_scam;
        $chat->save();
        $this($bot);
    }

    public function setWarnings(Nutgram $bot): void
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');
        $chat->is_show_warnings = !$chat->is_show_warnings;
        $chat->save();
        $this($bot);
    }

    public function setNetwork(Nutgram $bot, string $option): void
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');

        if ($network = Network::query()->where('slug', $option)->first()) {

            $chat->network()->associate($network);
            $chat->save();
            $this($bot);

        } else {

            $page = intval($option);
            $perPage = 16;
            $perRow = 4;

            $networks = Network::query()
                ->orderByDesc('priority')
                ->limit($perPage)
                ->offset($page * $perPage)
                ->get()
                ->map(fn (Network $network) => InlineKeyboardButton::make(($network->is($chat->network) ? '✅ ' : '') . $network->name, callback_data: "scanner:settings:network:$network->slug"))
                ->chunk($perRow);

            $buttons = InlineKeyboardMarkup::make();
            foreach ($networks as $chunk) {

                $soon = array_map(fn ($n) => InlineKeyboardButton::make(__('telegram.buttons.network_soon'), callback_data: "scanner:settings:network:0"), range(1, $perRow - $chunk->count()));
                $buttons->addRow(... ($chunk->all() + $soon));

            }

            foreach (range(1, 4 - $networks->count()) as $chunk) {

                $soon = array_map(fn ($n) => InlineKeyboardButton::make(__('telegram.buttons.network_soon'), callback_data: "scanner:settings:network:0"), range(1, 4));
                $buttons->addRow(... $soon);

            }

            $this->send($bot, $chat, $buttons);
        }

    }

    public function setLanguage(Nutgram $bot, string $option): void
    {
        /** @var Chat $chat */
        $chat = $bot->get('chat');

        if (in_array($option, Language::keys())) {

            $chat->language = $option;
            $chat->save();

            App::setLocale($chat->language->value);
            $this($bot);

        } else {

            $languages = array_chunk(Language::keys(), 2);
            $buttons = InlineKeyboardMarkup::make();
            foreach ($languages as $language)
                $buttons->addRow(... array_map(fn ($lang) => InlineKeyboardButton::make(__('telegram.buttons.' . $lang) . ($chat->language->value === $lang ? ' ✅' : ' ✔️'), callback_data: "scanner:settings:language:$lang"), $language));

            $this->send($bot, $chat, $buttons);
        }

    }

    public function exit(Nutgram $bot): void
    {
        $bot->deleteMessage($bot->chatId(), $bot->callbackQuery()->message->message_id);
    }

    protected function send(Nutgram $bot, Chat $chat, InlineKeyboardMarkup $buttons): void
    {
        $message_id = $bot->callbackQuery()?->message->message_id;
        $bot->{$message_id ? 'editImagedMessage' : 'sendImagedMessage'}(
            __('telegram.text.settings.main', [
                'is_show_warnings' => __('telegram.text.settings.is_show_warnings.' . ($chat->is_show_warnings ? 'yes' : 'no')),
                'is_show_scam' => __('telegram.text.settings.is_show_scam.' . ($chat->is_show_scam ? 'yes' : 'no')),
                'language' => __('telegram.buttons.' . $chat->language->value),
                'network' => $chat->network?->name ??__('telegram.text.settings.blank_network'),
            ]),
            $buttons,
            options: ['image' => public_path('img/scan.png')]
        );
    }
}
