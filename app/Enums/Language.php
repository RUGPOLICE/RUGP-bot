<?php

namespace App\Enums;

enum Language: string
{
    case RU = 'ru';
    case EN = 'en';

    public static function keys(): array
    {
        return [
            self::RU->value,
            self::EN->value,
        ];
    }
}
