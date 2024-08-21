<?php

namespace App\Telegram\Conversations;

use App\Jobs\ScanToken;
use App\Models\Token;
use App\Services\DexScreenerService;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Middleware\SpamProtection;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TokenScanner extends InlineMenu
{
    public function start(Nutgram $bot, string $referrer): void
    {
        $btn = match ($referrer) {
            Home::class => InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'),
            TokenReportHandler::class => InlineKeyboardButton::make(__('telegram.buttons.cancel'), callback_data: 'close@menu'),
        };

        $this->menuText(__('telegram.text.token_scanner.main'))
            ->addButtonRow($btn)
            ->orNext('handle')
            ->showMenu();
    }

    public function menu(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => Home::begin($bot),
        };
    }

    public function handle(Nutgram $bot): void
    {
        if (!(new SpamProtection)($bot))
            return;

        $account = $bot->get('account');
        $message_id = $bot->messageId();
        $address = $bot->message()->text;

        if (mb_strlen($address) < 48) {

            $this->restartWithMessage($bot, __('telegram.errors.address.invalid'));
            return;

        }

        $start = mb_strpos($address, 'pools/') ? mb_strpos($address, 'pools/') + 6 : 0;
        $end = mb_strpos($address, '?') ?: mb_strlen($address);
        $address = mb_substr($address, $start, $end - $start);

        $service = App::make(DexScreenerService::class);
        $address = $service->getTokenAddressByPoolAddress($address);

        if (!$address) {

            $this->restartWithMessage($bot, __('telegram.errors.address.empty'));
            return;

        }

        $this->clearButtons();
        $this->menuText(__('telegram.text.token_scanner.pending'));
        $this->showMenu();

        $token = Token::query()->firstOrCreate(['address' => $address]);
        ScanToken::dispatch($token, $account, $message_id);
    }

    private function restartWithMessage(Nutgram $bot, string $message): void
    {
        $bot->sendMessage($message, reply_to_message_id: $bot->messageId());
        $this->end();
        self::begin($bot);
    }
}
