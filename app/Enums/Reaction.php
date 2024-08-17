<?php

namespace App\Enums;

enum Reaction: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';

    public static function all(): array
    {
        return [
            self::LIKE->value,
            self::DISLIKE->value,
        ];
    }

    public static function verbose(self $reaction): string
    {
        return match ($reaction) {
            self::LIKE => __('telegram.buttons.like'),
            self::DISLIKE => __('telegram.buttons.dislike'),
        };
    }
}
