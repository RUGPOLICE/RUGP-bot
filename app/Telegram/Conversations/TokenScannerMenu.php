<?php

namespace App\Telegram\Conversations;

use App\Jobs\Scanner\SendReport;
use App\Models\Token;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Middleware\SpamProtection;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatAction;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TokenScannerMenu extends ImagedInlineMenu
{
    public function start(Nutgram $bot, string $referrer): void
    {
        $buttons = match ($referrer) {
            HomeMenu::class => [
                InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'),
            ],
            TokenReportHandler::class => [
                InlineKeyboardButton::make(__('telegram.buttons.cancel'), callback_data: 'close@menu'),
            ],
        };

        $this->menuText(__('telegram.text.token_scanner.main'))
            ->addButtonRow(... $buttons)
            ->orNext('handle')
            ->showMenu();
    }

    public function menu(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => HomeMenu::begin($bot),
        };
    }

    public function handle(Nutgram $bot): void
    {
        if (!(new SpamProtection)($bot))
            return;

        $account = $bot->get('account');
        $message_id = $bot->messageId();
        $address = $bot->message()->text;

        $address = Token::getAddress($address);
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

        $token = Token::query()->firstOrCreate(['address' => $address['address']]);
        SendReport::dispatch($token, $account, $account->language, $message_id);

        $this->end();
        $bot->sendChatAction(ChatAction::TYPING);
    }

    private function restartWithMessage(Nutgram $bot, string $message): void
    {
        $bot->sendImagedMessage($message, reply_to_message_id: $bot->messageId());
        $this->end();
        self::begin($bot, data: ['referrer' => HomeMenu::class]);
    }
}
