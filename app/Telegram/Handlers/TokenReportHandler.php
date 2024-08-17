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
    public function error(Nutgram $bot, string $message, string $chat_id, int $reply_message_id): void
    {
        $bot->sendMessage(
            $message,
            chat_id: $chat_id,
            parse_mode: ParseMode::HTML,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
            reply_to_message_id: $reply_message_id,
        );
    }

    public function route(Nutgram $bot, string $token, string $type): void
    {
        $bot->answerCallbackQuery();
        $token = Token::find($token);

        if ($token && in_array($type, ['main', 'chart', 'holders', 'volume']))
            $this->report($bot, $token, $type, chat_id: $bot->callbackQuery()->message->chat->id, message_id: $bot->callbackQuery()->message->message_id);
    }

    public function main(Nutgram $bot, Token $token, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $this->report($bot, $token, 'main', $chat_id, $reply_message_id, $message_id);
    }

    public function report(Nutgram $bot, Token $token, string $type, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        $tokenReportService = App::make(TokenReportService::class);
        $markup = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.chart'), callback_data: "reports:token:$token->id:chart"),
                InlineKeyboardButton::make(__('telegram.buttons.holders'), callback_data: "reports:token:$token->id:holders"),
                InlineKeyboardButton::make(__('telegram.buttons.volume'), callback_data: "reports:token:$token->id:volume"),
            );

        if ($type !== 'main')
            $markup->addRow(InlineKeyboardButton::make(__('telegram.buttons.report'), callback_data: "reports:token:$token->id:main"));

        $options = ['link_preview_options' => LinkPreviewOptions::make(is_disabled: true)];
        if (!$message_id)
            $options['reply_to_message_id'] = $reply_message_id;

        $params = $tokenReportService->{$type}($token);
        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        $bot->sendImagedMessage($params['text'], $markup, $options, $chat_id, $message_id);

    }
}
