<?php

namespace App\Telegram\Handlers;

use App\Enums\Reaction;
use App\Models\Account;
use App\Models\Token;
use App\Services\TokenReportService;
use App\Telegram\Conversations\Home;
use App\Telegram\Conversations\TokenScanner;
use Illuminate\Support\Facades\App;
use Nutgram\Laravel\Facades\Telegram;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatAction;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class TokenReportHandler
{
    public function route(Nutgram $bot, string $token, string $type): void
    {
        $bot->answerCallbackQuery();

        if ($type === 'back') {

            TokenScanner::begin($bot, data: ['referrer' => TokenReportHandler::class]);
            return;

        }

        if ($type === 'home') {

            Home::begin($bot);
            return;

        }

        $token = Token::find($token);
        if ($token && in_array($type, ['main', 'chart', 'holders', 'volume', 'like', 'dislike'])) {

            if (in_array($type, Reaction::all())) {

                \App\Models\Reaction::query()->updateOrCreate([
                    'token_id' => $token->id,
                    'account_id' => $bot->get('account')->id,
                ], ['type' => $type]);
                $type = 'main';

            }

            $this->report(
                $bot,
                $token,
                $type,
                chat_id: $bot->callbackQuery()->message->chat->id,
                message_id: $bot->callbackQuery()->message->message_id
            );

        }

    }

    public function pending(Token $token, Account $account, int $chat_id, int $message_id, bool $is_finished): void
    {
        Telegram::set('account', $account);
        $tokenReportService = App::make(TokenReportService::class);

        $params = $tokenReportService->main($token, $account, $is_finished);
        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true)];

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        Telegram::editImagedMessage($params['text'], buttons: $is_finished ? self::getButtons($token) : null, options: $options, chat_id: $chat_id, message_id: $message_id);
        if (!$is_finished) Telegram::sendChatAction(ChatAction::TYPING->value, chat_id: $chat_id);
    }

    public function error(Account $account, string $message, string $chat_id, int $message_id): void
    {
        Telegram::set('account', $account);
        Telegram::editImagedMessage(
            $message,
            options: ['image' => public_path('img/scan.png')],
            chat_id: $chat_id,
            message_id: $message_id,
        );
    }

    public function report(Nutgram $bot, Token $token, string $type, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $tokenReportService = App::make(TokenReportService::class);
        $options = [
            'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
            'reply_to_message_id' => $reply_message_id,
        ];

        $params = $tokenReportService->{$type}($token, $bot->get('account'));
        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        if (!$message_id) $bot->sendImagedMessage($params['text'], self::getButtons($token), $options, $chat_id, $message_id);
        else $bot->editImagedMessage($params['text'], self::getButtons($token), $options, $chat_id, $message_id);

    }

    private static function getButtons(Token $token): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.report'), callback_data: "reports:token:$token->id:main"),
                InlineKeyboardButton::make(__('telegram.buttons.chart'), callback_data: "reports:token:$token->id:chart"),
                InlineKeyboardButton::make(__('telegram.buttons.volume'), callback_data: "reports:token:$token->id:volume"),
                InlineKeyboardButton::make(__('telegram.buttons.holders'), callback_data: "reports:token:$token->id:holders"),
            )
            ->addRow(
                InlineKeyboardButton::make(Reaction::verbose(Reaction::LIKE), callback_data: "reports:token:$token->id:" . Reaction::LIKE->value),
                InlineKeyboardButton::make(Reaction::verbose(Reaction::DISLIKE), callback_data: "reports:token:$token->id:" . Reaction::DISLIKE->value),
                InlineKeyboardButton::make(__('telegram.buttons.to_scanner'), callback_data: "reports:token:$token->id:back"),
                InlineKeyboardButton::make(__('telegram.buttons.to_home'), callback_data: "reports:token:$token->id:home"),
            );
    }
}
