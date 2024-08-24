<?php

namespace App\Telegram\Handlers;

use App\Enums\Reaction;
use App\Models\Token;
use App\Services\TokenReportService;
use App\Telegram\Conversations\Home;
use App\Telegram\Conversations\TokenScanner;
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
                reply_message_id: $bot->callbackQuery()->message->reply_to_message->message_id,
                message_id: $bot->callbackQuery()->message->message_id
            );

        }

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

        $likes_count = $token->reactions()->where('type', Reaction::LIKE)->count();
        $dislikes_count = $token->reactions()->where('type', Reaction::DISLIKE)->count();
        $message_effect_id = match ($likes_count <=> $dislikes_count) {
            0 => null,
            -1 => 5104858069142078462, // dislike
            1 => 5107584321108051014, // like
        };

        $options = [
            'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
            'message_effect_id' => $message_effect_id,
            'reply_to_message_id' => $reply_message_id,
        ];

        $params = $tokenReportService->{$type}($token, $bot->get('account'));
        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        $bot->sendImagedMessage($params['text'], $markup, $options, $chat_id, $message_id);

    }
}
