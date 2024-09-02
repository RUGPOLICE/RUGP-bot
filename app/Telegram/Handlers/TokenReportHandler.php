<?php

namespace App\Telegram\Handlers;

use App\Enums\Reaction;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Token;
use App\Services\TokenReportService;
use App\Telegram\Conversations\HomeMenu;
use App\Telegram\Conversations\TokenScannerMenu;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class TokenReportHandler
{
    public function route(Nutgram $bot, string $token, string $type): void
    {
        $bot->asResponse()->answerCallbackQuery();

        if ($type === 'back') {

            TokenScannerMenu::begin($bot, data: ['referrer' => TokenReportHandler::class]);
            return;

        }

        if ($type === 'home') {

            HomeMenu::begin($bot);
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

    public function error(Nutgram $bot, string $message, string $chat_id, int $message_id): void
    {
        $bot->editImagedMessage(
            $message,
            options: ['image' => public_path('img/scan.png')],
            chat_id: $chat_id,
            message_id: $message_id,
        );
    }

    public function pending(Nutgram $bot, Token $token, Account|Chat $sendable, int $message_id, string $type, bool $is_finished, bool $show_buttons): void
    {
        $bot->set('language', $sendable->language->value);
        if ($sendable instanceof Account) {

            $bot->set('account', $sendable);
            $chat_id = $sendable->telegram_id;
            $is_show_warnings = !$sendable->is_hide_warnings;
            $for_group = false;

        } else {

            $bot->set('chat', $sendable);
            $chat_id = $sendable->chat_id;
            $is_show_warnings = $sendable->is_show_warnings;
            $for_group = true;

        }

        $tokenReportService = App::make(TokenReportService::class);
        $params = $tokenReportService->{$type}($token, $is_show_warnings, $is_finished, $for_group);
        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true)];

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        $bot->editImagedMessage($params['text'], buttons: $show_buttons ? self::getButtons($token) : null, options: $options, chat_id: $chat_id, message_id: $message_id);
        // if (!$is_finished) $bot->sendChatAction(ChatAction::TYPING->value, chat_id: $chat_id);
    }

    public function report(Nutgram $bot, Token $token, string $type, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $tokenReportService = App::make(TokenReportService::class);
        $options = [
            'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
            'reply_to_message_id' => $reply_message_id,
        ];

        $params = $tokenReportService->{$type}($token, !$bot->get('account')->is_hide_warnings);
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
