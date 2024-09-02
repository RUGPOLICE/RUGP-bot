<?php

namespace App\Telegram\Conversations;

use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class ImagedInlineMenu extends InlineMenu
{
    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt);
    }

    protected function doUpdate(string $text, ?int $chatId, ?int $messageId, InlineKeyboardMarkup $buttons, array $opt): bool|Message|null
    {
        return $this->bot->asResponse()->sendImagedMessage($text, $buttons, $opt, $chatId, $messageId);
    }
}
