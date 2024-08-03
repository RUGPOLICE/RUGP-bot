<?php

namespace App\Telegram\Handlers;

use App\Models\Token;
use App\Services\TokenReportService;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class TokenReportHandler
{
    public function route(Nutgram $bot, string $token, string $type): void
    {
        $bot->answerCallbackQuery();
        $token = Token::find($token);

        if ($token && in_array($type, ['main', 'chart', 'holders', 'volume']))
            $this->report($bot, $token, $type, message_id: $bot->callbackQuery()->message->message_id);
    }

    public function main(Nutgram $bot, Token $token, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $this->report($bot, $token, 'main', $chat_id, $reply_message_id, $message_id);
    }

    public function report(Nutgram $bot, Token $token, string $type, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $tokenReportService = App::make(TokenReportService::class);
        $buttons = [
            'main' => InlineKeyboardButton::make('Главная', callback_data: "reports:token:$token->id:main"),
            'chart' => InlineKeyboardButton::make('Чарт', callback_data: "reports:token:$token->id:chart"),
            'holders' => InlineKeyboardButton::make('Холдеры', callback_data: "reports:token:$token->id:holders"),
            'volume' => InlineKeyboardButton::make('Объем', callback_data: "reports:token:$token->id:volume"),
        ];

        unset($buttons[$type]);

        $method = $message_id ? 'editMessageText' : 'sendMessage';
        $options = [
            'chat_id' => $chat_id,
            'parse_mode' => ParseMode::HTML,
            'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
            'reply_markup' => InlineKeyboardMarkup::make()->addRow(... array_values($buttons)),
        ];

        if ($message_id) $options['message_id'] = $message_id;
        else $options['reply_to_message_id'] = $reply_message_id;

        $bot->{$method}($tokenReportService->{$type}($token), ... $options);
    }
}
