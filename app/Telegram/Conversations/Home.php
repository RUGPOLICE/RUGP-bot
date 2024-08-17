<?php

namespace App\Telegram\Conversations;

use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class Home extends ImagedInlineMenu
{
    public function start(Nutgram $bot): void
    {
        $account = $bot->get('account');

        if (!$account->is_shown_language) {

            $this->langFront($bot);
            return;

        }

        if (!$account->is_shown_rules) {

            $this->rulesFront($bot);
            return;

        }

        $this->menuFront($bot);
    }


    public function langFront(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText(__('telegram.text.lang'));
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.ru'), callback_data: 'ru@langBack'),
            InlineKeyboardButton::make(__('telegram.buttons.en'), callback_data: 'en@langBack'),
        );
        $this->showMenu();
    }

    public function langBack(Nutgram $bot): void
    {
        $lang = $bot->callbackQuery()->data;
        if (!in_array($lang, \App\Enums\Language::keys()))
            return;

        $account = $bot->get('account');
        $account->language = $lang;
        // $account->is_shown_language = true;
        $account->save();

        App::setLocale($lang);

        if ($account->is_shown_rules) $this->menuFront($bot);
        else $this->rulesFront($bot);
    }


    public function rulesFront(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText(__('telegram.text.rules'));
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.agree'), callback_data: 'yes@rulesBack'),
        );
        $this->showMenu();
    }

    public function rulesBack(Nutgram $bot): void
    {
        if ($bot->callbackQuery()->data !== 'yes')
            return;

        $account = $bot->get('account');
        $account->is_shown_rules = true;
        $account->save();

        $this->menuFront($bot);
    }


    public function menuFront(Nutgram $bot): void
    {
        $this->clearButtons();
        $this->menuText(__('telegram.text.home'), ['image' => public_path('img/home.png')]);
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.token_scanner'), callback_data: 'token_scanner@menuBack'),
            InlineKeyboardButton::make(__('telegram.buttons.wallet_tracker'), callback_data: 'wallet_tracker@menuBack'),
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.black_box'), callback_data: 'black_box@menuBack'),
            InlineKeyboardButton::make(__('telegram.buttons.check_wallet'), callback_data: 'check_wallet@menuBack'),
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.academy'), callback_data: 'academy@menuBack'),
            InlineKeyboardButton::make(__('telegram.buttons.gpt'), callback_data: 'gpt@menuBack'),
        );
        $this->addButtonRow(
            InlineKeyboardButton::make(__('telegram.buttons.profile'), callback_data: 'profile@menuBack')
        );
        $this->showMenu();
    }

    public function menuBack(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'token_scanner' => TokenScanner::begin($bot),
        };
    }
}
