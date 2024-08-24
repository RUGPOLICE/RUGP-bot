<?php

namespace App\Telegram\Conversations;

use App\Enums\Language;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class Profile extends ImagedInlineMenu
{
    public function start(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $this
            ->clearButtons()
            ->menuText(
                __('telegram.text.profile.main', [
                    'language' => __('telegram.buttons.' . $account->language->value),
                    'is_hide_warnings' => __('telegram.text.profile.warnings.' . ($account->is_hide_warnings ? 'hidden' : 'shown')),
                ])
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.rules'), callback_data: 'null@rules'),
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.' . $account->language->value), callback_data: 'main@language'),
                InlineKeyboardButton::make(__('telegram.buttons.warnings_' . ($account->is_hide_warnings ? 'hidden' : 'shown')), callback_data: 'null@warnings'),
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'),
            )
            ->showMenu();
    }

    public function menu(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $option = $bot->callbackQuery()->data;

        $this->end();
        match ($option) {
            'profile' => Profile::begin($bot),
            'back' => Home::begin($bot),
        };
    }

    public function warnings(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $account->is_hide_warnings = !$account->is_hide_warnings;
        $account->save();

        $this->end();
        Profile::begin($bot);
    }

    public function rules(Nutgram $bot): void
    {
        $this
            ->clearButtons()
            ->menuText(__('telegram.text.rules'))
            ->addButtonRow(InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'profile@menu'))
            ->showMenu();
    }

    public function language(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $option = $bot->callbackQuery()->data;

        if (in_array($option, Language::keys())) {

            $account->language = Language::keys()[(array_search($account->language->value, Language::keys()) + 1) % count(Language::keys())];
            $account->save();

            App::setLocale($account->language->value);
            $this->start($bot);

        } else {

            $account = $bot->get('account');
            $languages = array_chunk(Language::keys(), 2);

            $this->clearButtons()->menuText('Выберите язык');
            foreach ($languages as $language)
                $this->addButtonRow(... array_map(fn ($lang) => InlineKeyboardButton::make(__('telegram.buttons.' . $lang) . ($account->language->value === $lang ? ' ✅' : ' ✔️'), callback_data: $lang . '@language'), $language));

            $this->showMenu();
        }

    }
}
