<?php

namespace App\Telegram\Handlers;

use App\Enums\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class SettingsHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $chat = $bot->get('chat');
        $bot->sendImagedMessage(
            __('telegram.text.settings.main', [
                'is_show_warnings' => __('telegram.text.settings.is_show_warnings.' . ($chat->is_show_warnings ? 'yes' : 'no')),
                'language' => __('telegram.buttons.' . $chat->language->value),
            ]),
            options: ['image' => public_path('img/scan.png'),]
        );
    }

    public function showWarnings(Nutgram $bot): void
    {
        $chat = $bot->get('chat');
        $chat->is_show_warnings = true;
        $chat->save();
        $this($bot);
    }

    public function hideWarnings(Nutgram $bot): void
    {
        $chat = $bot->get('chat');
        $chat->is_show_warnings = false;
        $chat->save();
        $this($bot);
    }

    public function setRuLanguage(Nutgram $bot): void
    {
        $language = Language::language(mb_strtolower(mb_strcut(__FUNCTION__, 3, 2)));
        $this->setLanguage($bot, $language);
    }

    public function setEnLanguage(Nutgram $bot): void
    {
        $language = Language::language(mb_strtolower(mb_strcut(__FUNCTION__, 3, 2)));
        $this->setLanguage($bot, $language);
    }

    public function setLanguage(Nutgram $bot, Language|string $language): void
    {
        $chat = $bot->get('chat');
        $chat->language = $language;
        $chat->save();

        App::setLocale($language);
        $this($bot);
    }
}
