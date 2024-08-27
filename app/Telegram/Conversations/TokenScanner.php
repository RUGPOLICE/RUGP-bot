<?php

namespace App\Telegram\Conversations;

use App\Exceptions\ScanningError;
use App\Jobs\Scanner\CheckBurnLock;
use App\Jobs\Scanner\SendReport;
use App\Jobs\Scanner\SimulateTransactions;
use App\Jobs\Scanner\UpdateHolders;
use App\Jobs\Scanner\UpdateLiquidity;
use App\Jobs\Scanner\UpdateMetadata;
use App\Jobs\Scanner\UpdatePools;
use App\Models\Token;
use App\Services\DexScreenerService;
use App\Telegram\Handlers\TokenReportHandler;
use App\Telegram\Middleware\SpamProtection;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatAction;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TokenScanner extends ImagedInlineMenu
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

        $message_id = $bot->messageId();
        $address = $bot->message()->text;

        if (mb_strlen($address) < 48) {

            $this->restartWithMessage($bot, __('telegram.errors.address.invalid'));
            return;

        }

        $address = explode('/', $address);
        $address = $address[count($address) - 1];

        if (mb_strpos($address, '?') !== false)
            $address = mb_substr($address, 0, mb_strpos($address, '?'));

        $service = App::make(DexScreenerService::class);
        $address = $service->getTokenAddressByPoolAddress($address);

        if (!$address) {

            $this->restartWithMessage($bot, __('telegram.errors.address.empty'));
            return;

        }

        $message_id = $bot->sendImagedMessage(
            __('telegram.text.token_scanner.pending'),
            options: [
                'image' => public_path('img/home.png'),
                'reply_to_message_id' => $message_id,
            ]
        )->message_id;

        $token = Token::query()->firstOrCreate(['address' => $address]);
        SendReport::dispatch($token, $bot->get('account'), $this->bot->chatId(), $message_id);

        $this->end();
        $bot->sendChatAction(ChatAction::TYPING);
    }

    private function restartWithMessage(Nutgram $bot, string $message): void
    {
        $bot->sendImagedMessage($message, reply_to_message_id: $bot->messageId());
        $this->end();
        self::begin($bot);
    }
}
