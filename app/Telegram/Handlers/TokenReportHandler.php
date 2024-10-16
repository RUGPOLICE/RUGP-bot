<?php

namespace App\Telegram\Handlers;

use App\Enums\Frame;
use App\Enums\Reaction;
use App\Models\Account;
use App\Models\Token;
use App\Services\TokenReportService;
use App\Telegram\Conversations\HomeMenu;
use App\Telegram\Conversations\ScannerSettingsMenu;
use App\Telegram\Conversations\TokenScannerMenu;
use Illuminate\Support\Facades\App;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class TokenReportHandler
{
    public function route(Nutgram $bot, string $token, string $type): mixed
    {
        $navigation = ['back', 'home', 'settings', 'pro'];
        $types = ['main', 'chart', 'holders'];

        if (in_array($type, $navigation)) {

            $bot->asResponse()->answerCallbackQuery();
            return match ($type) {
                'back' => TokenScannerMenu::begin($bot, data: ['referrer' => TokenReportHandler::class]),
                'home' => HomeMenu::begin($bot),
                'settings' => ScannerSettingsMenu::begin($bot),
                'pro' => null,
            };

        }

        $account = $bot->get('account');
        $token = Token::find(intval($token));
        @[$type, $action] = explode('_', $type);

        if ($token && in_array($type, $types)) {

            if ($type === 'main' && in_array($action, Reaction::all()))
                \App\Models\Reaction::query()->updateOrCreate([
                    'token_id' => $token->id,
                    'account_id' => $bot->get('account')->id,
                ], ['type' => $action]);

            if ($type === 'chart' && $action !== null) {

                if ($action === 'clock') $account->update(['is_show_chart_text' => !$account->is_show_chart_text]);
                else if (!$account->is_show_chart_text) $account->update(['frame' => Frame::key($action)]);
                else {

                    $bot->asResponse()->answerCallbackQuery(text: __('telegram.text.token_scanner.chart.clock'), show_alert: true);
                    return false;

                }

            }

            $this->report(
                $bot,
                $token,
                $type,
                $action,
                chat_id: $bot->callbackQuery()->message->chat->id,
                message_id: $bot->callbackQuery()->message->message_id,
            );

        }

        $bot->asResponse()->answerCallbackQuery();
        return true;
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

    public function report(Nutgram $bot, Token $token, string $type, ?string $action = null, ?int $chat_id = null, ?int $reply_message_id = null, ?int $message_id = null): void
    {
        /** @var Account $account */
        $account = $bot->get('account');

        $tokenReportService = App::make(TokenReportService::class);
        $options = [
            'link_preview_options' => LinkPreviewOptions::make(is_disabled: true),
            'reply_to_message_id' => $reply_message_id,
        ];

        $params = match($type) {
            'main' => $tokenReportService->main($token, $account->is_show_warnings),
            'chart' => $tokenReportService->chart($token, $account->frame, $account->is_show_chart_text, $account->is_show_warnings),
            'holders' => $tokenReportService->holders($token, $account->is_show_warnings),
        };

        if (array_key_exists('image', $params))
            $options['image'] = $params['image'];

        if (!$message_id) $bot->sendImagedMessage($params['text'], self::getButtons($token, $type), $options, $chat_id, $message_id);
        else $bot->editImagedMessage($params['text'], self::getButtons($token, $type), $options, $chat_id, $message_id);
    }


    private static function getButtons(Token $token, string $type): InlineKeyboardMarkup
    {
        $markup = InlineKeyboardMarkup::make();
        $markup->addRow(
            InlineKeyboardButton::make(__('telegram.buttons.report'), callback_data: "reports:token:$token->id:main"),
            InlineKeyboardButton::make(__('telegram.buttons.chart'), callback_data: "reports:token:$token->id:chart"),
            InlineKeyboardButton::make(__('telegram.buttons.holders'), callback_data: "reports:token:$token->id:holders"),
        );

        $markup = match ($type) {
            'main' => $markup->addRow(
                InlineKeyboardButton::make(Reaction::verbose(Reaction::LIKE), callback_data: "reports:token:$token->id:main_" . Reaction::LIKE->value),
                InlineKeyboardButton::make(Reaction::verbose(Reaction::DISLIKE), callback_data: "reports:token:$token->id:main_" . Reaction::DISLIKE->value),
                InlineKeyboardButton::make(__('telegram.buttons.pro'), callback_data: "reports:token:$token->id:pro"),
            ),
            'chart' => $markup->addRow(
                InlineKeyboardButton::make(__('telegram.buttons.clock'), callback_data: "reports:token:$token->id:chart_clock"),
                InlineKeyboardButton::make(__('telegram.buttons.chart_aggregate_1'), callback_data: "reports:token:$token->id:chart_" . Frame::MINUTE->value),
                InlineKeyboardButton::make(__('telegram.buttons.chart_aggregate_2'), callback_data: "reports:token:$token->id:chart_" . Frame::MINUTES->value),
                InlineKeyboardButton::make(__('telegram.buttons.chart_aggregate_3'), callback_data: "reports:token:$token->id:chart_" . Frame::HOURS->value),
                InlineKeyboardButton::make(__('telegram.buttons.chart_aggregate_4'), callback_data: "reports:token:$token->id:chart_" . Frame::DAY->value),
            ),
            default => $markup
        };

        return $markup->addRow(
            InlineKeyboardButton::make(__('telegram.buttons.to_home'), callback_data: "reports:token:$token->id:home"),
            InlineKeyboardButton::make(__('telegram.buttons.to_settings'), callback_data: "reports:token:$token->id:settings"),
            InlineKeyboardButton::make(__('telegram.buttons.to_scanner'), callback_data: "reports:token:$token->id:back"),
        );
    }
}
