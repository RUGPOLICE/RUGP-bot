<?php

namespace App\Telegram\Mixins;

use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ImagedMessage
{
    public function sendImagedMessage(): \Closure
    {
        return function (string $text, InlineKeyboardMarkup $buttons, array $options = [], ?int $chat_id = null, ?int $message_id = null) {

            if ($chat_id && $message_id)
                $this->deleteMessage($chat_id, $message_id);

            $options['reply_markup'] = $buttons;
            $options['parse_mode'] = ParseMode::HTML;
            // $options['show_caption_above_media'] = true;

            if ($chat_id)
                $options['chat_id'] = $chat_id;

            if (array_key_exists('image', $options) && $options['image']) {

                $image = $options['image'];
                unset($options['image']);
                unset($options['link_preview_options']);

                return $this->sendPhoto(
                    mb_strpos($image, 'https') !== false ? $image : InputFile::make(fopen($image, 'r+')),
                    ... $options,
                    caption: $text
                );

            } else return $this->sendMessage($text, ... $options);

        };
    }
}
