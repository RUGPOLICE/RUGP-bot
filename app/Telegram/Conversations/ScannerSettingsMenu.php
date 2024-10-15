<?php

namespace App\Telegram\Conversations;

use App\Models\Network;
use App\Telegram\Handlers\TokenReportHandler;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ScannerSettingsMenu extends ImagedEditableInlineMenu
{
    public function start(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $this
            ->clearButtons()
            ->menuText(
                __('telegram.text.scanner_settings.main', [
                    'network' => $account->network?->name ?? __('telegram.text.scanner_settings.blank_network'),
                    'is_show_warnings' => __('telegram.text.scanner_settings.is_show_warnings.' . ($account->is_hide_warnings ? 'no' : 'yes')),
                    'is_show_scam' => __('telegram.text.scanner_settings.is_show_scam.' . ($account->is_show_scam ? 'yes' : 'no')),
                ])
            )
            ->addButtonRow(
                InlineKeyboardButton::make(__('telegram.buttons.scam_' . ($account->is_show_scam ? 'shown' : 'hidden')), callback_data: 'null@scam'),
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
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => TokenScannerMenu::begin($bot, data: ['referrer' => TokenReportHandler::class]),
        };
    }

    public function warnings(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $account->is_hide_warnings = !$account->is_hide_warnings;
        $account->save();

        $this->end();
        ScannerSettingsMenu::begin($bot);
    }

    public function scam(Nutgram $bot): void
    {
        $account = $bot->get('account');
        $account->is_show_scam = !$account->is_show_scam;
        $account->save();

        $this->end();
        ScannerSettingsMenu::begin($bot);
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
            $perPage = 16;
            $perRow = 4;

            $networks = Network::query()
                ->orderByDesc('priority')
                ->limit($perPage)
                ->offset($page * $perPage)
                ->get()
                ->map(fn (Network $network) => InlineKeyboardButton::make(($network->is($account->network) ? '• ' : '') . $network->name, callback_data: "$network->slug@network"))
                ->chunk($perRow);

            $this->clearButtons()->menuText(__('telegram.text.profile.network'));
            foreach ($networks as $chunk) {

                $soon = array_map(fn ($n) => InlineKeyboardButton::make(__('telegram.buttons.network_soon'), callback_data: "nullslug@network"), range(1, $perRow - $chunk->count()));
                $this->addButtonRow(... ($chunk->all() + $soon));

            }

            foreach (range(1, 4 - $networks->count()) as $chunk) {

                $soon = array_map(fn ($n) => InlineKeyboardButton::make(__('telegram.buttons.network_soon'), callback_data: "nullslug@network"), range(1, 4));
                $this->addButtonRow(... $soon);

            }

            $this->showMenu();
        }

    }
}
