<?php

namespace App\Enums;

enum RequestSource: string
{
    case TELEGRAM = 'telegram';
    case API = 'api';

    public static function all(): array
    {
        return [
            self::TELEGRAM->value,
            self::API->value,
        ];
    }

    public function verbose(): string
    {
        return match ($this) {
            self::TELEGRAM => 'Telegram',
            self::API => 'API',
        };
    }
}
