<?php

namespace App\Telegram\Conversations;

use App\Services\OpenAiService;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class GptMenu extends InlineMenu
{
    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt);
    }

    protected function doUpdate(string $text, ?int $chatId, ?int $messageId, InlineKeyboardMarkup $buttons, array $opt): bool|Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt);
    }

    public function start(Nutgram $bot): void
    {
        $this
            ->clearButtons()
            ->menuText(__('telegram.text.gpt.main'))
            ->addButtonRow(InlineKeyboardButton::make(__('telegram.buttons.back'), callback_data: 'back@menu'))
            ->orNext('handle')
            ->showMenu();
    }

    public function handle(Nutgram $bot): void
    {
        $openAiService = app(OpenAiService::class);
        $this->menuText($openAiService->getChatCompletion($bot->message()->text))->showMenu();
    }

    public function menu(Nutgram $bot): void
    {
        $this->end();
        match ($bot->callbackQuery()->data) {
            'back' => HomeMenu::begin($bot),
        };
    }
}
