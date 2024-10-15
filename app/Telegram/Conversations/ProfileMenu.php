<?php

namespace App\Telegram\Conversations;

use App\Enums\Language;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ProfileMenu extends ImagedInlineMenu
{
    public function start(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $this
            ->clearButtons()
            ->menuText(
                __('telegram.text.profile.main', [
                    'language' => __('telegram.buttons.' . $account->language->value),
                ])
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.rules'), callback_data: 'null@rules'),
                InlineKeyboardButton::make(__('telegram.buttons.language'), callback_data: 'main@language'),
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
            'profile' => ProfileMenu::begin($bot),
            'back' => HomeMenu::begin($bot),
        };
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

            $account->language = $option;
            $account->save();

            App::setLocale($account->language->value);
            $this->start($bot);

        } else {

            $languages = array_chunk(Language::keys(), 2);
            $this->clearButtons()->menuText(__('telegram.text.profile.language'));
            foreach ($languages as $language)
                $this->addButtonRow(... array_map(fn ($lang) => InlineKeyboardButton::make(__('telegram.buttons.' . $lang) . ($account->language->value === $lang ? ' ✅' : ' ✔️'), callback_data: $lang . '@language'), $language));

            $this->showMenu();
        }

    }
}
