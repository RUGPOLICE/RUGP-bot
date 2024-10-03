<?php

namespace App\Telegram\Conversations;

use App\Enums\Language;
use App\Models\Network;
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
                    'network' => $account->network?->name ?? __('telegram.text.profile.blank_network'),
                    'is_hide_warnings' => __('telegram.text.profile.warnings.' . ($account->is_hide_warnings ? 'hidden' : 'shown')),
                ])
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.rules'), callback_data: 'null@rules'),
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.language'), callback_data: 'main@language'),
                InlineKeyboardButton::make(__('telegram.buttons.network'), callback_data: '0@network'),
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
            'profile' => ProfileMenu::begin($bot),
            'back' => HomeMenu::begin($bot),
        };
    }

    public function warnings(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $account->is_hide_warnings = !$account->is_hide_warnings;
        $account->save();

        $this->end();
        ProfileMenu::begin($bot);
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

    public function network(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $option = $bot->callbackQuery()->data;

        if ($network = Network::query()->where('slug', $option)->first()) {

            $account->network()->associate($network);
            $account->save();
            $this->start($bot);

        } else {

            $page = intval($option);
            $perPage = 9;

            $networks = Network::query()
                ->orderByDesc('priority')
                ->limit($perPage)
                ->offset($page * $perPage)
                ->get()
                ->map(fn (Network $network) => InlineKeyboardButton::make($network->name, callback_data: "$network->slug@network"))
                ->chunk(3);

            $this->clearButtons()->menuText(__('telegram.text.profile.network'));
            foreach ($networks as $chunk)
                $this->addButtonRow(... $chunk->all());

            $this->showMenu();
        }

    }
}
