<?php

namespace App\Telegram\Conversations;

use App\Enums\RequestModule;
use App\Enums\RequestSource;
use App\Jobs\Scanner\SendReport;
use App\Models\Account;
use App\Models\Network;
use App\Models\Request;
use App\Models\Token;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Middleware\SpamProtection;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatAction;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TokenScannerMenu extends ImagedEditableInlineMenu
{
    public function start(Nutgram $bot, string $referrer = HomeMenu::class): void
    {
        /** @var Account $account */
        $account = $bot->get('account');

        $network = $account->network ?? Network::getDefault();
        $buttons = match ($referrer) {
            HomeMenu::class => [
                InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'),
                InlineKeyboardButton::make(__('telegram.buttons.network'), callback_data: '0@network'),
            ],
            TokenReportHandler::class => [
                InlineKeyboardButton::make(__('telegram.buttons.cancel'), callback_data: 'close@menu'),
            ],
        };

        $examples = $account->is_show_warnings ? __('telegram.text.token_scanner.examples') : '';
        $this->menuText(__('telegram.text.token_scanner.main', ['network' => $network->name]) . $examples)
            ->clearButtons()
            ->addButtonRow(... $buttons)
            ->orNext('handle')
            ->showMenu();
    }

    public function menu(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => HomeMenu::begin($bot),
            default => null,
        };
    }

    public function handle(Nutgram $bot): void
    {
        if (!(new SpamProtection)($bot))
            return;

        $account = $bot->get('account');
        $message_id = $bot->messageId();
        $address = $bot->message()->text;

        $networks = Network::all()->pluck('slug')->implode('|');
        $matches = [];
        if (!str_contains($address, '://') && preg_match("/^(\\\$\w*|EQ.{46}|0x.{40}|T.{33}|.{43}|.{44})(\s($networks))?$/i", $address, $matches) !== 1) {

            $this->restartWithMessage($bot, __('telegram.errors.address.retype'));
            return;

        }

        if (str_contains($address, '://'))
            $address = array_reverse(explode('://', $address))[0];

        $address = Token::getAddress($address, $account->network);
        if (!$address['success']) {

            $this->restartWithMessage($bot, $address['error']);
            return;

        }

        $message_id = $bot->sendImagedMessage(
            __('telegram.text.token_scanner.pending'),
            options: [
                'image' => public_path('img/scan.png'),
                'reply_to_message_id' => $message_id,
            ]
        )->message_id;

        $network = Network::query()->where('slug', $address['network'])->first();
        $token = Token::query()->firstOrCreate(['address' => $address['address']]);

        $token->network()->associate($network);
        $token->save();

        SendReport::dispatch($token, $account, $account->language, $message_id)->delay(now()->addSeconds(2));
        Request::log($account, $token, RequestSource::TELEGRAM, RequestModule::SCANNER);

        $this->end();
        $bot->sendChatAction(ChatAction::TYPING);
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

            $this->clearButtons()->menuText(__('telegram.text.scanner_settings.network'));
            foreach ($networks as $chunk) {

                $soon = array_map(fn ($n) => InlineKeyboardButton::make(__('telegram.buttons.network_soon'), callback_data: "nullslug@network"), range(1, $perRow - $chunk->count()));
                $this->addButtonRow(... ($chunk->all() + $soon));

            }

            $this->showMenu();
        }

    }

    private function restartWithMessage(Nutgram $bot, string $message): void
    {
        $bot->sendImagedMessage($message, reply_to_message_id: $bot->messageId());
        $this->end();
        self::begin($bot, data: ['referrer' => HomeMenu::class]);
    }
}
